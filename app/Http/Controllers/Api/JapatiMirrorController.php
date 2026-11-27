<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

// Jalur mirror sementara: cabut atau ganti saat koneksi resmi Japati siap.
// Menyajikan snapshot data Japati dari file JSON yang diunggah manual ke
// storage/app/private/japati-mirror/<kode-tahun>/. Isi file dikirim apa adanya
// (byte sama), tidak di-decode lalu di-encode ulang.
class JapatiMirrorController extends Controller
{
    private const FOLDER = 'japati-mirror';

    // Endpoint daftar satuan kerja Japati tidak memakai header tahun.
    // Satu-satunya snapshot yang ada: data 2026, versi v2.
    private const LIST_YEAR = '20262';

    private const YEAR_HEADER = 'Default-Key-Tahun-Kinerja-public';

    private const SNAPSHOT_HEADER = 'X-Japati-Mirror-Snapshot';

    public function satuanKerja(): Response
    {
        $content = $this->readJsonFile(self::FOLDER.'/'.self::LIST_YEAR.'/satuan-kerja.json');

        if ($content === null) {
            return $this->notFound('Data mirror Japati belum diunggah.');
        }

        return $this->jsonBody($content, self::LIST_YEAR);
    }

    public function cascading(Request $request): Response
    {
        $satuanKerjaId = $request->query('satuan_kerja_id');

        if (! is_string($satuanKerjaId) || $satuanKerjaId === '') {
            return response()->json(['message' => 'satuan_kerja_id wajib diisi.'], 422);
        }

        // Japati asli diam-diam membalas [] bila header tahun tidak ada.
        // Tahun harus tepat 5 digit karena dipakai sebagai nama folder.
        $year = $request->header(self::YEAR_HEADER);

        if (! is_string($year) || strlen($year) !== 5 || ! ctype_digit($year)) {
            return $this->jsonBody('[]', null);
        }

        $listContent = $this->readJsonFile(self::FOLDER.'/'.$year.'/satuan-kerja.json');

        if ($listContent === null) {
            return $this->jsonBody('[]', $year);
        }

        // Nama file dibentuk dari id yang cocok di daftar, bukan dari input.
        $matchedId = $this->findSatuanKerjaId($listContent, $satuanKerjaId);

        if ($matchedId === null) {
            return $this->notFound('Satuan kerja tidak dikenal di mirror Japati.');
        }

        $content = $this->readJsonFile(self::FOLDER.'/'.$year.'/cascading_'.$matchedId.'.json');

        if ($content === null) {
            return $this->notFound('Data cascading mirror Japati belum diunggah.');
        }

        return $this->jsonBody($content, $year);
    }

    // Isi file mentah, null kalau filenya belum diunggah. File ada tapi tidak
    // terbaca, kosong, atau bukan JSON valid (mis. unggahan belum selesai)
    // dibalas 503, supaya tidak tampil sebagai data kosong atau data rusak.
    private function readJsonFile(string $path): ?string
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        $content = $disk->get($path);

        if ($content === null || ! json_validate($content)) {
            abort(503, 'Data mirror Japati tidak terbaca atau belum lengkap.');
        }

        return $content;
    }

    // Cari id di daftar satuan kerja. Id Japati ada yang angka 4 digit, ada
    // yang 12 digit, jadi dibandingkan sebagai teks.
    private function findSatuanKerjaId(string $listContent, string $satuanKerjaId): ?string
    {
        $rows = json_decode($listContent, true);

        if (! is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowId = $row['satuan_kerja_id'] ?? null;

            if (! is_int($rowId) && ! is_string($rowId)) {
                continue;
            }

            if ((string) $rowId === $satuanKerjaId) {
                return (string) $rowId;
            }
        }

        return null;
    }

    // Tanggal tarikan snapshot: awal "waktu" entri pertama di manifest.json.
    // Manifest tidak ada atau tidak terbaca bukan error, headernya dilewati.
    private function snapshotDate(?string $year): ?string
    {
        if ($year === null) {
            return null;
        }

        $content = Storage::disk('local')->get(self::FOLDER.'/'.$year.'/manifest.json');

        if ($content === null) {
            return null;
        }

        $manifest = json_decode($content, true);
        $time = null;

        if (is_array($manifest) && isset($manifest[0]) && is_array($manifest[0])) {
            $time = $manifest[0]['waktu'] ?? null;
        }

        if (! is_string($time) || strlen($time) < 10) {
            return null;
        }

        return substr($time, 0, 10);
    }

    private function jsonBody(string $body, ?string $year): Response
    {
        $response = response($body, 200, ['Content-Type' => 'application/json']);

        $snapshotDate = $this->snapshotDate($year);

        if ($snapshotDate !== null) {
            $response->header(self::SNAPSHOT_HEADER, $snapshotDate);
        }

        return $response;
    }

    private function notFound(string $message): Response
    {
        return response()->json(['message' => $message], 404);
    }
}

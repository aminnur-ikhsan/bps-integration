<?php

namespace App\Services\Bps;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BpsClient
{
    public function __construct(
        private string $baseUrl,
        private string $key,
    ) {}

    /**
     * Ambil satu halaman dari sebuah endpoint BPS dan kembalikan body JSON-nya.
     */
    public function get(string $path, array $query = []): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(30)
                // throw: false supaya kegagalan HTTP jatuh ke pengecekan di bawah,
                // bukan dilempar retry() sebagai RequestException.
                ->retry(2, 500, throw: false)
                ->get($path, array_merge($query, ['key' => $this->key]));
        } catch (ConnectionException $e) {
            // Server tidak menjawab sama sekali. Dibungkus supaya pemanggil cukup
            // menangkap satu jenis exception; aslinya dirantai supaya penyebabnya
            // — DNS gagal, koneksi ditolak, timeout — tidak hilang dari log.
            throw new BpsApiException('Tidak bisa menghubungi server BPS.', previous: $e);
        }

        if ($response->failed()) {
            throw new BpsApiException('Permintaan ke BPS gagal.', $response->status());
        }

        $body = $response->json();

        // BPS membalas HTTP 200 walau permintaannya ditolak, jadi body wajib dicek.
        if (! is_array($body) || ($body['status'] ?? null) !== 'OK') {
            throw new BpsApiException(
                $body['message'] ?? 'Respons BPS tidak dikenali.',
                $response->status(),
            );
        }

        return $body;
    }
}

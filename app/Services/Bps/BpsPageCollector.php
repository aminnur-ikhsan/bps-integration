<?php

namespace App\Services\Bps;

// Koordinator pengambilan halaman BPS.
// Menggabungkan "pancing" (BpsClient, satuan) dan "jaring" (BpsClientPool, borongan).
// Tidak menyentuh database dan tidak menyimpan state.
class BpsPageCollector
{
    public function __construct(
        private BpsClient $pancing,
        private BpsClientPool $jaring,
    ) {}

    // STEP 1 — ambil halaman pertama. Melempar BpsApiException kalau gagal
    // (halaman 1 wajib berhasil supaya kita tahu jumlah halaman).
    public function fetchFirst(string $path, array $query): array
    {
        return $this->pancing->get($path, array_merge($query, ['page' => 1]));
    }

    // STEP 2 — ambil banyak halaman sekaligus (paralel). Tidak melempar.
    // Kembalian sama seperti BpsClientPool::fetchPages().
    public function fetchWithNet(string $path, array $query, array $pages): array
    {
        return $this->jaring->fetchPages($path, $query, $pages);
    }

    // STEP 3 — ambil halaman tertentu satu per satu. Tidak melempar:
    // halaman yang gagal dikumpulkan ke 'failedPages'.
    public function fetchOneByOne(string $path, array $query, array $pages): array
    {
        $bodies = [];
        $failedPages = [];

        foreach ($pages as $page) {
            try {
                $bodies[$page] = $this->pancing->get($path, array_merge($query, ['page' => $page]));
            } catch (BpsApiException $e) {
                $failedPages[$page] = $e->getMessage();
            }
        }

        return ['bodies' => $bodies, 'failedPages' => $failedPages];
    }
}

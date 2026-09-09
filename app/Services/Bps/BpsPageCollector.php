<?php

namespace App\Services\Bps;

// Koordinator pengambilan halaman: gabungan BpsClient (satuan) dan BpsClientPool (borongan).
class BpsPageCollector
{
    public function __construct(
        private BpsClient $pancing,
        private BpsClientPool $jaring,
    ) {}

    // Ambil halaman pertama. Melempar kalau gagal.
    public function fetchFirst(string $path, array $query): array
    {
        return $this->pancing->get($path, array_merge($query, ['page' => 1]));
    }

    // Ambil banyak halaman paralel.
    public function fetchWithNet(string $path, array $query, array $pages): array
    {
        return $this->jaring->fetchPages($path, $query, $pages);
    }

    // Ambil halaman tertentu satu per satu. Tidak melempar.
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

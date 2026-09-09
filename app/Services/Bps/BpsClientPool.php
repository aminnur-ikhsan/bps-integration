<?php

namespace App\Services\Bps;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

// Ambil banyak halaman BPS secara paralel. Tidak melempar.
class BpsClientPool
{
    public function __construct(
        private string $baseUrl,
        private string $key,
    ) {}

    private int $concurrency = 5;

    public function fetchPages(string $path, array $query, array $pages): array
    {
        $bodies = [];
        $failedPages = [];

        foreach (array_chunk($pages, $this->concurrency) as $grup) {
            $responses = Http::pool(function (Pool $pool) use ($path, $query, $grup) {
                foreach ($grup as $page) {
                    $pool->as((string) $page)
                        ->baseUrl($this->baseUrl)
                        ->timeout(30)
                        ->get($path, array_merge($query, [
                            'page' => $page,
                            'key' => $this->key,
                        ]));
                }
            });

            foreach ($grup as $page) {
                $response = $responses[(string) $page];

                if (! $response instanceof Response) {
                    $failedPages[$page] = 'koneksi gagal';

                    continue;
                }

                if ($response->failed()) {
                    $failedPages[$page] = 'HTTP '.$response->status();

                    continue;
                }

                $body = $response->json();

                if (! is_array($body) || ($body['status'] ?? null) !== 'OK') {
                    $failedPages[$page] = $body['message'] ?? 'body tidak dikenali';

                    continue;
                }

                $bodies[$page] = $body;
            }

            info('BpsClientPool grup', ['pages' => $grup]);
        }

        info('BpsClientPool selesai', [
            'sukses' => count($bodies),
            'gagal' => array_keys($failedPages),
        ]);

        return ['bodies' => $bodies, 'failedPages' => $failedPages];
    }
}

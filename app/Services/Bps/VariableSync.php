<?php

// app/Services/Bps/VariableSync.php

namespace App\Services\Bps;

use App\Models\BpsFetchLog;
use App\Models\BpsVariable;
use Carbon\CarbonInterface;

class VariableSync
{
    public function __construct(private BpsPageCollector $collector) {}

    public function sync(string $domainId, ?int $userId = null): SyncResult
    {
        set_time_limit(300); // Prevent PHP timeout for large sequential fetches
        $startedAt = microtime(true);
        $syncedAt = now();
        $params = [
            'model' => 'var',
            'domain' => $domainId,
            'lang' => 'ind',
        ];

        $tersimpan = 0;
        $sisaGagal = [];

        try {
            // STEP 1 — pancing halaman pertama, buat tahu jumlah halaman.
            $body1 = $this->collector->fetchFirst('list', $params);

            if (($body1['data-availability'] ?? null) !== 'available') {
                $this->log($params, 'success', 0, $startedAt, $userId);

                return new SyncResult(0, $syncedAt);
            }

            $lastPage = (int) ($body1['data'][0]['pages'] ?? 1);

            $rows = $this->extractRows([$body1]);
            $this->store($rows, $domainId, $syncedAt);
            $tersimpan += count($rows);

            info('VariableSync step 1', ['last_page' => $lastPage, 'tersimpan' => $tersimpan]);

            if ($lastPage > 1) {
                // STEP 2 — jaring halaman 2 sampai terakhir, sekaligus.
                $net = $this->collector->fetchWithNet('list', $params, range(2, $lastPage));

                $rows = $this->extractRows($net['bodies']);
                $this->store($rows, $domainId, $syncedAt);
                $tersimpan += count($rows);

                $sisaGagal = $net['failedPages'];

                info('VariableSync step 2', [
                    'sukses' => count($net['bodies']),
                    'gagal' => array_keys($sisaGagal),
                ]);

                // STEP 3 — pancing ulang halaman yang gagal, satu per satu.
                if ($sisaGagal !== []) {
                    $rod = $this->collector->fetchOneByOne('list', $params, array_keys($sisaGagal));

                    $rows = $this->extractRows($rod['bodies']);
                    $this->store($rows, $domainId, $syncedAt);
                    $tersimpan += count($rows);

                    $sisaGagal = $rod['failedPages'];

                    info('VariableSync step 3', [
                        'sukses' => count($rod['bodies']),
                        'gagal' => array_keys($sisaGagal),
                    ]);
                }
            }
        } catch (BpsApiException $e) {
            $this->log($params, 'failed', $tersimpan ?: null, $startedAt, $userId, $e);

            throw $e;
        }

        // STEP 4 — laporkan halaman yang masih gagal (di luar try supaya log tidak dobel).
        $this->log($params, $sisaGagal === [] ? 'success' : 'partial', $tersimpan, $startedAt, $userId);

        if ($sisaGagal !== []) {
            throw new BpsApiException(
                'Sebagian page gagal diambil: '.implode(', ', array_keys($sisaGagal)).'. Coba Fetch lagi.'
            );
        }

        return new SyncResult($tersimpan, $syncedAt);
    }

    // Ambil baris item dari kumpulan body BPS + pastikan bentuknya benar.
    // Dipakai di ketiga langkah pengambilan.
    private function extractRows(array $bodies): array
    {
        $rows = [];

        foreach ($bodies as $body) {
            $items = $body['data'][1] ?? [];

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['var_id'], $item['title'])) {
                    throw new BpsApiException('Bentuk data variable dari BPS tidak dikenali.');
                }

                $rows[] = $item;
            }
        }

        return $rows;
    }

    private function store(array $rows, string $domainId, CarbonInterface $syncedAt): void
    {
        if ($rows === []) {
            return;
        }

        $records = [];

        foreach ($rows as $row) {
            $records[] = [
                'domain_id' => $domainId,
                'var_id' => $row['var_id'],
                'title' => $row['title'],
                'sub_id' => $row['sub_id'] ?? null,
                'sub_name' => $row['sub_name'] ?? null,
                'def' => $row['def'] ?? null,
                'notes' => $row['notes'] ?? null,
                'vertical' => $row['vertical'] ?? null,
                'unit' => $row['unit'] ?? null,
                'graph_id' => $row['graph_id'] ?? null,
                'graph_name' => $row['graph_name'] ?? null,
                'last_synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        BpsVariable::upsert(
            $records,
            ['domain_id', 'var_id'],
            ['title', 'sub_id', 'sub_name', 'def', 'notes', 'vertical', 'unit', 'graph_id', 'graph_name', 'last_synced_at', 'updated_at'],
        );
    }

    private function log(array $params, string $status, ?int $count, float $startedAt, ?int $userId, ?BpsApiException $error = null): void
    {
        BpsFetchLog::create([
            'user_id' => $userId,
            'endpoint' => 'list',
            'params' => $params,
            'status' => $status,
            'http_status' => $error?->httpStatus,
            'records_count' => $count,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => $error ? ($error->cause ?? $error->getMessage()) : null,
            'created_at' => now(),
        ]);
    }
}

<?php
// app/Services/Bps/UnitSync.php

namespace App\Services\Bps;

use App\Models\BpsFetchLog;
use App\Models\BpsUnit;
use Carbon\CarbonInterface;

class UnitSync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $domainId, ?int $userId = null): SyncResult
    {
        set_time_limit(300);
        $startedAt = microtime(true);
        $params = [
            'model'  => 'unit',
            'domain' => $domainId,
            'lang'   => 'ind',
        ];

        try {
            $rows = $this->fetchAllPages($params);
            $syncedAt = now();

            $this->store($rows, $domainId, $syncedAt);
            $this->log($params, 'success', count($rows), $startedAt, $userId);

            return new SyncResult(count($rows), $syncedAt);
        } catch (BpsApiException $e) {
            $this->log($params, 'failed', null, $startedAt, $userId, $e);
            throw $e;
        }
    }

    private function fetchAllPages(array $params): array
    {
        $rows = [];
        $page = 1;
        $lastPage = 1;

        do {
            $body = $this->client->get('list', array_merge($params, ['page' => $page]));

            if (($body['data-availability'] ?? null) !== 'available') {
                return [];
            }

            $pagination = $body['data'][0] ?? [];
            $items      = $body['data'][1] ?? [];

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['unit_id'], $item['unit'])) {
                    throw new BpsApiException('Bentuk data unit dari BPS tidak dikenali.');
                }
            }

            $rows     = array_merge($rows, $items);
            $lastPage = (int) ($pagination['pages'] ?? 1);
            $page++;
        } while ($page <= $lastPage);

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
                'domain_id'      => $domainId,
                'unit_id'        => $row['unit_id'],
                'unit'           => $row['unit'],
                'last_synced_at' => $syncedAt,
                'created_at'     => $syncedAt,
                'updated_at'     => $syncedAt,
            ];
        }

        BpsUnit::upsert(
            $records,
            ['domain_id', 'unit_id'],
            ['unit', 'last_synced_at', 'updated_at'],
        );
    }

    private function log(array $params, string $status, ?int $count, float $startedAt, ?int $userId, ?BpsApiException $error = null): void
    {
        BpsFetchLog::create([
            'user_id'       => $userId,
            'endpoint'      => 'list',
            'params'        => $params,
            'status'        => $status,
            'http_status'   => $error?->httpStatus,
            'records_count' => $count,
            'duration_ms'   => (int) round((microtime(true) - $startedAt) * 1000),
            'error'         => $error ? ($error->cause ?? $error->getMessage()) : null,
            'created_at'    => now(),
        ]);
    }
}

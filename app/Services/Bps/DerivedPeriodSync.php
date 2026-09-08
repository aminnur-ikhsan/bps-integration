<?php
// app/Services/Bps/DerivedPeriodSync.php

namespace App\Services\Bps;

use App\Models\BpsDerivedPeriod;
use App\Models\BpsFetchLog;
use Carbon\CarbonInterface;

class DerivedPeriodSync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $domainId, ?int $varId = null, ?int $userId = null): SyncResult
    {
        set_time_limit(300);
        $startedAt = microtime(true);
        $params = array_filter([
            'model'  => 'turth',
            'domain' => $domainId,
            'lang'   => 'ind',
            'var'    => $varId,
        ], fn($v) => $v !== null);

        try {
            $rows = $this->fetchAllPages($params);
            $syncedAt = now();

            $this->store($rows, $domainId, $varId, $syncedAt);
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
                break;
            }

            $pagination = $body['data'][0] ?? [];
            $items      = $body['data'][1] ?? [];

            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['turth_id'], $item['turth'])) {
                    throw new BpsApiException('Bentuk data derived period dari BPS tidak dikenali.');
                }
            }

            $rows     = array_merge($rows, $items);
            $lastPage = (int) ($pagination['pages'] ?? 1);
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }

    private function store(array $rows, string $domainId, ?int $varId, CarbonInterface $syncedAt): void
    {
        if ($rows === []) {
            return;
        }

        $records = [];

        foreach ($rows as $row) {
            $records[] = [
                'domain_id'        => $domainId,
                'var_id'           => $varId,
                'turth_id'         => $row['turth_id'],
                'turth'            => $row['turth'],
                'group_turth_id'   => $row['group_turth_id'] ?? null,
                'name_group_turth' => $row['name_group_turth'] ?? null,
                'last_synced_at'   => $syncedAt,
                'created_at'       => $syncedAt,
                'updated_at'       => $syncedAt,
            ];
        }

        BpsDerivedPeriod::upsert(
            $records,
            ['domain_id', 'turth_id'],
            ['var_id', 'turth', 'group_turth_id', 'name_group_turth', 'last_synced_at', 'updated_at'],
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

<?php

// app/Services/Bps/PeriodSync.php

namespace App\Services\Bps;

use App\Models\BpsFetchLog;
use App\Models\BpsPeriod;
use Carbon\CarbonInterface;

class PeriodSync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $domainId, ?int $varId = null, ?int $userId = null): SyncResult
    {
        set_time_limit(300);
        $startedAt = microtime(true);
        $params = array_filter([
            'model' => 'th',
            'domain' => $domainId,
            'lang' => 'ind',
            'var' => $varId,
        ], fn ($v) => $v !== null);

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
            $items = $body['data'][1] ?? [];

            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['th_id'], $item['th'])) {
                    throw new BpsApiException('Bentuk data period dari BPS tidak dikenali.');
                }
            }

            $rows = array_merge($rows, $items);
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
                'domain_id' => $domainId,
                'var_id' => $varId,
                'th_id' => $row['th_id'],
                'th' => (string) $row['th'],
                'last_synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        BpsPeriod::upsert(
            $records,
            ['domain_id', 'th_id'],
            ['var_id', 'th', 'last_synced_at', 'updated_at'],
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

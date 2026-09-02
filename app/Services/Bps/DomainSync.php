<?php

namespace App\Services\Bps;

use App\Models\BpsDomain;
use App\Models\BpsFetchLog;
use Illuminate\Support\Facades\DB;

class DomainSync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $type = 'all', ?int $userId = null): SyncResult
    {
        $startedAt = microtime(true);
        $params = ['type' => $type];

        try {
            $rows = $this->fetchAllPages($type);
            $syncedAt = now();

            DB::transaction(fn () => $this->store($rows, $type, $syncedAt));

            $this->log($params, 'success', count($rows), $startedAt, $userId);

            return new SyncResult(count($rows), $syncedAt);
        } catch (BpsApiException $e) {
            $this->log($params, 'failed', null, $startedAt, $userId, $e);

            throw $e;
        }
    }

    /**
     * BPS membagi hasil per halaman, jadi diambil sampai halaman terakhir.
     */
    private function fetchAllPages(string $type): array
    {
        $rows = [];
        $page = 1;
        $lastPage = 1;

        do {
            $body = $this->client->get('domain', ['type' => $type, 'page' => $page]);

            if (($body['data-availability'] ?? null) !== 'available') {
                return [];
            }

            $pagination = $body['data'][0] ?? [];
            $items = $body['data'][1] ?? [];

            $rows = array_merge($rows, $items);
            $lastPage = (int) ($pagination['pages'] ?? 1);
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }

    private function store(array $rows, string $type, $syncedAt): void
    {
        if ($rows === []) {
            return;
        }

        $records = array_map(fn (array $row) => [
            'domain_id' => $row['domain_id'],
            'domain_name' => $row['domain_name'],
            'domain_url' => $row['domain_url'] ?? null,
            'type' => $type,
            'last_synced_at' => $syncedAt,
            'created_at' => $syncedAt,
            'updated_at' => $syncedAt,
        ], $rows);

        BpsDomain::upsert(
            $records,
            ['domain_id'],
            ['domain_name', 'domain_url', 'type', 'last_synced_at', 'updated_at'],
        );
    }

    private function log(
        array $params,
        string $status,
        ?int $count,
        float $startedAt,
        ?int $userId,
        ?BpsApiException $error = null,
    ): void {
        BpsFetchLog::create([
            'user_id' => $userId,
            'endpoint' => 'domain',
            'params' => $params,
            'status' => $status,
            'http_status' => $error?->httpStatus,
            'records_count' => $count,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => $error?->getMessage(),
            'created_at' => now(),
        ]);
    }
}

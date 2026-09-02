<?php

namespace App\Services\Bps;

use App\Models\BpsDomain;
use App\Models\BpsFetchLog;
use Carbon\CarbonInterface;

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

            $this->store($rows, $type, $syncedAt);

            $this->log($params, 'success', count($rows), $startedAt, $userId);

            return new SyncResult(count($rows), $syncedAt);
        } catch (BpsApiException $e) {
            $this->log($params, 'failed', null, $startedAt, $userId, $e);

            throw $e;
        }
    }

    // BPS membagi hasil per halaman, jadi diambil sampai halaman terakhir.
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

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['domain_id'], $item['domain_name'])) {
                    throw new BpsApiException('Bentuk data domain dari BPS tidak dikenali.');
                }
            }

            $rows = array_merge($rows, $items);
            $lastPage = (int) ($pagination['pages'] ?? 1);
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }

    private function store(array $rows, string $type, CarbonInterface $syncedAt): void
    {
        if ($rows === []) {
            return;
        }

        $records = [];

        foreach ($rows as $row) {
            $records[] = [
                'domain_id' => $row['domain_id'],
                'domain_name' => $row['domain_name'],
                'domain_url' => $row['domain_url'] ?? null,
                'type' => $type,
                'last_synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

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
        $httpStatus = null;
        $errorMessage = null;

        if ($error !== null) {
            $httpStatus = $error->httpStatus;
            $errorMessage = $error->cause ?? $error->getMessage();
        }

        BpsFetchLog::create([
            'user_id' => $userId,
            'endpoint' => 'domain',
            'params' => $params,
            'status' => $status,
            'http_status' => $httpStatus,
            'records_count' => $count,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}

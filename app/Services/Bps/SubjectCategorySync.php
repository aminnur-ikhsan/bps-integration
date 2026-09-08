<?php

namespace App\Services\Bps;

use App\Models\BpsFetchLog;
use App\Models\BpsSubjectCategory;
use Carbon\CarbonInterface;

class SubjectCategorySync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $domainId, ?int $userId = null): SyncResult
    {
        $startedAt = microtime(true);
        $params = [
            'model' => 'subcat',
            'domain' => $domainId,
            'lang' => 'ind',
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
            $currentParams = array_merge($params, ['page' => $page]);
            $body = $this->client->get('list', $currentParams);

            if (($body['data-availability'] ?? null) !== 'available') {
                break;
            }

            $pagination = $body['data'][0] ?? [];
            $items = $body['data'][1] ?? [];

            if ($items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['subcat_id'], $item['title'])) {
                    throw new BpsApiException('Bentuk data subject category dari BPS tidak dikenali.');
                }
            }

            $rows = array_merge($rows, $items);
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
                'domain_id' => $domainId,
                'subcat_id' => $row['subcat_id'],
                'title' => $row['title'],
                'last_synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        BpsSubjectCategory::upsert(
            $records,
            ['domain_id', 'subcat_id'],
            ['title', 'last_synced_at', 'updated_at'],
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
            'endpoint' => 'list',
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

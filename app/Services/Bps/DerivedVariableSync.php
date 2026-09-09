<?php

// app/Services/Bps/DerivedVariableSync.php

namespace App\Services\Bps;

use App\Models\BpsDerivedVariable;
use App\Models\BpsFetchLog;
use Carbon\CarbonInterface;

class DerivedVariableSync
{
    public function __construct(private BpsClient $client) {}

    public function sync(string $domainId, ?int $varId = null, ?int $userId = null): SyncResult
    {
        $startedAt = microtime(true);
        $params = array_filter([
            'model' => 'turvar',
            'domain' => $domainId,
            'lang' => 'ind',
            'var' => $varId,
            'nopage' => 1,
        ], fn ($v) => $v !== null);

        try {
            $body = $this->client->get('list', $params);
            $syncedAt = now();

            if (($body['data-availability'] ?? null) !== 'available') {
                $this->log($params, 'success', 0, $startedAt, $userId);

                return new SyncResult(0, $syncedAt);
            }

            $items = $body['data'][1] ?? [];

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['turvar_id'], $item['turvar'])) {
                    throw new BpsApiException('Bentuk data derived variable dari BPS tidak dikenali.');
                }
            }

            $this->store($items, $domainId, $varId, $syncedAt);
            $this->log($params, 'success', count($items), $startedAt, $userId);

            return new SyncResult(count($items), $syncedAt);
        } catch (BpsApiException $e) {
            $this->log($params, 'failed', null, $startedAt, $userId, $e);
            throw $e;
        }
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
                'turvar_id' => $row['turvar_id'],
                'turvar' => $row['turvar'],
                'group_turvar_id' => $row['group_turvar_id'] ?? null,
                'name_group_turvar' => $row['name_group_turvar'] ?? null,
                'last_synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        }

        BpsDerivedVariable::upsert(
            $records,
            ['domain_id', 'turvar_id'],
            ['var_id', 'turvar', 'group_turvar_id', 'name_group_turvar', 'last_synced_at', 'updated_at'],
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

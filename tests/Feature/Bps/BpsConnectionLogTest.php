<?php

namespace Tests\Feature\Bps;

use App\Models\BpsConnectionLog;
use Tests\TestCase;

class BpsConnectionLogTest extends TestCase
{
    public function test_creates_row_with_masked_and_json_fields(): void
    {
        $log = BpsConnectionLog::create([
            'user_id' => null,
            'method' => 'GET',
            'base_url' => 'https://webapi.bps.go.id/v1/api',
            'path' => 'domain',
            'http_status' => 200,
            'dns_ms' => 12,
            'connect_ms' => 34,
            'ttfb_ms' => 56,
            'total_ms' => 78,
            'response_bytes' => 1234,
            'request_header' => ['Accept' => ['application/json']],
            'request_parameters' => ['type' => 'prov', 'key' => '***'],
            'response_header' => ['content-type' => ['application/json']],
            'response_body' => ['status' => 'OK', 'data' => []],
            'created_at' => now(),
        ]);

        $fresh = BpsConnectionLog::find($log->id);

        $this->assertSame('domain', $fresh->path);
        $this->assertSame(200, $fresh->http_status);
        $this->assertIsArray($fresh->request_parameters);
        $this->assertSame('***', $fresh->request_parameters['key']);
        $this->assertIsArray($fresh->response_body);
        $this->assertEquals(['status' => 'OK', 'data' => []], $fresh->response_body);
    }
}

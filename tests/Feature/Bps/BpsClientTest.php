<?php

namespace Tests\Feature\Bps;

use App\Models\BpsConnectionLog;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\BpsClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BpsClientTest extends TestCase
{
    private function client(): BpsClient
    {
        return new BpsClient('https://webapi.bps.go.id/v1/api', 'secret-key-123');
    }

    public function test_get_berhasil_membuat_baris_log_koneksi(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'OK', 'data' => ['isi']], 200),
        ]);

        $body = $this->client()->get('domain', ['type' => 'prov']);

        $this->assertEquals(['status' => 'OK', 'data' => ['isi']], $body);
        $this->assertSame(1, BpsConnectionLog::count());

        $log = BpsConnectionLog::first();
        $this->assertSame('https://webapi.bps.go.id/v1/api', $log->base_url);
        $this->assertSame('domain', $log->path);
        $this->assertSame(200, $log->http_status);
        $this->assertEquals(['status' => 'OK', 'data' => ['isi']], $log->response_body);
    }

    public function test_key_api_dimask_di_parameter_yang_disimpan(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'OK', 'data' => []], 200),
        ]);

        $this->client()->get('domain', ['type' => 'prov']);

        $log = BpsConnectionLog::first();
        $this->assertSame('prov', $log->request_parameters['type']);
        $this->assertSame('***', $log->request_parameters['key']);
    }

    public function test_connection_exception_tidak_membuat_baris_log(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Gagal konek.');
        });

        try {
            $this->client()->get('domain', ['type' => 'prov']);
        } catch (BpsApiException) {
            // Diharapkan — fokus test ini ke jumlah baris log, bukan exception-nya.
        }

        $this->assertSame(0, BpsConnectionLog::count());
    }

    public function test_insert_log_gagal_tidak_menggagalkan_request(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'OK', 'data' => []], 200),
        ]);

        BpsConnectionLog::creating(function () {
            throw new \RuntimeException('DB down (simulasi)');
        });

        try {
            $body = $this->client()->get('domain', ['type' => 'prov']);

            $this->assertEquals(['status' => 'OK', 'data' => []], $body);
            $this->assertSame(0, BpsConnectionLog::count());
        } finally {
            BpsConnectionLog::flushEventListeners();
        }
    }
}

<?php

namespace Tests\Feature\ClientAccess;

use App\Models\ClientAccess\ApiClient;
use App\Models\ClientAccess\ApiRequestLog;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiClientAccessTest extends TestCase
{
    public function test_api_client_tersimpan_di_schema_data_access_clients(): void
    {
        $client = ApiClient::create([
            'app_name' => 'Aplikasi Uji Model',
            'token' => hash('sha256', 'token-uji'),
            'is_active' => true,
        ]);

        $this->assertSame('data_access_clients.api_clients', $client->getTable());
        $this->assertTrue($client->fresh()->is_active);
        $this->assertNull($client->fresh()->last_used_at);
    }

    public function test_request_log_menyimpan_kolom_jsonb_sebagai_array(): void
    {
        $client = ApiClient::create([
            'app_name' => 'Aplikasi Uji Log',
            'token' => hash('sha256', 'token-uji-log'),
            'is_active' => true,
        ]);

        $log = ApiRequestLog::create([
            'api_client_id' => $client->id,
            'method' => 'GET',
            'path' => 'api/v1/ping',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'http_status' => 200,
            'duration_ms' => 5,
            'response_bytes' => 15,
            'request_header' => ['accept' => ['application/json']],
            'request_parameters' => ['foo' => 'bar'],
            'response_header' => ['content-type' => ['application/json']],
            'response_body' => ['status' => 'ok'],
            'created_at' => now(),
        ]);

        $fresh = $log->fresh();

        $this->assertSame('data_access_clients.api_request_logs', $log->getTable());
        $this->assertSame(['foo' => 'bar'], $fresh->request_parameters);
        $this->assertSame(['status' => 'ok'], $fresh->response_body);
        $this->assertSame($client->id, $fresh->client->id);
    }

    public function test_command_register_membuat_klien_dan_mencetak_token(): void
    {
        $this->artisan('client-access:register', ['app_name' => 'Aplikasi Uji Command'])
            ->assertExitCode(0);

        $client = ApiClient::where('app_name', 'Aplikasi Uji Command')->first();

        $this->assertNotNull($client);
        $this->assertTrue($client->is_active);
        // Yang tersimpan hash, panjangnya selalu 64 karakter hex.
        $this->assertSame(64, strlen($client->token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $client->token);
    }

    public function test_command_register_menolak_nama_yang_sudah_terpakai(): void
    {
        ApiClient::create([
            'app_name' => 'Aplikasi Kembar',
            'token' => hash('sha256', Str::random(64)),
            'is_active' => true,
        ]);

        $this->artisan('client-access:register', ['app_name' => 'Aplikasi Kembar'])
            ->assertExitCode(1);

        $this->assertSame(1, ApiClient::where('app_name', 'Aplikasi Kembar')->count());
    }
}

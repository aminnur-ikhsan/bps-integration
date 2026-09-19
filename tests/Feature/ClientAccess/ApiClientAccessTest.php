<?php

namespace Tests\Feature\ClientAccess;

use App\Models\ClientAccess\ApiClient;
use App\Models\ClientAccess\ApiRequestLog;
use Illuminate\Support\Facades\Route;
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

    public function test_ping_tanpa_token_ditolak_403(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertStatus(403);
        $response->assertExactJson(['message' => 'Forbidden.']);
    }

    public function test_ping_dengan_token_asal_ditolak_403(): void
    {
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer token-ngawur'])
            ->assertStatus(403);
    }

    public function test_ping_dengan_klien_tidak_aktif_ditolak_403(): void
    {
        $token = Str::random(64);

        ApiClient::create([
            'app_name' => 'Aplikasi Dimatikan',
            'token' => hash('sha256', $token),
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/ping', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(403);
    }

    public function test_ping_dengan_token_valid_berhasil_dan_memperbarui_last_used_at(): void
    {
        $token = Str::random(64);

        $client = ApiClient::create([
            'app_name' => 'Aplikasi Sah',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/ping', ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'ok']);
        $this->assertNotNull($client->fresh()->last_used_at);
    }

    public function test_request_yang_berhasil_menulis_satu_baris_log(): void
    {
        $token = Str::random(64);

        $client = ApiClient::create([
            'app_name' => 'Aplikasi Pencatat',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/ping?kota=Bandung', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(200);

        $log = ApiRequestLog::where('api_client_id', $client->id)->firstOrFail();

        $this->assertSame('GET', $log->method);
        $this->assertSame('api/v1/ping', $log->path);
        $this->assertSame(200, $log->http_status);
        $this->assertSame(['kota' => 'Bandung'], $log->request_parameters);
        $this->assertGreaterThan(0, $log->response_bytes);
        // Route ping tidak ditandai, jadi isi response tidak disimpan.
        $this->assertNull($log->response_body);
    }

    public function test_request_yang_ditolak_tetap_tercatat_tanpa_client_id(): void
    {
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer token-ngawur'])
            ->assertStatus(403);

        $log = ApiRequestLog::whereNull('api_client_id')->firstOrFail();

        $this->assertSame(403, $log->http_status);
        $this->assertSame('api/v1/ping', $log->path);
    }

    public function test_header_authorization_tidak_ikut_tersimpan(): void
    {
        $token = Str::random(64);

        ApiClient::create([
            'app_name' => 'Aplikasi Rahasia',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/ping', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(200);

        $log = ApiRequestLog::latest('id')->firstOrFail();

        $this->assertArrayNotHasKey('authorization', $log->request_header);
    }

    public function test_route_bertanda_menyimpan_isi_response(): void
    {
        // Route uji didaftarkan di dalam test supaya routes/api.php tetap bersih
        // dari endpoint contoh.
        Route::prefix('api/v1')
            ->middleware(['client.log', 'client.token'])
            ->get('uji-simpan-body', fn () => response()->json(['nilai' => 42]))
            ->middleware('saving_body_response');

        $token = Str::random(64);

        ApiClient::create([
            'app_name' => 'Aplikasi Inspeksi',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/uji-simpan-body', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(200);

        $log = ApiRequestLog::latest('id')->firstOrFail();

        $this->assertSame('api/v1/uji-simpan-body', $log->path);
        $this->assertSame(['nilai' => 42], $log->response_body);
    }
}

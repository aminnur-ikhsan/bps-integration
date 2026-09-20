<?php

namespace Tests\Feature\Api;

use App\Models\ClientAccess\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiRegionTest extends TestCase
{
    public function test_middleware_region_menaruh_domain_id_di_request_attributes_untuk_wilayah_yang_dikenal(): void
    {
        // Route uji berdiri sendiri, tidak lewat routes/api.php, supaya middleware
        // ini diuji terpisah dari autentikasi bearer token.
        Route::middleware('region')
            ->get('uji-wilayah/{region}', fn (Request $request) => response()->json([
                'domain_id' => $request->attributes->get('domain_id'),
            ]));

        $this->getJson('/uji-wilayah/jawa-barat')
            ->assertOk()
            ->assertExactJson(['domain_id' => '3200']);
    }

    public function test_middleware_region_menolak_wilayah_yang_tidak_dikenal(): void
    {
        Route::middleware('region')
            ->get('uji-wilayah/{region}', fn (Request $request) => response()->json([
                'domain_id' => $request->attributes->get('domain_id'),
            ]));

        $this->getJson('/uji-wilayah/entah-mana')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Wilayah tidak dikenal.']);
    }

    public function test_endpoint_jawa_barat_mengembalikan_data_domain_3200(): void
    {
        $token = Str::random(64);

        ApiClient::create([
            'app_name' => 'Aplikasi Uji Region',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $categories = $this->getJson('/api/v1/jawa-barat/subject-categories', [
            'Authorization' => "Bearer {$token}",
        ]);
        $categories->assertOk();
        $categories->assertJsonPath('data.0.domain_id', '3200');

        $subjects = $this->getJson('/api/v1/jawa-barat/subjects', [
            'Authorization' => "Bearer {$token}",
        ]);
        $subjects->assertOk();
        $subjects->assertJsonPath('data.0.domain_id', '3200');
    }

    public function test_endpoint_wilayah_tidak_dikenal_membalas_404_kustom(): void
    {
        $token = Str::random(64);

        ApiClient::create([
            'app_name' => 'Aplikasi Uji Region Tidak Dikenal',
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/entah-mana/subject-categories', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Wilayah tidak dikenal.']);
    }
}

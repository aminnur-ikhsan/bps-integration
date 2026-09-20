<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
}

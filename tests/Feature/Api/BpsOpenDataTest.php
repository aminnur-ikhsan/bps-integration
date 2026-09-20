<?php

namespace Tests\Feature\Api;

use App\Models\BpsSubject;
use App\Models\BpsSubjectCategory;
use App\Models\ClientAccess\ApiClient;
use Illuminate\Support\Str;
use Tests\TestCase;

class BpsOpenDataTest extends TestCase
{
    private function validToken(string $appName): string
    {
        $token = Str::random(64);

        ApiClient::create([
            'app_name' => $appName,
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        return $token;
    }

    public function test_subject_categories_tanpa_token_ditolak_403(): void
    {
        $this->getJson('/api/v1/jawa-barat/subject-categories')
            ->assertStatus(403)
            ->assertExactJson(['message' => 'Forbidden.']);
    }

    public function test_subject_categories_token_valid_menampilkan_data_domain_jawa_barat(): void
    {
        BpsSubjectCategory::create([
            'domain_id' => '3200',
            'subcat_id' => 9001,
            'title' => 'Kategori Uji',
        ]);

        $token = $this->validToken('Aplikasi Uji Kategori');

        $response = $this->getJson('/api/v1/jawa-barat/subject-categories', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'domain_id' => '3200',
            'subcat_id' => 9001,
            'title' => 'Kategori Uji',
        ]);
    }
}

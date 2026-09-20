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

    public function test_subjects_token_valid_menampilkan_kategori_terkait(): void
    {
        BpsSubjectCategory::create([
            'domain_id' => '3200',
            'subcat_id' => 9002,
            'title' => 'Kategori Relasi',
        ]);

        BpsSubject::create([
            'domain_id' => '3200',
            'sub_id' => 90002,
            'subcat_id' => 9002,
            'title' => 'Subjek Uji',
        ]);

        $token = $this->validToken('Aplikasi Uji Subjek');

        $response = $this->getJson('/api/v1/jawa-barat/subjects', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);

        $item = collect($response->json('data'))->firstWhere('sub_id', 90002);

        $this->assertNotNull($item);
        $this->assertSame('Subjek Uji', $item['title']);
        $this->assertSame(9002, $item['category']['subcat_id']);
        $this->assertSame('Kategori Relasi', $item['category']['title']);
    }

    public function test_subjects_filter_subcat_id_hanya_mengembalikan_kategori_tersebut(): void
    {
        BpsSubject::create([
            'domain_id' => '3200',
            'sub_id' => 90003,
            'subcat_id' => 9003,
            'title' => 'Subjek Filter A',
        ]);

        BpsSubject::create([
            'domain_id' => '3200',
            'sub_id' => 90004,
            'subcat_id' => 9004,
            'title' => 'Subjek Filter B',
        ]);

        $token = $this->validToken('Aplikasi Uji Filter');

        $response = $this->getJson('/api/v1/jawa-barat/subjects?subcat_id=9003', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['sub_id' => 90003]);
        $response->assertJsonMissing(['sub_id' => 90004]);
    }

    public function test_subjects_kategori_null_ketika_subcat_id_tidak_match(): void
    {
        BpsSubject::create([
            'domain_id' => '3200',
            'sub_id' => 90005,
            'subcat_id' => 9999,
            'title' => 'Subjek Tanpa Kategori',
        ]);

        $token = $this->validToken('Aplikasi Uji Tanpa Kategori');

        $response = $this->getJson('/api/v1/jawa-barat/subjects', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200);

        $item = collect($response->json('data'))->firstWhere('sub_id', 90005);

        $this->assertNotNull($item);
        $this->assertNull($item['category']);
    }
}

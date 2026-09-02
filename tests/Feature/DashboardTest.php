<?php

namespace Tests\Feature;

use App\Models\BpsDomain;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_it_shows_how_many_domains_are_stored(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['TEST-0002', 'TEST-0004', 'TEST-0005'] as $code) {
            BpsDomain::create([
                'domain_id' => $code,
                'domain_name' => 'Wilayah '.$code,
                'domain_url' => null,
                'type' => 'all',
                'last_synced_at' => now(),
            ]);
        }

        $expected = BpsDomain::count();

        // Angkanya harus muncul tepat setelah label kartunya. Sekadar assertSee
        // tidak cukup — digit yang sama gampang muncul di tempat lain pada HTML.
        Livewire::test('pages::dashboard')
            ->assertSeeInOrder([__('Domain tersimpan'), (string) $expected, __('Sync terakhir')]);
    }

    public function test_it_shows_a_dash_when_nothing_has_been_synced_yet(): void
    {
        $this->actingAs(User::factory()->create());

        BpsDomain::query()->delete();

        Livewire::test('pages::dashboard')
            ->assertSeeInOrder([__('Domain tersimpan'), '0', __('Sync terakhir'), '—']);
    }

    public function test_it_shows_the_last_sync_time_in_indonesian(): void
    {
        $this->actingAs(User::factory()->create());

        // Kartunya menampilkan max(last_synced_at) dari seluruh tabel, jadi baris
        // lain harus dibersihkan dulu supaya yang diuji benar-benar baris ini.
        BpsDomain::query()->delete();

        BpsDomain::create([
            'domain_id' => 'TEST-0002',
            'domain_name' => 'Uji Aceh',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => Date::parse('2026-08-17 09:30'),
        ]);

        Livewire::test('pages::dashboard')
            ->assertSee('17 Agustus 2026, 09:30');
    }
}

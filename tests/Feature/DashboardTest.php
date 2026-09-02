<?php

namespace Tests\Feature;

use App\Models\User;
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
        $this->actingAs(\App\Models\User::factory()->create());

        foreach (['1100', '1200', '1300'] as $code) {
            \App\Models\BpsDomain::create([
                'domain_id' => $code,
                'domain_name' => 'Wilayah '.$code,
                'domain_url' => null,
                'type' => 'all',
                'last_synced_at' => now(),
            ]);
        }

        $expected = \App\Models\BpsDomain::count();

        // Angkanya harus muncul tepat setelah label kartunya. Sekadar assertSee
        // tidak cukup — digit yang sama gampang muncul di tempat lain pada HTML.
        \Livewire\Livewire::test('pages::dashboard')
            ->assertSeeInOrder([__('Domain tersimpan'), (string) $expected, __('Sync terakhir')]);
    }

    public function test_it_shows_a_dash_when_nothing_has_been_synced_yet(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        \App\Models\BpsDomain::query()->delete();

        \Livewire\Livewire::test('pages::dashboard')
            ->assertSeeInOrder([__('Domain tersimpan'), '0', __('Sync terakhir'), '—']);
    }

    public function test_it_shows_the_last_sync_time_in_indonesian(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Kartunya menampilkan max(last_synced_at) dari seluruh tabel, jadi baris
        // lain harus dibersihkan dulu supaya yang diuji benar-benar baris ini.
        \App\Models\BpsDomain::query()->delete();

        \App\Models\BpsDomain::create([
            'domain_id' => '1100',
            'domain_name' => 'Aceh',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => \Illuminate\Support\Facades\Date::parse('2026-08-17 09:30'),
        ]);

        \Livewire\Livewire::test('pages::dashboard')
            ->assertSee('17 Agustus 2026, 09:30');
    }
}

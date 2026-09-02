<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DomainPageTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('bps.domains'))->assertRedirect(route('login'));
    }

    public function test_the_page_lists_the_stored_domains(): void
    {
        $this->actingAs(User::factory()->create());

        // Tabel dikosongkan supaya baris uji pasti berada di halaman pertama.
        // Transaksi test di-rollback, jadi data sungguhan tidak terpengaruh.
        BpsDomain::query()->delete();

        BpsDomain::create([
            'domain_id' => 'TEST-0002',
            'domain_name' => 'Uji Aceh',
            'domain_url' => 'https://aceh.bps.go.id',
            'type' => 'all',
            'last_synced_at' => now(),
        ]);

        $this->get(route('bps.domains'))
            ->assertOk()
            ->assertSee('Uji Aceh');
    }

    public function test_the_fetch_button_stores_the_domains(): void
    {
        $this->actingAs(User::factory()->create());

        Http::fake([
            '*' => Http::response([
                'status' => 'OK',
                'data-availability' => 'available',
                'data' => [
                    ['page' => 1, 'pages' => 1, 'total' => 1],
                    [['domain_id' => 'TEST-0002', 'domain_name' => 'Uji Aceh', 'domain_url' => null]],
                ],
            ]),
        ]);

        Livewire::test('pages::bps.domains')->call('fetchData');

        $this->assertDatabaseHas('bps_domains', ['domain_id' => 'TEST-0002']);
    }

    public function test_a_failed_fetch_shows_a_message_instead_of_crashing(): void
    {
        $this->actingAs(User::factory()->create());

        Http::fake([
            '*' => Http::response(['status' => 'Error', 'message' => 'kunci salah'], 200),
        ]);

        Livewire::test('pages::bps.domains')
            ->call('fetchData')
            ->assertSet('message', 'kunci salah')
            ->assertSet('failed', true);
    }

    public function test_the_search_box_filters_the_table(): void
    {
        $this->actingAs(User::factory()->create());

        BpsDomain::query()->delete();

        foreach ([['TEST-0002', 'Uji Aceh'], ['TEST-0003', 'Uji Jawa Timur']] as [$id, $name]) {
            BpsDomain::create([
                'domain_id' => $id,
                'domain_name' => $name,
                'domain_url' => null,
                'type' => 'all',
                'last_synced_at' => now(),
            ]);
        }

        // Search filters the query before pagination, so real BPS rows on other
        // pages can't hide a match — "Uji Aceh" genuinely doesn't contain "Jawa".
        Livewire::test('pages::bps.domains')
            ->set('search', 'Jawa')
            ->assertSee('Uji Jawa Timur')
            ->assertDontSee('Uji Aceh');
    }
}

<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class QueryBuilderReloadButtonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BpsDomain::firstOrCreate(['domain_id' => '3200'], [
            'domain_name' => 'DKI Jakarta',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => now(),
        ]);

        $this->actingAs(User::factory()->create());
    }

    public function test_each_card_shows_a_reload_button_wired_to_its_own_field(): void
    {
        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->assertSeeHtml("wire:click=\"reload('years')\"")
            ->assertSeeHtml("wire:click=\"reload('turyears')\"")
            ->assertSeeHtml("wire:click=\"reload('characteristics')\"")
            ->assertSeeHtml("wire:click=\"reload('vervars')\"");
    }

    public function test_a_success_notice_renders_inside_its_own_card(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'OK',
            'data-availability' => 'available',
            'data' => [
                ['page' => 1, 'pages' => 1, 'total' => 1],
                [['th_id' => 88001, 'th' => '2088']],
            ],
        ])]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('reload', 'years')
            ->assertSee('Berhasil, 1 data diperbarui.');
    }

    public function test_an_error_message_renders_inside_its_own_card(): void
    {
        Http::fake(['*' => Http::response('down', 500)]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('reload', 'years')
            ->assertSee('Permintaan ke BPS gagal.');
    }
}

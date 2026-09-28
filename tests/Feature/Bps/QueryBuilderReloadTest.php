<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class QueryBuilderReloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Komponen query-builder mengunci DOMAIN ke '3200', jadi domain ini
        // wajib ada. var_id 900001 dan th_id 88001 sengaja dipakai angka
        // yang tidak mungkin dipakai data BPS asli, biar gampang dibedakan.
        BpsDomain::firstOrCreate(['domain_id' => '3200'], [
            'domain_name' => 'DKI Jakarta',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => now(),
        ]);

        $this->actingAs(User::factory()->create());
    }

    private function periodPage(int $thId, string $th): array
    {
        return [
            'status' => 'OK',
            'data-availability' => 'available',
            'data' => [
                ['page' => 1, 'pages' => 1, 'total' => 1],
                [['th_id' => $thId, 'th' => $th]],
            ],
        ];
    }

    public function test_reload_fetches_only_the_requested_field_and_stores_it(): void
    {
        Http::fake(['*' => Http::response($this->periodPage(88001, '2088'))]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('reload', 'years')
            ->assertSet('reloadNotices.years', 'Berhasil, 1 data diperbarui.');

        Http::assertSentCount(1);
        $this->assertDatabaseHas('data_bps.bps_periods', [
            'domain_id' => '3200',
            'th_id' => 88001,
            'th' => '2088',
        ]);
    }

    public function test_reload_resets_the_selection_for_that_field_only(): void
    {
        Http::fake(['*' => Http::response($this->periodPage(88001, '2088'))]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('years', ['1'])
            ->set('vervars', ['2'])
            ->call('reload', 'years')
            ->assertSet('years', [])
            ->assertSet('vervars', ['2']);
    }

    public function test_reload_records_an_error_and_keeps_old_selection_when_bps_fails(): void
    {
        Http::fake(['*' => Http::response('down', 500)]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('years', ['1'])
            ->call('reload', 'years')
            ->assertSet('years', ['1'])
            ->assertSet('reloadNotices.years', null);

        $this->assertDatabaseMissing('data_bps.bps_periods', [
            'domain_id' => '3200',
            'th_id' => 88001,
        ]);
    }

    public function test_reload_does_nothing_without_an_indicator_selected(): void
    {
        Http::fake();

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->call('reload', 'years');

        Http::assertNothingSent();
    }
}

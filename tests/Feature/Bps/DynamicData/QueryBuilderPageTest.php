<?php

namespace Tests\Feature\Bps\DynamicData;

use App\Models\BpsDomain;
use App\Models\BpsVariable;
use App\Models\BpsVerticalVariable;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QueryBuilderPageTest extends TestCase
{
    private function createDomain(): void
    {
        if (BpsDomain::where('domain_id', '3200')->exists()) {
            return;
        }

        BpsDomain::create([
            'domain_id' => '3200',
            'domain_name' => 'Jawa Barat',
            'domain_url' => 'https://jabar.bps.go.id',
            'type' => 'all',
            'last_synced_at' => now(),
        ]);
    }

    public function test_searching_filters_the_judul_baris_options(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900001, 'vervar' => 'Uji Provinsi Jawa Barat']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900002, 'vervar' => 'Uji Kota Bandung']);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('vervarSearch', 'Bandung')
            ->assertSee('Uji Kota Bandung')
            ->assertDontSee('Uji Provinsi Jawa Barat');
    }

    public function test_choosing_a_different_table_clears_the_search_boxes(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);
        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900002, 'title' => 'Uji APM']);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('vervarSearch', 'Bandung')
            ->set('varId', '900002')
            ->assertSet('vervarSearch', '');
    }
}

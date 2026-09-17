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

    public function test_toggle_adds_and_removes_a_value(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);

        $component = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('toggle', 'vervars', '900001');

        $this->assertSame(['900001'], $component->instance()->vervars);

        $component->call('toggle', 'vervars', '900001');

        $this->assertSame([], $component->instance()->vervars);
    }

    public function test_toggle_ignores_a_field_that_is_not_a_selection_property(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);

        $component = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('toggle', 'resultJson', 'hacked');

        $this->assertNull($component->instance()->resultJson);
    }

    public function test_toggle_all_selects_only_the_currently_visible_options(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900001, 'vervar' => 'Uji Provinsi Jawa Barat']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900002, 'vervar' => 'Uji Kota Bandung']);

        $component = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('vervarSearch', 'Bandung')
            ->call('toggleAll', 'vervars');

        $this->assertSame(['900002'], $component->instance()->vervars);
    }

    public function test_toggle_all_does_not_drop_selections_hidden_by_search(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900001, 'vervar' => 'Uji Provinsi Jawa Barat']);
        BpsVerticalVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'vervar_id' => 900002, 'vervar' => 'Uji Kota Bandung']);

        $component = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->call('toggle', 'vervars', '900001')
            ->set('vervarSearch', 'Bandung')
            ->call('toggleAll', 'vervars');

        $selected = $component->instance()->vervars;
        sort($selected);

        $this->assertSame(['900001', '900002'], $selected);
    }
}

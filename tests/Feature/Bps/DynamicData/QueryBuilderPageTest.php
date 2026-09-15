<?php

namespace Tests\Feature\Bps\DynamicData;

use App\Models\BpsDomain;
use App\Models\BpsSubject;
use App\Models\BpsSubjectCategory;
use App\Models\BpsVariable;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

// Domain 3200 di database dev ini sudah berisi data sinkron asli dari BPS
// (ratusan variable/subject/vervar dsb). Semua ID di fixture test memakai
// angka sintetis (900000+, di luar rentang ID BPS yang nyata) supaya tidak
// bentrok unique constraint dengan data itu — pola yang sama dipakai di
// riwayat commit 319e059 "use synthetic domain ids in BPS test fixtures".
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

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('bps.dynamic-data.query-builder'))->assertRedirect(route('login'));
    }

    public function test_the_page_is_reachable_from_the_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertSee('Data Dinamis');

        $this->get(route('bps.dynamic-data.query-builder'))
            ->assertOk()
            ->assertSee('Data Dinamis');
    }

    public function test_it_lists_categories_subjects_and_tables_with_nothing_filtered(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsSubjectCategory::create(['domain_id' => '3200', 'subcat_id' => 900001, 'title' => 'Uji Sosial dan Kependudukan']);
        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900001, 'subcat_id' => 900001, 'title' => 'Uji Gender']);
        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi Bulanan (M-to-M)', 'sub_id' => 900001]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->assertSee('Uji Sosial dan Kependudukan')
            ->assertSee('Uji Gender')
            ->assertSee('Uji Inflasi Bulanan (M-to-M)');
    }

    public function test_choosing_a_category_filters_the_subject_list(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsSubjectCategory::create(['domain_id' => '3200', 'subcat_id' => 900001, 'title' => 'Uji Sosial dan Kependudukan']);
        BpsSubjectCategory::create(['domain_id' => '3200', 'subcat_id' => 900002, 'title' => 'Uji Ekonomi dan Perdagangan']);
        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900001, 'subcat_id' => 900001, 'title' => 'Uji Gender']);
        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900002, 'subcat_id' => 900002, 'title' => 'Uji Perdagangan']);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('subcatId', '900001')
            ->assertSee('Uji Gender')
            ->assertDontSee('Uji Perdagangan');
    }

    public function test_choosing_a_subject_filters_the_table_list(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900001, 'subcat_id' => 900001, 'title' => 'Uji Gender']);
        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900002, 'subcat_id' => 900002, 'title' => 'Uji Perdagangan']);
        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi Bulanan (M-to-M)', 'sub_id' => 900001]);
        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900002, 'title' => 'Uji Angka Partisipasi Murni', 'sub_id' => 900002]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('subId', '900001')
            ->assertSee('Uji Inflasi Bulanan (M-to-M)')
            ->assertDontSee('Uji Angka Partisipasi Murni');
    }

    public function test_changing_the_category_resets_the_subject_and_table(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsSubjectCategory::create(['domain_id' => '3200', 'subcat_id' => 900001, 'title' => 'Uji Sosial dan Kependudukan']);
        BpsSubject::create(['domain_id' => '3200', 'sub_id' => 900001, 'subcat_id' => 900001, 'title' => 'Uji Gender']);
        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi Bulanan (M-to-M)', 'sub_id' => 900001]);

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('subId', '900001')
            ->set('varId', '900001')
            ->set('subcatId', '900001')
            ->assertSet('subId', '')
            ->assertSet('varId', null);
    }
}

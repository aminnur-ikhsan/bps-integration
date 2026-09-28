<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\BpsSubject;
use App\Models\BpsSubjectCategory;
use App\Models\BpsVariable;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class VariablesPageTest extends TestCase
{
    public function test_the_page_shows_all_eight_columns_including_the_joined_subject_category(): void
    {
        $this->actingAs(User::factory()->create());

        BpsDomain::firstOrCreate(
            ['domain_id' => '9999'],
            [
                'domain_name' => 'Uji Aceh',
                'domain_url' => null,
                'type' => 'all',
                'last_synced_at' => now(),
            ],
        );

        BpsSubjectCategory::create([
            'domain_id' => '9999',
            'subcat_id' => 1,
            'title' => 'Uji Kategori Sosial',
            'last_synced_at' => now(),
        ]);

        BpsSubject::create([
            'domain_id' => '9999',
            'sub_id' => 10,
            'subcat_id' => 1,
            'title' => 'Uji Subjek Kependudukan',
            'last_synced_at' => now(),
        ]);

        BpsVariable::create([
            'domain_id' => '9999',
            'var_id' => 99,
            'title' => 'Uji Indikator Penduduk',
            'sub_id' => 10,
            'sub_name' => 'Uji Subjek Nama',
            'def' => 'Uji definisi indikator',
            'notes' => null,
            'vertical' => null,
            'unit' => 'Persen',
            'graph_id' => null,
            'graph_name' => 'Uji Grafik Batang',
            'last_synced_at' => now(),
        ]);

        Livewire::test('pages::bps.dynamic-data.variables')
            ->set('domainId', '9999')
            ->assertSeeInOrder([
                'Var ID',
                'Nama Indikator',
                'Definisi',
                'Grafik',
                'Subject Kategori',
                'Subject',
                'Satuan',
                'Sync terakhir',
            ])
            ->assertSee('Uji Indikator Penduduk')
            ->assertSee('Uji definisi indikator')
            ->assertSee('Uji Grafik Batang')
            ->assertSee('Uji Kategori Sosial')
            ->assertSee('Uji Subjek Nama')
            ->assertSee('Persen');
    }
}

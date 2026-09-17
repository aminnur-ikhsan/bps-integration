<?php

namespace Tests\Feature\Bps\DynamicData;

use App\Models\BpsDomain;
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

    // position:sticky selalu bikin stacking context baru. Tanpa z-index
    // eksplisit di kotak sticky-nya sendiri, dropdown di dalamnya (z-20)
    // terjebak di stacking context itu dan kalah sama elemen yang urutan
    // DOM-nya lebih belakang (Submit, Salin, blok JSON) — persis bug yang
    // dilaporkan user. Test ini cuma jaga supaya kelas z-index-nya tidak
    // hilang lagi nanti, bukan bukti visual (itu manual, lihat verifikasi).
    public function test_the_sticky_filter_column_has_an_explicit_z_index(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        $html = Livewire::test('pages::bps.dynamic-data.query-builder')->html();

        $stickyPos = strpos($html, 'lg:sticky');
        $this->assertNotFalse($stickyPos, 'Kolom kiri harus sticky.');

        $nearby = substr($html, max(0, $stickyPos - 80), 160);
        $this->assertMatchesRegularExpression('/lg:z-\d+/', $nearby, 'Kotak sticky harus punya z-index eksplisit, atau dropdown di dalamnya akan ketutupan elemen setelahnya di DOM.');
    }
}

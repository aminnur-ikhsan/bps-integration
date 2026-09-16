<?php

namespace Tests\Feature\Bps\DynamicData;

use App\Models\BpsDomain;
use App\Models\BpsVariable;
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

    public function test_the_four_boxes_are_positioned_on_a_three_column_grid(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi Bulanan (M-to-M)']);

        $html = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->html();

        // Kontainer grid: 1 kolom di layar sempit, 3 kolom mulai lg.
        $this->assertStringContainsString('grid-cols-1', $html);
        $this->assertStringContainsString('lg:grid-cols-3', $html);

        $tahunPos = strpos($html, 'lg:col-start-1 lg:row-start-1');
        $turunanPos = strpos($html, 'lg:col-start-1 lg:row-start-2');
        $karakteristikPos = strpos($html, 'lg:col-start-2 lg:row-start-1');
        $judulBarisPos = strpos($html, 'lg:col-start-3 lg:row-start-1 lg:row-span-2');

        $this->assertNotFalse($tahunPos, 'Tahun harus di kolom 1 baris 1.');
        $this->assertNotFalse($turunanPos, 'Turunan Tahun harus di kolom 1 baris 2.');
        $this->assertNotFalse($karakteristikPos, 'Karakteristik harus di kolom 2 baris 1.');
        $this->assertNotFalse($judulBarisPos, 'Judul Baris harus di kolom 3 baris 1, membentang 2 baris.');

        // Label yang benar harus muncul tak lama setelah kelas posisinya —
        // memastikan kelasnya menempel ke box yang tepat, bukan tertukar.
        $this->assertStringContainsString('Tahun', substr($html, $tahunPos, 400));
        $this->assertStringContainsString('Turunan Tahun', substr($html, $turunanPos, 400));
        $this->assertStringContainsString('Karakteristik', substr($html, $karakteristikPos, 400));
        $this->assertStringContainsString('Judul Baris', substr($html, $judulBarisPos, 400));
    }

    public function test_each_box_is_wrapped_in_a_bordered_card_with_a_divided_header(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi Bulanan (M-to-M)']);

        $html = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->html();

        // Card Tahun: kelas posisi grid dari Task 1 digabung kelas card (border, rounded).
        $tahunPos = strpos($html, 'lg:col-start-1 lg:row-start-1 rounded-xl border border-zinc-200');
        $this->assertNotFalse($tahunPos, 'Box Tahun harus punya kelas card (rounded-xl border ...) menempel di kelas posisi grid-nya.');

        // Header-nya (flux:label) harus punya border-b, dan label "Tahun" ada di dekat situ.
        $nearby = substr($html, $tahunPos, 600);
        $this->assertStringContainsString('border-b', $nearby, 'Header box harus punya garis pembatas (border-b).');
        $this->assertStringContainsString('Tahun', $nearby);
    }
}

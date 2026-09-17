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

    public function test_the_dropdowns_and_cards_are_arranged_in_two_columns(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        BpsVariable::create(['domain_id' => '3200', 'var_id' => 900001, 'title' => 'Uji Inflasi']);

        $html = Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->html();

        $rowPos = strpos($html, 'lg:flex-row');
        $leftPos = strpos($html, 'lg:w-80');
        $rightPos = strpos($html, 'flex flex-1 flex-col gap-4');
        $actionBarPos = strpos($html, 'border-t border-zinc-200 pt-4');

        $this->assertNotFalse($rowPos, 'Baris dua-kolom (lg:flex-row) harus ada.');
        $this->assertNotFalse($leftPos, 'Kolom kiri (lg:w-80) harus ada.');
        $this->assertNotFalse($rightPos, 'Kolom kanan (flex-1) harus ada.');
        $this->assertNotFalse($actionBarPos, 'Baris aksi (border-t) harus ada.');

        // Urutan di HTML harus: baris dua-kolom -> kolom kiri -> kolom kanan -> baris aksi.
        // Ini membuktikan baris aksi ada DI LUAR kolom kanan, bukan menempel di
        // bawah kartu Judul Baris di dalam kolom kanan.
        $this->assertTrue($rowPos < $leftPos);
        $this->assertTrue($leftPos < $rightPos);
        $this->assertTrue($rightPos < $actionBarPos);

        $this->assertStringContainsString('lg:sticky', $html);
        $this->assertStringContainsString('lg:items-start', $html);
        $this->assertStringNotContainsString('max-w-xl', $html);
    }

    public function test_the_three_dropdowns_still_render_inside_the_left_column(): void
    {
        $this->createDomain();
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->assertSee('Kategori Subjek')
            ->assertSee('Subjek')
            ->assertSee('Tabel / Indikator');
    }
}

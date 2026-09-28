<?php

namespace Tests\Feature\Bps;

use Livewire\Livewire;
use Tests\TestCase;

class QueryBuilderReloadTest extends TestCase
{
    public function test_changing_the_indicator_clears_stale_reload_messages(): void
    {
        Livewire::test('pages::bps.dynamic-data.query-builder')
            ->set('varId', '900001')
            ->set('reloadNotices', ['years' => 'Berhasil, 12 data diperbarui.'])
            ->set('reloadErrors', ['turyears' => 'Permintaan ke BPS gagal.'])
            ->set('varId', '900002')
            ->assertSet('reloadNotices', [])
            ->assertSet('reloadErrors', []);
    }
}

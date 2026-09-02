<?php

namespace Tests\Feature;

use App\Models\BpsDomain;
use App\Models\BpsFetchLog;
use Tests\TestCase;

class BpsDomainTest extends TestCase
{
    public function test_a_domain_can_be_stored(): void
    {
        $domain = BpsDomain::create([
            'domain_id' => '1100',
            'domain_name' => 'Aceh',
            'domain_url' => 'https://aceh.bps.go.id',
            'type' => 'all',
            'last_synced_at' => now(),
        ]);

        $this->assertDatabaseHas('bps_domains', ['domain_id' => '1100', 'domain_name' => 'Aceh']);
        // Dibaca ulang dari database, supaya yang diuji benar-benar cast-nya —
        // bukan objek yang memang sudah bertipe tanggal sejak sebelum disimpan.
        // Aplikasi memakai Date::use(CarbonImmutable), jadi yang dicek antarmukanya.
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $domain->fresh()->last_synced_at);
    }

    public function test_a_fetch_log_stores_its_params_as_an_array(): void
    {
        $log = BpsFetchLog::create([
            'user_id' => null,
            'endpoint' => 'domain',
            'params' => ['type' => 'all'],
            'status' => 'success',
            'http_status' => 200,
            'records_count' => 3,
            'duration_ms' => 120,
            'error' => null,
            'created_at' => now(),
        ]);

        $this->assertSame(['type' => 'all'], $log->fresh()->params);
    }
}

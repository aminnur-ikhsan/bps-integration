<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\BpsFetchLog;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\DomainSync;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainSyncTest extends TestCase
{
    private function fakeOnePage(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'OK',
                'data-availability' => 'available',
                'data' => [
                    ['page' => 1, 'pages' => 1, 'total' => 2],
                    [
                        ['domain_id' => 'TEST-0001', 'domain_name' => 'Uji Pusat', 'domain_url' => 'https://www.bps.go.id'],
                        ['domain_id' => 'TEST-0002', 'domain_name' => 'Uji Aceh', 'domain_url' => 'https://aceh.bps.go.id'],
                    ],
                ],
            ]),
        ]);
    }

    public function test_it_stores_the_domains_it_receives(): void
    {
        $this->fakeOnePage();

        $result = app(DomainSync::class)->sync();

        $this->assertSame(2, $result->count);
        $this->assertDatabaseHas('bps_domains', ['domain_id' => 'TEST-0002', 'domain_name' => 'Uji Aceh']);
        $this->assertDatabaseHas('bps_domains', ['domain_id' => 'TEST-0001', 'domain_name' => 'Uji Pusat']);
    }

    public function test_syncing_twice_updates_instead_of_duplicating(): void
    {
        $this->fakeOnePage();

        app(DomainSync::class)->sync();
        app(DomainSync::class)->sync();

        $this->assertSame(1, BpsDomain::where('domain_id', 'TEST-0002')->count());
    }

    public function test_it_follows_every_page(): void
    {
        Http::fakeSequence()
            ->push([
                'status' => 'OK',
                'data-availability' => 'available',
                'data' => [
                    ['page' => 1, 'pages' => 2, 'total' => 2],
                    [['domain_id' => 'TEST-0001', 'domain_name' => 'Uji Pusat', 'domain_url' => null]],
                ],
            ])
            ->push([
                'status' => 'OK',
                'data-availability' => 'available',
                'data' => [
                    ['page' => 2, 'pages' => 2, 'total' => 2],
                    [['domain_id' => 'TEST-0002', 'domain_name' => 'Uji Aceh', 'domain_url' => null]],
                ],
            ]);

        $result = app(DomainSync::class)->sync();

        $this->assertSame(2, $result->count);
        $this->assertSame(2, BpsDomain::whereIn('domain_id', ['TEST-0001', 'TEST-0002'])->count());
    }

    public function test_it_writes_a_success_log(): void
    {
        $this->fakeOnePage();

        app(DomainSync::class)->sync('all', null);

        $log = BpsFetchLog::latest('id')->first();

        $this->assertSame('domain', $log->endpoint);
        $this->assertSame('success', $log->status);
        $this->assertSame(2, $log->records_count);
        $this->assertSame(['type' => 'all'], $log->params);
    }

    public function test_a_failure_is_logged_and_leaves_the_data_untouched(): void
    {
        BpsDomain::create([
            'domain_id' => 'TEST-0002',
            'domain_name' => 'Aceh lama',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => now(),
        ]);

        Http::fake([
            '*' => Http::response(['status' => 'Error', 'message' => 'kunci salah'], 200),
        ]);

        try {
            app(DomainSync::class)->sync();
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            $this->assertSame('kunci salah', $e->getMessage());
        }

        $log = BpsFetchLog::latest('id')->first();

        $this->assertSame('failed', $log->status);
        $this->assertSame('kunci salah', $log->error);
        $this->assertSame('Aceh lama', BpsDomain::where('domain_id', 'TEST-0002')->value('domain_name'));
    }

    public function test_it_stores_nothing_when_bps_reports_no_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'OK',
                'data-availability' => 'not-available',
            ]),
        ]);

        $result = app(DomainSync::class)->sync();

        $this->assertSame(0, $result->count);
    }

    public function test_a_connection_failure_logs_the_redacted_cause_not_the_generic_message(): void
    {
        // Key tetap diikat ke nilai yang diketahui supaya penyamaran benar-benar teruji.
        $this->app->instance(
            \App\Services\Bps\BpsClient::class,
            new \App\Services\Bps\BpsClient('https://webapi.bps.go.id/v1/api', 'kunci-rahasia'),
        );

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException(
                'cURL error 6: Could not resolve host for https://webapi.bps.go.id/v1/api/domain?key=kunci-rahasia'
            );
        });

        try {
            app(DomainSync::class)->sync();
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            // diharapkan
        }

        $log = BpsFetchLog::latest('id')->first();

        $this->assertStringContainsString('Could not resolve host', $log->error);
        $this->assertStringNotContainsString('kunci-rahasia', $log->error);
    }

    public function test_a_malformed_row_is_reported_as_a_failure_instead_of_crashing(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'OK',
                'data-availability' => 'available',
                'data' => [
                    ['page' => 1, 'pages' => 1, 'total' => 1],
                    [['domain_id' => 'TEST-0001']],
                ],
            ]),
        ]);

        try {
            app(DomainSync::class)->sync();
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            // diharapkan
        }

        $log = BpsFetchLog::latest('id')->first();

        $this->assertSame('failed', $log->status);
    }
}

<?php

namespace Tests\Feature\Bps;

use App\Models\BpsDomain;
use App\Models\BpsFetchLog;
use App\Models\BpsVariable;
use App\Services\Bps\BpsApiException;
use App\Services\Bps\VariableSync;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VariableSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // bps_variables punya foreign key ke bps_domains. Domain fiktif dipakai
        // supaya tidak bentrok dengan data BPS sungguhan di database.
        BpsDomain::firstOrCreate(['domain_id' => '9999'], [
            'domain_name' => 'Domain Uji',
            'domain_url' => null,
            'type' => 'all',
            'last_synced_at' => now(),
        ]);
    }

    private function page(int $page, int $pages, array $vars): array
    {
        return [
            'status' => 'OK',
            'data-availability' => 'available',
            'data' => [
                ['page' => $page, 'pages' => $pages, 'total' => 99],
                $vars,
            ],
        ];
    }

    private function var(int $id): array
    {
        return ['var_id' => $id, 'title' => "Variabel {$id}", 'sub_name' => 'Subjek', 'unit' => 'jiwa'];
    }

    private function fakeByPage(callable $decide): void
    {
        Http::fake(function ($request) use ($decide) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return $decide((int) ($q['page'] ?? 1));
        });
    }

    public function test_it_stores_a_single_page(): void
    {
        Http::fake(['*' => Http::response($this->page(1, 1, [$this->var(11), $this->var(12)]))]);

        $result = app(VariableSync::class)->sync('9999');

        $this->assertSame(2, $result->count);
        $this->assertDatabaseHas('data_bps.bps_variables', ['domain_id' => '9999', 'var_id' => 11]);
        $this->assertDatabaseHas('data_bps.bps_variables', ['domain_id' => '9999', 'var_id' => 12]);
    }

    public function test_it_stores_nothing_when_data_is_not_available(): void
    {
        Http::fake(['*' => Http::response(['status' => 'OK', 'data-availability' => 'not-available'])]);

        $result = app(VariableSync::class)->sync('9999');

        $this->assertSame(0, $result->count);
        $this->assertSame('success', BpsFetchLog::latest('id')->first()->status);
    }

    public function test_it_fetches_every_page_with_the_net(): void
    {
        $this->fakeByPage(fn (int $page) => Http::response($this->page($page, 3, [$this->var($page * 10)])));

        $result = app(VariableSync::class)->sync('9999');

        $this->assertSame(3, $result->count);
        $this->assertSame(3, BpsVariable::where('domain_id', '9999')->count());
        $this->assertSame('success', BpsFetchLog::latest('id')->first()->status);
    }

    public function test_a_page_that_fails_the_net_is_recovered_one_by_one(): void
    {
        $failedOnce = [];

        $this->fakeByPage(function (int $page) use (&$failedOnce) {
            // Halaman 2 gagal saat jaring, lalu sukses saat dipancing ulang.
            if ($page === 2 && ! in_array(2, $failedOnce, true)) {
                $failedOnce[] = 2;

                return Http::response('down', 500);
            }

            return Http::response($this->page($page, 3, [$this->var($page * 10)]));
        });

        $result = app(VariableSync::class)->sync('9999');

        $this->assertSame(3, $result->count);
        $this->assertSame('success', BpsFetchLog::latest('id')->first()->status);
        $this->assertDatabaseHas('data_bps.bps_variables', ['domain_id' => '9999', 'var_id' => 20]);
    }

    public function test_pages_that_keep_failing_are_reported_but_successes_are_saved(): void
    {
        $this->fakeByPage(fn (int $page) => $page === 3
            ? Http::response('down', 500)
            : Http::response($this->page($page, 3, [$this->var($page * 10)])));

        try {
            app(VariableSync::class)->sync('9999');
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            $this->assertStringContainsString('3', $e->getMessage());
        }

        $this->assertDatabaseHas('data_bps.bps_variables', ['domain_id' => '9999', 'var_id' => 10]);
        $this->assertDatabaseHas('data_bps.bps_variables', ['domain_id' => '9999', 'var_id' => 20]);
        $this->assertSame('partial', BpsFetchLog::latest('id')->first()->status);
    }

    public function test_a_malformed_item_is_reported_as_a_failure(): void
    {
        // Item tanpa 'title'.
        Http::fake(['*' => Http::response($this->page(1, 1, [['var_id' => 5]]))]);

        try {
            app(VariableSync::class)->sync('9999');
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            // diharapkan
        }

        $this->assertSame('failed', BpsFetchLog::latest('id')->first()->status);
    }
}

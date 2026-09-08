<?php

namespace Tests\Feature\Bps;

use App\Services\Bps\BpsApiException;
use App\Services\Bps\BpsClient;
use App\Services\Bps\BpsClientPool;
use App\Services\Bps\BpsPageCollector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BpsPageCollectorTest extends TestCase
{
    private function collector(): BpsPageCollector
    {
        return new BpsPageCollector(
            new BpsClient('https://webapi.bps.go.id/v1/api', 'kunci-rahasia'),
            new BpsClientPool('https://webapi.bps.go.id/v1/api', 'kunci-rahasia'),
        );
    }

    private function okBody(int $page): array
    {
        return [
            'status' => 'OK',
            'data-availability' => 'available',
            'data' => [
                ['page' => $page, 'pages' => 3, 'total' => 3],
                [['var_id' => $page, 'title' => "V{$page}"]],
            ],
        ];
    }

    private function fakeByPage(callable $decide): void
    {
        Http::fake(function ($request) use ($decide) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return $decide((int) ($q['page'] ?? 1));
        });
    }

    public function test_fetch_first_returns_the_page_body(): void
    {
        Http::fake(['*' => Http::response($this->okBody(1))]);

        $body = $this->collector()->fetchFirst('list', ['model' => 'var']);

        $this->assertSame('available', $body['data-availability']);
    }

    public function test_fetch_first_throws_when_page_one_fails(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);

        $this->expectException(BpsApiException::class);

        $this->collector()->fetchFirst('list', ['model' => 'var']);
    }

    public function test_fetch_with_net_passes_pool_results_straight_through(): void
    {
        $this->fakeByPage(fn (int $page) => $page === 3
            ? Http::response('down', 500)
            : Http::response($this->okBody($page)));

        $result = $this->collector()->fetchWithNet('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([2, 4], array_keys($result['bodies']));
        $this->assertSame('HTTP 500', $result['failedPages'][3]);
    }

    public function test_fetch_one_by_one_collects_failures_without_throwing(): void
    {
        $this->fakeByPage(fn (int $page) => $page === 3
            ? Http::response('down', 500)
            : Http::response($this->okBody($page)));

        $result = $this->collector()->fetchOneByOne('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([2, 4], array_keys($result['bodies']));
        $this->assertArrayHasKey(3, $result['failedPages']);
        $this->assertIsString($result['failedPages'][3]);
    }

    public function test_fetch_one_by_one_returns_empty_result_for_no_pages(): void
    {
        Http::fake(['*' => Http::response($this->okBody(1))]);

        $result = $this->collector()->fetchOneByOne('list', ['model' => 'var'], []);

        $this->assertSame([], $result['bodies']);
        $this->assertSame([], $result['failedPages']);
    }
}

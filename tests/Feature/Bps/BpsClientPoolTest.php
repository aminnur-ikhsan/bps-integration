<?php

namespace Tests\Feature\Bps;

use App\Services\Bps\BpsClientPool;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BpsClientPoolTest extends TestCase
{
    private function pool(): BpsClientPool
    {
        return new BpsClientPool('https://webapi.bps.go.id/v1/api', 'kunci-rahasia');
    }

    private function okBody(int $page): array
    {
        return [
            'status' => 'OK',
            'data-availability' => 'available',
            'data' => [
                ['page' => $page, 'pages' => 9, 'total' => 90],
                [['var_id' => $page * 10, 'title' => "Variabel {$page}"]],
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

    public function test_it_returns_bodies_for_pages_that_succeed(): void
    {
        $this->fakeByPage(fn (int $page) => Http::response($this->okBody($page)));

        $result = $this->pool()->fetchPages('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([2, 3, 4], array_keys($result['bodies']));
        $this->assertSame([], $result['failedPages']);
        $this->assertSame(20, $result['bodies'][2]['data'][1][0]['var_id']);
    }

    public function test_a_page_with_an_http_error_lands_in_failed_pages(): void
    {
        $this->fakeByPage(fn (int $page) => $page === 3
            ? Http::response('boom', 500)
            : Http::response($this->okBody($page)));

        $result = $this->pool()->fetchPages('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([2, 4], array_keys($result['bodies']));
        $this->assertSame('HTTP 500', $result['failedPages'][3]);
    }

    public function test_a_page_where_bps_reports_an_error_body_lands_in_failed_pages(): void
    {
        $this->fakeByPage(fn (int $page) => $page === 4
            ? Http::response(['status' => 'Error', 'message' => 'kunci salah'], 200)
            : Http::response($this->okBody($page)));

        $result = $this->pool()->fetchPages('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([2, 3], array_keys($result['bodies']));
        $this->assertSame('kunci salah', $result['failedPages'][4]);
    }

    public function test_a_connection_failure_lands_in_failed_pages_without_crashing(): void
    {
        $this->fakeByPage(function (int $page) {
            if ($page === 2) {
                throw new ConnectionException('cURL error 28: connect timed out');
            }

            return Http::response($this->okBody($page));
        });

        $result = $this->pool()->fetchPages('list', ['model' => 'var'], [2, 3, 4]);

        $this->assertSame([3, 4], array_keys($result['bodies']));
        $this->assertSame('koneksi gagal', $result['failedPages'][2]);
    }

    public function test_it_sends_the_api_key_and_page_number_on_every_request(): void
    {
        $this->fakeByPage(fn (int $page) => Http::response($this->okBody($page)));

        $this->pool()->fetchPages('list', ['model' => 'var'], [2, 3]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'page=2') && str_contains($r->url(), 'key=kunci-rahasia'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'page=3'));
    }

    public function test_it_fetches_more_pages_than_the_concurrency_limit(): void
    {
        $this->fakeByPage(fn (int $page) => Http::response($this->okBody($page)));

        $result = $this->pool()->fetchPages('list', ['model' => 'var'], range(2, 13));

        $this->assertSame(range(2, 13), array_keys($result['bodies']));
        $this->assertSame([], $result['failedPages']);
    }
}

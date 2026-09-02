<?php

namespace Tests\Feature\Bps;

use App\Services\Bps\BpsApiException;
use App\Services\Bps\BpsClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BpsClientTest extends TestCase
{
    private function client(): BpsClient
    {
        return new BpsClient('https://webapi.bps.go.id/v1/api', 'kunci-rahasia');
    }

    public function test_it_adds_the_api_key_to_the_query(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'OK', 'data' => []]),
        ]);

        $this->client()->get('domain', ['type' => 'all']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'type=all')
                && str_contains($request->url(), 'key=kunci-rahasia');
        });
    }

    public function test_it_returns_the_response_body(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'OK', 'data' => ['ada isinya']]),
        ]);

        $body = $this->client()->get('domain', ['type' => 'all']);

        $this->assertSame(['ada isinya'], $body['data']);
    }

    public function test_it_throws_when_bps_reports_an_error_in_the_body(): void
    {
        // BPS membalas HTTP 200 walau permintaannya ditolak.
        Http::fake([
            '*' => Http::response([
                'status' => 'Error',
                'message' => 'You are not Allowed to take this action. Please re-check your key',
            ], 200),
        ]);

        $this->expectException(BpsApiException::class);
        $this->expectExceptionMessage('You are not Allowed to take this action. Please re-check your key');

        $this->client()->get('domain', ['type' => 'all']);
    }

    public function test_it_throws_when_the_request_fails(): void
    {
        Http::fake([
            '*' => Http::response('gateway timeout', 504),
        ]);

        try {
            $this->client()->get('domain', ['type' => 'all']);
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            $this->assertSame(504, $e->httpStatus);
        }
    }

    public function test_it_throws_when_the_server_cannot_be_reached(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'cURL error 6: Could not resolve host for https://webapi.bps.go.id/v1/api/domain?key=kunci-rahasia'
            );
        });

        try {
            $this->client()->get('domain', ['type' => 'all']);
            $this->fail('Seharusnya melempar BpsApiException.');
        } catch (BpsApiException $e) {
            $this->assertSame('Tidak bisa menghubungi server BPS.', $e->getMessage());
            $this->assertNull($e->httpStatus);
            $this->assertStringContainsString('Could not resolve host', $e->cause);
            $this->assertStringNotContainsString('kunci-rahasia', $e->cause);
        }
    }
}

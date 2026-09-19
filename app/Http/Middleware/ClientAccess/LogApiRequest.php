<?php

namespace App\Http\Middleware\ClientAccess;

use App\Models\ClientAccess\ApiClient;
use App\Models\ClientAccess\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->writeLog($request, $response, $durationMs);

        return $response;
    }

    private function writeLog(Request $request, Response $response, int $durationMs): void
    {
        $client = $request->attributes->get('api_client');
        $content = $response->getContent();
        $body = $content === false ? '' : $content;

        ApiRequestLog::create([
            'api_client_id' => $client instanceof ApiClient ? $client->id : null,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'http_status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'response_bytes' => strlen($body),
            'request_header' => $this->safeHeaders($request),
            'request_parameters' => $request->all(),
            'response_header' => $response->headers->all(),
            'response_body' => $this->responseBody($request, $body),
            'created_at' => now(),
        ]);
    }

    /**
     * Header authorization berisi token mentah milik klien, jadi tidak disimpan.
     *
     * @return array<string, array<int, string|null>>
     */
    private function safeHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        unset($headers['authorization']);

        return $headers;
    }

    /**
     * Isi response hanya disimpan kalau route-nya ditandai middleware
     * saving_body_response, supaya tabel log tidak cepat membengkak.
     *
     * @return array<mixed>|null
     */
    private function responseBody(Request $request, string $body): ?array
    {
        if (! $request->attributes->get('saving_body_response', false)) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}

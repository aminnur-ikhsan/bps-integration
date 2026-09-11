<?php

namespace App\Services\Bps;

use App\Models\BpsConnectionLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class BpsClient
{
    public function __construct(
        private string $baseUrl,
        private string $key,
    ) {}

    // Ambil satu halaman dari sebuah endpoint BPS dan kembalikan body JSON-nya.
    // $logBody: default false — response_body di log koneksi disimpan null,
    // biar tabel log tidak bengkak. Aktifkan per call site kalau lagi dibutuhkan.
    public function get(string $path, array $query = [], bool $logBody = false): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(30)
                // throw: false supaya kegagalan HTTP jatuh ke pengecekan di bawah,
                // bukan dilempar retry() sebagai RequestException.
                ->retry(2, 500, throw: false)
                ->get($path, array_merge($query, ['key' => $this->key]));
        } catch (ConnectionException $e) {
            // Pesan Guzzle memuat URL lengkap berikut API key-nya, jadi key
            // disamarkan sebelum penyebabnya ikut dicatat.
            throw new BpsApiException(
                'Tidak bisa menghubungi server BPS.',
                cause: str_replace($this->key, '***', $e->getMessage()),
            );
        }

        $this->logConnection($response, $path, $query, $logBody);

        if ($response->failed()) {
            throw new BpsApiException('Permintaan ke BPS gagal.', $response->status());
        }

        $body = $response->json();

        // BPS membalas HTTP 200 walau permintaannya ditolak, jadi body wajib dicek.
        if (! is_array($body) || ($body['status'] ?? null) !== 'OK') {
            throw new BpsApiException(
                $body['message'] ?? 'Respons BPS tidak dikenali.',
                $response->status(),
            );
        }

        return $body;
    }

    // Catat satu baris koneksi. Gagal insert tidak boleh menggagalkan request BPS.
    private function logConnection(Response $response, string $path, array $query, bool $logBody): void
    {
        $stats = $response->handlerStats();

        try {
            BpsConnectionLog::create([
                'user_id' => Auth::id(),
                'method' => 'GET',
                'base_url' => $this->baseUrl,
                'path' => $path,
                'http_status' => $response->status(),
                'dns_ms' => round(($stats['namelookup_time'] ?? 0) * 1000),
                'connect_ms' => round(($stats['connect_time'] ?? 0) * 1000),
                'ttfb_ms' => round(($stats['starttransfer_time'] ?? 0) * 1000),
                'total_ms' => round(($stats['total_time'] ?? 0) * 1000),
                'response_bytes' => strlen($response->body()),
                'request_header' => $response->transferStats?->getRequest()?->getHeaders(),
                'request_parameters' => array_merge($query, ['key' => '***']),
                'response_header' => $response->headers(),
                'response_body' => $logBody ? $response->json() : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

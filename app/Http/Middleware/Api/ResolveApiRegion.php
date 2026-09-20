<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiRegion
{
    // Wilayah yang sengaja dibuka lewat Open API. Tambah baris di sini untuk
    // membuka wilayah baru — jangan buka semua data_bps.bps_domains sekaligus.
    private const REGIONS = [
        'jawa-barat' => '3200',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $region = (string) $request->route('region');
        $domainId = self::REGIONS[$region] ?? null;

        if ($domainId === null) {
            return response()->json(['message' => 'Wilayah tidak dikenal.'], 404);
        }

        $request->attributes->set('domain_id', $domainId);

        return $next($request);
    }
}

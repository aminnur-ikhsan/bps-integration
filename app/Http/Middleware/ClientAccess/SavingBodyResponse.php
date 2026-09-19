<?php

namespace App\Http\Middleware\ClientAccess;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SavingBodyResponse
{
    /**
     * Middleware ini tidak menulis log sendiri. Dia hanya menandai request,
     * lalu LogApiRequest di lapisan luar membaca tandanya waktu menyimpan baris log.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('saving_body_response', true);

        return $next($request);
    }
}

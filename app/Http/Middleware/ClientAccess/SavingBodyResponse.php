<?php

namespace App\Http\Middleware\ClientAccess;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SavingBodyResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('saving_body_response', true);

        return $next($request);
    }
}

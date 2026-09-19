<?php

namespace App\Http\Middleware\ClientAccess;

use App\Models\ClientAccess\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (blank($token)) {
            return $this->forbidden();
        }

        // Database menyimpan hash, jadi token dari header dihash dulu.
        $client = ApiClient::where('token', hash('sha256', $token))->first();

        if ($client === null || ! $client->is_active) {
            return $this->forbidden();
        }

        $client->update(['last_used_at' => now()]);

        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    private function forbidden(): Response
    {
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}

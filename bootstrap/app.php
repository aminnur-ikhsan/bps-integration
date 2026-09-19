<?php

use App\Http\Middleware\ClientAccess\AuthenticateApiClient;
use App\Http\Middleware\ClientAccess\LogApiRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'client.token' => AuthenticateApiClient::class,
            'client.log' => LogApiRequest::class,
        ]);

        // Di server aplikasi berada di belakang reverse proxy yang mengakhiri TLS.
        // Tanpa ini Laravel mengira koneksinya http dan menulis URL aset dengan
        // skema yang salah, lalu browser memblokirnya.
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

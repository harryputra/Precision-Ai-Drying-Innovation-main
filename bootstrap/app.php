<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang Cloudflare Tunnel/reverse-proxy (koneksi masuk dari
        // localhost) — percayai X-Forwarded-* agar deteksi HTTPS & URL benar
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role'        => \App\Http\Middleware\EnsureRole::class,
            'locale'      => \App\Http\Middleware\SetLocale::class,
            'ai.webhook'  => \App\Http\Middleware\VerifyAiWebhookKey::class,
            'iot.device'  => \App\Http\Middleware\VerifyIotDeviceKey::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Header keamanan global (web + api)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

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

        // Produksi: visitor http → redirect https (cookie Secure butuh HTTPS).
        // WAJIB append (bukan prepend): harus jalan SETELAH TrustProxies
        // membaca X-Forwarded-Proto — kalau tidak, semua request terdeteksi
        // http (koneksi apache memang plain) → redirect loop tanpa henti.
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

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

        // 419 (CSRF kedaluwarsa — form terlalu lama terbuka, umum di HP):
        // jangan tampilkan layar "419 Page Expired" mentah; muat ulang halaman
        // dengan token segar + pesan yang bisa dipahami pengguna.
        // Catatan: TokenMismatchException sudah dikonversi framework menjadi
        // HttpException(419) SEBELUM callback render dipanggil, jadi tangkap
        // HttpException lalu saring status 419 (status lain diteruskan).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->is('api/*') || $request->expectsJson()) {
                return null; // bukan urusan kita — pakai handler default
            }

            return redirect()
                ->back(fallback: route('login'))
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->withErrors(['email' => __('Sesi kamu sudah berakhir karena halaman terlalu lama terbuka. Halaman telah dimuat ulang — silakan coba lagi.')]);
        });
    })->create();

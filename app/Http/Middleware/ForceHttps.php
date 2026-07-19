<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa HTTPS di produksi (lapis aplikasi).
 *
 * Kasus nyata: Cloudflare melayani http:// tanpa redirect → cookie sesi
 * ber-atribut Secure ditolak browser → login mustahil (HP yang mengetik
 * domain tanpa https). Redirect 301 ke https menutup celah ini meski
 * "Always Use HTTPS" di Cloudflare belum/lupa diaktifkan.
 *
 * /api/* SENGAJA dikecualikan: n8n memanggil http://app/api/* lewat
 * network internal docker, healthcheck memakai http://localhost/api/health,
 * dan ESP32 dev lokal boleh http — semuanya tidak boleh dipaksa redirect.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->secure()
            && app()->environment('production')
            && !$request->is('api/*')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan HTTP — dipasang GLOBAL (bukan per-route).
 *
 * Catatan CSP: hanya frame-ancestors yang dipasang. default-src 'self'
 * TIDAK dipakai karena UI memakai inline style/script (Alpine) dan layout
 * viewer memuat aset CDN — CSP ketat akan mematikan seluruh halaman.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers  = $response->headers;

        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        // Aman dikirim selalu — browser hanya menghormatinya di konteks HTTPS
        $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Respons personal (user login) jangan pernah di-cache CDN/browser.
        // Halaman auth publik (login/register/quick-login) juga no-store agar
        // browser HP tidak menyimpan form dengan token CSRF basi (bfcache) —
        // penyebab "419 Page Expired" saat tab lama dibuka kembali.
        // Aset statis ber-hash (/build/**) dilayani Apache, tidak lewat sini.
        if ($request->user() !== null || $request->is('login', 'register', 'q/*')) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        }

        return $response;
    }
}

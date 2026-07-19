<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proteksi endpoint AI webhook (n8n).
 *
 * n8n kirim API key di header:
 *   X-AI-Webhook-Key: <value dari AI_WEBHOOK_KEY di .env>
 *
 * Jika AI_WEBHOOK_KEY kosong (belum di-set), middleware skip
 * agar development lokal tetap berjalan tanpa konfigurasi tambahan.
 */
class VerifyAiWebhookKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.webhooks.ai_key');

        // Jika key belum dikonfigurasi, lewati (local/dev mode)
        if (empty($configuredKey)) {
            return $next($request);
        }

        $providedKey = $request->header('X-AI-Webhook-Key')
            ?? $request->query('api_key');

        if (!$providedKey || !hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Valid X-AI-Webhook-Key header required.',
            ], 401);
        }

        return $next($request);
    }
}

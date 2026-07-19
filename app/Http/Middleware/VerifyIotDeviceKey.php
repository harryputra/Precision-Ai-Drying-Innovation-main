<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proteksi endpoint IoT device (ESP32).
 *
 * ESP32 kirim device key di header:
 *   X-Device-Key: <value dari IOT_DEVICE_KEY di .env>
 *
 * Atau via query string: ?device_key=xxx
 * (query string fallback untuk ESP32 yang tidak bisa set custom header)
 *
 * Jika IOT_DEVICE_KEY kosong, middleware skip untuk dev lokal.
 */
class VerifyIotDeviceKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.webhooks.iot_key');

        // Jika key belum dikonfigurasi, lewati (local/dev mode)
        if (empty($configuredKey)) {
            return $next($request);
        }

        $providedKey = $request->header('X-Device-Key')
            ?? $request->header('X-IOT-Key')
            ?? $request->query('device_key');

        if (!$providedKey || !hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Valid X-Device-Key header required.',
            ], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Admin: kelola API keys (Gemini, Groq, OpenWeather) via dashboard.
 * Keys disimpan terenkripsi di tabel `settings`, bukan edit .env.
 */
class ApiSettingsController extends Controller
{
    /**
     * Halaman pengaturan API keys.
     */
    public function index(): View
    {
        return view('admin.api-settings', [
            'geminiKey'       => Setting::getMasked('gemini_api_key'),
            'geminiModel'     => Setting::getOrConfig('gemini_model', 'services.gemini.model', 'gemini-2.0-flash'),
            'groqKey'         => Setting::getMasked('groq_api_key'),
            'openweatherKey'  => Setting::getMasked('openweather_api_key'),
            'hasGeminiKey'    => !empty(Setting::getOrConfig('gemini_api_key', 'services.gemini.api_key')),
            'hasGroqKey'      => !empty(Setting::getOrConfig('groq_api_key', 'services.groq.api_key')),
            'hasOpenweatherKey' => !empty(Setting::getOrConfig('openweather_api_key', 'services.openweather.api_key')),
        ]);
    }

    /**
     * Simpan API keys ke database.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gemini_api_key'      => 'nullable|string|max:500',
            'gemini_model'        => 'nullable|string|max:100',
            'groq_api_key'        => 'nullable|string|max:500',
            'openweather_api_key' => 'nullable|string|max:500',
        ]);

        $userId  = $request->user()->id;
        $updated = [];

        // Simpan hanya field yang benar-benar diisi (bukan placeholder mask)
        if (!empty($data['gemini_api_key']) && !str_contains($data['gemini_api_key'], '•')) {
            Setting::setValue('gemini_api_key', $data['gemini_api_key'], encrypted: true, userId: $userId);
            $updated[] = 'Gemini API Key';
        }

        if (isset($data['gemini_model']) && $data['gemini_model'] !== '') {
            Setting::setValue('gemini_model', $data['gemini_model'], encrypted: false, userId: $userId);
            $updated[] = 'Gemini Model';
        }

        if (!empty($data['groq_api_key']) && !str_contains($data['groq_api_key'], '•')) {
            Setting::setValue('groq_api_key', $data['groq_api_key'], encrypted: true, userId: $userId);
            $updated[] = 'Groq API Key';
        }

        if (!empty($data['openweather_api_key']) && !str_contains($data['openweather_api_key'], '•')) {
            Setting::setValue('openweather_api_key', $data['openweather_api_key'], encrypted: true, userId: $userId);
            $updated[] = 'OpenWeather API Key';
        }

        if (empty($updated)) {
            return back()->with('error', 'Tidak ada perubahan yang disimpan. Pastikan Anda mengisi field yang ingin diperbarui.');
        }

        SystemLog::write('info', 'api_settings_update', 'API settings diperbarui: ' . implode(', ', $updated), [
            'fields' => $updated,
            'ip'     => $request->ip(),
        ], userId: $userId, channel: 'admin');

        return back()->with('success', 'API Settings berhasil disimpan: ' . implode(', ', $updated));
    }

    /**
     * Test koneksi Gemini API.
     */
    public function testGemini(Request $request): JsonResponse
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
            'model'   => 'nullable|string|max:100',
        ]);

        $apiKey = $request->input('api_key');
        $model  = $request->input('model', 'gemini-2.0-flash');

        // Jika key tidak dikirim atau masked, pakai dari DB/env
        if (empty($apiKey) || str_contains($apiKey, '•')) {
            $apiKey = Setting::getOrConfig('gemini_api_key', 'services.gemini.api_key');
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key belum diisi.',
            ]);
        }

        try {
            $startTime = microtime(true);

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Jawab hanya: "OK". Jangan jawab yang lain.']]],
                ],
                'generationConfig' => [
                    'temperature'     => 0,
                    'maxOutputTokens' => 10,
                ],
            ]);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text', '—');
                return response()->json([
                    'success'       => true,
                    'message'       => "Koneksi berhasil! Model: {$model}",
                    'response_time' => "{$elapsed}ms",
                    'reply'         => trim($reply),
                ]);
            }

            $errorBody = $response->json('error.message', $response->body());
            return response()->json([
                'success' => false,
                'message' => "Gagal (HTTP {$response->status()}): {$errorBody}",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test koneksi Groq API.
     */
    public function testGroq(Request $request): JsonResponse
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
        ]);

        $apiKey = $request->input('api_key');

        if (empty($apiKey) || str_contains($apiKey, '•')) {
            $apiKey = Setting::getOrConfig('groq_api_key', 'services.groq.api_key');
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key belum diisi.',
            ]);
        }

        try {
            $startTime = microtime(true);

            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.1-8b-instant',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Jawab hanya: "OK". Jangan jawab yang lain.'],
                    ],
                    'temperature' => 0,
                    'max_tokens'  => 10,
                ]);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content', '—');
                $model = $response->json('model', 'llama-3.1-8b-instant');
                return response()->json([
                    'success'       => true,
                    'message'       => "Koneksi berhasil! Model: {$model}",
                    'response_time' => "{$elapsed}ms",
                    'reply'         => trim($reply),
                ]);
            }

            $errorBody = $response->json('error.message', $response->body());
            return response()->json([
                'success' => false,
                'message' => "Gagal (HTTP {$response->status()}): {$errorBody}",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test koneksi OpenWeather API.
     */
    public function testOpenWeather(Request $request): JsonResponse
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
        ]);

        $apiKey = $request->input('api_key');

        if (empty($apiKey) || str_contains($apiKey, '•')) {
            $apiKey = Setting::getOrConfig('openweather_api_key', 'services.openweather.api_key');
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key belum diisi.',
            ]);
        }

        try {
            $startTime = microtime(true);

            $lat = config('services.openweather.lat', -7.0271);
            $lon = config('services.openweather.lon', 107.5892);

            $response = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat'   => $lat,
                'lon'   => $lon,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang'  => 'id',
            ]);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data   = $response->json();
                $city   = $data['name'] ?? '—';
                $temp   = $data['main']['temp'] ?? '—';
                $desc   = $data['weather'][0]['description'] ?? '—';
                return response()->json([
                    'success'       => true,
                    'message'       => "Koneksi berhasil! Lokasi: {$city}",
                    'response_time' => "{$elapsed}ms",
                    'reply'         => "{$temp}°C, {$desc}",
                ]);
            }

            $errorBody = $response->json('message', $response->body());
            return response()->json([
                'success' => false,
                'message' => "Gagal (HTTP {$response->status()}): {$errorBody}",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}

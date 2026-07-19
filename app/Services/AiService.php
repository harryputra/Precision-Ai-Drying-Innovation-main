<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\KnowledgeBase;
use App\Models\SensorReading;
use App\Models\Setting;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private GroqService $groq)
    {
        $this->apiKey = Setting::getOrConfig('gemini_api_key', 'services.gemini.api_key');
        $this->model  = Setting::getOrConfig('gemini_model', 'services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Kirim pesan ke Gemini dan kembalikan balasan teks.
     * Fallback ke Groq jika Gemini 429 (rate limit).
     */
    public function chat(string $userMessage, string $sessionId, ?int $deviceId = null, ?int $batchId = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($deviceId, $batchId);

        $history = AiConversation::session($sessionId)
            ->orderBy('created_at')
            ->limit(10)
            ->get(['role', 'message']);

        $contents  = [];
        $messages  = []; // format OpenAI untuk Groq fallback

        foreach ($history as $msg) {
            $role      = $msg->role === 'assistant' ? 'model' : 'user';
            $groqRole  = $msg->role === 'assistant' ? 'assistant' : 'user';

            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $msg->message]],
            ];
            $messages[] = ['role' => $groqRole, 'content' => $msg->message];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // Coba Gemini dulu
        try {
            return $this->callGemini($systemPrompt, $contents);
        } catch (\RuntimeException $e) {
            // Fallback ke Groq jika 429 atau Gemini gagal
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), '503')) {
                Log::info('Gemini rate limit, fallback ke Groq');

                if ($this->groq->isConfigured()) {
                    return $this->groq->chat($systemPrompt, $messages);
                }
            }
            throw $e;
        }
    }

    /**
     * Panggil Gemini API langsung.
     */
    private function callGemini(string $systemPrompt, array $contents): array
    {
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 1024,
            ],
        ];

        $url      = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";
        $response = Http::timeout(30)->post($url, $payload);

        // Retry sekali jika 429
        if ($response->status() === 429) {
            sleep(3);
            $response = Http::timeout(30)->post($url, $payload);
        }

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Gemini API error: ' . $response->status());
        }

        $data       = $response->json();
        $replyText  = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, tidak dapat memproses permintaan.';
        $tokensUsed = ($data['usageMetadata']['promptTokenCount'] ?? 0)
                    + ($data['usageMetadata']['candidatesTokenCount'] ?? 0);

        return [
            'message'     => $replyText,
            'tokens_used' => $tokensUsed,
            'model'       => $this->model,
        ];
    }

    /**
     * Closed-loop: AI analisis semua data dan buat keputusan aktuator.
     * Dipanggil oleh n8n tiap interval.
     */
    public function analyzeAndDecide(array $context): array
    {
        $prompt       = $this->buildDecisionPrompt($context);
        $systemPrompt = $this->buildDecisionSystemPrompt();

        // Coba Gemini dulu
        try {
            return $this->callGeminiDecision($systemPrompt, $prompt);
        } catch (\RuntimeException $e) {
            // Fallback ke Groq jika 429 / 503
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), '503')) {
                Log::info('Gemini rate limit on decision, fallback ke Groq');

                if ($this->groq->isConfigured()) {
                    return $this->callGroqDecision($systemPrompt, $prompt);
                }
            }
            throw $e;
        }
    }

    /**
     * Panggil Gemini untuk decision engine.
     */
    private function callGeminiDecision(string $systemPrompt, string $prompt): array
    {
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'      => 0.3,
                'maxOutputTokens'  => 1024,
                'responseMimeType' => 'application/json',
            ],
        ];

        $url      = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";
        $response = Http::timeout(30)->post($url, $payload);

        // Retry sekali jika 429
        if ($response->status() === 429) {
            Log::info('Gemini 429, retry setelah 5 detik...');
            sleep(5);
            $response = Http::timeout(30)->post($url, $payload);
        }

        if ($response->failed()) {
            Log::error('Gemini analyzeAndDecide error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Gemini API error: ' . $response->status());
        }

        $data       = $response->json();
        $rawText    = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $tokensUsed = ($data['usageMetadata']['promptTokenCount'] ?? 0)
                    + ($data['usageMetadata']['candidatesTokenCount'] ?? 0);

        return $this->parseDecisionJson($rawText, $this->model, $tokensUsed);
    }

    /**
     * Fallback ke Groq untuk decision engine.
     */
    private function callGroqDecision(string $systemPrompt, string $prompt): array
    {
        $result = $this->groq->chat($systemPrompt, [
            ['role' => 'user', 'content' => $prompt],
        ]);

        return $this->parseDecisionJson($result['message'], $result['model'], $result['tokens_used']);
    }

    /**
     * Parse dan validasi JSON keputusan dari AI.
     */
    private function parseDecisionJson(string $rawText, string $model, int $tokensUsed): array
    {
        $original = $rawText;

        // Hapus markdown code block jika ada
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $rawText, $matches)) {
            $rawText = $matches[1];
        }

        // Coba ekstrak objek JSON pertama jika ada teks lain
        if (preg_match('/\{[\s\S]*\}/m', $rawText, $matches)) {
            $rawText = $matches[0];
        }

        $decision = json_decode(trim($rawText), true);

        if (!$decision || !isset($decision['decision_type'])) {
            Log::warning('AI returned invalid decision JSON', [
                'model'    => $model,
                'raw'      => $original,
                'trimmed'  => $rawText,
                'json_err' => json_last_error_msg(),
            ]);
            throw new \RuntimeException('Invalid decision format from AI');
        }

        // Fallback decision_type ke 'other' jika nilai tidak dikenal
        $validTypes = [
            'start_heater','stop_heater','start_fan','stop_fan',
            'adjust_temperature','adjust_airflow','pause_drying','resume_drying',
            'alert_operator','open_roof','close_roof','other',
        ];
        if (!in_array($decision['decision_type'], $validTypes)) {
            $decision['decision_type'] = 'other';
        }

        return [
            'decision'    => $decision,
            'tokens_used' => $tokensUsed,
            'model'       => $model,
            'raw'         => $rawText,
        ];
    }

    /**
     * Chat khusus viewer (petani) — bahasa sederhana, tanpa istilah teknis.
     * Konteks dikunci ke device aktif petani.
     */
    public function chatViewer(string $userMessage, string $sessionId, ?int $deviceId = null): array
    {
        $systemPrompt = $this->buildViewerSystemPrompt($deviceId);

        $history = AiConversation::session($sessionId)
            ->orderBy('created_at')
            ->limit(10)
            ->get(['role', 'message']);

        $contents = [];
        $messages = [];

        foreach ($history as $msg) {
            $role     = $msg->role === 'assistant' ? 'model' : 'user';
            $groqRole = $msg->role === 'assistant' ? 'assistant' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg->message]]];
            $messages[] = ['role' => $groqRole, 'content' => $msg->message];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            return $this->callGemini($systemPrompt, $contents);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), '503')) {
                if ($this->groq->isConfigured()) {
                    return $this->groq->chat($systemPrompt, $messages);
                }
            }
            throw $e;
        }
    }

    /**
     * System prompt untuk chatbot viewer (petani).
     * Bahasa warung, tidak ada istilah teknis, fokus ke gabah petani itu saja.
     */
    private function buildViewerSystemPrompt(?int $deviceId): string
    {
        $context = [];

        $sensorQuery = SensorReading::valid()->latest('recorded_at');
        if ($deviceId) $sensorQuery->forDevice($deviceId);
        $sensor = $sensorQuery->first();

        $batchQuery = DryingBatch::active()->latest();
        if ($deviceId) $batchQuery->where('device_id', $deviceId);
        $batch = $batchQuery->first();

        $weather = \App\Models\WeatherData::actual()->latest('recorded_at')->first();

        if ($sensor) {
            $context[] = "Kondisi mesin pengering sekarang:
- Suhu dalam mesin: {$sensor->temperature_inside}°C
- Kelembaban: {$sensor->humidity_inside}%
- Kadar air gabah: {$sensor->grain_moisture}%
- Cahaya matahari: {$sensor->solar_irradiance} W/m²";
        }

        if ($batch) {
            $progress = 0;
            if ($batch->initial_moisture > $batch->target_moisture) {
                $reduced  = $batch->initial_moisture - ($batch->current_moisture ?? $batch->initial_moisture);
                $total    = $batch->initial_moisture - $batch->target_moisture;
                $progress = min(100, max(0, round($reduced / $total * 100)));
            }
            $petaniInfo = $batch->petani_name ? "milik {$batch->petani_name}" : '';
            $context[] = "Gabah yang sedang dikeringkan {$petaniInfo}:
- Jenis: {$batch->rice_variety}
- Kadar air sekarang: " . ($batch->current_moisture ?? $batch->initial_moisture) . "% (target: {$batch->target_moisture}%)
- Progress pengeringan: {$progress}%
- Status: {$batch->status}";
        }

        if ($weather) {
            $context[] = "Cuaca sekarang: {$weather->weather_condition}, hujan: {$weather->rainfall} mm";
        }

        $contextBlock = implode("\n\n", $context);

        return <<<PROMPT
Kamu adalah asisten sistem pengering gabah PADI PRECISION yang membantu petani.

CARA MENJAWAB:
- Gunakan bahasa yang mudah dipahami petani biasa, seperti ngobrol di warung
- JANGAN gunakan istilah teknis seperti: humidity, PID, setpoint, relay, API, sensor reading
- Ganti dengan kata sederhana: kelembaban → "kondisi lembab", temperature → "suhu/panas"
- Jawab singkat dan langsung ke poin, maksimal 3–4 kalimat
- Jika gabah hampir kering, beritahu dengan antusias
- Jika ada masalah, tenangkan petani dan jelaskan apa yang sistem lakukan otomatis

KONTEKS KONDISI SEKARANG:
{$contextBlock}

TOPIK YANG BISA DIJAWAB:
- Kapan gabah selesai / sudah kering belum
- Apakah cuaca aman untuk pengeringan hari ini
- Kenapa mesin berhenti / dijeda
- Apakah gabah aman ditinggal
- Berapa lama lagi kira-kira selesai
- Jika petani minta video atau tutorial: JANGAN membuat atau mengarang URL YouTube. Minta petani paste URL YouTube sendiri — sistem akan otomatis tampilkan videonya di chat.

Jika ditanya hal di luar topik gabah/pengeringan, arahkan kembali ke topik gabah.
PROMPT;
    }

    /**
     * System prompt untuk chat operator.
     */
    private function buildSystemPrompt(?int $deviceId, ?int $batchId): string
    {
        $context = [];

        if ($deviceId) {
            $device = Device::find($deviceId);
            if ($device) {
                $context[] = "DEVICE: {$device->device_name} (SN: {$device->serial_number}) - Status: {$device->status} - Lokasi: {$device->location}";
            }
        }

        $sensorQuery = SensorReading::valid()->latest('recorded_at');
        if ($deviceId) $sensorQuery->forDevice($deviceId);
        $sensor = $sensorQuery->first();

        if ($sensor) {
            $context[] = "SENSOR TERBARU ({$sensor->recorded_at?->format('d M H:i')}):
- Suhu dalam: {$sensor->temperature_inside}°C
- Suhu luar: {$sensor->temperature_outside}°C
- Kelembaban dalam: {$sensor->humidity_inside}%
- Kelembaban luar: {$sensor->humidity_outside}%
- Iradiasi surya: {$sensor->solar_irradiance} W/m²
- Kadar air gabah: {$sensor->grain_moisture}%
- Kecepatan angin: {$sensor->wind_speed} m/s";
        }

        $weather = WeatherData::actual()->latest('recorded_at')->first();
        if ($weather) {
            $context[] = "CUACA ({$weather->recorded_at?->format('d M H:i')}):
- Suhu: {$weather->temperature}°C, Kelembaban: {$weather->humidity}%
- Solar: {$weather->solar_irradiance} W/m², Angin: {$weather->wind_speed} m/s
- Kondisi: {$weather->weather_condition}, Hujan: {$weather->rainfall} mm";
        }

        $batchQuery = DryingBatch::active()->with('device')->latest();
        if ($deviceId) $batchQuery->where('device_id', $deviceId);
        $batch = $batchId ? DryingBatch::find($batchId) : $batchQuery->first();

        if ($batch) {
            $context[] = "BATCH AKTIF: {$batch->batch_code}
- Varietas: {$batch->rice_variety} ({$batch->rice_type})
- Berat: {$batch->initial_weight}kg → {$batch->current_weight}kg
- Kadar air: {$batch->initial_moisture}% → {$batch->current_moisture}% (target: {$batch->target_moisture}%)
- Status: {$batch->status}, Mulai: {$batch->start_time?->format('d M H:i')}";
        }

        $knowledge = KnowledgeBase::forAi()
            ->where('category', '!=', 'video_tutorial')
            ->orderByDesc('priority_weight')
            ->limit(5)
            ->get(['title', 'content', 'category']);

        if ($knowledge->isNotEmpty()) {
            $kbText    = $knowledge->map(fn($k) => "[{$k->category}] {$k->title}:\n{$k->content}")->implode("\n\n");
            $context[] = "KNOWLEDGE BASE:\n{$kbText}";
        }

        $videos = KnowledgeBase::forAi()
            ->where('category', 'video_tutorial')
            ->orderByDesc('priority_weight')
            ->get(['title', 'content']);

        if ($videos->isNotEmpty()) {
            $videoText = $videos->map(fn($v) => "- {$v->title}:\n  " . trim($v->content))->implode("\n\n");
            $context[] = "VIDEO TUTORIAL TERSEDIA:\n{$videoText}";
        }

        $contextBlock = implode("\n\n", $context);

        return <<<PROMPT
Anda adalah Padi PRECISION Assistant, asisten AI ahli untuk sistem pengeringan padi bertenaga surya.

PERAN:
- Analisis data sensor real-time dan berikan rekomendasi pengeringan
- Bantu operator dalam pengambilan keputusan (buka/tutup atap, aktifkan kipas, dll)
- Jelaskan kondisi cuaca dan dampaknya terhadap pengeringan
- Berikan panduan sesuai varietas padi
- Troubleshoot masalah perangkat

KONTEKS REAL-TIME:
{$contextBlock}

ATURAN RESPONS:
- Jawab dalam Bahasa Indonesia yang jelas dan mudah dipahami
- Berikan rekomendasi konkret dan actionable
- Sertakan angka/data spesifik jika relevan
- Jika tidak ada data sensor, sampaikan dengan jelas
- Format dengan baik menggunakan poin-poin jika perlu
- Jika user meminta video tutorial: HANYA sertakan URL YouTube jika URL tersebut ada di KNOWLEDGE BASE di atas. JANGAN pernah membuat atau mengarang URL YouTube. Jika tidak ada di knowledge base, minta user paste URL YouTube-nya sendiri dan sistem akan menampilkan video embed secara otomatis.
PROMPT;
    }

    /**
     * System prompt untuk mode decision-making (closed-loop).
     *
     * Arsitektur LLM+PID:
     * - ESP32 punya PID controller yang mengendalikan relay heater
     * - AI TIDAK mengontrol relay heater secara langsung (heater ON/OFF bukan tugas AI)
     * - AI bertugas menentukan SETPOINT SUHU OPTIMAL → PID yang eksekusi
     * - Analogi: AI = supervisor yang tentukan target, PID = operator yang capai target
     */
    private function buildDecisionSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah AI Supervisory Controller untuk sistem pengeringan padi surya (Padi PRECISION).

ARSITEKTUR KONTROL:
- ESP32 menjalankan PID controller yang mengatur heater secara real-time (tiap 500ms)
- Tugas Anda: tentukan SETPOINT SUHU OPTIMAL yang akan dikejar PID
- Anda TIDAK mengontrol relay heater langsung — PID yang mengurus naik/turun heater
- Analogi: Anda adalah ATC yang kasih instruksi ketinggian, PID adalah autopilot yang capai ketinggian itu

TUGAS: Analisis data sensor, cuaca, forecast, dan kondisi batch gabah.
Tentukan satu setpoint suhu optimal dan konfigurasi aktuator.

OUTPUT WAJIB berupa JSON valid dengan struktur PERSIS seperti ini, tidak ada teks lain di luar JSON:
{
  "decision_type": "adjust_temperature",
  "reasoning": "penjelasan singkat max 200 karakter",
  "confidence_score": 0.85,
  "output_action": {
    "target_temperature": 48,
    "fan": false,
    "fan_speed": 0,
    "duration_hours": 2,
    "mode": "auto"
  },
  "risk_level": "low",
  "alerts": []
}

FIELD target_temperature (WAJIB ada, tidak boleh null):
- Range valid: 35–55°C
- 35–38°C : setpoint rendah, PID matikan heater (gabah sudah kering / darurat)
- 40–45°C : pengeringan ringan (cuaca mendung, gabah hampir kering)
- 45–50°C : pengeringan normal (kondisi standar)
- 50–55°C : pengeringan intensif (kadar air tinggi, cuaca baik, butuh cepat kering)
- Selalu sertakan target_temperature meski decision_type adalah pause_drying (kirim 35)

FIELD mode:
- "auto" : fan dikontrol threshold ESP32 (suhu >= 38°C fan ON, < 35°C fan OFF)
- "forced_on" : paksa fan ON (udara lembab tinggi, butuh sirkulasi ekstra)
- "forced_off" : paksa fan OFF (jarang dipakai)

NILAI decision_type yang DIIZINKAN (pilih salah satu persis):
start_heater, stop_heater, start_fan, stop_fan, adjust_temperature, adjust_airflow,
pause_drying, resume_drying, alert_operator, open_roof, close_roof, other

PANDUAN SETPOINT BERDASARKAN KONDISI:
- Kadar air gabah > 25%  : target_temperature 50–55°C (pengeringan intensif)
- Kadar air gabah 18–25% : target_temperature 47–50°C (pengeringan normal)
- Kadar air gabah 14–18% : target_temperature 43–47°C (pengeringan akhir, hati-hati)
- Kadar air gabah < 14%  : target_temperature 35, decision_type stop_heater (sudah kering)
- Forecast hujan > 70% dalam 3 jam : decision_type pause_drying, target_temperature 35
- Suhu dalam > 57°C      : decision_type stop_heater, target_temperature 35 (safety)
- RH dalam > 80%         : fan forced_on, kurangi setpoint 2–3°C dari rencana

PANDUAN ENERGI HIBRIDA (surya + listrik):
- Iradiasi surya > 600 W/m² : energi surya dominan, heater bisa setpoint lebih tinggi (efisien)
- Iradiasi surya 200–600 W/m² : surya + listrik, setpoint normal
- Iradiasi surya < 200 W/m² : listrik dominan (mendung/malam), pertimbangkan hemat energi
- Jika surya tinggi + forecast cerah: manfaatkan panas surya, kurangi setpoint 2–3°C

PRIORITAS: keselamatan gabah > kecepatan pengeringan > efisiensi energi

PENTING: Kembalikan HANYA JSON. Tidak ada kalimat penjelasan, tidak ada markdown, tidak ada ```json.
PROMPT;
    }

    /**
     * Bangun prompt konteks untuk keputusan closed-loop.
     */
    private function buildDecisionPrompt(array $context): string
    {
        $sensor   = $context['sensor'] ?? null;
        $weather  = $context['weather_current'] ?? null;
        $forecast = $context['weather_forecast'] ?? null;
        $batch    = $context['batch'] ?? null;
        $summary  = $context['ai_summary'] ?? null;

        $parts = [];

        if ($sensor) {
            $pidInfo = '';
            if (isset($sensor['pid_setpoint'])) {
                $pidInfo = "\n- PID setpoint aktif: {$sensor['pid_setpoint']}°C";
            }
            $solarInfo = '';
            if (!empty($sensor['solar_irradiance'])) {
                $solarLevel = $sensor['solar_irradiance'] > 600 ? 'tinggi (surya dominan)'
                    : ($sensor['solar_irradiance'] > 200 ? 'sedang (surya+listrik)'
                    : 'rendah (listrik dominan)');
                $solarInfo = "\n- Iradiasi surya: {$sensor['solar_irradiance']} W/m² — {$solarLevel}";
            }
            $parts[] = "SENSOR AKTUAL:
- Suhu dalam: {$sensor['temperature_inside']}°C{$pidInfo}
- Suhu luar: {$sensor['temperature_outside']}°C
- Kelembaban dalam: {$sensor['humidity_inside']}%
- Kelembaban luar: {$sensor['humidity_outside']}%{$solarInfo}
- Kadar air gabah: {$sensor['grain_moisture']}%
- Kecepatan angin: {$sensor['wind_speed']} m/s";
        }

        if ($weather) {
            $parts[] = "CUACA AKTUAL (OpenWeather Margahurip, Banjaran):
- Suhu: {$weather['temperature']}°C
- Kelembaban: {$weather['humidity']}%
- Angin: {$weather['wind_speed']} m/s
- Kondisi: {$weather['description']}
- Hujan 1 jam: {$weather['rainfall_1h']} mm
- Awan: {$weather['clouds']}%";
        }

        if ($forecast && ($forecast['available'] ?? false)) {
            $parts[] = "FORECAST 48 JAM:
- Risiko hujan 6 jam: {$forecast['rain_risk_6h']} ({$forecast['max_pop_6h']})
- Risiko hujan 24 jam: {$forecast['rain_risk_24h']} ({$forecast['max_pop_24h']})
- Jendela hujan pertama: " . ($forecast['next_rain_window'] ?? 'tidak ada') . "
- Kondisi 6 jam ke depan: " . implode(', ', $forecast['next_6h_conditions'] ?? []);
        }

        if ($batch) {
            $parts[] = "BATCH AKTIF:
- Kode: {$batch['batch_code']}
- Varietas: {$batch['rice_variety']} ({$batch['rice_type']})
- Kadar air: {$batch['initial_moisture']}% → {$batch['current_moisture']}% (target: {$batch['target_moisture']}%)
- Berat: {$batch['initial_weight']}kg → {$batch['current_weight']}kg";
        }

        if ($summary && !empty($summary['alerts'])) {
            $parts[] = "ALERT AKTIF:\n" . implode("\n", $summary['alerts']);
        }

        $contextBlock = implode("\n\n", $parts);

        return <<<PROMPT
Tentukan setpoint suhu optimal untuk PID controller heater berdasarkan data berikut:

{$contextBlock}

TUGAS: Analisis kondisi gabah, cuaca aktual, dan forecast. Tentukan target_temperature optimal (35–55°C) yang akan dikejar PID.
Pertimbangkan: kadar air saat ini vs target, forecast hujan, efisiensi energi, dan keselamatan gabah.
Output HANYA JSON sesuai format yang ditentukan. Field target_temperature wajib ada.
PROMPT;
    }
}

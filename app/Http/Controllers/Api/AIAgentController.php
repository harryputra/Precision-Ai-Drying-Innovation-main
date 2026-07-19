<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\KnowledgeBase;
use App\Models\SensorReading;
use App\Models\WeatherData;
use App\Services\NotificationService;
use App\Services\OpenWeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AIAgentController extends Controller
{
    public function __construct(
        private OpenWeatherService $weather,
        private NotificationService $notif
    ) {}

    /**
     * Endpoint utama untuk n8n AI Agent.
     * Snapshot kondisi real-time + knowledge base + OpenWeather forecast.
     */
    public function context(Request $request): JsonResponse
    {
        $deviceId = $request->query('device_id');

        // Sensor reading terbaru
        $sensorQuery = SensorReading::valid()->latest('recorded_at');
        if ($deviceId) $sensorQuery->forDevice($deviceId);
        $sensor = $sensorQuery->first();

        // Data cuaca DB (aktual dari sensor luar)
        $weatherQuery = WeatherData::actual()->latest('recorded_at');
        if ($deviceId) $weatherQuery->where('device_id', $deviceId);
        $weatherLocal = $weatherQuery->first();

        // OpenWeather: cuaca aktual + forecast 48 jam
        $weatherCurrent  = $this->weather->current();
        $weatherForecast = $this->weather->forecastSummaryForAi();

        // Batch aktif
        $batchQuery = DryingBatch::with('device')->active()->latest();
        if ($deviceId) $batchQuery->where('device_id', $deviceId);
        $batch = $batchQuery->first();

        // Knowledge base relevan
        $knowledge = KnowledgeBase::forAi()
            ->orderByDesc('priority_weight')
            ->get(['id', 'category', 'title', 'content', 'tags'])
            ->groupBy('category');

        // Keputusan AI pending (belum dieksekusi)
        $pendingQuery = AiDecision::pending()->latest('decided_at');
        if ($deviceId) $pendingQuery->forDevice($deviceId);
        $pendingDecisions = $pendingQuery->limit(5)->get();

        // Info device
        $device = $deviceId
            ? Device::find($deviceId)
            : Device::online()->latest('last_seen')->first();

        // Ringkasan status untuk AI
        $aiSummary = $this->buildAiSummary($sensor, $weatherCurrent, $weatherForecast, $batch);

        return response()->json([
            'status'  => true,
            'message' => 'AI Context',
            'data'    => [
                'device'            => $device,
                'sensor'            => $sensor,
                'weather_local'     => $weatherLocal,
                'weather_current'   => $weatherCurrent,
                'weather_forecast'  => $weatherForecast,
                'batch'             => $batch,
                'knowledge'         => $knowledge,
                'pending_decisions' => $pendingDecisions,
                'ai_summary'        => $aiSummary,
                'timestamp'         => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Ringkasan kondisi — langsung masuk ke prompt AI.
     */
    private function buildAiSummary($sensor, $weatherCurrent, $weatherForecast, $batch): array
    {
        $alerts = [];

        if ($sensor) {
            if ($sensor->humidity_inside > 70)
                $alerts[] = "ALERT: Kelembaban dalam tinggi ({$sensor->humidity_inside}%) — risiko bertunas";
            if ($sensor->temperature_inside < 35)
                $alerts[] = "ALERT: Suhu dalam rendah ({$sensor->temperature_inside}°C) — pengeringan tidak optimal";
            if ($sensor->temperature_inside > 60)
                $alerts[] = "ALERT: Suhu dalam terlalu tinggi ({$sensor->temperature_inside}°C) — risiko rusak gabah";
            if ($sensor->grain_moisture && $sensor->grain_moisture > 18)
                $alerts[] = "INFO: Kadar air gabah masih {$sensor->grain_moisture}% — perlu pengeringan lebih lama";
        }

        if ($weatherForecast['available'] ?? false) {
            if ($weatherForecast['rain_risk_6h'] === 'high')
                $alerts[] = "ALERT: Hujan diprediksi dalam 6 jam (prob: {$weatherForecast['max_pop_6h']}) — pertimbangkan tutup ventilasi";
            elseif ($weatherForecast['rain_risk_6h'] === 'medium')
                $alerts[] = "WARNING: Kemungkinan hujan 6 jam ke depan (prob: {$weatherForecast['max_pop_6h']})";
        }

        $batchStatus = 'Tidak ada batch aktif';
        if ($batch) {
            $progress = $batch->target_moisture > 0
                ? round((($batch->initial_moisture - $batch->current_moisture) / ($batch->initial_moisture - $batch->target_moisture)) * 100)
                : 0;
            $batchStatus = "Batch {$batch->batch_code}: {$batch->rice_variety}, kadar air {$batch->current_moisture}% → target {$batch->target_moisture}% (progress {$progress}%)";
        }

        return [
            'alerts'           => $alerts,
            'alert_count'      => count($alerts),
            'batch_status'     => $batchStatus,
            'rain_risk_6h'     => $weatherForecast['rain_risk_6h'] ?? 'unknown',
            'rain_risk_24h'    => $weatherForecast['rain_risk_24h'] ?? 'unknown',
            'next_rain_window' => $weatherForecast['next_rain_window'] ?? null,
            'drying_optimal'   => $sensor
                ? ($sensor->temperature_inside >= 40 && $sensor->temperature_inside <= 55 && $sensor->humidity_inside <= 65)
                : null,
        ];
    }

    /**
     * n8n kirim keputusan AI.
     */
    public function decide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'        => 'required|exists:devices,id',
            'batch_id'         => 'nullable|exists:drying_batches,id',
            'decision_type'    => ['required', Rule::in([
                'open_roof', 'close_roof', 'start_fan', 'stop_fan',
                'start_heater', 'stop_heater', 'pause_drying', 'resume_drying',
                'alert_operator', 'adjust_temperature', 'adjust_airflow', 'other',
            ])],
            'reasoning'        => 'required|string',
            'input_data'       => 'nullable|array',
            'output_action'    => 'nullable|array',
            'confidence_score' => 'nullable|numeric|between:0,1',
            'ai_model'         => 'nullable|string',
        ]);

        $decision = AiDecision::create([
            ...$data,
            'decided_at'       => now(),
            'execution_status' => 'pending',
        ]);

        // Notifikasi targeted ke petani berdasarkan decision_type
        if (!empty($data['batch_id'])) {
            $batch = DryingBatch::find($data['batch_id']);
            if ($batch) {
                $outputAction = $data['output_action'] ?? [];
                $alerts       = $outputAction['alerts'] ?? [];
                $riskLevel    = $outputAction['risk_level'] ?? 'low';

                if ($data['decision_type'] === 'pause_drying') {
                    $reason = !empty($alerts) ? $alerts[0] : ($data['reasoning'] ?? 'Cuaca tidak mendukung');
                    $this->notif->batchPaused($batch, $reason);

                } elseif ($data['decision_type'] === 'resume_drying') {
                    $this->notif->batchResumed($batch);

                } elseif ($riskLevel === 'critical' && !empty($alerts)) {
                    $this->notif->criticalAlert($batch, $alerts[0]);
                }
            }
        }

        return response()->json([
            'status' => true,
            'data'   => $decision,
        ], 201);
    }

    /**
     * Chat dengan AI agent.
     */
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message'    => 'required|string',
            'session_id' => 'nullable|string',
            'device_id'  => 'nullable|exists:devices,id',
            'batch_id'   => 'nullable|exists:drying_batches,id',
        ]);

        $sessionId = $data['session_id'] ?? (string) Str::uuid();

        $userMsg = AiConversation::create([
            'user_id'    => $request->user()?->id,
            'device_id'  => $data['device_id'] ?? null,
            'batch_id'   => $data['batch_id'] ?? null,
            'session_id' => $sessionId,
            'role'       => 'user',
            'message'    => $data['message'],
        ]);

        $history = AiConversation::session($sessionId)
            ->orderBy('created_at')
            ->get(['role', 'message', 'created_at']);

        return response()->json([
            'status' => true,
            'data'   => [
                'session_id' => $sessionId,
                'message_id' => $userMsg->id,
                'history'    => $history,
            ],
        ]);
    }

    /**
     * Simpan balasan AI setelah n8n selesai proses.
     */
    public function chatReply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'   => 'required|string',
            'message'      => 'required|string',
            'ai_model'     => 'nullable|string',
            'tokens_used'  => 'nullable|integer',
            'context_data' => 'nullable|array',
        ]);

        $reply = AiConversation::create([
            'session_id'   => $data['session_id'],
            'role'         => 'assistant',
            'message'      => $data['message'],
            'ai_model'     => $data['ai_model'] ?? null,
            'tokens_used'  => $data['tokens_used'] ?? null,
            'context_data' => $data['context_data'] ?? null,
        ]);

        return response()->json(['status' => true, 'data' => $reply], 201);
    }
}

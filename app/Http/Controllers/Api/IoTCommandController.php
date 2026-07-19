<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\SensorReading;
use App\Models\SystemLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint khusus ESP32:
 * - GET  /api/iot/pending-command  → ESP32 polling perintah terbaru
 * - POST /api/iot/command-ack      → ESP32 konfirmasi sudah eksekusi
 */
class IoTCommandController extends Controller
{
    public function __construct(private NotificationService $notif) {}
    /**
     * ESP32 polling perintah pending.
     * ESP32 panggil ini tiap 30 detik.
     *
     * Query param: device_id (required)
     *
     * Response:
     * - 200 + data  → ada perintah, ESP32 harus eksekusi
     * - 200 + null  → tidak ada perintah, ESP32 standby
     */
    public function pendingCommand(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
        ]);

        $deviceId = $request->device_id;

        // Update last_seen device
        Device::where('id', $deviceId)->update([
            'status'    => 'online',
            'last_seen' => now(),
        ]);

        // Ambil 1 keputusan pending yang belum dikirim ke ESP32
        // confidence_score >= 0.6: skip keputusan AI yang tidak yakin
        // Keputusan ragu-ragu tidak boleh dieksekusi ke hardware fisik
        $decision = AiDecision::where('device_id', $deviceId)
            ->where('execution_status', 'pending')
            ->whereNull('command_sent_at')  // belum dikirim
            ->where('confidence_score', '>=', 0.6)  // hanya keputusan yang cukup yakin
            ->latest('decided_at')
            ->first();

        if (!$decision) {
            return response()->json([
                'status'  => true,
                'command' => null,
                'message' => 'No pending command',
            ]);
        }

        // Format perintah untuk ESP32
        $esp32Command = $this->formatEsp32Command($decision);

        // Tandai sudah dikirim
        $decision->markCommandSent($esp32Command);

        return response()->json([
            'status'  => true,
            'command' => [
                'decision_id'   => $decision->id,
                'decision_type' => $decision->decision_type,
                'actions'       => $esp32Command,
                'reasoning'     => $decision->reasoning,
                'sent_at'       => now()->toISOString(),
            ],
        ]);
    }

    /**
     * ESP32 konfirmasi sudah eksekusi perintah.
     *
     * Body: { decision_id, status: "success"|"failed", message?: "..." }
     *
     * Auto-complete batch:
     * Jika decision_type = stop_heater DAN grain_moisture <= target_moisture,
     * batch otomatis di-set completed tanpa perlu aksi manual operator.
     */
    public function commandAck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'decision_id' => 'required|exists:ai_decisions,id',
            'device_id'   => 'required|exists:devices,id',
            'status'      => 'required|in:success,failed',
            'message'     => 'nullable|string|max:500',
        ]);

        $decision = AiDecision::findOrFail($data['decision_id']);

        // Pastikan ACK dari device yang benar
        if ($decision->device_id != $data['device_id']) {
            return response()->json([
                'status'  => false,
                'message' => 'Device mismatch',
            ], 403);
        }

        $batchCompleted = false;

        if ($data['status'] === 'success') {
            $decision->markAcknowledged();

            // Auto-complete batch jika stop_heater dan kadar air sudah <= target
            if (in_array($decision->decision_type, ['stop_heater', 'pause_drying'])
                && $decision->batch_id
            ) {
                $batch = DryingBatch::find($decision->batch_id);

                if ($batch && $batch->isActive()) {
                    // Ambil kadar air terbaru dari sensor
                    $latestSensor = SensorReading::valid()
                        ->where('device_id', $data['device_id'])
                        ->whereNotNull('grain_moisture')
                        ->latest('recorded_at')
                        ->first();

                    $currentMoisture = $latestSensor?->grain_moisture
                        ?? $batch->current_moisture
                        ?? $batch->initial_moisture;

                    // Complete batch jika kadar air sudah mencapai target
                    if ($currentMoisture <= $batch->target_moisture) {
                        $batch->update([
                            'status'           => 'completed',
                            'end_time'         => now(),
                            'current_moisture' => $currentMoisture,
                        ]);

                        $batchCompleted = true;

                        // Notifikasi targeted ke petani pemilik batch + admin/operator
                        $batch->refresh();
                        $this->notif->batchCompleted($batch);

                        SystemLog::create([
                            'level'     => 'info',
                            'event'     => 'batch.auto_completed',
                            'message'   => "Batch [{$batch->batch_code}] selesai otomatis — kadar air {$currentMoisture}% ≤ target {$batch->target_moisture}%",
                            'device_id' => $data['device_id'],
                            'context'   => [
                                'batch_id'         => $batch->id,
                                'batch_code'       => $batch->batch_code,
                                'decision_id'      => $decision->id,
                                'current_moisture' => $currentMoisture,
                                'target_moisture'  => $batch->target_moisture,
                            ],
                        ]);
                    }
                }
            }
        } else {
            $decision->update([
                'acknowledged_at'  => now(),
                'ack_status'       => 'failed',
                'execution_status' => 'failed',
                'override_reason'  => $data['message'] ?? 'ESP32 reported failure',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'ACK received',
            'data'    => [
                'decision_id'     => $decision->id,
                'ack_status'      => $decision->ack_status,
                'acknowledged_at' => $decision->acknowledged_at,
                'batch_completed' => $batchCompleted,
            ],
        ]);
    }

    /**
     * Format keputusan AI menjadi perintah untuk ESP32.
     *
     * Arsitektur LLM+PID:
     * - ESP32 punya PID controller yang mengatur heater
     * - AI tidak kontrol relay heater langsung
     * - AI kirim target_temp sebagai SETPOINT PID
     * - PID yang urus naik/turun heater untuk capai setpoint
     *
     * Field yang dibaca ESP32:
     * - target_temp : setpoint baru untuk PID heater (°C)
     * - fan         : kontrol fan (jika mode != "auto")
     * - fan_speed   : kecepatan fan 0-100%
     * - mode        : "auto" = fan ikut threshold, selainnya = fan ikut AI
     * - duration_h  : informasi estimasi durasi (tidak mengontrol relay langsung)
     */
    private function formatEsp32Command(AiDecision $decision): array
    {
        $outputAction = $decision->output_action ?? [];
        $decisionType = $decision->decision_type;

        // target_temperature dari AI → setpoint PID di ESP32
        // Clamp di server sebelum dikirim: 35–55°C range aman
        $rawTarget  = $outputAction['target_temperature'] ?? null;
        $targetTemp = $rawTarget !== null
            ? max(35.0, min(55.0, (float) $rawTarget))
            : $this->defaultSetpointForType($decisionType);

        $command = [
            'target_temp' => $targetTemp,                       // setpoint PID heater
            'fan'         => $outputAction['fan'] ?? false,     // AI kontrol fan (jika mode != auto)
            'fan_speed'   => $outputAction['fan_speed'] ?? 0,   // kecepatan fan 0-100%
            'mode'        => $outputAction['mode'] ?? 'auto',   // auto = ESP32 kontrol fan via threshold
            'duration_h'  => $outputAction['duration_hours'] ?? 0,
        ];

        return $command;
    }

    /**
     * Setpoint default berdasarkan decision_type jika AI tidak menyertakan target_temperature.
     * Dipakai sebagai fallback — AI seharusnya selalu kirim target_temperature.
     */
    private function defaultSetpointForType(string $decisionType): float
    {
        return match ($decisionType) {
            'start_heater'       => 48.0,  // mulai pengeringan optimal
            'stop_heater'        => 35.0,  // setpoint rendah → PID tidak nyalakan heater
            'adjust_temperature' => 47.0,
            'pause_drying'       => 35.0,  // PID tidak nyalakan heater saat pause
            'resume_drying'      => 45.0,
            'open_roof'          => 42.0,
            'close_roof'         => 48.0,
            default              => 45.0,  // default aman
        };
    }
}

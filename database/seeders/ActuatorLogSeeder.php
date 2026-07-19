<?php

namespace Database\Seeders;

use App\Models\ActuatorLog;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use Illuminate\Database\Seeder;

class ActuatorLogSeeder extends Seeder
{
    public function run(): void
    {
        ActuatorLog::truncate();

        $d1 = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2 = Device::where('serial_number', 'PADI-BNJ-002')->first();
        $b1 = DryingBatch::where('batch_code', 'BNJ-2026-001')->first();
        $b2 = DryingBatch::where('batch_code', 'BNJ-2026-002')->first();
        $b3 = DryingBatch::where('batch_code', 'BNJ-2026-003')->first();

        $decHeater  = AiDecision::where('decision_type', 'start_heater')->where('device_id', $d1->id)->first();
        $decFan     = AiDecision::where('decision_type', 'start_fan')->first();
        $decAirflow = AiDecision::where('decision_type', 'adjust_airflow')->first();
        $decTemp    = AiDecision::where('decision_type', 'adjust_temperature')->where('device_id', $d1->id)->first();
        $decPause   = AiDecision::where('decision_type', 'pause_drying')->first();
        $decStop    = AiDecision::where('decision_type', 'stop_heater')->first();

        $logs = [
            // ── Batch 1 aktif — unit 1 ─────────────────────────────────────
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'ai_decision_id'   => $decHeater?->id,
                'actuator_type'    => 'heater',
                'actuator_name'    => 'Heater H1',
                'command'          => 'on',
                'set_value'        => 48.00,
                'actual_value'     => 47.80,
                'unit'             => 'celsius',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 285,
                'executed_at'      => now()->subHours(5)->subMinutes(25),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'ai_decision_id'   => $decFan?->id,
                'actuator_type'    => 'fan',
                'actuator_name'    => 'Fan Exhaust F1',
                'command'          => 'on',
                'set_value'        => 70.00,
                'actual_value'     => 71.50,
                'unit'             => 'percent',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 210,
                'executed_at'      => now()->subHours(4)->subMinutes(10),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'ai_decision_id'   => $decAirflow?->id,
                'actuator_type'    => 'fan',
                'actuator_name'    => 'Fan Exhaust F1',
                'command'          => 'adjust',
                'set_value'        => 90.00,
                'actual_value'     => 88.50,
                'unit'             => 'percent',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 195,
                'executed_at'      => now()->subHours(3)->subMinutes(40),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'ai_decision_id'   => $decTemp?->id,
                'actuator_type'    => 'heater',
                'actuator_name'    => 'Heater H1',
                'command'          => 'off',
                'set_value'        => 0.00,
                'actual_value'     => 0.00,
                'unit'             => 'celsius',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 175,
                'executed_at'      => now()->subHours(2)->subMinutes(55),
            ],
            // Manual kontrol operator
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'ai_decision_id'   => null,
                'actuator_type'    => 'fan',
                'actuator_name'    => 'Fan Exhaust F1',
                'command'          => 'adjust',
                'set_value'        => 75.00,
                'actual_value'     => 75.00,
                'unit'             => 'percent',
                'triggered_by'     => 'manual',
                'status'           => 'success',
                'response_time_ms' => 145,
                'executed_at'      => now()->subHours(1)->subMinutes(30),
            ],

            // ── Batch 2 aktif — unit 2 ─────────────────────────────────────
            [
                'device_id'        => $d2->id,
                'batch_id'         => $b2->id,
                'ai_decision_id'   => null,
                'actuator_type'    => 'heater',
                'actuator_name'    => 'Heater H2',
                'command'          => 'on',
                'set_value'        => 46.00,
                'actual_value'     => 45.60,
                'unit'             => 'celsius',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 302,
                'executed_at'      => now()->subHours(2),
            ],

            // ── Batch 3 selesai kemarin ─────────────────────────────────────
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'ai_decision_id'   => $decPause?->id,
                'actuator_type'    => 'heater',
                'actuator_name'    => 'Heater H1',
                'command'          => 'off',
                'set_value'        => 0.00,
                'actual_value'     => 0.00,
                'unit'             => 'celsius',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 188,
                'executed_at'      => now()->subDays(1)->setTime(11, 30),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'ai_decision_id'   => $decPause?->id,
                'actuator_type'    => 'fan',
                'actuator_name'    => 'Fan Exhaust F1',
                'command'          => 'off',
                'set_value'        => 0.00,
                'actual_value'     => 0.00,
                'unit'             => 'percent',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 155,
                'executed_at'      => now()->subDays(1)->setTime(11, 30)->addSeconds(2),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'ai_decision_id'   => $decStop?->id,
                'actuator_type'    => 'heater',
                'actuator_name'    => 'Heater H1',
                'command'          => 'off',
                'set_value'        => 0.00,
                'actual_value'     => 0.00,
                'unit'             => 'celsius',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 165,
                'executed_at'      => now()->subDays(1)->setTime(15, 30),
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'ai_decision_id'   => $decStop?->id,
                'actuator_type'    => 'fan',
                'actuator_name'    => 'Fan Exhaust F1',
                'command'          => 'adjust',
                'set_value'        => 30.00,
                'actual_value'     => 30.00,
                'unit'             => 'percent',
                'triggered_by'     => 'ai',
                'status'           => 'success',
                'response_time_ms' => 142,
                'executed_at'      => now()->subDays(1)->setTime(15, 30)->addSeconds(3),
            ],
        ];

        foreach ($logs as $l) {
            ActuatorLog::create($l);
        }
    }
}

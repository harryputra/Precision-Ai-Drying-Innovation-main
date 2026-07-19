<?php

namespace Database\Seeders;

use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use Illuminate\Database\Seeder;

class AiDecisionSeeder extends Seeder
{
    public function run(): void
    {
        AiDecision::truncate();

        $d1 = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2 = Device::where('serial_number', 'PADI-BNJ-002')->first();
        $b1 = DryingBatch::where('batch_code', 'BNJ-2026-001')->first();
        $b2 = DryingBatch::where('batch_code', 'BNJ-2026-002')->first();
        $b3 = DryingBatch::where('batch_code', 'BNJ-2026-003')->first();
        $b4 = DryingBatch::where('batch_code', 'BNJ-2026-004')->first();

        $decisions = [
            // ── Batch 1 aktif — keputusan berjalan ──────────────────────────

            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'decision_type'    => 'start_heater',
                'reasoning'        => 'Suhu ruang pengering 38.2°C masih di bawah rentang optimal 40–55°C untuk varietas Ciherang. Iradiasi surya 420 W/m² belum cukup memanaskan ruang. Aktifkan heater untuk mempercepat proses penguapan moisture.',
                'input_data'       => ['temperature_inside' => 38.2, 'humidity_inside' => 71.8, 'solar_irradiance' => 420, 'grain_moisture' => 24.20, 'wind_speed' => 1.4],
                'output_action'    => ['heater' => true, 'fan' => false, 'fan_speed' => 0, 'target_temperature' => 48, 'duration_hours' => 2, 'mode' => 'auto'],
                'confidence_score' => 0.912,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subHours(5)->subMinutes(25),
                'executed_at'      => now()->subHours(5)->subMinutes(25)->addSeconds(28),
                'command_sent_at'  => now()->subHours(5)->subMinutes(25)->addSeconds(5),
                'acknowledged_at'  => now()->subHours(5)->subMinutes(25)->addSeconds(28),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'decision_type'    => 'start_fan',
                'reasoning'        => 'Kelembaban dalam ruang pengering meningkat ke 69.4% akibat penguapan moisture gabah yang intens. Suhu sudah optimal di 52.1°C. Aktifkan fan 70% untuk sirkulasi udara lembab ke luar ruang pengering.',
                'input_data'       => ['temperature_inside' => 52.1, 'humidity_inside' => 69.4, 'solar_irradiance' => 780, 'grain_moisture' => 21.30, 'wind_speed' => 1.8],
                'output_action'    => ['heater' => true, 'fan' => true, 'fan_speed' => 70, 'target_temperature' => 52, 'duration_hours' => 1, 'mode' => 'auto'],
                'confidence_score' => 0.881,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subHours(4)->subMinutes(10),
                'executed_at'      => now()->subHours(4)->subMinutes(10)->addSeconds(31),
                'command_sent_at'  => now()->subHours(4)->subMinutes(10)->addSeconds(6),
                'acknowledged_at'  => now()->subHours(4)->subMinutes(10)->addSeconds(31),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'decision_type'    => 'adjust_airflow',
                'reasoning'        => 'Kelembaban dalam masih 63.2% setelah fan berjalan 30 menit. Tingkatkan kecepatan fan ke 90% untuk memaksimalkan pembuangan udara lembab. Suhu masih aman di 54.6°C.',
                'input_data'       => ['temperature_inside' => 54.6, 'humidity_inside' => 63.2, 'solar_irradiance' => 850, 'grain_moisture' => 19.80, 'wind_speed' => 2.1],
                'output_action'    => ['heater' => true, 'fan' => true, 'fan_speed' => 90, 'target_temperature' => 54, 'duration_hours' => 1, 'mode' => 'auto'],
                'confidence_score' => 0.856,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subHours(3)->subMinutes(40),
                'executed_at'      => now()->subHours(3)->subMinutes(40)->addSeconds(29),
                'command_sent_at'  => now()->subHours(3)->subMinutes(40)->addSeconds(5),
                'acknowledged_at'  => now()->subHours(3)->subMinutes(40)->addSeconds(29),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'decision_type'    => 'adjust_temperature',
                'reasoning'        => 'Suhu ruang mencapai 57.8°C mendekati batas atas 60°C. Forecast cuaca menunjukkan iradiasi surya akan terus meningkat 1 jam ke depan. Turunkan target suhu heater ke 50°C untuk mencegah overheating yang dapat merusak tekstur gabah Ciherang.',
                'input_data'       => ['temperature_inside' => 57.8, 'humidity_inside' => 51.0, 'solar_irradiance' => 890, 'grain_moisture' => 18.20, 'wind_speed' => 1.6, 'rain_risk_6h' => 'low'],
                'output_action'    => ['heater' => false, 'fan' => true, 'fan_speed' => 80, 'target_temperature' => 50, 'duration_hours' => 1, 'mode' => 'auto'],
                'confidence_score' => 0.944,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subHours(2)->subMinutes(55),
                'executed_at'      => now()->subHours(2)->subMinutes(55)->addSeconds(27),
                'command_sent_at'  => now()->subHours(2)->subMinutes(55)->addSeconds(5),
                'acknowledged_at'  => now()->subHours(2)->subMinutes(55)->addSeconds(27),
                'ack_status'       => 'acked',
            ],
            // Keputusan terbaru — pending, menunggu ESP32 polling
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b1->id,
                'decision_type'    => 'alert_operator',
                'reasoning'        => 'Kadar air gabah mencapai 17.6%, sudah turun 6.6% dari awal. Progress 64% menuju target 14%. Estimasi selesai dalam 2–3 jam. Notifikasi operator untuk persiapan tahap pendinginan dan pengujian sampel manual.',
                'input_data'       => ['temperature_inside' => 55.3, 'humidity_inside' => 49.8, 'solar_irradiance' => 760, 'grain_moisture' => 17.60, 'target_moisture' => 14.00, 'rain_risk_6h' => 'low'],
                'output_action'    => ['heater' => true, 'fan' => true, 'fan_speed' => 75, 'target_temperature' => 52, 'duration_hours' => 2, 'mode' => 'auto'],
                'confidence_score' => 0.928,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'pending',
                'decided_at'       => now()->subMinutes(8),
                'executed_at'      => null,
                'command_sent_at'  => null,
                'acknowledged_at'  => null,
                'ack_status'       => null,
            ],

            // ── Batch 2 aktif — unit 2 ──────────────────────────────────────
            [
                'device_id'        => $d2->id,
                'batch_id'         => $b2->id,
                'decision_type'    => 'start_heater',
                'reasoning'        => 'Batch BNJ-2026-002 (Mekongga) baru dimulai. Suhu ruang 36.4°C di bawah optimal. Iradiasi surya baru 380 W/m² karena masih pagi. Aktifkan heater untuk mempercepat pemanasan awal ruang pengering.',
                'input_data'       => ['temperature_inside' => 36.4, 'humidity_inside' => 73.6, 'solar_irradiance' => 380, 'grain_moisture' => 22.80, 'wind_speed' => 1.2],
                'output_action'    => ['heater' => true, 'fan' => false, 'fan_speed' => 0, 'target_temperature' => 46, 'duration_hours' => 2, 'mode' => 'auto'],
                'confidence_score' => 0.907,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subHours(2)->subMinutes(0),
                'executed_at'      => now()->subHours(2)->addSeconds(25),
                'command_sent_at'  => now()->subHours(2)->addSeconds(4),
                'acknowledged_at'  => now()->subHours(2)->addSeconds(25),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d2->id,
                'batch_id'         => $b2->id,
                'decision_type'    => 'adjust_temperature',
                'reasoning'        => 'Suhu ruang unit 2 baru mencapai 45.2°C, sudah dalam rentang optimal. Kurangi target heater ke 46°C dan aktifkan fan 50% agar distribusi panas lebih merata di seluruh lapisan gabah Mekongga.',
                'input_data'       => ['temperature_inside' => 45.2, 'humidity_inside' => 67.8, 'solar_irradiance' => 560, 'grain_moisture' => 21.10, 'wind_speed' => 1.5],
                'output_action'    => ['heater' => true, 'fan' => true, 'fan_speed' => 50, 'target_temperature' => 46, 'duration_hours' => 1, 'mode' => 'auto'],
                'confidence_score' => 0.863,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'pending',
                'decided_at'       => now()->subMinutes(12),
                'executed_at'      => null,
                'command_sent_at'  => null,
                'acknowledged_at'  => null,
                'ack_status'       => null,
            ],

            // ── Batch 3 selesai kemarin — riwayat historis ──────────────────
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'decision_type'    => 'start_heater',
                'reasoning'        => 'Awal pengeringan batch BNJ-2026-003 (IR64, 520kg). Suhu ruang 39.8°C perlu ditingkatkan. Aktifkan heater target 48°C.',
                'input_data'       => ['temperature_inside' => 39.8, 'humidity_inside' => 70.2, 'solar_irradiance' => 450, 'grain_moisture' => 23.50],
                'output_action'    => ['heater' => true, 'fan' => false, 'fan_speed' => 0, 'target_temperature' => 48, 'duration_hours' => 2, 'mode' => 'auto'],
                'confidence_score' => 0.895,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subDays(1)->setTime(7, 45),
                'executed_at'      => now()->subDays(1)->setTime(7, 45)->addSeconds(30),
                'command_sent_at'  => now()->subDays(1)->setTime(7, 45)->addSeconds(5),
                'acknowledged_at'  => now()->subDays(1)->setTime(7, 45)->addSeconds(30),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'decision_type'    => 'pause_drying',
                'reasoning'        => 'Prakiraan cuaca OpenWeatherMap mendeteksi probabilitas hujan 78% dalam 3 jam ke depan. Curah hujan aktual mulai terdeteksi 0.3mm/jam. Hentikan sementara proses pengeringan dan tutup ventilasi untuk melindungi gabah.',
                'input_data'       => ['temperature_inside' => 53.2, 'humidity_inside' => 55.4, 'solar_irradiance' => 320, 'grain_moisture' => 17.80, 'rainfall_1h' => 0.3, 'rain_risk_6h' => 'high', 'max_pop_6h' => '78%'],
                'output_action'    => ['heater' => false, 'fan' => false, 'fan_speed' => 0, 'target_temperature' => 0, 'duration_hours' => 0, 'mode' => 'pause'],
                'confidence_score' => 0.971,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subDays(1)->setTime(11, 30),
                'executed_at'      => now()->subDays(1)->setTime(11, 30)->addSeconds(26),
                'command_sent_at'  => now()->subDays(1)->setTime(11, 30)->addSeconds(5),
                'acknowledged_at'  => now()->subDays(1)->setTime(11, 30)->addSeconds(26),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'decision_type'    => 'resume_drying',
                'reasoning'        => 'Hujan berhenti. Probabilitas hujan 6 jam ke depan turun ke 12%. Iradiasi surya kembali ke 680 W/m². Lanjutkan pengeringan untuk mencapai target 14%.',
                'input_data'       => ['temperature_inside' => 44.1, 'humidity_inside' => 61.3, 'solar_irradiance' => 680, 'grain_moisture' => 17.80, 'rainfall_1h' => 0.0, 'rain_risk_6h' => 'low', 'max_pop_6h' => '12%'],
                'output_action'    => ['heater' => true, 'fan' => true, 'fan_speed' => 65, 'target_temperature' => 50, 'duration_hours' => 3, 'mode' => 'auto'],
                'confidence_score' => 0.934,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subDays(1)->setTime(13, 15),
                'executed_at'      => now()->subDays(1)->setTime(13, 15)->addSeconds(29),
                'command_sent_at'  => now()->subDays(1)->setTime(13, 15)->addSeconds(5),
                'acknowledged_at'  => now()->subDays(1)->setTime(13, 15)->addSeconds(29),
                'ack_status'       => 'acked',
            ],
            [
                'device_id'        => $d1->id,
                'batch_id'         => $b3->id,
                'decision_type'    => 'stop_heater',
                'reasoning'        => 'Kadar air gabah IR64 telah mencapai 13.9%, di bawah target 14%. Pengeringan selesai. Matikan heater dan kurangi fan ke 30% untuk fase pendinginan bertahap sebelum gabah dipindahkan.',
                'input_data'       => ['temperature_inside' => 56.8, 'humidity_inside' => 42.1, 'solar_irradiance' => 580, 'grain_moisture' => 13.90, 'target_moisture' => 14.00],
                'output_action'    => ['heater' => false, 'fan' => true, 'fan_speed' => 30, 'target_temperature' => 0, 'duration_hours' => 0.5, 'mode' => 'cooling'],
                'confidence_score' => 0.989,
                'ai_model'         => 'gemini-2.0-flash',
                'execution_status' => 'executed',
                'decided_at'       => now()->subDays(1)->setTime(15, 30),
                'executed_at'      => now()->subDays(1)->setTime(15, 30)->addSeconds(24),
                'command_sent_at'  => now()->subDays(1)->setTime(15, 30)->addSeconds(5),
                'acknowledged_at'  => now()->subDays(1)->setTime(15, 30)->addSeconds(24),
                'ack_status'       => 'acked',
            ],
        ];

        foreach ($decisions as $dec) {
            AiDecision::create($dec);
        }
    }
}

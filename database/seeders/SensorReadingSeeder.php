<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\SensorReading;
use Illuminate\Database\Seeder;

class SensorReadingSeeder extends Seeder
{
    public function run(): void
    {
        SensorReading::truncate();

        $d1 = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2 = Device::where('serial_number', 'PADI-BNJ-002')->first();
        $b1 = DryingBatch::where('batch_code', 'BNJ-2026-001')->first();
        $b2 = DryingBatch::where('batch_code', 'BNJ-2026-002')->first();
        $b3 = DryingBatch::where('batch_code', 'BNJ-2026-003')->first();

        // ── Batch 1 (BNJ-2026-001) ─────────────────────────────────────────
        // 5.5 jam pengeringan, 66 readings (interval 5 menit)
        // Suhu naik bertahap 38→57°C, moisture turun 24.2→17.6%
        for ($i = 66; $i >= 1; $i--) {
            $progress        = (66 - $i) / 66;
            $moisture        = round(24.20 - ($progress * 6.60), 2);
            $tempInside      = round(38 + ($progress * 19) + (rand(-10, 10) / 10), 2);
            $humidInside     = round(72 - ($progress * 22) + (rand(-8, 8) / 10), 2);
            $solar           = $this->solarCurve($i, 66);

            SensorReading::create([
                'device_id'           => $d1->id,
                'batch_id'            => $b1->id,
                'temperature_inside'  => max(36.0, $tempInside),
                'temperature_outside' => round(27 + (rand(-15, 30) / 10), 2),
                'humidity_inside'     => max(35.0, min(80.0, $humidInside)),
                'humidity_outside'    => round(72 + (rand(-30, 20) / 10), 2),
                'solar_irradiance'    => $solar,
                'lux'                 => round($solar * 115 + rand(-2000, 2000), 2),
                'grain_moisture'      => $moisture,
                'grain_weight'        => round(450.00 - ($progress * 21.50), 2),
                'wind_speed'          => round(1.2 + (rand(0, 25) / 10), 2),
                'wind_direction'      => rand(135, 225),
                'is_valid'            => true,
                'recorded_at'         => now()->subMinutes($i * 5),
            ]);
        }

        // ── Batch 2 (BNJ-2026-002) ─────────────────────────────────────────
        // 2 jam 15 menit, 27 readings (interval 5 menit)
        // Moisture turun 22.8→19.4%, suhu masih naik (baru mulai)
        for ($i = 27; $i >= 1; $i--) {
            $progress        = (27 - $i) / 27;
            $moisture        = round(22.80 - ($progress * 3.40), 2);
            $tempInside      = round(36 + ($progress * 12) + (rand(-8, 8) / 10), 2);
            $humidInside     = round(74 - ($progress * 12) + (rand(-6, 6) / 10), 2);
            $solar           = $this->solarCurve($i, 27);

            SensorReading::create([
                'device_id'           => $d2->id,
                'batch_id'            => $b2->id,
                'temperature_inside'  => max(35.0, $tempInside),
                'temperature_outside' => round(28 + (rand(-10, 25) / 10), 2),
                'humidity_inside'     => max(40.0, min(80.0, $humidInside)),
                'humidity_outside'    => round(70 + (rand(-25, 20) / 10), 2),
                'solar_irradiance'    => $solar,
                'lux'                 => round($solar * 112 + rand(-1500, 1500), 2),
                'grain_moisture'      => $moisture,
                'grain_weight'        => round(380.00 - ($progress * 12.00), 2),
                'wind_speed'          => round(1.5 + (rand(0, 20) / 10), 2),
                'wind_direction'      => rand(120, 210),
                'is_valid'            => true,
                'recorded_at'         => now()->subMinutes($i * 5),
            ]);
        }

        // ── Batch 3 (BNJ-2026-003) selesai kemarin ─────────────────────────
        // Rekam data historis 8 jam, 96 readings
        for ($i = 96; $i >= 1; $i--) {
            $progress        = (96 - $i) / 96;
            $moisture        = round(23.50 - ($progress * 9.60), 2);
            $tempInside      = round(40 + ($progress * 17) + (rand(-12, 12) / 10), 2);
            $humidInside     = round(70 - ($progress * 25) + (rand(-8, 8) / 10), 2);
            $solar           = $this->solarCurve($i, 96);

            SensorReading::create([
                'device_id'           => $d1->id,
                'batch_id'            => $b3->id,
                'temperature_inside'  => max(38.0, $tempInside),
                'temperature_outside' => round(26 + (rand(-10, 30) / 10), 2),
                'humidity_inside'     => max(32.0, min(78.0, $humidInside)),
                'humidity_outside'    => round(68 + (rand(-30, 25) / 10), 2),
                'solar_irradiance'    => $solar,
                'lux'                 => round($solar * 113 + rand(-2500, 2500), 2),
                'grain_moisture'      => max(13.90, $moisture),
                'grain_weight'        => round(520.00 - ($progress * 28.20), 2),
                'wind_speed'          => round(1.0 + (rand(0, 30) / 10), 2),
                'wind_direction'      => rand(130, 220),
                'is_valid'            => true,
                'recorded_at'         => now()->subDays(1)->subMinutes($i * 5),
            ]);
        }
    }

    /**
     * Simulasi kurva iradiasi surya — puncak di tengah rentang waktu.
     * Nilai realistis untuk lokasi Banjaran (7°LS), siang hari.
     */
    private function solarCurve(int $step, int $total): float
    {
        $normalized = ($total - $step) / $total; // 0.0 → 1.0
        // Bell curve: puncak di tengah (step 50%)
        $bell = sin(M_PI * $normalized);
        $base = 350 + ($bell * 550); // 350 W/m² pagi → 900 W/m² siang → 350 sore
        return round(max(80.0, $base + rand(-40, 40)), 2);
    }
}

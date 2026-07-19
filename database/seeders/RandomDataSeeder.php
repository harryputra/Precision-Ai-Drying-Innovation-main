<?php

namespace Database\Seeders;

use App\Models\ActuatorLog;
use App\Models\AiConversation;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\KnowledgeBase;
use App\Models\Notification;
use App\Models\SensorReading;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\WeatherData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RandomDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Hapus video YouTube dari KB ─────────────────────────────
        KnowledgeBase::where('category', 'video_tutorial')->delete();
        $this->command->info('Video YouTube KB dihapus.');

        // ── Ambil referensi yang dibutuhkan ─────────────────────────
        $devices = Device::all();
        $admin   = User::where('role', 'admin')->first();
        $viewer  = User::where('role', 'viewer')->first();

        if ($devices->isEmpty()) {
            $this->command->warn('Tidak ada device — jalankan DeviceSeeder dulu.');
            return;
        }

        $d1 = $devices->first();
        $d2 = $devices->count() > 1 ? $devices->get(1) : $d1;

        $varieties  = ['Ciherang', 'Mekongga', 'IR64', 'Inpari 32', 'Inpari 43', 'Situ Bagendit', 'Cisadane', 'Memberamo'];
        $riceTypes  = ['Gabah', 'Beras'];
        $methods    = ['Solar', 'Hybrid', 'Konvensional'];
        $operators  = ['Budi Santoso', 'Ahmad Fauzi', 'Rini Wulandari', 'Dedi Kurniawan'];
        $petanis    = ['Pak Asep', 'Bu Tini', 'Pak Udin', 'Bu Sari', 'Pak Hasan', 'Bu Eti', 'Pak Oman', 'Bu Neni', 'Pak Dedi', 'Bu Ratna'];
        $phones     = ['08121111001', '08122222002', '08133333003', '08144444004', '08155555005',
                       '08166666006', '08177777007', '08188888008', '08199999009', '08100000010'];

        // ── 1. DryingBatch — 10 batch baru ──────────────────────────
        $this->command->info('Membuat 10 DryingBatch...');
        $statuses   = ['completed', 'completed', 'completed', 'completed', 'failed', 'waiting', 'paused', 'completed', 'completed', 'failed'];
        $batchIds   = [];

        for ($i = 1; $i <= 10; $i++) {
            $device      = $i % 2 === 0 ? $d2 : $d1;
            $variety     = $varieties[array_rand($varieties)];
            $initMoist   = round(rand(210, 270) / 10, 1);
            $currMoist   = round(rand(130, 170) / 10, 1);
            $initWeight  = round(rand(200, 600) / 1, 0);
            $status      = $statuses[$i - 1];
            $startTime   = now()->subDays(rand(1, 30))->subHours(rand(0, 8));
            $endTime     = in_array($status, ['completed', 'failed']) ? $startTime->copy()->addHours(rand(4, 12)) : null;
            $padI        = str_pad($i, 3, '0', STR_PAD_LEFT);
            $suffix      = now()->format('His') . $i;

            $batch = DryingBatch::create([
                'device_id'        => $device->id,
                'batch_code'       => 'BNJ-RND-' . $suffix,
                'rice_type'        => $riceTypes[array_rand($riceTypes)],
                'rice_variety'     => $variety,
                'initial_weight'   => $initWeight,
                'current_weight'   => $status === 'waiting' ? null : round($initWeight * 0.93, 1),
                'initial_moisture' => $initMoist,
                'current_moisture' => $status === 'waiting' ? null : $currMoist,
                'target_moisture'  => 14.00,
                'drying_method'    => $methods[array_rand($methods)],
                'operator_name'    => $operators[array_rand($operators)],
                'petani_name'      => $petanis[$i - 1],
                'petani_phone'     => $phones[$i - 1],
                'start_time'       => $status === 'waiting' ? null : $startTime,
                'end_time'         => $endTime,
                'status'           => $status,
            ]);
            $batchIds[] = $batch->id;
        }

        // ── 2. SensorReading — 10 pembacaan baru ────────────────────
        $this->command->info('Membuat 10 SensorReading...');
        for ($i = 1; $i <= 10; $i++) {
            $device = $i % 2 === 0 ? $d2 : $d1;
            SensorReading::create([
                'device_id'          => $device->id,
                'temperature_inside' => round(rand(400, 560) / 10, 1),
                'temperature_outside'=> round(rand(270, 360) / 10, 1),
                'humidity_inside'    => round(rand(350, 700) / 10, 1),
                'humidity_outside'   => round(rand(600, 850) / 10, 1),
                'solar_irradiance'   => round(rand(100, 900), 0),
                'grain_moisture'     => round(rand(130, 250) / 10, 1),
                'wind_speed'         => round(rand(5, 35) / 10, 1),
                'pid_setpoint'       => round(rand(400, 540) / 10, 1),
                'pid_output'         => round(rand(0, 1000) / 10, 1),
                'ai_active'          => (bool) rand(0, 1),
                'recorded_at'        => now()->subMinutes(rand(1, 1440)),
                'is_valid'           => true,
            ]);
        }

        // ── 3. WeatherData — 10 data cuaca baru ─────────────────────
        $this->command->info('Membuat 10 WeatherData...');
        $conditions = ['Clear', 'Clouds', 'Rain', 'Drizzle', 'Thunderstorm', 'Mist'];
        for ($i = 1; $i <= 10; $i++) {
            $device = $i % 2 === 0 ? $d2 : $d1;
            WeatherData::create([
                'device_id'        => $device->id,
                'temperature'      => round(rand(220, 360) / 10, 1),
                'humidity'         => round(rand(500, 950) / 10, 1),
                'solar_irradiance' => round(rand(0, 900), 0),
                'wind_speed'       => round(rand(5, 80) / 10, 1),
                'wind_direction'   => rand(0, 359),
                'rainfall'         => round(rand(0, 50) / 10, 1),
                'weather_condition'=> $conditions[array_rand($conditions)],
                'cloud_cover'      => rand(0, 100),
                'is_forecast'      => (bool) rand(0, 1),
                'recorded_at'      => now()->subHours(rand(1, 72)),
            ]);
        }

        // ── 4. AiDecision — 10 keputusan AI baru ────────────────────
        $this->command->info('Membuat 10 AiDecision...');
        $decisionTypes = ['adjust_temperature', 'start_fan', 'stop_fan', 'pause_drying', 'resume_drying', 'alert_operator', 'open_roof', 'close_roof'];
        $execStatuses  = ['executed', 'executed', 'executed', 'pending', 'overridden'];
        $aiModels      = ['gemini-2.0-flash', 'gemini-2.0-flash-lite', 'llama-3.1-8b-instant'];
        $aiDecisionIds = [];

        for ($i = 1; $i <= 10; $i++) {
            $device   = $i % 2 === 0 ? $d2 : $d1;
            $batchId  = $batchIds[array_rand($batchIds)];
            $targTemp = rand(40, 55);
            $decidedAt = now()->subHours(rand(1, 168));

            $dec = AiDecision::create([
                'device_id'        => $device->id,
                'batch_id'         => $batchId,
                'decision_type'    => $decisionTypes[array_rand($decisionTypes)],
                'reasoning'        => 'Kadar air gabah ' . rand(15, 25) . '%, cuaca ' . $conditions[array_rand($conditions)] . ', setpoint dioptimalkan.',
                'input_data'       => [
                    'temperature_inside' => rand(40, 56),
                    'humidity_inside'    => rand(40, 75),
                    'grain_moisture'     => round(rand(140, 240) / 10, 1),
                    'solar_irradiance'   => rand(200, 800),
                ],
                'output_action'    => [
                    'target_temperature' => $targTemp,
                    'fan'                => (bool) rand(0, 1),
                    'fan_speed'          => rand(0, 100),
                    'duration_hours'     => rand(1, 6),
                    'mode'               => 'auto',
                ],
                'confidence_score' => round(rand(700, 980) / 1000, 3),
                'ai_model'         => $aiModels[array_rand($aiModels)],
                'execution_status' => $execStatuses[array_rand($execStatuses)],
                'decided_at'       => $decidedAt,
                'executed_at'      => $decidedAt->copy()->addMinutes(rand(1, 5)),
            ]);
            $aiDecisionIds[] = $dec->id;
        }

        // ── 5. ActuatorLog — 10 log aktuator baru ───────────────────
        $this->command->info('Membuat 10 ActuatorLog...');
        $actuatorTypes = ['heater', 'fan', 'roof', 'ventilation'];
        $commands      = ['on', 'off', 'open', 'close', 'adjust'];
        $triggeredBy   = ['ai', 'manual', 'schedule'];

        for ($i = 1; $i <= 10; $i++) {
            $device    = $i % 2 === 0 ? $d2 : $d1;
            $aType     = $actuatorTypes[array_rand($actuatorTypes)];
            $execAt    = now()->subHours(rand(1, 120));

            ActuatorLog::create([
                'device_id'         => $device->id,
                'batch_id'          => $batchIds[array_rand($batchIds)],
                'ai_decision_id'    => $aiDecisionIds[array_rand($aiDecisionIds)],
                'actuator_type'     => $aType,
                'actuator_name'     => ucfirst($aType) . ' Unit ' . ($i % 2 + 1),
                'command'           => $commands[array_rand($commands)],
                'set_value'         => round(rand(0, 550) / 10, 1),
                'actual_value'      => round(rand(0, 550) / 10, 1),
                'unit'              => $aType === 'heater' ? '°C' : '%',
                'triggered_by'      => $triggeredBy[array_rand($triggeredBy)],
                'triggered_by_user' => null,
                'status'            => rand(0, 9) > 1 ? 'success' : 'failed',
                'error_message'     => null,
                'response_time_ms'  => rand(50, 500),
                'executed_at'       => $execAt,
            ]);
        }

        // ── 6. Notification — 10 notifikasi baru ────────────────────
        $this->command->info('Membuat 10 Notification...');
        $notifTypes    = ['info', 'warning', 'success', 'error'];
        $notifTitles   = [
            'Pengeringan selesai',
            'Kadar air mencapai target',
            'Suhu melebihi batas',
            'Hujan terdeteksi',
            'Kipas mati otomatis',
            'Batch baru dimulai',
            'AI memberikan rekomendasi',
            'Koneksi perangkat pulih',
            'Perangkat offline',
            'Pembalikan gabah diingatkan',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $device  = $i % 2 === 0 ? $d2 : $d1;
            $isRead  = (bool) rand(0, 1);
            $sentAt  = now()->subHours(rand(1, 72));
            Notification::create([
                'device_id'   => $device->id,
                'user_id'     => $viewer?->id ?? $admin?->id,
                'batch_id'    => $batchIds[array_rand($batchIds)],
                'type'        => $notifTypes[array_rand($notifTypes)],
                'category'    => 'system',
                'title'       => $notifTitles[$i - 1],
                'message'     => 'Detail notifikasi ke-' . $i . ' untuk perangkat ' . $device->device_name . '.',
                'via_app'     => true,
                'via_email'   => false,
                'via_sms'     => false,
                'via_whatsapp'=> false,
                'read_at'     => $isRead ? $sentAt->copy()->addMinutes(rand(5, 60)) : null,
                'sent_at'     => $sentAt,
                'created_at'  => $sentAt,
                'updated_at'  => $sentAt,
            ]);
        }

        // ── 7. SystemLog — 10 log sistem baru ───────────────────────
        $this->command->info('Membuat 10 SystemLog...');
        $logLevels   = ['info', 'warning', 'error', 'debug'];
        $logEvents   = ['sensor.read', 'ai.decision', 'actuator.command', 'weather.fetch', 'auth.login', 'batch.create'];
        $logMessages = [
            'Pembacaan sensor berhasil',
            'Koneksi ke API cuaca timeout',
            'Keputusan AI dieksekusi',
            'Kipas dinyalakan otomatis',
            'User login berhasil',
            'Batch baru dibuat',
            'Sensor tidak merespons',
            'Fallback ke Groq berhasil',
            'Export data selesai',
            'Pembaruan firmware tersedia',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $device = $i % 2 === 0 ? $d2 : $d1;
            SystemLog::create([
                'device_id'  => $device->id,
                'user_id'    => $admin?->id,
                'level'      => $logLevels[array_rand($logLevels)],
                'channel'    => 'app',
                'event'      => $logEvents[array_rand($logEvents)],
                'message'    => $logMessages[$i - 1],
                'context'    => ['iteration' => $i, 'device' => $device->serial_number],
                'created_at' => now()->subHours(rand(1, 168)),
                'updated_at' => now()->subHours(rand(1, 168)),
            ]);
        }

        // ── 8. KnowledgeBase — 10 artikel baru ──────────────────────
        $this->command->info('Membuat 10 KnowledgeBase...');
        $kbEntries = [
            ['category' => 'troubleshooting', 'title' => 'Sensor Kelembaban Membaca Nilai Tidak Wajar', 'content' => "Jika sensor kelembaban membaca >95% terus-menerus:\n1. Periksa kondensasi di sensor — bersihkan dengan kain kering\n2. Kalibrasi ulang sensor setiap 3 bulan\n3. Pastikan ventilasi ruang sensor tidak tersumbat\n4. Ganti sensor jika masih error setelah kalibrasi", 'tags' => ['sensor', 'kelembaban', 'troubleshooting']],
            ['category' => 'troubleshooting', 'title' => 'Heater Tidak Mencapai Setpoint Suhu', 'content' => "Penyebab umum heater gagal capai setpoint:\n1. Daya listrik tidak stabil — cek tegangan di bawah 180V\n2. Elemen pemanas aus — cek resistansi\n3. PID parameter kurang optimal — tuning Kp, Ki, Kd\n4. Beban gabah terlalu berat — kurangi 20% dari kapasitas normal", 'tags' => ['heater', 'setpoint', 'PID', 'troubleshooting']],
            ['category' => 'best_practices', 'title' => 'Jadwal Pemeliharaan Bulanan Solar Dryer', 'content' => "Pemeliharaan rutin setiap bulan:\n- Bersihkan panel surya dari debu dan kotoran burung\n- Periksa kabel dan konektor ESP32\n- Kalibrasi sensor suhu dan kelembaban\n- Ganti filter kipas jika tersumbat\n- Backup data ke cloud\n- Test mode manual semua aktuator", 'tags' => ['maintenance', 'bulanan', 'best practices']],
            ['category' => 'best_practices', 'title' => 'Optimasi Pengeringan Saat Musim Hujan', 'content' => "Strategi pengeringan musim hujan (Oktober–Maret Jawa Barat):\n1. Gunakan mode Hybrid — jangan andalkan solar saja\n2. Aktifkan forecast monitoring — set alert hujan >60%\n3. Naikkan setpoint 3–5°C untuk kompensasi kelembaban tinggi\n4. Fan forced_on saat RH dalam >70%\n5. Lapisan gabah dikurangi ke 4cm untuk sirkulasi udara lebih baik", 'tags' => ['musim hujan', 'hybrid', 'optimasi']],
            ['category' => 'weather_patterns', 'title' => 'Karakteristik Cuaca Banjaran Juli–September', 'content' => "Pola cuaca Banjaran, Kabupaten Bandung periode Juli–September:\n- Musim kemarau — iradiasi surya optimal 650–900 W/m²\n- Suhu udara: 24–32°C (siang), 18–22°C (malam)\n- Kelembaban relatif: 55–70% (siang)\n- Angin: NE/SE, 2–5 m/s\n- Hujan: jarang, <100mm/bulan\n- Kondisi ideal untuk pengeringan solar", 'tags' => ['cuaca', 'banjaran', 'musim kemarau', 'pola']],
            ['category' => 'rice_varieties', 'title' => 'Panduan Pengeringan Varietas Inpari 32', 'content' => "Inpari 32 — varietas unggul tahan wereng.\n\nKarakteristik pengeringan:\n- Suhu optimal: 43–50°C\n- Kadar air panen: 21–24%\n- Target: 14%\n- Durasi: 5–7 jam (cuaca cerah)\n- Sensitivitas: sedang\n\nCatatan: Inpari 32 punya kulit yang lebih tebal, lebih tahan suhu tinggi dibanding Ciherang.", 'tags' => ['inpari 32', 'varietas', 'pengeringan']],
            ['category' => 'rice_varieties', 'title' => 'Panduan Pengeringan Varietas Situ Bagendit', 'content' => "Situ Bagendit — varietas gogo lahan kering.\n\nKarakteristik pengeringan:\n- Suhu optimal: 40–48°C (lebih rendah dari varietas sawah)\n- Kadar air panen: 20–23%\n- Target: 14%\n- Durasi: 6–8 jam\n- Sangat sensitif terhadap suhu > 52°C — risiko cracking tinggi\n\nRekomendasi: gunakan mode Solar tanpa heater backup saat iradiasi >600 W/m²", 'tags' => ['situ bagendit', 'gogo', 'lahan kering']],
            ['category' => 'equipment_specs', 'title' => 'Spesifikasi Panel Surya Padi PRECISION', 'content' => "Spesifikasi panel surya sistem Padi PRECISION:\n- Tipe: Monocrystalline 400Wp\n- Jumlah: 4 panel (total 1600Wp)\n- Tegangan Voc: 49.2V\n- Arus Isc: 10.2A\n- Efisiensi: 21.3%\n- MPPT Controller: 60A, 12/24/48V\n- Orientasi: 15° dari horizontal, menghadap utara\n- Garansi: 25 tahun linear performance", 'tags' => ['panel surya', 'spesifikasi', 'monocrystalline']],
            ['category' => 'equipment_specs', 'title' => 'Spesifikasi ESP32 dan Sensor yang Digunakan', 'content' => "Komponen elektronik Padi PRECISION:\n- MCU: ESP32-WROOM-32D (dual core 240MHz)\n- Sensor suhu/RH: DHT22 (akurasi ±0.5°C, ±2%RH)\n- Sensor kadar air gabah: resistive moisture probe kustom\n- Sensor iradiasi: pyranometer BH1750\n- Relay: 4-channel 10A 250VAC\n- Koneksi: WiFi 802.11b/g/n\n- Update interval: 500ms (PID), 30s (cloud)", 'tags' => ['ESP32', 'sensor', 'hardware', 'spesifikasi']],
            ['category' => 'drying_rules', 'title' => 'Protokol Darurat Saat Listrik Padam', 'content' => "Prosedur saat listrik PLN padam:\n1. UPS/baterai akan menjaga ESP32 tetap aktif (±2 jam)\n2. Solar panel tetap beroperasi jika iradiasi >200 W/m²\n3. Kipas mati → buka ventilasi manual untuk mencegah kondensasi\n4. Jika padam >30 menit saat malam → jangan lanjutkan pengeringan, tunggu pagi\n5. Setelah listrik kembali: reset setpoint, cek sensor, restart batch\n6. Log kejadian di sistem setelah pemulihan", 'tags' => ['darurat', 'listrik padam', 'protokol', 'UPS']],
        ];

        foreach ($kbEntries as $entry) {
            KnowledgeBase::create(array_merge($entry, [
                'priority_weight' => round(rand(50, 90) / 10, 1),
                'use_for_ai'      => true,
                'is_active'       => true,
                'created_by'      => $admin?->id ?? 1,
            ]));
        }

        // ── 9. AiConversation — 10 pesan chat baru ──────────────────
        $this->command->info('Membuat 10 AiConversation...');
        $chatPairs = [
            ['user' => 'Kadar air gabah sekarang berapa?', 'ai' => 'Kadar air gabah saat ini 17.6%. Target 14%, masih butuh sekitar 2–3 jam lagi dengan kondisi optimal.'],
            ['user' => 'Apakah aman buka atap sekarang?', 'ai' => 'Forecast hujan 15% dalam 6 jam ke depan. Aman untuk buka atap dan manfaatkan iradiasi surya 780 W/m² saat ini.'],
            ['user' => 'Kenapa suhu tidak naik ke 50 derajat?', 'ai' => 'PID sedang mengejar setpoint. Biasanya butuh 8–12 menit. Jika >15 menit tidak tercapai, periksa elemen heater dan tegangan input.'],
            ['user' => 'Berapa lama lagi selesai?', 'ai' => 'Estimasi 2.5 jam lagi berdasarkan laju penurunan kadar air saat ini (0.8%/jam). Kondisi cuaca stabil mendukung target tercapai.'],
            ['user' => 'Rekomendasikan setpoint untuk Ciherang', 'ai' => 'Untuk Ciherang dengan kadar air 22%, rekomendasikan setpoint 48°C. Jangan melebihi 55°C karena risiko cracking biji tinggi.'],
            ['user' => 'Hujan akan datang tidak?', 'ai' => 'Forecast BMKG: peluang hujan 45% dalam 3 jam ke depan. Disarankan memantau setiap 30 menit dan siap menutup atap jika peluang naik >70%.'],
            ['user' => 'Aktifkan kipas sekarang?', 'ai' => 'Kelembaban dalam ruang 68% — di atas threshold 65%. Disarankan aktifkan kipas mode forced_on selama 20 menit untuk menurunkan RH.'],
            ['user' => 'Ekspor data batch bulan ini', 'ai' => 'Silakan kunjungi menu Batches → Export → pilih format Excel atau PDF. Data akan mencakup semua batch Juli 2026.'],
            ['user' => 'Varietas apa yang paling cepat kering?', 'ai' => 'IR64 dan Memberamo relatif paling cepat kering (5–6 jam) karena kulit biji lebih tipis. Ciherang butuh 6–8 jam dan paling sensitif terhadap suhu tinggi.'],
            ['user' => 'Bagaimana cara kalibrasi sensor kadar air?', 'ai' => 'Kalibrasi dilakukan dengan sampel gabah referensi kadar air terukur (oven method). Ikuti panduan di menu Knowledge Base → Spesifikasi → ESP32 dan Sensor.'],
        ];

        $sessionId = (string) Str::uuid();
        foreach ($chatPairs as $idx => $pair) {
            $ts = now()->subHours(rand(1, 48))->subMinutes($idx * 2);
            AiConversation::create([
                'user_id'     => $admin?->id ?? 1,
                'device_id'   => ($idx % 2 === 0 ? $d1 : $d2)->id,
                'session_id'  => $sessionId,
                'role'        => 'user',
                'message'     => $pair['user'],
                'created_at'  => $ts,
                'updated_at'  => $ts,
            ]);
            $ts2 = $ts->copy()->addSeconds(rand(3, 10));
            AiConversation::create([
                'user_id'     => $admin?->id ?? 1,
                'device_id'   => ($idx % 2 === 0 ? $d1 : $d2)->id,
                'session_id'  => $sessionId,
                'role'        => 'assistant',
                'message'     => $pair['ai'],
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => rand(80, 300),
                'created_at'  => $ts2,
                'updated_at'  => $ts2,
            ]);
        }

        $this->command->info('RandomDataSeeder selesai. Data ditambahkan:');
        $this->command->info('  ✓ Video YouTube KB dihapus');
        $this->command->info('  ✓ 10 DryingBatch');
        $this->command->info('  ✓ 10 SensorReading');
        $this->command->info('  ✓ 10 WeatherData');
        $this->command->info('  ✓ 10 AiDecision');
        $this->command->info('  ✓ 10 ActuatorLog');
        $this->command->info('  ✓ 10 Notification');
        $this->command->info('  ✓ 10 SystemLog');
        $this->command->info('  ✓ 10 KnowledgeBase');
        $this->command->info('  ✓ 10 AiConversation (20 pesan)');
    }
}

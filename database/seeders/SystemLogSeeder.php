<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemLogSeeder extends Seeder
{
    public function run(): void
    {
        SystemLog::truncate();

        $user = User::where('email', 'admin@solardryerai.test')->first();
        $d1   = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2   = Device::where('serial_number', 'PADI-BNJ-002')->first();

        $logs = [
            ['level' => 'info',    'channel' => 'auth',     'event' => 'user.login',           'message' => 'User Ihsan Alfarisi berhasil login dari 192.168.1.10.',                          'user_id' => $user->id, 'device_id' => null,    'ip_address' => '192.168.1.10',  'method' => 'POST', 'url' => '/login',              'created_at' => now()->subHours(6)],
            ['level' => 'info',    'channel' => 'drying',   'event' => 'batch.started',        'message' => 'Batch BNJ-2026-001 dimulai pada perangkat PADI-BNJ-001.',                        'user_id' => $user->id, 'device_id' => $d1->id, 'ip_address' => '192.168.1.10',  'method' => 'POST', 'url' => '/batches',            'created_at' => now()->subHours(5)->subMinutes(30)],
            ['level' => 'info',    'channel' => 'sensor',   'event' => 'sensor.reading',       'message' => 'Data sensor valid diterima dari PADI-BNJ-001 — suhu: 38.2°C, RH: 71.8%.',       'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'POST', 'url' => '/api/iot/sensor',     'created_at' => now()->subHours(5)->subMinutes(25)],
            ['level' => 'info',    'channel' => 'ai',       'event' => 'ai.decision',          'message' => 'AI Gemini 2.0 Flash membuat keputusan start_heater untuk BNJ-2026-001. Confidence: 91.2%.',  'user_id' => null, 'device_id' => $d1->id, 'ip_address' => '127.0.0.1', 'method' => 'POST', 'url' => '/api/ai/decide',      'created_at' => now()->subHours(5)->subMinutes(24)],
            ['level' => 'info',    'channel' => 'iot',      'event' => 'command.sent',         'message' => 'Perintah AI dikirim ke ESP32 PADI-BNJ-001: heater=true, target=48°C.',           'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'GET',  'url' => '/api/iot/pending-command', 'created_at' => now()->subHours(5)->subMinutes(20)],
            ['level' => 'info',    'channel' => 'iot',      'event' => 'command.ack',          'message' => 'ACK diterima dari ESP32 PADI-BNJ-001 — eksekusi berhasil dalam 285ms.',          'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'POST', 'url' => '/api/iot/command-ack', 'created_at' => now()->subHours(5)->subMinutes(19)],
            ['level' => 'warning', 'channel' => 'sensor',   'event' => 'sensor.humidity_high', 'message' => 'Kelembaban ruang PADI-BNJ-001 melebihi threshold 65% (nilai: 69.4%).',           'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'POST', 'url' => '/api/iot/sensor',     'created_at' => now()->subHours(4)->subMinutes(10)],
            ['level' => 'info',    'channel' => 'ai',       'event' => 'ai.decision',          'message' => 'AI Gemini 2.0 Flash membuat keputusan start_fan untuk BNJ-2026-001. Confidence: 88.1%.',    'user_id' => null, 'device_id' => $d1->id, 'ip_address' => '127.0.0.1', 'method' => 'POST', 'url' => '/api/ai/decide',      'created_at' => now()->subHours(4)->subMinutes(9)],
            ['level' => 'warning', 'channel' => 'sensor',   'event' => 'sensor.temp_high',     'message' => 'Suhu PADI-BNJ-001 mencapai 57.8°C mendekati batas maksimal 60°C.',               'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'POST', 'url' => '/api/iot/sensor',     'created_at' => now()->subHours(2)->subMinutes(56)],
            ['level' => 'info',    'channel' => 'ai',       'event' => 'ai.decision',          'message' => 'AI membuat keputusan adjust_temperature — heater dimatikan, fan 80%. Confidence: 94.4%.',   'user_id' => null, 'device_id' => $d1->id, 'ip_address' => '127.0.0.1', 'method' => 'POST', 'url' => '/api/ai/decide',      'created_at' => now()->subHours(2)->subMinutes(55)],
            ['level' => 'info',    'channel' => 'drying',   'event' => 'batch.started',        'message' => 'Batch BNJ-2026-002 dimulai pada perangkat PADI-BNJ-002.',                        'user_id' => $user->id, 'device_id' => $d2->id, 'ip_address' => '192.168.1.10',  'method' => 'POST', 'url' => '/batches',            'created_at' => now()->subHours(2)->subMinutes(15)],
            ['level' => 'info',    'channel' => 'weather',  'event' => 'weather.fetched',      'message' => 'Data cuaca OpenWeatherMap berhasil diambil. Suhu: 31.5°C, RH: 60%, awan: 8%.',  'user_id' => null,      'device_id' => null,    'ip_address' => '127.0.0.1',     'method' => 'GET',  'url' => '/api/ai/context',     'created_at' => now()->subMinutes(15)],
            ['level' => 'info',    'channel' => 'ai',       'event' => 'ai.decision',          'message' => 'AI Gemini 2.0 Flash membuat keputusan alert_operator untuk BNJ-2026-001. Confidence: 92.8%.', 'user_id' => null, 'device_id' => $d1->id, 'ip_address' => '127.0.0.1', 'method' => 'POST', 'url' => '/api/ai/decide',     'created_at' => now()->subMinutes(8)],
            ['level' => 'info',    'channel' => 'drying',   'event' => 'batch.completed',      'message' => 'Batch BNJ-2026-003 selesai. Kadar air akhir: 13.9%. Durasi: 8j 15m.',            'user_id' => null,      'device_id' => $d1->id, 'ip_address' => '192.168.1.101', 'method' => 'POST', 'url' => '/api/iot/sensor',     'created_at' => now()->subDays(1)->setTime(15, 45)],
            ['level' => 'warning', 'channel' => 'weather',  'event' => 'weather.rain',         'message' => 'Hujan terdeteksi (0.3mm/jam). Probabilitas 78%. Sistem menjeda pengeringan BNJ-2026-003.',  'user_id' => null, 'device_id' => $d1->id, 'ip_address' => '127.0.0.1', 'method' => 'POST', 'url' => '/api/ai/decide',      'created_at' => now()->subDays(1)->setTime(11, 30)],
            ['level' => 'info',    'channel' => 'app',      'event' => 'knowledge.created',    'message' => 'Knowledge base entry baru: Aturan Utama Pengeringan Gabah dengan Solar Dryer.',  'user_id' => $user->id, 'device_id' => null,    'ip_address' => '192.168.1.10',  'method' => 'POST', 'url' => '/knowledge',          'created_at' => now()->subDays(2)],
        ];

        foreach ($logs as $log) {
            SystemLog::create($log);
        }
    }
}

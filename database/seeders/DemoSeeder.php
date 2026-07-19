<?php

namespace Database\Seeders;

use App\Models\QuickLoginConfig;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed DEMO — data contoh realistis (device, batch, sensor, keputusan AI,
 * notifikasi, percakapan) + akun contoh per-role + Quick-Login AKTIF.
 *
 * HANYA untuk mode demo (`./run.sh demo`). Entrypoint produksi TIDAK
 * PERNAH memanggil seeder ini.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Akun contoh per-role (password: "password")
        User::firstOrCreate(
            ['email' => 'admin@solardryerai.test'],
            ['name' => 'Ihsan Alfarisi', 'password' => Hash::make('password'), 'role' => 'admin']
        );
        User::firstOrCreate(
            ['email' => 'operator@solardryerai.test'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password'), 'role' => 'operator']
        );
        User::firstOrCreate(
            ['email' => 'viewer@solardryerai.test'],
            ['name' => 'Siti Rahayu', 'password' => Hash::make('password'), 'role' => 'viewer']
        );

        $this->call([
            DeviceSeeder::class,
            DryingBatchSeeder::class,
            SensorReadingSeeder::class,
            WeatherDataSeeder::class,
            AiDecisionSeeder::class,
            ActuatorLogSeeder::class,
            NotificationSeeder::class,
            SystemLogSeeder::class,
            KnowledgeBaseSeeder::class,
            AiConversationSeeder::class,
        ]);

        // Quick-Login AKTIF + tombol per-role tampil di halaman login
        $config = QuickLoginConfig::current();
        $config->update([
            'enabled'              => true,
            'show_button_on_login' => true,
            'token'                => $config->token ?: bin2hex(random_bytes(16)),
            'expires_at'           => null,
        ]);
    }
}

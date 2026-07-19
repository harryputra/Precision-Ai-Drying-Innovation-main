<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\KnowledgeBase;
use App\Models\QuickLoginConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed ESENSIAL — aman & idempoten untuk PRODUKSI (dipanggil entrypoint
 * docker setiap start). Isi: 1 admin dari .env, 1 device fisik, knowledge
 * base referensi AI, dan baris konfigurasi quick-login (default NONAKTIF).
 *
 * TIDAK berisi data contoh/demo — itu tugas DemoSeeder (mode demo saja).
 */
class EssentialSeeder extends Seeder
{
    public function run(): void
    {
        // 1 akun admin asli dari .env — dibuat SEKALI (firstOrCreate).
        // Ganti password selanjutnya lewat UI profil, bukan .env, agar
        // restart container tidak menimpa password yang sudah diubah.
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            if (in_array($password, ['password', 'changeme', 'admin123'], true)
                || str_starts_with(strtolower($password), 'ganti')) {
                $this->command?->warn('⚠ ADMIN_PASSWORD masih placeholder/lemah — ganti di .env!');
            }

            User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => env('ADMIN_NAME', 'Administrator'),
                    'password' => Hash::make($password),
                    'role'     => 'admin',
                ]
            );
        }

        // Device fisik pertama — ESP32 (DEVICE_ID=1) butuh baris ini untuk
        // kirim sensor. Hanya dibuat bila tabel masih kosong.
        if (Device::count() === 0) {
            Device::create([
                'device_name'      => 'Padi PRECISION Unit 1',
                'serial_number'    => 'PADI-BNJ-001',
                'firmware_version' => 'v1.4.0',
                'location'         => 'Kelompok Tani Maju Bersama — Margahurip, Banjaran',
                'status'           => 'offline',
            ]);
        }

        // Knowledge base = data REFERENSI untuk prompt AI (lookup), bukan
        // data contoh — boleh ada di produksi. Seed hanya bila kosong agar
        // editan admin tidak tertimpa saat restart.
        if (KnowledgeBase::count() === 0) {
            $this->call(KnowledgeBaseSeeder::class);
        }

        // Pastikan baris config quick-login ada — default NONAKTIF (404).
        QuickLoginConfig::current();
    }
}

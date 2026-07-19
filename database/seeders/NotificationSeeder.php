<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Notification::truncate();

        $admin    = User::where('email', 'admin@solardryerai.test')->first();
        $operator = User::where('email', 'operator@solardryerai.test')->first();
        $viewer   = User::where('email', 'viewer@solardryerai.test')->first();
        $d1       = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2       = Device::where('serial_number', 'PADI-BNJ-002')->first();
        $b1       = DryingBatch::where('batch_code', 'BNJ-2026-001')->first();
        $b2       = DryingBatch::where('batch_code', 'BNJ-2026-002')->first();
        $b3       = DryingBatch::where('batch_code', 'BNJ-2026-003')->first();
        $b4       = DryingBatch::where('batch_code', 'BNJ-2026-004')->first();
        $b5       = DryingBatch::where('batch_code', 'BNJ-2026-005')->first();
        $b6       = DryingBatch::where('batch_code', 'BNJ-2026-006')->first();

        $notifications = [

            // -------------------------------------------------------
            // ADMIN notifications
            // -------------------------------------------------------
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Pengeringan Dimulai — BNJ-2026-001',
                'message'   => 'Batch BNJ-2026-001 (Ciherang, 450 kg) telah dimulai di Padi PRECISION Unit 1. Kadar air awal: 24.2%.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(5)->subMinutes(30),
                'read_at'   => now()->subHours(5),
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'warning',
                'category'  => 'temperature_alert',
                'title'     => 'Kelembaban Tinggi — Unit 1',
                'message'   => 'Kelembaban ruang pengering Unit 1 mencapai 69.4%. Sistem AI telah mengaktifkan fan exhaust 70%.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(4)->subMinutes(10),
                'read_at'   => now()->subHours(3)->subMinutes(50),
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'warning',
                'category'  => 'temperature_alert',
                'title'     => 'Suhu Mendekati Batas — Unit 1',
                'message'   => 'Suhu ruang pengering Unit 1 mencapai 57.8°C (batas aman 60°C). AI telah mengurangi target heater ke 50°C.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(2)->subMinutes(55),
                'read_at'   => now()->subHours(2)->subMinutes(40),
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'moisture_alert',
                'title'     => 'Progress Pengeringan 64%',
                'message'   => 'Kadar air gabah BNJ-2026-001 sekarang 17.6%. Progress 64% menuju target 14%. Estimasi selesai 2–3 jam lagi.',
                'via_app'   => true,
                'sent_at'   => now()->subMinutes(8),
                'read_at'   => null,
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d2->id,
                'batch_id'  => $b2->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Pengeringan Dimulai — BNJ-2026-002',
                'message'   => 'Batch BNJ-2026-002 (Mekongga, 380 kg) telah dimulai di Padi PRECISION Unit 2. Kadar air awal: 22.8%.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(2)->subMinutes(15),
                'read_at'   => now()->subHours(2),
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'alert',
                'category'  => 'weather_alert',
                'title'     => 'Hujan Terdeteksi — Pengeringan Dijeda',
                'message'   => 'Curah hujan 0.3mm/jam terdeteksi. Probabilitas hujan 78% dalam 3 jam. Proses pengeringan BNJ-2026-003 dijeda otomatis.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subDays(1)->setTime(11, 30),
                'read_at'   => now()->subDays(1)->setTime(11, 45),
            ],
            [
                'user_id'   => $admin->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'success',
                'category'  => 'batch_complete',
                'title'     => 'Pengeringan Selesai — BNJ-2026-003',
                'message'   => 'Batch BNJ-2026-003 (IR64, 520 kg) berhasil diselesaikan. Kadar air akhir: 13.9%. Target tercapai.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subDays(1)->setTime(15, 45),
                'read_at'   => now()->subDays(1)->setTime(16, 0),
            ],

            // -------------------------------------------------------
            // OPERATOR notifications (Budi Santoso)
            // -------------------------------------------------------

            // Batch BNJ-2026-001 dimulai — operator yang jalankan
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Pengeringan Dimulai — BNJ-2026-001',
                'message'   => 'Anda memulai batch BNJ-2026-001 (Ciherang, 450 kg) di Unit 1. Sistem AI aktif memantau proses. Kadar air awal: 24.2%.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(5)->subMinutes(30),
                'read_at'   => now()->subHours(5)->subMinutes(25),
            ],
            // Kelembaban tinggi — butuh tindakan operator
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'warning',
                'category'  => 'temperature_alert',
                'title'     => 'Kelembaban Tinggi — Tindakan Diperlukan',
                'message'   => 'Kelembaban ruang Unit 1 mencapai 69.4%. AI telah mengaktifkan fan exhaust 70%. Periksa kondisi fisik ventilasi jika kelembaban tidak turun dalam 15 menit.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subHours(4)->subMinutes(10),
                'read_at'   => now()->subHours(4),
            ],
            // Suhu mendekati batas — AI sudah handle, tapi operator diberi tahu
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'warning',
                'category'  => 'temperature_alert',
                'title'     => 'Suhu Mendekati Batas Aman — Unit 1',
                'message'   => 'Suhu Unit 1 mencapai 57.8°C (batas 60°C). AI telah menurunkan setpoint heater ke 50°C. Pantau grafik suhu dan siap override jika perlu.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(2)->subMinutes(55),
                'read_at'   => now()->subHours(2)->subMinutes(45),
            ],
            // Progress update
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'moisture_alert',
                'title'     => 'Update Progress — BNJ-2026-001 (64%)',
                'message'   => 'Kadar air BNJ-2026-001 turun ke 17.6% dari target 14.0%. Progress 64%. Estimasi selesai 2–3 jam lagi. Tidak ada tindakan diperlukan.',
                'via_app'   => true,
                'sent_at'   => now()->subMinutes(8),
                'read_at'   => null,
            ],
            // Batch BNJ-2026-003 — hujan, dijeda
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'alert',
                'category'  => 'weather_alert',
                'title'     => 'Hujan — BNJ-2026-003 Dijeda Otomatis',
                'message'   => 'Curah hujan terdeteksi 0.3mm/jam, probabilitas hujan 78% dalam 3 jam. Batch BNJ-2026-003 dijeda otomatis. Penutup atap sudah ditutup AI. Cek kondisi fisik atap.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subDays(1)->setTime(11, 30),
                'read_at'   => now()->subDays(1)->setTime(11, 40),
            ],
            // Batch BNJ-2026-003 selesai
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'success',
                'category'  => 'batch_complete',
                'title'     => 'Pengeringan Selesai — BNJ-2026-003',
                'message'   => 'Batch BNJ-2026-003 (IR64, 520 kg) selesai. Kadar air akhir 13.9% — target 14% tercapai. Gabah siap diangkut. Mohon lakukan penimbangan akhir.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subDays(1)->setTime(15, 45),
                'read_at'   => now()->subDays(1)->setTime(15, 55),
            ],
            // Batch BNJ-2026-005 gagal — operator perlu lapor
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b5->id,
                'type'      => 'error',
                'category'  => 'batch_failed',
                'title'     => 'Pengeringan Gagal — BNJ-2026-005',
                'message'   => 'Batch BNJ-2026-005 (Inpari 32, 200 kg) gagal akibat hujan deras. Kadar air tetap 25.8%, tidak ada penurunan signifikan. Gabah perlu dijadwalkan ulang untuk pengeringan.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subDays(3)->setTime(10, 30),
                'read_at'   => now()->subDays(3)->setTime(10, 45),
            ],
            // Batch BNJ-2026-006 menunggu — pengingat untuk mulai
            [
                'user_id'   => $operator->id,
                'device_id' => $d2->id,
                'batch_id'  => $b6->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Batch Menunggu — BNJ-2026-006',
                'message'   => 'Batch BNJ-2026-006 (Mekongga, 500 kg) masih berstatus waiting. Unit 2 tersedia. Prakiraan cuaca hari ini cerah 80%. Pertimbangkan memulai pengeringan segera.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(1),
                'read_at'   => null,
            ],
            // AI override oleh operator — notifikasi ke operator sendiri sebagai konfirmasi
            [
                'user_id'   => $operator->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'ai_decision',
                'title'     => 'Override AI Dikonfirmasi — Unit 1',
                'message'   => 'Override manual Anda pada keputusan AI (stop_fan) telah dicatat. Sistem berjalan dalam mode manual untuk aktuator fan Unit 1. AI tetap memantau sensor.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(3),
                'read_at'   => now()->subHours(2)->subMinutes(50),
            ],
            // Device offline — operator harus cek fisik
            [
                'user_id'   => $operator->id,
                'device_id' => $d2->id,
                'batch_id'  => null,
                'type'      => 'alert',
                'category'  => 'device_offline',
                'title'     => 'Unit 2 Tidak Merespons',
                'message'   => 'Padi PRECISION Unit 2 (PADI-BNJ-002) tidak mengirim data selama 5 menit. Terakhir online: '.now()->subMinutes(6)->format('H:i').'. Periksa koneksi jaringan dan power supply perangkat.',
                'via_app'   => true,
                'via_email' => true,
                'sent_at'   => now()->subMinutes(30),
                'read_at'   => null,
            ],

            // -------------------------------------------------------
            // VIEWER notifications (Siti Rahayu — petani)
            // Bahasa sederhana, fokus info actionable, tanpa istilah teknis
            // -------------------------------------------------------

            // Gabah mulai dikeringkan (BNJ-2026-001)
            [
                'user_id'   => $viewer->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Gabah Anda Mulai Dikeringkan',
                'message'   => 'Gabah Ciherang milik Anda (450 kg) sudah mulai dikeringkan hari ini. Kadar air awal 24.2%. Kami akan memberi tahu Anda saat sudah selesai.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(5)->subMinutes(30),
                'read_at'   => now()->subHours(5)->subMinutes(20),
            ],
            // Update kadar air — progres 64%
            [
                'user_id'   => $viewer->id,
                'device_id' => $d1->id,
                'batch_id'  => $b1->id,
                'type'      => 'info',
                'category'  => 'moisture_alert',
                'title'     => 'Update Gabah Anda — 64% Selesai',
                'message'   => 'Kadar air gabah Ciherang Anda sudah turun ke 17.6% dari 24.2%. Target 14%. Perkiraan selesai 2–3 jam lagi. Tidak perlu tindakan dari Anda.',
                'via_app'   => true,
                'sent_at'   => now()->subMinutes(8),
                'read_at'   => null,
            ],
            // Gabah BNJ-2026-003 selesai — siap diambil
            [
                'user_id'   => $viewer->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'success',
                'category'  => 'batch_complete',
                'title'     => 'Gabah Anda Selesai Dikeringkan',
                'message'   => 'Gabah IR64 Anda (520 kg) sudah selesai dikeringkan. Kadar air akhir 13.9% — sudah aman untuk disimpan. Silakan datang untuk mengambil gabah Anda.',
                'via_app'   => true,
                'via_whatsapp' => true,
                'sent_at'   => now()->subDays(1)->setTime(15, 45),
                'read_at'   => now()->subDays(1)->setTime(16, 10),
            ],
            // BNJ-2026-003 dijeda hujan — viewer perlu tahu tapi tidak perlu khawatir
            [
                'user_id'   => $viewer->id,
                'device_id' => $d1->id,
                'batch_id'  => $b3->id,
                'type'      => 'warning',
                'category'  => 'weather_alert',
                'title'     => 'Pengeringan Sementara Dijeda — Hujan',
                'message'   => 'Pengeringan gabah IR64 Anda dijeda sementara karena hujan. Gabah sudah aman, atap penutup sudah ditutup otomatis. Pengeringan akan dilanjutkan saat cuaca membaik.',
                'via_app'   => true,
                'sent_at'   => now()->subDays(1)->setTime(11, 30),
                'read_at'   => now()->subDays(1)->setTime(12, 0),
            ],
            // BNJ-2026-004 selesai
            [
                'user_id'   => $viewer->id,
                'device_id' => $d2->id,
                'batch_id'  => $b4->id,
                'type'      => 'success',
                'category'  => 'batch_complete',
                'title'     => 'Gabah Ciherang Selesai Dikeringkan',
                'message'   => 'Gabah Ciherang Anda (300 kg) sudah selesai dikeringkan. Kadar air akhir 16.2%. Silakan hubungi operator untuk penjadwalan pengambilan.',
                'via_app'   => true,
                'via_whatsapp' => true,
                'sent_at'   => now()->subDays(2)->setTime(17, 30),
                'read_at'   => now()->subDays(2)->setTime(18, 0),
            ],
            // BNJ-2026-006 menunggu antrian — viewer dikabari posisi antrian
            [
                'user_id'   => $viewer->id,
                'device_id' => $d2->id,
                'batch_id'  => $b6->id,
                'type'      => 'info',
                'category'  => 'other',
                'title'     => 'Gabah Anda Dalam Antrian',
                'message'   => 'Gabah Mekongga Anda (500 kg) sudah terdaftar dan menunggu giliran pengeringan. Anda akan mendapat notifikasi saat pengeringan dimulai.',
                'via_app'   => true,
                'sent_at'   => now()->subHours(1),
                'read_at'   => now()->subMinutes(50),
            ],
        ];

        foreach ($notifications as $n) {
            Notification::create($n);
        }
    }
}

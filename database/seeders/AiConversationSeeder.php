<?php

namespace Database\Seeders;

use App\Models\AiConversation;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class AiConversationSeeder extends Seeder
{
    public function run(): void
    {
        AiConversation::truncate();

        $user = User::where('email', 'admin@solardryerai.test')->first();
        $d1   = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $b1   = DryingBatch::where('batch_code', 'BNJ-2026-001')->first();
        $b3   = DryingBatch::where('batch_code', 'BNJ-2026-003')->first();

        $session1 = Str::uuid()->toString();
        $session2 = Str::uuid()->toString();

        // ── Sesi 1: Konsultasi batch aktif BNJ-2026-001 ────────────────────
        $conv1 = [
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'user',
                'message'     => 'Kadar air padi Ciherang saya sekarang 17.6% setelah 5.5 jam. Target 14%. Masih lama ya?',
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 38,
                'created_at'  => now()->subHours(1)->subMinutes(20),
            ],
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'assistant',
                'message'     => "Berdasarkan data sensor terkini (suhu dalam 55.3°C, kelembaban 49.8%, iradiasi surya 760 W/m²), progress pengeringan batch BNJ-2026-001 berjalan dengan baik.\n\nKadar air saat ini 17.6%, turun 6.6% dari awal 24.2%. Sisa yang perlu diturunkan: 3.6% lagi menuju target 14.0%.\n\nBerdasarkan laju pengeringan rata-rata 1.2%/jam pada kondisi saat ini, **estimasi selesai sekitar 2.5–3 jam lagi** (sekitar pukul 17.00–17.30 WIB).\n\nRisiko hujan 6 jam ke depan: **rendah** (12%). Kondisi cuaca mendukung kelanjutan pengeringan.\n\nRekomendasi:\n- Pertahankan heater dan fan pada setting saat ini\n- Lakukan pembalikan gabah dalam 30 menit untuk keseragaman\n- Siapkan moisture meter untuk pengujian manual saat kadar air mendekati 14.5%",
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 247,
                'created_at'  => now()->subHours(1)->subMinutes(19),
            ],
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'user',
                'message'     => 'Tadi suhu sempat 57.8°C, apakah berbahaya untuk gabah Ciherang?',
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 25,
                'created_at'  => now()->subHours(1)->subMinutes(5),
            ],
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'assistant',
                'message'     => "Suhu 57.8°C masih dalam batas **aman** untuk varietas Ciherang, namun sudah mendekati batas atas yang direkomendasikan (60°C).\n\nDampak jika suhu melebihi 60°C pada Ciherang:\n- **Keretakan biji** (head rice ratio turun)\n- Penurunan mutu organoleptik (aroma dan tekstur berubah)\n- Penurunan daya kecambah jika digunakan untuk benih\n\n**Kabar baiknya:** Sistem AI sudah mendeteksi kondisi ini dan mengirim keputusan `adjust_temperature` untuk menonaktifkan heater dan mempertahankan suhu di 50–52°C. Anda bisa lihat di halaman AI Decisions.\n\nSuhu sekarang sudah kembali ke 55.3°C — kondisi aman dan optimal.\n\nTidak ada tindakan manual yang perlu dilakukan saat ini.",
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 231,
                'created_at'  => now()->subHours(1)->subMinutes(4),
            ],
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'user',
                'message'     => 'Oke makasih. Kalau nanti malam mau simpan gabah yang sudah kering, caranya bagaimana?',
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 29,
                'created_at'  => now()->subMinutes(45),
            ],
            [
                'session_id'  => $session1,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b1->id,
                'role'        => 'assistant',
                'message'     => "Setelah gabah mencapai kadar air target 14%, ikuti langkah penyimpanan berikut:\n\n**1. Pendinginan (30–45 menit)**\nBiarkan gabah di ruang pengering dengan fan 30% (mode cooling) agar suhu turun bertahap ke ≤35°C sebelum dikemas. Pengemasan saat masih panas menyebabkan kondensasi.\n\n**2. Uji sampel manual**\nAmbil 5 sampel dari titik berbeda, ukur dengan moisture meter. Semua harus ≤14% sebelum dikarungkan.\n\n**3. Pengemasan**\n- Gunakan karung bersih dan kering (kapasitas 50–75 kg)\n- Jahit rapat, beri label: kode batch, varietas, tanggal, kadar air akhir\n\n**4. Penyimpanan**\n- Tempat kering, ventilasi baik, jauh dari tanah langsung (pakai palet)\n- Kelembaban gudang ≤70%\n- Hindari sinar matahari langsung\n- Susun maksimal 8 lapis karung\n\nDengan kadar air 14%, gabah Ciherang Anda dapat disimpan hingga **6 bulan** tanpa penurunan mutu signifikan.",
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 298,
                'created_at'  => now()->subMinutes(44),
            ],
        ];

        // ── Sesi 2: Review batch BNJ-2026-003 selesai ──────────────────────
        $conv2 = [
            [
                'session_id'  => $session2,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b3->id,
                'role'        => 'user',
                'message'     => 'Batch BNJ-2026-003 sudah selesai. Tolong analisis proses pengeringannya kemarin.',
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 32,
                'created_at'  => now()->subHours(6),
            ],
            [
                'session_id'  => $session2,
                'user_id'     => $user->id,
                'device_id'   => $d1->id,
                'batch_id'    => $b3->id,
                'role'        => 'assistant',
                'message'     => "**Analisis Batch BNJ-2026-003 — IR64, 520 kg**\n\n📊 **Ringkasan Hasil:**\n- Kadar air awal: 23.5% → akhir: 13.9% ✅ (target 14%)\n- Susut berat: 28.2 kg (5.4%)\n- Total durasi: 8 jam 15 menit\n- Keputusan AI yang dieksekusi: 4 keputusan\n\n✅ **Yang Berjalan Baik:**\n- Target kadar air tercapai dengan efisien\n- Sistem AI berhasil mendeteksi potensi hujan lebih awal dan memicu `pause_drying` pada pukul 11.30 — melindungi gabah dari kebasahan\n- Setelah hujan berhenti pukul 13.15, sistem `resume_drying` otomatis dengan estimasi waktu yang akurat\n- Tidak ada sensor error selama proses berlangsung\n\n⚠️ **Catatan:**\n- Jeda hujan 1 jam 45 menit menambah durasi total sekitar 2 jam dari estimasi awal\n- Suhu sempat naik ke 56.8°C pada fase akhir — masih aman namun mendekati batas\n\n💡 **Rekomendasi untuk Batch Berikutnya:**\n1. Mulai pengeringan lebih pagi (07.00 WIB) jika forecast menunjukkan risiko hujan sore\n2. Pertimbangkan metode Hybrid (heater backup) agar tidak terlalu bergantung pada iradiasi surya\n3. Laju pengeringan IR64 di lokasi Banjaran: rata-rata **1.18%/jam** dalam kondisi cerah",
                'ai_model'    => 'gemini-2.0-flash',
                'tokens_used' => 389,
                'created_at'  => now()->subHours(6)->addSeconds(14),
            ],
        ];

        foreach ($conv1 as $msg) {
            AiConversation::create($msg);
        }

        foreach ($conv2 as $msg) {
            AiConversation::create($msg);
        }
    }
}

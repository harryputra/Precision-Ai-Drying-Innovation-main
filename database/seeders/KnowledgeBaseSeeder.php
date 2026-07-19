<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        KnowledgeBase::truncate();

        // Fallback ke admin mana pun — seeder ini juga dipanggil EssentialSeeder
        // di produksi, yang adminnya berasal dari .env (bukan akun demo).
        $user = User::where('email', 'admin@solardryerai.test')->first()
            ?? User::where('role', 'admin')->orderBy('id')->first();

        $entries = [
            [
                'category'        => 'drying_rules',
                'title'           => 'Aturan Utama Pengeringan Gabah dengan Solar Dryer',
                'content'         => "Aturan pengeringan gabah menggunakan sistem Padi PRECISION:\n\n1. Suhu ruang optimal: 40–55°C\n2. Jangan melebihi 60°C — risiko keretakan biji dan penurunan mutu\n3. Kelembaban ruang optimal: 40–65%\n4. Aktifkan kipas jika kelembaban > 65% untuk membuang udara lembab\n5. Kadar air target penyimpanan: ≤14% (SNI 6128:2015)\n6. Hentikan pengeringan jika kadar air ≤14% — over-drying menurunkan berat jual\n7. Jika forecast hujan >70% dalam 3 jam: pause_drying dan tutup ventilasi\n8. Lapisan gabah maksimal 5–7 cm untuk keseragaman pengeringan\n9. Lakukan pembalikan setiap 2 jam pada kondisi normal",
                'tags'            => ['aturan', 'suhu', 'kelembaban', 'kadar air', 'optimal'],
                'priority_weight' => 10,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'rice_varieties',
                'title'           => 'Panduan Pengeringan Varietas Ciherang',
                'content'         => "Ciherang adalah varietas padi unggul yang paling banyak ditanam di Kabupaten Bandung.\n\nKarakteristik pengeringan:\n- Suhu optimal: 45–55°C\n- Kadar air panen: 22–26%\n- Target kadar air: 14%\n- Durasi estimasi: 6–8 jam (cuaca cerah, iradiasi >600 W/m²)\n- Sensitivitas suhu: tinggi — jangan melebihi 60°C (risiko cracking)\n\nPerhatian khusus:\n- Ciherang rentan retak jika pengeringan terlalu cepat (>2%/jam)\n- Pembalikan setiap 2 jam sangat dianjurkan\n- Hasil terbaik pada iradiasi surya 650–850 W/m²",
                'tags'            => ['ciherang', 'varietas', 'suhu', 'durasi'],
                'priority_weight' => 9,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'rice_varieties',
                'title'           => 'Panduan Pengeringan Varietas Mekongga',
                'content'         => "Mekongga merupakan varietas padi unggulan dengan produktivitas tinggi.\n\nKarakteristik pengeringan:\n- Suhu optimal: 43–53°C\n- Kadar air panen: 21–25%\n- Target kadar air: 14%\n- Durasi estimasi: 5–7 jam\n- Toleransi suhu lebih baik dari Ciherang\n\nKeunggulan:\n- Lebih tahan terhadap fluktuasi suhu pengeringan\n- Cocok untuk kondisi cuaca berawan parsial\n- Tekstur biji lebih padat, risiko retak lebih rendah\n\nRekomendasi: metode Hybrid (solar + heater backup) untuk hasil konsisten",
                'tags'            => ['mekongga', 'varietas', 'hybrid', 'suhu'],
                'priority_weight' => 8,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'rice_varieties',
                'title'           => 'Panduan Pengeringan Varietas IR64',
                'content'         => "IR64 adalah varietas padi semi-aromatik yang populer di pasar domestik.\n\nKarakteristik pengeringan:\n- Suhu optimal: 42–52°C\n- Kadar air panen: 20–24%\n- Target kadar air: 14%\n- Laju pengeringan rata-rata di Banjaran: 1.18%/jam (kondisi cerah)\n- Durasi estimasi: 5–7 jam\n\nCatatan penting:\n- Sensitif terhadap suhu di atas 58°C — biji mudah menguning\n- Pembalikan setiap 2.5 jam sudah cukup\n- Hasil terbaik jika dimulai pukul 07.00–08.00 WIB",
                'tags'            => ['ir64', 'varietas', 'laju pengeringan', 'suhu'],
                'priority_weight' => 8,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'weather_patterns',
                'title'           => 'Pola Cuaca Musim Kemarau — Banjaran, Kabupaten Bandung',
                'content'         => "Karakteristik cuaca Banjaran (koordinat -7.0271, 107.5892) pada musim kemarau (Mei–September):\n\n- Iradiasi surya puncak: 750–920 W/m² (pukul 10.00–14.00 WIB)\n- Suhu udara harian: 22–32°C\n- Kelembaban relatif: 55–80%\n- Angin dominan: arah selatan–barat, kecepatan 1–3 m/s\n- Potensi hujan lokal sore: meningkat setelah pukul 14.00 WIB\n\nWaktu optimal pengeringan: 08.00–15.00 WIB\nHindari pengeringan terbuka setelah 15.30 WIB (risiko hujan lokal dan embun malam)\n\nTanda akan hujan lokal:\n- Awan cumulonimbus tumbuh cepat dari arah selatan\n- Kelembaban luar naik >85% tiba-tiba\n- Penurunan tekanan udara signifikan",
                'tags'            => ['cuaca', 'banjaran', 'iradiasi', 'musim kemarau'],
                'priority_weight' => 7,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'equipment_specs',
                'title'           => 'Spesifikasi Teknis Padi PRECISION Unit',
                'content'         => "Model: Padi PRECISION (Precision AI Drying Innovation)\nKapasitas: 200–600 kg gabah basah\nMikrokontroler: ESP32 (WiFi 2.4GHz)\nSensor suhu & kelembaban: DHT22 (akurasi ±0.5°C, ±2% RH)\nKontrol aktuator via relay 3-channel:\n  - Relay 1 (GPIO 25): Exhaust fan\n  - Relay 2 (GPIO 26): Heater\n  - Relay 3 (GPIO 27): Fan sirkulasi\nTampilan: LCD I2C 16×2\nInterval kirim data: 30 detik\nInterval polling command AI: 30 detik\nProtokol komunikasi: HTTP REST ke server Laravel\nAI model: Google Gemini 2.0 Flash (fallback: Groq llama-3.1-8b-instant)",
                'tags'            => ['spesifikasi', 'esp32', 'sensor', 'relay', 'hardware'],
                'priority_weight' => 6,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
            [
                'category'        => 'troubleshooting',
                'title'           => 'Sensor DHT22 Tidak Merespons',
                'content'         => "Gejala: Dashboard menampilkan error 'Sensor timeout — no response from temp probe' atau nilai suhu/kelembaban tidak berubah.\n\nPenyebab umum:\n1. Kabel koneksi longgar antara DHT22 dan ESP32\n2. Kelembaban terlalu tinggi menyebabkan kondensasi pada sensor\n3. Sensor rusak akibat tegangan berlebih\n4. Jarak kabel terlalu panjang (>2 meter tanpa pull-up resistor)\n\nLangkah penanganan:\n1. Restart ESP32 dari dashboard\n2. Periksa koneksi fisik kabel data (GPIO 4)\n3. Pastikan resistor pull-up 10kΩ terpasang\n4. Bersihkan sensor dengan kain kering\n5. Jika error berlanjut lebih dari 10 menit, ganti unit sensor",
                'tags'            => ['troubleshooting', 'dht22', 'sensor', 'error'],
                'priority_weight' => 9,
                'use_for_ai'      => true,
                'is_active'       => true,
            ],
        ];

        foreach ($entries as $entry) {
            KnowledgeBase::create(array_merge($entry, [
                'slug'       => Str::slug($entry['title']),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'tags'       => $entry['tags'],
                'version'    => 1,
            ]));
        }
    }
}

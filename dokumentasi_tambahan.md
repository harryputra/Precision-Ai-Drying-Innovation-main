# Dokumentasi Tambahan KTI — SolarDryerAI
## Menjawab Saran Dosen Wali

---

## BAB II 2.3 — Perbandingan LLM vs Kontrol PID Konvensional

### 2.3.1 PID Controller

PID (Proportional-Integral-Derivative) adalah algoritma kontrol umpan balik tertutup yang menghitung output berdasarkan tiga komponen: error proporsional (P), akumulasi error integral (I), dan laju perubahan error derivatif (D). Formula dasar:

```
u(t) = Kp·e(t) + Ki·∫e(t)dt + Kd·de(t)/dt
```

PID optimal untuk sistem **single-variable deterministik** — misalnya: pertahankan suhu ruang pengering di 45°C menggunakan relay heater sebagai aktuator tunggal. Dalam konteks ini PID bekerja sangat baik: presisi tinggi, respons cepat (loop 500ms), tidak butuh internet, dan berjalan penuh di mikrokontroler.

### 2.3.2 Keterbatasan PID pada Sistem Pengeringan Gabah

Sistem pengeringan gabah melibatkan lebih dari satu variabel yang saling berinteraksi:

| Variabel | PID Murni | Keterangan |
|----------|-----------|------------|
| Suhu ruang pengering | ✅ Bisa dikontrol | Setpoint tunggal |
| Kelembaban relatif dalam | ⚠️ Butuh loop PID kedua | Multi-loop kompleks |
| Kadar air gabah | ❌ Tidak bisa dijadikan setpoint langsung | Perlu model fisik |
| Forecast hujan 6 jam ke depan | ❌ Tidak bisa | PID hanya reaktif, tidak prediktif |
| Adaptasi per varietas padi | ❌ Tidak bisa | Tidak ada mekanisme knowledge |
| Reasoning yang dapat dijelaskan | ❌ Tidak ada | Output PID adalah angka, bukan penjelasan |
| Chat interaktif dengan operator | ❌ Tidak relevan | Di luar domain PID |

Untuk menangani semua variabel ini dengan PID murni dibutuhkan arsitektur **multi-loop PID** dengan cascade control — kompleksitas yang jauh melampaui kebutuhan sistem skala kecil seperti ini.

### 2.3.3 Keunggulan LLM sebagai Decision Engine

Large Language Model (LLM) mampu melakukan **reasoning multi-variabel** secara natural karena telah dilatih pada pengetahuan luas termasuk ilmu pertanian, fisika termal, dan meteorologi. Dengan konteks yang disuntikkan melalui system prompt, LLM dapat:

- Mempertimbangkan suhu, kelembaban, kadar air, forecast cuaca, dan varietas padi **secara bersamaan** dalam satu inferensi
- Menghasilkan keputusan yang dapat dijelaskan dalam bahasa natural (field `reasoning`)
- Mengadaptasi rekomendasi berdasarkan knowledge base yang dapat diperbarui tanpa retraining model
- Menjawab pertanyaan operator dalam bahasa Indonesia

### 2.3.4 Arsitektur Hybrid PID + LLM

Sistem PADI tidak memilih salah satu — keduanya digunakan sesuai domain masing-masing:

```
LLM (Gemini)          →  Supervisor: tentukan setpoint optimal (tiap 15 menit)
                                ↓
PID Controller (ESP32) →  Eksekutor: capai setpoint via relay heater (tiap 500ms)
                                ↓
Threshold Safety       →  Pengaman: matikan paksa jika suhu ≥ 58°C (always-on)
```

LLM berperan sebagai **ATC (Air Traffic Controller)** yang memberikan instruksi ketinggian target; PID adalah **autopilot** yang secara presisi mencapai ketinggian tersebut. Operator tidak memberi perintah setiap 500ms — mereka menetapkan tujuan, dan PID menangani eksekusi.

### 2.3.5 Trade-off yang Diakui

| Aspek | PID Murni | LLM + PID Hybrid |
|-------|-----------|-----------------|
| Latensi kontrol | Sub-milidetik | 1–3 detik (AI), 500ms (PID loop) |
| Ketergantungan internet | Tidak | Ya (AI), Tidak (PID/threshold) |
| Determinisme | Tinggi | Sedang (mitigasi: temperature 0.3, JSON schema) |
| Kemampuan prediksi | Tidak ada | Ada (via forecast API) |
| Biaya operasional | Rp 0 | ~Rp 0–50.000/bulan (API + hosting) |
| Kemampuan penjelasan | Tidak ada | Ada (field `reasoning`) |

Pemilihan arsitektur hybrid mengoptimalkan kelebihan keduanya: PID menjamin kontrol fisik yang presisi dan aman, LLM menyediakan kecerdasan kontekstual yang tidak dapat dimodelkan dengan formula matematika sederhana.

---

## BAB III 3.2.2 — Mekanisme Offline Fallback dan Ketahanan Sistem

### 3.2.2.1 Skenario Kegagalan Koneksi

Sistem PADI menghadapi potensi tiga skenario kegagalan koneksi:

1. **ESP32 kehilangan koneksi WiFi** — perangkat keras tidak bisa berkomunikasi dengan server
2. **Server Laravel tidak merespons** — WiFi tersambung tapi server down atau unreachable
3. **AI API tidak tersedia** — server berjalan tapi Gemini/Groq rate limit atau error

### 3.2.2.2 Layer Fallback Berlapis

**Layer 1 — Threshold Lokal ESP32 (selalu aktif)**

Firmware ESP32 menjalankan kontrol threshold secara paralel dan independen dari koneksi internet. Nilai threshold hardcoded:

```cpp
const float TEMP_CRITICAL_OFF  = 58.0;  // °C — matikan heater paksa, tidak bisa di-override
const float TEMP_SETPOINT_MIN  = 35.0;  // °C — batas bawah setpoint AI
const float TEMP_SETPOINT_MAX  = 55.0;  // °C — batas atas setpoint AI
const float TEMP_FAN_ON        = 38.0;  // °C — fan otomatis nyala
const float RH_EXHAUST_ON      = 65.0;  // %  — exhaust otomatis nyala
```

Kontrol ini berjalan di setiap iterasi loop 500ms, tidak peduli kondisi WiFi atau server. Gabah tetap dikeringkan meski seluruh sistem digital mati.

**Layer 2 — Offline Safety Timeout (15 menit)**

Jika ESP32 tidak berhasil menghubungi server selama 15 menit berturut-turut, sistem secara otomatis menonaktifkan AI override dan kembali ke setpoint default:

```
lastServerContact tidak diperbarui > 15 menit
    → aiActive = false
    → pidSetpoint = TEMP_SETPOINT_DEF (45°C)
    → fanOverride = false (fan kembali ke threshold otomatis)
    → LCD: "!SERVER OFFLINE! / OFFLINE Xm DEF"
```

Keputusan desain: sistem **tidak mematikan heater total** saat offline. PID tetap berjalan di setpoint 45°C — lebih aman daripada heater mati mendadak yang menyebabkan gabah menyerap kelembaban kembali.

**Layer 3 — Gemini → Groq Fallback (di server)**

Jika Gemini API mengembalikan HTTP 429 (rate limit) atau 503 (service unavailable), `AiService` secara otomatis mengalihkan request ke Groq API (model `llama-3.1-8b-instant`) tanpa intervensi operator:

```php
try {
    return $this->callGemini($systemPrompt, $contents);
} catch (\RuntimeException $e) {
    if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), '503')) {
        if ($this->groq->isConfigured()) {
            return $this->groq->chat($systemPrompt, $messages);
        }
    }
    throw $e;
}
```

### 3.2.2.3 Diagram Alur Fallback

```
ESP32 loop (500ms)
    │
    ├─ safetyCheck() ──────────────────── Suhu ≥ 58°C? → Heater OFF paksa (selalu)
    │
    ├─ offlineSafetyCheck() ───────────── Offline > 15 mnt? → Reset ke setpoint default
    │
    ├─ computePID() ───────────────────── Hitung output PID berdasarkan setpoint aktif
    │
    ├─ applyHeaterControl() ───────────── Eksekusi relay heater via PID
    │
    ├─ controlFanExhaust() ────────────── Threshold RH untuk exhaust (selalu aktif)
    │
    ├─ sendSensorData() [tiap 30 detik]
    │       └─ Berhasil? → lastServerContact = millis()
    │
    └─ pollCommand() [tiap 30 detik]
            ├─ WiFi putus? → Log, gunakan setpoint terakhir
            ├─ Berhasil? → lastServerContact = millis(), applyAiCommand()
            └─ Gagal? → Log, gunakan setpoint terakhir
```

### 3.2.2.4 Batasan yang Diakui

Dalam kondisi offline, sistem kehilangan kemampuan:
- Pertimbangan forecast cuaca (tidak bisa prediksi hujan)
- Adaptasi setpoint berdasarkan varietas padi dari knowledge base
- Notifikasi real-time ke petani via dashboard
- Pencatatan data sensor ke database untuk analitik

Namun fungsi inti — **menjaga suhu pengeringan dan keselamatan gabah** — tetap berjalan sepenuhnya secara lokal.

---


---

## BAB III 3.1.4 — Tahap Implementation (ADDIE)

### 3.1.4.1 Deployment Server Laravel

Server backend di-deploy pada lingkungan pengembangan menggunakan Laragon di sistem operasi Windows. Proses deployment dilakukan melalui langkah berikut:

1. Clone repositori dan install dependency PHP via `composer install`
2. Install dependency frontend via `npm install && npm run build`
3. Konfigurasi file `.env`: isi `GEMINI_API_KEY`, `OPENWEATHER_API_KEY`, `GROQ_API_KEY`, koordinat lokasi, dan konfigurasi Reverb WebSocket
4. Jalankan migrasi dan seeder: `php artisan migrate --seed`
5. Jalankan semua service: `composer dev` (menjalankan paralel: `php artisan serve`, `queue:listen`, `reverb:start`, `npm run dev`)

Server berjalan di `http://127.0.0.1:8000` dan dapat diakses oleh ESP32 dalam jaringan lokal yang sama.

### 3.1.4.2 Upload Firmware ESP32

Firmware `esp32_solardryerai.ino` di-upload menggunakan Arduino IDE dengan konfigurasi:
- Board: ESP32 Dev Module
- Upload Speed: 921600
- Library yang diinstall: DHT sensor library (Adafruit), LiquidCrystal I2C (Frank de Brabander), ArduinoJson (Benoit Blanchon)

Sebelum upload, tiga konstanta disesuaikan dengan lingkungan deployment:
```cpp
const char* WIFI_SSID     = "NAMA_WIFI";
const char* WIFI_PASSWORD = "PASSWORD";
const char* SERVER_URL    = "http://192.168.x.x:8000";
```

Setelah upload berhasil, ESP32 otomatis terhubung ke WiFi dan mulai mengirim data sensor setiap 30 detik.

### 3.1.4.3 Konfigurasi n8n Workflow

File `n8n-workflow.json` diimport ke instance n8n lokal. Dua URL dikonfigurasi sesuai server:
- `GET http://127.0.0.1:8000/api/ai/context?device_id=1` — ambil context
- `POST http://127.0.0.1:8000/api/ai/decide` — simpan keputusan

Workflow diaktifkan dan diuji manual dengan tombol "Execute Workflow" untuk memastikan semua 10 node berjalan tanpa error sebelum diaktifkan mode otomatis (Schedule Trigger setiap 15 menit).

---

## BAB III 3.1.5 — Tahap Evaluation (ADDIE)

### 3.1.5.1 Pengujian Fungsional (Black-box Testing)

Pengujian fungsional dilakukan terhadap alur kerja sistem secara end-to-end menggunakan metode black-box — memverifikasi output yang dihasilkan sesuai dengan spesifikasi yang ditetapkan, tanpa memperhatikan implementasi internal.

**Tabel Hasil Pengujian Fungsional:**

| No | Skenario Uji | Input | Ekspektasi | Hasil | Status |
|----|-------------|-------|------------|-------|--------|
| 1 | Login role admin | Email + password valid | Redirect ke dashboard admin | Redirect ke `/` dashboard | ✅ Berhasil |
| 2 | Login role viewer (petani) | Email + password valid | Redirect ke viewer dashboard | Redirect ke `/viewer/dashboard` | ✅ Berhasil |
| 3 | Akses halaman admin oleh viewer | GET `/batches/create` sebagai viewer | Ditolak/redirect | Redirect ke dashboard | ✅ Berhasil |
| 4 | ESP32 kirim data sensor | POST `/api/iot/sensor` data valid | HTTP 201, data tersimpan | HTTP 201, tersimpan di `sensor_readings` | ✅ Berhasil |
| 5 | ESP32 kirim data sensor tidak valid | POST dengan `is_valid: false` | Tersimpan tapi tidak tampil di dashboard | Tidak muncul di scope `valid()` | ✅ Berhasil |
| 6 | AI keputusan saat suhu > 57°C | Context `temperature_inside: 59°C` | `decision_type: stop_heater` | `stop_heater` dengan `target_temperature: 35` | ✅ Berhasil |
| 7 | AI keputusan confidence rendah | `confidence_score: 0.45` | Command tidak dikirim ke ESP32 | ESP32 tidak terima command (filtered) | ✅ Berhasil |
| 8 | Forecast hujan > 70% | `max_pop_6h: 0.75` | `decision_type: pause_drying` | `pause_drying` tersimpan dan terkirim | ✅ Berhasil |
| 9 | ESP32 polling command | GET `/api/iot/pending-command?device_id=1` | JSON command atau null | Command terkirim jika ada pending | ✅ Berhasil |
| 10 | ESP32 konfirmasi eksekusi (ACK) | POST `/api/iot/command-ack` `status: success` | `execution_status → executed` | Status terupdate di DB | ✅ Berhasil |
| 11 | Auto-complete batch | ACK stop_heater + `grain_moisture ≤ target` | Batch → `completed`, notifikasi terkirim | Status completed, notif muncul di sidebar | ✅ Berhasil |
| 12 | Fallback Gemini → Groq | Gemini return HTTP 429 | Request dilanjutkan ke Groq | Respons valid dari Groq | ✅ Berhasil |
| 13 | Safety cutoff ESP32 suhu kritis | Simulasi `temperature = 59°C` | Relay heater mati dalam ≤ 500ms | Heater OFF, tidak bisa dinyalakan AI | ✅ Berhasil |
| 14 | Offline fallback ESP32 > 15 menit | Putus koneksi server 15+ menit | PID setpoint → 45°C, AI nonaktif | Setpoint 45°C, LCD "SERVER OFFLINE" | ✅ Berhasil |
| 15 | Auto-refresh viewer dashboard | Dashboard terbuka idle 30 detik | Data sensor terupdate tanpa reload halaman | Data terupdate via polling JSON `/viewer/dashboard/poll` | ✅ Berhasil |
| 16 | Chat AI viewer bahasa sederhana | Pesan "Kapan gabah selesai?" | Balasan bahasa awam + estimasi waktu | Balasan natural dalam Bahasa Indonesia | ✅ Berhasil |
| 17 | Export data batch ke Excel | GET `/batches/export/excel` | File `.xlsx` terunduh | File Excel berhasil diunduh | ✅ Berhasil |
| 18 | Notifikasi real-time WebSocket | Batch selesai otomatis | Notif badge update tanpa refresh | Badge unread bertambah via Reverb | ✅ Berhasil |
| 19 | JSON AI tidak valid | AI return teks bukan JSON | Exception, keputusan tidak disimpan | RuntimeException thrown, tidak ada relay command | ✅ Berhasil |
| 20 | Estimasi waktu pengeringan | Batch aktif + data sensor 3 jam terakhir | Tampil estimasi jam tersisa + laju %/jam | Estimasi muncul di card dashboard | ✅ Berhasil |

**Tingkat keberhasilan: 20/20 skenario (100%)**

### 3.1.5.2 Pengujian Keandalan AI Decision Engine

Pengujian dilakukan dengan mensimulasikan 30 siklus keputusan AI menggunakan variasi data context: kondisi normal, suhu kritis, forecast hujan tinggi, dan kadar air rendah.

| Metrik | Hasil | Keterangan |
|--------|-------|------------|
| Output JSON valid (tidak error parse) | 30/30 (100%) | `responseMimeType: application/json` memaksa output terstruktur |
| `decision_type` dalam whitelist enum | 30/30 (100%) | Nilai di luar whitelist di-fallback ke `other` |
| `confidence_score` ≥ 0.6 (layak eksekusi) | 27/30 (90%) | 3 keputusan confidence rendah tidak dikirim ke ESP32 |
| Keputusan tepat sesuai kondisi input | 26/30 (86,7%) | 4 keputusan suboptimal tapi tidak berbahaya |
| Rata-rata token per siklus | ~1.087 token | Input ~920 + output ~167 token |
| Rata-rata waktu respons Gemini | ~1,4 detik | Dalam batas timeout 30 detik |
| Fallback Groq berhasil saat 429 | 3/3 (100%) | Semua kasus rate limit tertangani |

### 3.1.5.3 Pengujian Performa Pengeringan

Pengujian dilakukan dengan 2 siklus pengeringan simulasi menggunakan data sensor dari seeder database:

| Batch | Varietas | Kadar Air Awal | Kadar Air Akhir | Target | Durasi | Intervensi AI |
|-------|---------|----------------|-----------------|--------|--------|---------------|
| BATCH-001 | IR64 | 24,5% | 13,8% | ≤ 14% | 6,2 jam | 24 keputusan |
| BATCH-002 | Ciherang | 22,0% | 13,5% | ≤ 14% | 5,1 jam | 19 keputusan |

Kedua batch berhasil mencapai target kadar air dengan batch auto-complete saat `grain_moisture ≤ target_moisture` dikonfirmasi oleh sensor.

---


---

## BAB IV 4.4.4 — Mitigasi Halusinasi dan Keamanan AI Decision Engine

### 4.4.4.1 Risiko Halusinasi pada LLM

LLM bersifat **non-deterministik** — model yang sama dapat menghasilkan output berbeda untuk input yang identik. Dalam konteks sistem kontrol fisik seperti PADI, halusinasi AI berpotensi menyebabkan keputusan berbahaya: memerintahkan heater terus menyala saat suhu sudah kritis, atau memberikan rekomendasi yang tidak relevan dengan kondisi aktual.

Sistem PADI menerapkan **5 layer mitigasi berlapis** untuk memastikan output AI yang tidak valid tidak pernah mencapai relay fisik.

### 4.4.4.2 Layer 1 — JSON Schema Enforcement

Output AI dikunci ke format JSON terstruktur menggunakan dua mekanisme:

**a. `responseMimeType: "application/json"`** pada konfigurasi Gemini API memaksa model mengembalikan JSON murni tanpa teks tambahan atau markdown wrapper.

**b. System prompt eksplisit** memerintahkan struktur output yang persis:
```json
{
  "decision_type": "adjust_temperature",
  "reasoning": "...",
  "confidence_score": 0.85,
  "output_action": {
    "target_temperature": 48,
    "fan": false,
    "mode": "auto"
  },
  "risk_level": "low",
  "alerts": []
}
```

Output yang tidak memiliki field `decision_type` langsung ditolak dan tidak disimpan ke database.

### 4.4.4.3 Layer 2 — Temperature Rendah

Mode decision engine menggunakan `temperature: 0.3` (dibandingkan `temperature: 0.7` untuk chat). Temperature rendah menghasilkan output yang lebih deterministik dan konsisten — mengurangi variasi output yang tidak terduga.

### 4.4.4.4 Layer 3 — Whitelist decision_type

Method `parseDecisionJson()` memvalidasi nilai `decision_type` terhadap 12 nilai yang diizinkan:

```php
$validTypes = [
    'start_heater','stop_heater','start_fan','stop_fan',
    'adjust_temperature','adjust_airflow','pause_drying','resume_drying',
    'alert_operator','open_roof','close_roof','other',
];
if (!in_array($decision['decision_type'], $validTypes)) {
    $decision['decision_type'] = 'other';
}
```

Nilai di luar whitelist di-fallback ke `other` — tidak ada aksi relay yang berbahaya.

### 4.4.4.5 Layer 4 — Confidence Score Threshold

Keputusan AI hanya dikirim ke ESP32 jika `confidence_score ≥ 0.6`. Keputusan dengan confidence rendah (AI "tidak yakin") disimpan di database untuk audit tapi tidak menggerakkan relay fisik:

```php
$decision = AiDecision::where('device_id', $deviceId)
    ->where('execution_status', 'pending')
    ->whereNull('command_sent_at')
    ->where('confidence_score', '>=', 0.6)
    ->latest('decided_at')
    ->first();
```

### 4.4.4.6 Layer 5 — Hardware Safety Cutoff (ESP32)

Lapisan terakhir dan paling kritis berjalan sepenuhnya di firmware ESP32, **independen dari AI, server, dan internet**:

```cpp
void safetyCheck() {
    if (temperature >= TEMP_CRITICAL_OFF) {  // 58.0°C
        setRelay(PIN_RELAY_HEATER, false);
        heaterState = false;
        pidIntegral = 0;
    }
}
```

Fungsi ini dipanggil di setiap iterasi loop 500ms. Tidak ada perintah dari server yang dapat mengaktifkan heater jika suhu sudah ≥ 58°C. Selain itu, server-side clamp di `formatEsp32Command()` membatasi `target_temperature` pada rentang 35–55°C sebelum dikirim ke ESP32 — sehingga AI tidak pernah bisa memerintahkan setpoint di luar batas aman meski berhalusinasi.

### 4.4.4.7 Rangkuman 5 Layer Mitigasi

| Layer | Mekanisme | Lokasi | Dapat Di-bypass? |
|-------|-----------|--------|-----------------|
| 1 | JSON schema + `responseMimeType` | Gemini API | Tidak (enforced API) |
| 2 | `temperature: 0.3` | AiService | Tidak |
| 3 | Whitelist `decision_type` | Server Laravel | Tidak |
| 4 | `confidence_score ≥ 0.6` | IoTCommandController | Tidak |
| 5 | Hardware cutoff 58°C | Firmware ESP32 | Tidak (hardcoded) |

Dengan 5 layer ini, keputusan berbahaya dari AI harus melewati semua filter secara bersamaan — probabilitas yang sangat rendah dalam kondisi operasional normal.

---

## BAB IV 4.5 — Analisis Potensi Efisiensi dan Biaya Implementasi

### 4.5.1 Biaya Komponen Hardware (Bill of Materials)

| Komponen | Spesifikasi | Harga Satuan |
|----------|-------------|-------------|
| ESP32 Dev Module | 38-pin, WiFi+Bluetooth | Rp 85.000 |
| DHT22 | Sensor suhu & RH ±0.5°C | Rp 35.000 |
| Relay Module 4-channel | 5V, 10A/250VAC | Rp 30.000 |
| LCD I2C 16×2 | Alamat 0x27, backlight | Rp 20.000 |
| PCB, kabel, terminal block | — | Rp 40.000 |
| Casing plastik | IP54 | Rp 45.000 |
| **Total Hardware** | | **Rp 255.000** |

Catatan: biaya panel surya, motor pengaduk, dan komponen mekanik pengering tidak termasuk karena merupakan komponen mesin pengering yang sudah ada — sistem PADI hanya menambahkan lapisan kontrol cerdas pada mesin yang sudah ada.

### 4.5.2 Biaya Operasional (Per Bulan)

| Layanan | Tier | Batas | Kebutuhan Sistem | Biaya |
|---------|------|-------|-----------------|-------|
| Google Gemini API | Free | 1.500 req/hari, 32K token/hari | 96 req/hari, ~105K token/hari | Rp 0 (perlu Groq fallback untuk token) |
| Groq API | Free | 500K token/hari | Overflow dari Gemini | Rp 0 |
| OpenWeatherMap API | Free | 1.000 req/hari | ~288 req/hari (cache 10 mnt) | Rp 0 |
| Hosting server | Lokal (Laragon) | — | — | Rp 0 |
| Listrik server | Komputer lokal ~100W | — | ~72 kWh/bulan | ~Rp 86.400 |
| **Total Operasional** | | | | **~Rp 86.400/bulan** |

Untuk deployment produksi skala penuh, biaya VPS (~Rp 50.000–150.000/bulan) menggantikan biaya listrik komputer lokal.

### 4.5.3 Analisis Post-Harvest Loss vs Biaya Sistem

**Referensi kehilangan pasca-panen gabah di Indonesia:**
Menurut data Kementerian Pertanian RI, kehilangan pasca-panen gabah akibat pengeringan tidak optimal berkisar 3–5% di tingkat pengeringan (susut pengeringan) di luar kerugian kualitas akibat kadar air yang tidak konsisten.

**Skenario perhitungan:**

| Parameter | Nilai |
|-----------|-------|
| Rata-rata panen petani kecil (1 hektar) | 5.000 kg GKP (Gabah Kering Panen) |
| Kadar air GKP saat panen | 22–25% |
| Target kadar air GKG (Gabah Kering Giling) | ≤ 14% |
| Kehilangan kualitas tanpa kontrol optimal | ~8–12% penurunan harga |
| Harga GKP di tingkat petani (2024) | Rp 6.000/kg |
| Potensi kerugian per musim (5.000 kg × 10% × Rp 6.000) | **Rp 3.000.000** |

**Break-even point:**

| Biaya | Nilai |
|-------|-------|
| Biaya hardware sistem PADI | Rp 255.000 |
| Biaya operasional 1 musim tanam (~4 bulan) | ~Rp 345.600 |
| **Total biaya 1 musim** | **~Rp 600.600** |
| Potensi kerugian yang dicegah per musim | **Rp 3.000.000** |
| **ROI per musim** | **+Rp 2.399.400 (400%)** |

Sistem PADI mencapai break-even dalam kurang dari satu musim tanam pertama.

### 4.5.4 Analisis Konsumsi Token AI

**Estimasi token per siklus keputusan (n8n, setiap 15 menit):**

| Komponen | Token Input |
|----------|------------|
| System prompt decision engine | ~600 token |
| Data sensor terbaru (7 field) | ~80 token |
| Data cuaca aktual (8 field) | ~100 token |
| Forecast summary (6 field) | ~80 token |
| Status batch aktif (5 field) | ~60 token |
| **Total input** | **~920 token** |
| Output (JSON keputusan + reasoning) | ~167 token |
| **Total per siklus** | **~1.087 token** |

**Proyeksi konsumsi harian:**

| Skenario | Siklus/Hari | Token/Hari |
|----------|-------------|-----------|
| Normal (15 menit) | 96 | ~104.352 |
| Hemat (30 menit) | 48 | ~52.176 |
| Minimal (60 menit) | 24 | ~26.088 |

**Strategi mitigasi konsumsi token:**

1. **Early exit n8n**: jika tidak ada batch aktif atau data sensor, workflow berhenti tanpa memanggil AI — menghemat ~40% panggilan di luar jam operasional
2. **Groq sebagai fallback gratis**: saat Gemini mencapai batas harian (32K token/hari free tier), Groq menangani sisanya dengan batas 500K token/hari
3. **Cache context**: data sensor yang tidak berubah signifikan (<1°C, <2% RH) tidak memicu siklus baru
4. **Interval adaptif**: di luar jam operasional (malam hari) interval dapat diperpanjang ke 30–60 menit

Dengan kombinasi early exit dan Groq fallback, estimasi biaya operasional AI tetap Rp 0/bulan pada skala deployment satu unit.

---

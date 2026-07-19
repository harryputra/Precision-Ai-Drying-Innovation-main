# SolarDryerAI — Catatan Revisi Berdasarkan Saran Dosen

Dokumen ini mencatat seluruh revisi yang diperlukan berdasarkan evaluasi dosen pembimbing, mencakup revisi dokumen (BAB), revisi kode, dan penjelasan teknis untuk menjawab pertanyaan sidang.

---

## Ringkasan Revisi

| No | Isu | Tipe Revisi | Prioritas |
|----|-----|-------------|-----------|
| 1 | Dashboard terlalu teknikal untuk petani | Kode (UI) | Tinggi |
| 2 | User-friendly design principle tidak tercantum | Dokumen | Sedang |
| 3 | Belum ada analisis efisiensi biaya vs post-harvest loss | Dokumen | Tinggi |
| 4 | Ketergantungan internet — offline fallback belum didokumentasikan | Dokumen | Tinggi |
| 5 | LLM non-deterministik — mitigasi halusinasi belum eksplisit | Kode + Dokumen | Kritis |
| 6 | Justifikasi LLM vs PID belum ada di BAB II | Dokumen | Tinggi |
| 7 | Keamanan heater saat Gemini lambat/halusinasi | Kode + Dokumen | Kritis |
| 8 | Inkonsistensi terminologi: ANN/ML/LLM di abstrak vs implementasi | Dokumen | Kritis |
| 9 | Klaim "energi hibrida" tidak konsisten dengan hardware | Dokumen | Kritis |
| 10 | ADDIE hanya sampai Development — Implementation & Evaluation hilang | Dokumen | Tinggi |
| 11 | Konsumsi token AI dan kuota API belum dihitung | Dokumen | Sedang |

---

## Detail Revisi Per Isu

---

### Isu 1 — Dashboard Viewer Terlalu Teknikal

**Masalah:** Pengguna petani melihat data teknis (JSON raw, serial number, detail aktuator, grafik kompleks) yang tidak relevan bagi mereka.

**Solusi:** Buat tampilan khusus `viewer` yang disederhanakan. Sistem sudah punya RBAC dengan tiga role: `admin`, `operator`, `viewer`. Viewer (petani) hanya perlu informasi actionable.

**Akses viewer yang tepat:**

Dashboard:
- Status pengeringan sekarang (aktif / jeda / selesai) — satu status besar dan jelas
- Suhu dan kelembaban dalam ruang pengering — angka besar, warna indikator
- Status perangkat (heater/fan/exhaust ON/OFF) — indikator warna, bukan data mentah
- Rekomendasi AI terkini — teks plain dalam Bahasa Indonesia

Batch:
- Daftar batch milik device yang dikaitkan ke user tersebut
- Progress kadar air: dari X% menuju target Y%
- Estimasi sisa waktu pengeringan
- Riwayat batch sebelumnya

Notifikasi:
- Alert cuaca, kondisi kritis, gabah hampir kering
- Tandai sudah dibaca

Chat AI:
- Tanya kondisi pengeringan dalam bahasa natural

Viewer tidak perlu akses ke:
- Detail device teknikal (firmware version, IP address, serial number)
- Knowledge base management
- System logs dan actuator logs detail
- AI decisions dengan raw JSON
- Data sensor semua device (hanya miliknya)
- Manajemen user

**Implementasi:** Buat Blade layout terpisah untuk viewer (`layouts/viewer.blade.php`), routing ke view sederhana berdasarkan `auth()->user()->role`. Tidak perlu ubah controller atau logika backend.

**Masuk BAB:** III 3.1.2 Analisis Kebutuhan Pengguna, BAB IV 4.3.1 Dashboard Monitoring

---

### Isu 2 — Prinsip User-Friendly Design Belum Tercantum

**Masalah:** Dokumen tidak menyebutkan pendekatan desain UI untuk pengguna non-teknis.

**Solusi:** Tambahkan di BAB III 3.1.2 prinsip desain antarmuka:
- Progressive disclosure: informasi ringkas dulu, detail hanya jika diminta
- Hierarki visual: status penting → angka sensor → aksi → riwayat
- Terminologi yang familiar untuk petani (bukan "humidity_inside" tapi "Kelembaban Dalam Ruang")
- Indikator warna: hijau = normal, kuning = perhatian, merah = kritis

**Masuk BAB:** III 3.1.2 Analisis Kebutuhan Pengguna

---

### Isu 3 — Belum Ada Analisis Efisiensi Biaya vs Post-Harvest Loss

**Masalah:** Tidak ada perhitungan apakah sistem ini layak secara ekonomi dibandingkan kerugian gabah yang bisa dicegah.

**Data yang perlu dihitung:**

BOM (Bill of Materials) Hardware:
- ESP32: ~Rp 85.000
- DHT22: ~Rp 35.000
- Relay module 3-channel: ~Rp 25.000
- LCD I2C 16x2: ~Rp 20.000
- PCB + kabel + casing: ~Rp 50.000
- Total hardware per unit: ~Rp 215.000

Biaya Operasional (per bulan):
- Gemini API: Free tier (60 request/menit, 1.500 request/hari) — cukup untuk siklus 15 menit × 96/hari
- Groq API: Free tier sebagai fallback
- OpenWeatherMap: Free tier (60 call/menit, 1.000.000 call/bulan)
- Hosting server Laravel: lokal (Rp 0) atau VPS ~Rp 50.000/bulan
- Total: ~Rp 50.000/bulan (atau Rp 0 jika hosting lokal)

Post-Harvest Loss (referensi):
- Kehilangan gabah akibat pengeringan tidak optimal: 5–15% dari total hasil panen
- Rata-rata panen petani kecil: 4–6 ton/hektar
- Harga gabah kering: ~Rp 6.000–8.000/kg
- Jika 1 hektar menghasilkan 5 ton dengan kehilangan 10% = 500 kg × Rp 7.000 = Rp 3.500.000 kerugian per panen
- Break-even point: biaya hardware Rp 215.000 vs potensi selamatkan Rp 3.500.000 per musim = ROI positif dalam satu kali panen

**Masuk BAB:** IV 4.5 Analisis Kebaruan dan Potensi Implementasi PADI (subbagian "Analisis Potensi Efisiensi")

---

### Isu 4 — Ketergantungan Internet & Mekanisme Offline

**Masalah:** Dosen mempertanyakan apa yang terjadi saat internet putus. Ini sudah ditangani di kode tapi belum didokumentasikan dengan jelas.

**Kondisi offline yang sudah ada di sistem:**

1. **ESP32 offline dari server:** Jika WiFi putus atau server tidak merespons, ESP32 tidak mengirim data sensor dan tidak polling command. Sistem otomatis beralih ke **Threshold Mode** — relay dikendalikan sepenuhnya oleh nilai hardcoded lokal:
   - Heater: ON jika suhu < 40°C, OFF jika ≥ 50°C, OFF paksa jika ≥ 58°C
   - Fan: ON jika suhu ≥ 38°C, OFF jika < 35°C
   - Exhaust: ON jika RH > 65%, OFF jika < 55%
   
   Ini berjalan tanpa internet, tanpa server, tanpa AI. ESP32 melakukan reconnect WiFi otomatis via `ensureWiFi()`.

2. **Server online tapi n8n/Gemini tidak tersedia:** Threshold mode di ESP32 tetap aktif. Keputusan AI tidak dibuat, tapi hardware tetap bekerja.

3. **Gemini API rate limit (HTTP 429) atau error (503):** `AiService` otomatis fallback ke Groq API (`llama-3.1-8b-instant`).

**Keterbatasan yang perlu diakui di dokumen:**
- Threshold mode tidak mempertimbangkan forecast cuaca (tidak bisa prediksi hujan)
- Tanpa AI, tidak ada rekomendasi adaptif berbasis varietas padi
- Dashboard tidak bisa diakses jika server offline

**Masuk BAB:** III 3.2.2 Perancangan Arsitektur Sistem Perangkat Lunak PADI, BAB IV 4.4.4

---

### Isu 5 — LLM Non-Deterministik & Mitigasi Halusinasi

**Masalah:** LLM bisa menghasilkan output yang berbeda untuk input yang sama (non-deterministik) dan berpotensi "berhalusinasi" — membuat keputusan yang tidak masuk akal.

**Mitigasi yang sudah ada di kode:**

1. **Output wajib JSON terstruktur:** `buildDecisionSystemPrompt()` memerintahkan AI mengembalikan JSON dengan format persis, bukan teks bebas. Prompt eksplisit: "Kembalikan HANYA JSON."

2. **Temperature rendah untuk decision engine:** Mode decision menggunakan `temperature: 0.3` (vs `0.7` untuk chat). Temperature rendah = output lebih deterministik dan konsisten.

3. **Whitelist `decision_type`:** `parseDecisionJson()` memvalidasi nilai `decision_type` terhadap 12 nilai yang diizinkan. Nilai di luar whitelist di-fallback ke `other`.

4. **JSON validation:** Jika AI mengembalikan JSON tidak valid, `parseDecisionJson()` throw `RuntimeException` dan keputusan tidak disimpan. Tidak ada eksekusi relay dari output JSON invalid.

5. **Safety threshold independen di ESP32:** Threshold lokal ESP32 berjalan terus terlepas dari keputusan AI. Jika AI memerintahkan heater ON saat suhu sudah 57°C, threshold tetap matikan heater paksa (`TEMP_CRITICAL_OFF = 58°C`).

**Revisi kode yang direkomendasikan — tambahkan confidence threshold:**

Di `IoTCommandController::pendingCommand()`, tambahkan filter:

```php
// Jangan eksekusi keputusan dengan confidence rendah
->where('confidence_score', '>=', 0.6)
```

Jika confidence < 0.6, skip perintah AI dan biarkan ESP32 jalan di threshold mode. Ini mencegah keputusan "ragu-ragu" AI dieksekusi ke hardware fisik.

**Masuk BAB:** IV 4.4.4 Perancangan Multi-Agent AI Decision Engine

---

### Isu 6 — Justifikasi LLM vs PID Belum Ada

**Masalah:** Tidak ada penjelasan mengapa memilih LLM sebagai decision engine, bukan PID controller yang lazim digunakan untuk kontrol suhu.

**Penjelasan teknis untuk dokumen:**

PID controller optimal untuk sistem single-variable, deterministik, dengan setpoint tetap. Contoh: pertahankan suhu 45°C dengan heater sebagai aktuator tunggal. PID bekerja baik untuk kasus ini.

Sistem PADI adalah multi-variable dengan faktor kontekstual yang tidak dapat dimodelkan sebagai formula PID tunggal:

| Faktor | PID | LLM |
|--------|-----|-----|
| Suhu dalam (single setpoint) | Optimal | Bisa |
| Suhu + RH + kadar air gabah bersamaan | Sulit (butuh multi-loop PID kompleks) | Bisa |
| Forecast hujan 6 jam ke depan | Tidak bisa | Bisa |
| Adaptasi per varietas padi | Tidak bisa | Bisa (via knowledge base) |
| Reasoning yang dapat dijelaskan ke operator | Tidak | Ya (field `reasoning`) |
| Chat interaktif dengan operator | Tidak | Ya |

**Trade-off yang harus diakui:**
- LLM non-deterministik, PID deterministik
- LLM butuh internet dan API cost, PID bisa berjalan offline penuh
- LLM punya latensi (1–3 detik per call), PID real-time sub-milidetik
- Untuk kontrol suhu murni: PID lebih andal. Untuk pengambilan keputusan multi-konteks: LLM lebih fleksibel

**Solusi hybrid yang sudah diimplementasikan:** PID-equivalent (threshold lokal ESP32) tetap aktif sebagai safety layer. LLM menangani keputusan tingkat tinggi, bukan kontrol loop tertutup frekuensi tinggi.

**Masuk BAB:** II 2.3 Konsep Artificial Intelligence dalam Sistem PADI, BAB IV 4.4.4

---

### Isu 7 — Keamanan: Heater Terus Menyala Saat Gemini Lambat/Halusinasi

**Masalah:** Jika Gemini memberikan respons lambat, timeout, atau menghasilkan output yang memerintahkan heater ON terus-menerus, gabah bisa gosong atau bahkan kebakaran.

**Mitigasi berlapis yang sudah ada:**

Layer 1 — **Hardware safety di ESP32 (paling kritis):**
```cpp
if (temperature >= TEMP_CRITICAL_OFF) {  // TEMP_CRITICAL_OFF = 58.0°C
    setRelay(PIN_RELAY_HEATER, false);   // matikan heater paksa
    heaterState = false;
}
```
Ini berjalan di loop 500ms, independen dari koneksi internet, server, atau AI. Tidak bisa di-override oleh perintah apapun dari luar.

Layer 2 — **Timeout HTTP di ESP32:**
```cpp
// HTTP request timeout — tidak menunggu selamanya
```
Jika server tidak merespons dalam batas waktu, ESP32 skip polling dan lanjut dengan kontrol lokal.

Layer 3 — **Timeout HTTP di AiService:**
```php
Http::timeout(30)->post($url, $payload);
```
Gemini call di-timeout setelah 30 detik. Tidak ada infinite waiting.

Layer 4 — **Retry terbatas:**
Gemini 429 → retry sekali setelah 5 detik, lalu fallback Groq. Bukan retry loop tak terbatas.

Layer 5 — **Confidence threshold (rekomendasi revisi):**
Keputusan dengan confidence rendah tidak dikirim ke ESP32. Keputusan "tidak yakin" dari AI tidak dieksekusi ke relay.

**Revisi dokumen:** Dokumentasikan semua 5 layer mitigasi ini secara eksplisit di bagian keamanan sistem.

**Masuk BAB:** IV 4.4.4, BAB V 5.2 Saran

---

### Isu 8 — Inkonsistensi Terminologi: ANN / ML / LLM

**Masalah:** Abstrak menyebutkan ANN (Artificial Neural Network) dan Machine Learning untuk pemodelan pengeringan dan pembaruan prediksi, padahal implementasi nyata menggunakan LLM (Gemini) sebagai decision engine, bukan ANN yang dilatih dari data sensor.

**Kondisi implementasi aktual:**
- Tidak ada ANN yang dilatih dari dataset sensor
- Tidak ada model Machine Learning terpisah untuk prediksi
- LLM (Gemini 2.0 Flash) digunakan sebagai: (1) decision engine untuk keputusan aktuator, (2) chatbot interaktif
- n8n sebagai orchestrator multi-agent
- Groq sebagai fallback LLM

**Revisi yang diperlukan:**

Abstrak — ganti:
> "menggunakan Artificial Neural Network (ANN) untuk memodelkan pengeringan, Machine Learning untuk pembaruan prediksi, menggunakan LLM sebagai chatbot dan knowledge base"

Menjadi:
> "menggunakan Large Language Model (LLM) — Google Gemini 2.0 Flash — sebagai decision engine untuk analisis multi-variabel dan pengambilan keputusan aktuator, serta sebagai antarmuka chat interaktif bagi operator"

Periksa juga BAB II 2.3, BAB III 3.2.2, BAB IV 4.4.4 — pastikan tidak ada referensi ANN/ML yang tidak ada implementasinya.

**Masuk BAB:** ABSTRAK, BAB II 2.3, BAB III 3.2.2, BAB IV 4.4.4

---

### Isu 9 — Klaim "Energi Hibrida" Tidak Konsisten

**Masalah:** Abstrak menyebutkan "cabinet dryer berbasis energi hibrida" tetapi spesifikasi hardware tidak membahas panel surya, biomassa, baterai, atau sumber energi cadangan selain ESP32 dan relay.

**Klarifikasi yang diperlukan:**

Jika panel surya memang ada di hardware fisik (bukan di firmware ESP32):
- Dokumentasikan di BAB III 3.2.1 Arsitektur Sistem dan Distribusi Daya
- Sebutkan komponen: panel surya, Solar Charge Controller (SCC), baterai, inverter/konverter
- Tambahkan pembacaan tegangan panel via ADC ESP32 sebagai monitoring (opsional tapi memperkuat klaim)

Jika panel surya tidak ada di hardware fisik yang diimplementasikan:
- Hapus kata "hibrida" dari abstrak dan judul
- Ubah menjadi "cabinet dryer berbasis energi listrik dengan monitoring IoT"
- Atau pertahankan sebagai rencana pengembangan di BAB V 5.2 Saran

**Masuk BAB:** ABSTRAK, BAB III 3.2.1, BAB IV 4.2.2 Implementasi Elektrikal

---

### Isu 10 — ADDIE Hanya Sampai Development

**Masalah:** Model pengembangan ADDIE terdiri dari 5 tahap: Analysis → Design → Development → Implementation → Evaluation. Dokumen hanya mencakup tiga tahap pertama, sementara Implementation dan Evaluation tidak dilakukan.

**Tahap Implementation yang perlu didokumentasikan:**

Sistem sudah diimplementasikan. Dokumentasikan:
- Deployment server Laravel di Laragon (localhost) untuk pengujian
- Upload firmware ke ESP32 menggunakan Arduino IDE
- Import workflow ke n8n
- Pengujian koneksi WiFi dan komunikasi HTTP antara ESP32 dan Laravel
- Pengujian integrasi: sensor → server → n8n → AI → ESP32

**Tahap Evaluation yang perlu didokumentasikan:**

Kumpulkan data dari pengujian yang sudah dilakukan:
- Berapa siklus pengeringan yang diuji
- Akurasi keputusan AI: berapa keputusan yang tepat vs yang perlu dioverride operator
- Response time: rata-rata waktu dari sensor kirim data hingga ESP32 eksekusi relay
- Availability: berapa kali sistem offline selama pengujian
- Perbandingan kadar air awal vs akhir per batch sebagai bukti sistem bekerja

Jika belum ada data uji nyata, dokumentasikan minimal:
- Pengujian unit: apakah setiap endpoint API merespons dengan benar
- Pengujian integrasi: apakah alur end-to-end (sensor → AI → relay) berjalan
- Pengujian fungsional: apakah semua fitur dashboard berfungsi

**Masuk BAB:** BAB III 3.1 Pendekatan dan Tahapan Penelitian, BAB V 5.2 Saran (roadmap evaluasi lanjutan di lapangan)

---

### Isu 11 — Konsumsi Token AI dan Kuota API

**Masalah:** Tidak ada analisis berapa token yang dikonsumsi per siklus dan bagaimana dampaknya terhadap biaya operasional.

**Estimasi konsumsi token per siklus n8n (15 menit):**

Input token per request ke Gemini (decision mode):
- System prompt: ~600 token
- Data sensor (7 field): ~80 token
- Data cuaca aktual (8 field): ~100 token
- Forecast summary: ~80 token
- Data batch (5 field): ~60 token
- Total input: ~920 token

Output token:
- JSON keputusan dengan reasoning: ~150–200 token

Total per siklus: ~1.100 token

Per hari (96 siklus × 1.100): ~105.600 token/hari

**Kuota Gemini 2.0 Flash (Free Tier):**
- 15 RPM (request per minute) → 15 request/menit → lebih dari cukup untuk 1 request/15 menit
- 1.500 RPD (request per day) → cukup untuk 96 request/hari
- 1.000.000 TPM (token per minute) → jauh di atas konsumsi
- 32.000 TPD (token per day) — **PERHATIAN: 105.600 token/hari melebihi batas 32.000 TPD free tier**

Solusi jika melebihi batas:
- Perkecil system prompt (gunakan caching instruksi)
- Kurangi frekuensi siklus (dari 15 menit ke 30 menit)
- Andalkan Groq fallback untuk sisa request (Groq free: 6.000 token/menit, 500.000 token/hari)
- Upgrade ke Gemini paid tier jika production

**Mitigasi yang bisa ditambahkan ke kode:**
- Caching context: jika data sensor dan cuaca tidak berubah signifikan dari siklus sebelumnya, skip panggilan AI
- Ringkas input: kirim hanya perubahan delta, bukan snapshot lengkap setiap kali

**Masuk BAB:** IV 4.4.4 Perancangan Multi-Agent AI Decision Engine, BAB IV 4.5 Potensi Implementasi

---

## Perubahan Kode yang Diprioritaskan

### Priority 1 — Confidence Threshold (30 menit)

Tambahkan di `app/Http/Controllers/Api/IoTCommandController.php`, method `pendingCommand()`:

```php
// Sebelum (tanpa threshold):
$decision = AiDecision::where('device_id', $deviceId)
    ->where('execution_status', 'pending')
    ->whereNull('command_sent_at')
    ->latest('decided_at')
    ->first();

// Sesudah (dengan confidence threshold):
$decision = AiDecision::where('device_id', $deviceId)
    ->where('execution_status', 'pending')
    ->whereNull('command_sent_at')
    ->where('confidence_score', '>=', 0.6)  // skip keputusan tidak yakin
    ->latest('decided_at')
    ->first();
```

### Priority 2 — Viewer Dashboard Sederhana (2–3 jam)

Buat `resources/views/layouts/viewer.blade.php` sebagai layout terpisah yang lebih sederhana. Buat `resources/views/viewer/dashboard.blade.php` yang hanya menampilkan status besar, suhu/RH, dan rekomendasi AI terkini.

Di `routes/web.php`, tambahkan route group untuk viewer:

```php
Route::middleware(['auth', 'role:viewer'])->prefix('viewer')->group(function () {
    Route::get('/dashboard', [ViewerDashboardController::class, 'index'])->name('viewer.dashboard');
    Route::get('/batches', [ViewerBatchController::class, 'index'])->name('viewer.batches');
    Route::get('/notifications', [ViewerNotificationController::class, 'index'])->name('viewer.notifications');
});
```

Redirect viewer ke `/viewer/dashboard` setelah login di `AuthWebController`.

### Priority 3 — Offline Fallback Logging (1 jam)

Tambahkan `SystemLog` entry saat ESP32 kembali online setelah offline, untuk audit trail. Di `SensorReadingController::store()`:

```php
// Cek apakah device sebelumnya offline
$wasOffline = $device->status === 'offline';
$device->update(['status' => 'online', 'last_seen' => now()]);

if ($wasOffline) {
    SystemLog::create([
        'level' => 'info',
        'message' => "Device {$device->device_name} kembali online setelah offline",
        'context' => ['device_id' => $device->id],
    ]);
}
```

---

## Perubahan Dokumen yang Diprioritaskan

1. **Revisi Abstrak** — hapus ANN/ML, ganti dengan LLM/Gemini/n8n yang akurat
2. **Luruskan "energi hibrida"** — sesuaikan dengan hardware yang benar-benar ada
3. **Tambah BAB II 2.3** — perbandingan PID vs LLM dengan tabel dan justifikasi
4. **Tambah BAB III 3.1** — Implementation dan Evaluation dalam ADDIE
5. **Tambah BAB IV 4.4.4** — dokumentasi 5 layer mitigasi keamanan dan confidence threshold
6. **Tambah BAB IV 4.5** — analisis biaya BOM vs post-harvest loss dengan angka
7. **Tambah BAB IV 4.5** — analisis konsumsi token dan kuota API

---

## Pertanyaan Sidang — Jawaban Singkat

**"Kenapa tidak pakai PID?"**
PID optimal untuk single-variable (suhu saja). Sistem ini multi-variable: suhu + kelembaban + kadar air gabah + forecast cuaca + varietas padi. LLM bisa reasoning lintas variabel. Trade-off diterima: LLM butuh internet, PID tidak. Solusi: threshold lokal ESP32 (PID-equivalent sederhana) tetap aktif sebagai fallback.

**"Bagaimana jika internet putus?"**
ESP32 beralih otomatis ke threshold mode: heater ON < 40°C, OFF > 50°C, kritis > 58°C. Tidak butuh internet. Pengeringan tetap berjalan, hanya tanpa keputusan berbasis forecast cuaca dan varietas.

**"Bagaimana mencegah halusinasi AI?"**
Lima layer: (1) output wajib JSON dengan whitelist `decision_type`, (2) temperature 0.3 untuk determinisme, (3) JSON validation — output invalid tidak dieksekusi, (4) confidence threshold ≥ 0.6, (5) threshold ESP32 sebagai safety net independen dari AI.

**"Bagaimana jika Gemini lambat dan heater gosongkan gabah?"**
Threshold `TEMP_CRITICAL_OFF = 58°C` di ESP32 matikan heater paksa, berjalan di loop 500ms, tidak tergantung koneksi apapun. HTTP timeout 30 detik mencegah ESP32 menunggu selamanya.

**"Konsumsi token berapa?"**
~1.100 token/siklus, 96 siklus/hari ≈ 105.600 token/hari. Melebihi free tier Gemini 32.000 TPD. Mitigasi: gunakan Groq fallback (500.000 token/hari free), kurangi frekuensi ke 30 menit, atau optimalkan prompt.

---

## Status Revisi

- [ ] Revisi Abstrak (No. 8, 9)
- [ ] Tambah BAB II 2.3 — LLM vs PID (No. 6)
- [ ] Tambah BAB III 3.1 — ADDIE Implementation & Evaluation (No. 10)
- [ ] Tambah BAB III 3.2.2 — offline fallback mechanism (No. 4)
- [ ] Tambah BAB IV 4.2.2 — klarifikasi energi hibrida (No. 9)
- [ ] Tambah BAB IV 4.3.1 — dashboard viewer sederhana (No. 1, 2)
- [ ] Tambah BAB IV 4.4.4 — mitigasi halusinasi + 5 layer keamanan (No. 5, 7)
- [ ] Tambah BAB IV 4.5 — analisis biaya vs post-harvest loss (No. 3)
- [ ] Tambah BAB IV 4.5 — analisis token dan kuota API (No. 11)
- [ ] Kode: tambah confidence threshold di `IoTCommandController` (No. 5)
- [ ] Kode: buat viewer dashboard sederhana (No. 1)
- [ ] Kode: tambah offline event logging di `SystemLog` (No. 4)

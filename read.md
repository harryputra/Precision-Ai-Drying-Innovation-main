# SolarDryerAI — Dokumentasi Sistem Lengkap

## Gambaran Umum

SolarDryerAI adalah sistem pengeringan padi bertenaga surya berbasis IoT yang terintegrasi kecerdasan buatan. Sistem ini menggabungkan perangkat keras ESP32 sebagai controller fisik, server web Laravel sebagai otak sistem, dua AI model (Gemini dan Groq) sebagai decision engine, n8n sebagai automation workflow, dan antarmuka web real-time sebagai panel kontrol operator. Tujuan utamanya adalah mencapai kadar air gabah target (di bawah 14%) secara efisien dengan keputusan aktuator yang diambil secara otomatis berdasarkan data sensor, cuaca aktual, dan prediksi cuaca 48 jam ke depan.

---

## Arsitektur Sistem

Sistem terdiri dari lima lapisan yang saling terhubung:

**Lapisan Hardware (ESP32)** membaca sensor DHT22 setiap 500ms, menjalankan kontrol relay berdasarkan threshold lokal, mengirim data sensor ke server setiap 30 detik via HTTP POST, dan polling perintah AI dari server setiap 30 detik via HTTP GET. Saat tidak ada perintah AI, ESP32 beroperasi mandiri menggunakan threshold suhu dan kelembaban yang hardcoded.

**Lapisan API (Laravel REST)** menerima data dari ESP32, memvalidasi, menyimpan ke database, dan mem-broadcast perubahan ke browser via WebSocket. Lapisan ini juga menyediakan endpoint untuk n8n mengambil snapshot kondisi real-time dan mengirim keputusan AI.

**Lapisan Automation (n8n)** berjalan terjadwal setiap 15 menit. Workflow multi-agent mengambil context dari Laravel, menjalankan beberapa agen khusus (weather agent, sensor agent, batch agent) secara berurutan, merakit prompt final, mengirim ke Gemini, lalu menyimpan keputusan kembali ke Laravel.

**Lapisan AI (Gemini + Groq)** menerima prompt terstruktur berisi data sensor, cuaca, forecast, status batch, dan knowledge base. Gemini 2.0 Flash menjadi model utama; Groq (llama-3.1-8b-instant) menjadi fallback otomatis saat Gemini rate limit (HTTP 429) atau service error (503).

**Lapisan Web (Browser)** menampilkan dashboard real-time yang menerima update sensor, keputusan AI, dan notifikasi via Laravel Reverb WebSocket tanpa perlu refresh halaman.

---

## Teknologi yang Digunakan

### Backend — Laravel 13 (PHP 8.3)

Server dibangun di atas Laravel 13 dengan PHP 8.3. Pemilihan Laravel karena arsitektur MVC yang terstruktur, Eloquent ORM untuk manajemen database, sistem event/broadcasting bawaan, dan dukungan middleware granular untuk role-based access control.

Package PHP yang digunakan:

- **laravel/reverb** `^1.10` — WebSocket server native Laravel untuk real-time broadcasting tanpa ketergantungan Pusher eksternal
- **laravel/sanctum** `^4.0` — token-based API authentication untuk user login dan proteksi endpoint
- **maatwebsite/excel** `^3.1` — ekspor data ke format Excel (.xlsx) dan CSV
- **barryvdh/laravel-dompdf** `^3.1` — generate laporan PDF dari Blade view
- **guzzlehttp/guzzle** — HTTP client untuk komunikasi dengan Gemini API, Groq API, dan OpenWeatherMap API

### Kecerdasan Buatan

**Google Gemini 2.0 Flash** adalah AI utama, dikonsumsi via `https://generativelanguage.googleapis.com/v1beta/models`. Digunakan untuk dua mode berbeda: mode chat interaktif dengan operator (temperature 0.7, max 1024 token) dan mode decision engine closed-loop (temperature 0.3, response MIME type `application/json`). Pada mode decision engine, AI wajib mengembalikan JSON terstruktur berisi `decision_type`, `reasoning`, `confidence_score`, `output_action` (parameter relay), `risk_level`, dan `alerts`.

**Groq (llama-3.1-8b-instant)** adalah fallback yang aktif otomatis saat Gemini mengembalikan status 429 atau 503. Groq menggunakan format OpenAI-compatible API (`https://api.groq.com/openai/v1/chat/completions`). Logika fallback ada di `AiService::chat()` dan `AiService::analyzeAndDecide()` — keduanya membungkus exception `RuntimeException` dan memeriksa pesan error sebelum beralih ke Groq.

### Otomasi Workflow — n8n

n8n menjalankan workflow bernama "SolarDryerAI - Multi-Agent Sequential" setiap 15 menit menggunakan Schedule Trigger. Workflow ini mengorkestrasi pipeline multi-agen: Weather Agent (analisis risiko hujan), Sensor Agent (evaluasi kondisi suhu dan kelembaban), Batch Agent (progress pengeringan), lalu semua output dirakit menjadi satu prompt untuk dikirim ke Gemini. Jika tidak ada batch aktif atau tidak ada data sensor, workflow berhenti lebih awal (early exit) tanpa memanggil AI.

### Data Cuaca — OpenWeatherMap

`OpenWeatherService` mengonsumsi dua endpoint OpenWeatherMap: `/weather` untuk cuaca aktual dan `/forecast` untuk prediksi 5 hari (interval 3 jam, 16 data point = 48 jam). Data cuaca aktual di-cache 10 menit, forecast di-cache 30 menit. Lokasi default dikonfigurasi ke **Margahurip, Banjaran** (lat: -7.0271, lon: 107.5892). Method `forecastSummaryForAi()` meringkas forecast menjadi format ringkas yang langsung masuk ke prompt AI — berisi `rain_risk_6h`, `rain_risk_24h`, probabilitas hujan maksimum, dan jendela hujan pertama.

### Perangkat Keras — ESP32

Mikrokontroler ESP32 menjalankan firmware `esp32_solardryerai.ino`. Hardware yang terhubung:

- **DHT22** di GPIO 4 — sensor suhu dan kelembaban
- **LCD I2C 16x2** di alamat 0x27 (SDA=21, SCL=22) — tampilan lokal 3 halaman berrotasi setiap 3 detik: halaman suhu/RH, status relay, dan status WiFi/AI
- **Relay Exhaust** di GPIO 25 — kontrol exhaust fan (buang udara lembab)
- **Relay Heater** di GPIO 26 — kontrol pemanas
- **Relay Fan** di GPIO 27 — kontrol fan sirkulasi

Semua relay aktif LOW (LOW = ON, HIGH = OFF). ESP32 punya dua mode operasi: **threshold mode** (kontrol otomatis berdasarkan nilai hardcoded) dan **AI override mode** (perintah dari server mengalahkan threshold). Exhaust selalu dikontrol threshold kelembaban meski dalam AI override mode.

### Frontend — Blade + Vite + Laravel Echo

Antarmuka web menggunakan Blade sebagai template engine dengan Vite sebagai bundler. `resources/js/app.js` menginisialisasi Laravel Echo yang terhubung ke Reverb WebSocket. Browser men-subscribe tiga channel: `sensor-updates` (data sensor baru), `ai-decisions` (keputusan AI baru), dan `notifications.{userId}` (private channel notifikasi per user). Sistem mendukung dua bahasa — Indonesia dan Inggris — via middleware `SetLocale` yang membaca session `locale`.

---

## Alur Data Lengkap

### 1. Ingest Data Sensor dari ESP32

ESP32 setiap 30 detik mengirim HTTP POST ke `POST /api/iot/sensor` dengan payload JSON berisi `device_id`, `temperature_inside`, `humidity_inside`, dan field sensor lainnya. Controller `SensorReadingController::store()` memvalidasi data, menyimpan ke tabel `sensor_readings`, memperbarui kolom `status = 'online'` dan `last_seen` di tabel `devices`, lalu mem-broadcast event `SensorUpdated` ke channel `sensor-updates` dan `device.{device_id}`. Browser yang sedang membuka dashboard langsung menerima data baru tanpa refresh.

### 2. Polling Perintah AI dari ESP32

ESP32 setiap 30 detik mengirim HTTP GET ke `GET /api/iot/pending-command?device_id=1`. Controller `IoTCommandController::pendingCommand()` memperbarui `last_seen` device, lalu mencari satu record `ai_decisions` dengan status `pending` dan `command_sent_at` masih null (belum pernah dikirim). Jika ada, sistem memanggil `formatEsp32Command()` untuk mengubah `output_action` JSON dari AI menjadi format konkret yang ESP32 pahami (field `heater`, `fan`, `fan_speed`, `target_temp`, `duration_h`, `mode`), lalu mengisi `command_sent_at` dan `ack_status = 'waiting'`. Respons dikirim ke ESP32 berisi `decision_id`, `decision_type`, dan objek `actions`.

### 3. Eksekusi dan ACK dari ESP32

Setelah menerima perintah, ESP32 mengeksekusi relay sesuai `decision_type`. Logika eksekusi memiliki kasus khusus: `pause_drying` mematikan semua relay; `stop_heater` mematikan heater tapi fan tetap mengikuti perintah AI; selain itu relay diset langsung dari field `actions`. Setelah eksekusi, ESP32 mengirim HTTP POST ke `POST /api/iot/command-ack` dengan `decision_id`, `device_id`, dan `status` (`success` atau `failed`). Server memverifikasi device_id cocok dengan keputusan, lalu mengisi `acknowledged_at`, `ack_status = 'acked'`, dan `execution_status = 'executed'`.

### 4. n8n Workflow — Multi-Agent Pipeline

Setiap 15 menit, n8n menjalankan pipeline berikut:

- **Node 1 — Schedule Trigger**: memulai workflow
- **Node 2 — HTTP Request ke Laravel** (`GET /api/ai/context?device_id=1`): mengambil snapshot lengkap berisi sensor terbaru, cuaca aktual, forecast 48 jam, batch aktif, knowledge base, dan keputusan pending
- **Node 3 — Code: Parse & Validasi**: memeriksa apakah ada batch aktif dan data sensor; jika tidak ada, set `skip: true` dan workflow berhenti lebih awal
- **Node 4 — If: Ada Batch & Sensor?**: routing berdasarkan flag `skip`
- **Node 5 — Weather Agent**: menganalisis data cuaca — jika hujan aktual > 0.5mm/jam → `pause_drying` dengan urgensi `critical`; jika risiko hujan tinggi 6 jam ke depan → `prepare_close` dengan urgensi `high`; jika awan > 80% → rekomendasikan `start_heater`; jika cerah → `optimize_airflow`
- **Node 6 — Sensor Agent**: mengevaluasi kondisi sensor — suhu kritis > 60°C → stop heater segera; suhu rendah < 35°C → nyalakan heater; kelembaban > 80% → exhaust speed 100%; kadar air gabah < 14% → stop pengeringan
- **Node 7 — Batch Agent**: menghitung progress pengeringan (persentase reduksi kadar air) dan estimasi sisa waktu
- **Node 8 — Assemble Prompt**: merakit semua output agen menjadi satu prompt terstruktur
- **Node 9 — Gemini API**: mengirim prompt ke Gemini 2.0 Flash
- **Node 10 — HTTP POST ke Laravel** (`POST /api/ai/decide`): menyimpan keputusan AI dengan `device_id`, `batch_id`, `decision_type`, `reasoning`, `input_data`, `output_action`, `confidence_score`, dan `ai_model`

### 5. Manual AI Trigger dari Dashboard

Operator dengan role `admin` atau `operator` dapat memicu keputusan AI secara manual dari halaman AI Decisions tanpa menunggu jadwal n8n. `AiDecisionWebController::triggerDecision()` mengambil sensor terbaru dari DB, cuaca dari OpenWeatherMap, merakit context, memanggil `AiService::analyzeAndDecide()` langsung (Gemini/Groq), lalu menyimpan hasilnya ke `ai_decisions`. Keputusan langsung tersedia untuk diambil ESP32 pada polling berikutnya.

### 6. AI Chat Interaktif

Operator membuka `/ai/chat` dan mengirim pesan. `AiChatWebController::send()` menyimpan pesan user ke `ai_conversations` dengan session ID unik, lalu memanggil `AiService::chat()`. Method ini mengambil 10 pesan terakhir dari session yang sama untuk membangun histori percakapan, menyuntikkan system prompt berisi data sensor terbaru, cuaca, batch aktif, dan 5 knowledge base tertinggi prioritasnya, kemudian mengirim ke Gemini. Balasan disimpan ke `ai_conversations` dengan role `assistant` dan dikembalikan ke browser.

---

## Closed-Loop Decision Engine

Ini adalah inti sistem AI. Loop tertutup bekerja sebagai berikut:

```
Sensor ESP32 → POST /api/iot/sensor → Database
                                          ↓
n8n (tiap 15 menit) → GET /api/ai/context → Gemini → POST /api/ai/decide
                                                              ↓
ESP32 (tiap 30 detik) → GET /api/iot/pending-command → eksekusi relay
                                                              ↓
                              POST /api/iot/command-ack → update execution_status
```

AI mengikuti aturan keputusan berikut yang di-encode dalam system prompt:

- Suhu optimal pengeringan: 40–55°C
- Kelembaban dalam optimal: < 65%
- Kadar air target gabah: < 14%
- Jika forecast hujan > 70% dalam 3 jam → `pause_drying`
- Jika suhu > 60°C → `stop_heater` (risiko gosong)
- Jika kadar air < 14% → `stop_heater` (selesai)
- Jika RH dalam > 80% → `fan_speed` 100%
- Prioritas: keselamatan gabah > efisiensi energi

Confidence score (0.000–1.000) dan risk level (`low`/`medium`/`high`/`critical`) disimpan di setiap keputusan. Operator bisa melihat reasoning lengkap dan snapshot data input yang digunakan AI saat membuat keputusan tersebut.

---

## Database Schema

Database menggunakan **SQLite** (`database/database.sqlite`) dengan 13 tabel. Semua tabel punya timestamp `created_at` dan `updated_at` kecuali yang disebutkan berbeda.

### `users`

Akun pengguna sistem. Kolom `role` menentukan hak akses: `admin` (full access), `operator` (bisa trigger AI dan kelola batch), `viewer` (read-only). Method `hasRole(array $roles)` di model User digunakan middleware `EnsureRole`.

### `devices`

Unit pengering fisik yang terdaftar. Kolom: `device_name`, `serial_number`, `firmware_version`, `ip_address`, `location`, `status` (`online`/`offline`), `last_seen` (diperbarui setiap ESP32 kirim sensor atau polling command). Scope `online()` memfilter device aktif. Method `activeBatch()` mengembalikan batch aktif terkait device.

### `drying_batches`

Sesi pengeringan gabah. Kolom penting:

- `batch_code` — unik, identifier batch (contoh: `BATCH-2026-001`)
- `rice_type`, `rice_variety` — jenis dan varietas padi
- `initial_weight`, `current_weight` — berat awal dan saat ini (kg, decimal 8,2)
- `initial_moisture`, `current_moisture`, `target_moisture` — kadar air (%, decimal 5,2)
- `drying_method` — metode pengeringan (default: `Hybrid`)
- `status` — enum: `waiting`, `drying`, `paused`, `completed`, `failed`
- `start_time`, `end_time` — timestamp mulai dan selesai

Model ini juga menyimpan kalkulasi **OEE (Overall Equipment Effectiveness)**: `oeeAvailability()`, `oeePerformance()`, `oeeQuality()`, dan `oeeScore()` sebagai static method. OEE dihitung dari 30 hari terakhir secara default. `oeeBatchTrend()` mengembalikan collection 10 batch terakhir dengan performance score individual untuk chart tren.

### `sensor_readings`

Semua pembacaan sensor dari ESP32. Kolom:

- `temperature_inside`, `temperature_outside` — suhu dalam dan luar ruang (°C)
- `humidity_inside`, `humidity_outside` — kelembaban relatif dalam dan luar (%)
- `solar_irradiance` — iradiasi surya (W/m²)
- `lux` — intensitas cahaya (lux)
- `grain_moisture` — kadar air gabah (%)
- `grain_weight` — berat gabah saat ini (kg)
- `wind_speed`, `wind_direction` — kecepatan (m/s) dan arah angin (0–359°)
- `is_valid` — boolean, false jika pembacaan gagal/error
- `error_message` — pesan error jika `is_valid = false`
- `recorded_at` — timestamp pembacaan aktual (bukan waktu insert)

Index: `device_id`, `batch_id`, `recorded_at`. Scope `valid()` memfilter `is_valid = true`. Scope `forDevice()`, `recent(minutes)` tersedia.

### `weather_data`

Data cuaca yang disimpan dari OpenWeatherMap atau sensor luar. Kolom: `temperature`, `humidity`, `solar_irradiance`, `wind_speed`, `wind_direction`, `weather_condition`, `rainfall`, `uv_index`, `source` (dibedakan aktual vs forecast), `recorded_at`.

### `ai_decisions`

Log setiap keputusan AI. Kolom kunci:

- `decision_type` — enum 12 nilai: `open_roof`, `close_roof`, `start_fan`, `stop_fan`, `start_heater`, `stop_heater`, `pause_drying`, `resume_drying`, `alert_operator`, `adjust_temperature`, `adjust_airflow`, `other`
- `reasoning` — penjelasan keputusan dari AI (text)
- `input_data` — JSON snapshot semua data yang dikirim ke AI saat keputusan dibuat
- `output_action` — JSON parameter aktuator: `heater`, `fan`, `fan_speed`, `target_temperature`, `duration_hours`, `mode`
- `confidence_score` — decimal 4,3 (0.000–1.000)
- `ai_model` — model yang dipakai (contoh: `gemini-2.0-flash`, `llama-3.1-8b-instant`)
- `execution_status` — enum: `pending`, `executed`, `failed`, `skipped`, `overridden`
- `override_reason`, `overridden_by` — jika operator menolak keputusan AI
- `decided_at`, `executed_at` — timestamp diputuskan dan dieksekusi
- `command_sent_at` — kapan perintah dikirim ke ESP32
- `acknowledged_at`, `ack_status`, `esp32_command` — tracking ACK dari ESP32

### `actuator_logs`

Log eksekusi fisik setiap aktuator. Kolom: `actuator_type` (enum: `roof`, `fan`, `heater`, `ventilation`, `pump`, `conveyor`, `other`), `actuator_name`, `command` (enum: `on`/`off`/`open`/`close`/`adjust`), `set_value`, `actual_value`, `unit`, `triggered_by` (enum: `ai`/`manual`/`schedule`/`safety`), `triggered_by_user`, `status` (enum: `success`/`failed`/`timeout`), `response_time_ms`, `executed_at`. Relasi ke `ai_decision_id` untuk traceability keputusan AI → aksi fisik.

### `ai_conversations`

Riwayat percakapan per session. Kolom: `session_id` (UUID), `role` (`user`/`assistant`), `message`, `ai_model`, `tokens_used`, `context_data` (JSON konteks yang dipakai AI saat menjawab). Scope `session(sessionId)` memfilter per session. `AiService::chat()` mengambil 10 pesan terakhir per session untuk membangun histori.

### `knowledge_base`

Basis pengetahuan yang bisa dikelola admin. Kolom: `category`, `title`, `content`, `tags`, `priority_weight` (angka — semakin tinggi semakin diprioritaskan), `is_active`. Method `forAi()` scope mengambil hanya record aktif, diurutkan `priority_weight` descending. Maksimal 5 record tertinggi disuntikkan ke setiap system prompt chat.

### `notifications`

Notifikasi untuk pengguna. Kolom: `type` (enum: `info`/`warning`/`alert`/`success`/`error`), `category` (enum 9 nilai: `moisture_alert`, `temperature_alert`, `weather_alert`, `device_offline`, `batch_complete`, `batch_failed`, `ai_decision`, `system`, `other`), `title`, `message`, `data` (JSON payload tambahan). Mendukung 4 channel pengiriman: `via_app`, `via_email`, `via_sms`, `via_whatsapp` (boolean per channel). Kolom `read_at` untuk tracking status baca. Notifikasi private di-broadcast via `PrivateChannel("notifications.{user_id}")`.

### `system_logs`

Audit trail aktivitas sistem untuk debugging dan monitoring.

### `personal_access_tokens`

Token Sanctum untuk autentikasi API (dipakai user login via `/api/auth/login`).

### `cache` dan `jobs`

Tabel cache Laravel (dipakai oleh `Cache::remember` untuk cuaca) dan jobs queue (untuk background processing).

---

## API Endpoints

### IoT Device (ESP32) — tanpa auth

| Method | Endpoint                   | Fungsi                            |
| ------ | -------------------------- | --------------------------------- |
| POST   | `/api/iot/sensor`          | Kirim satu data sensor            |
| POST   | `/api/iot/sensor/bulk`     | Bulk ingest hingga 100 readings   |
| POST   | `/api/iot/weather`         | Kirim data cuaca dari sensor luar |
| POST   | `/api/iot/actuator`        | Log hasil eksekusi aktuator       |
| GET    | `/api/iot/pending-command` | Polling perintah AI pending       |
| POST   | `/api/iot/command-ack`     | Konfirmasi eksekusi perintah      |

### n8n AI Agent — tanpa auth

| Method | Endpoint             | Fungsi                                                       |
| ------ | -------------------- | ------------------------------------------------------------ |
| GET    | `/api/ai/context`    | Snapshot kondisi real-time (sensor, cuaca, batch, knowledge) |
| POST   | `/api/ai/decide`     | Simpan keputusan AI dari n8n                                 |
| POST   | `/api/ai/chat/reply` | Simpan balasan AI untuk percakapan                           |

### User Auth

| Method | Endpoint           | Fungsi                        |
| ------ | ------------------ | ----------------------------- |
| POST   | `/api/auth/login`  | Login, mendapat Sanctum token |
| POST   | `/api/auth/logout` | Logout (hapus token)          |
| GET    | `/api/auth/me`     | Data user yang login          |

### Protected Endpoints (Bearer Token Sanctum)

Semua endpoint berikut butuh header `Authorization: Bearer {token}`.

**Devices** — read semua role, write hanya admin/operator:
`GET /api/devices`, `GET /api/devices/{id}`, `POST /api/devices`, `PUT /api/devices/{id}`, `DELETE /api/devices/{id}`, `POST /api/devices/{id}/heartbeat`

**Batches** — read semua role, write admin/operator:
`GET /api/batches`, `GET /api/batches/{id}`, `GET /api/batches-active`, `POST /api/batches`, `PUT /api/batches/{id}`, `DELETE /api/batches/{id}`

**Sensor Readings** — read-only:
`GET /api/sensor-readings`, `GET /api/sensor-readings/{id}`, `GET /api/sensor-readings/latest`

**Weather Data** — read-only:
`GET /api/weather`, `GET /api/weather/{id}`, `GET /api/weather/latest`

**AI Decisions**:
`GET /api/ai-decisions`, `GET /api/ai-decisions/{id}`, `GET /api/ai-decisions/pending`, `PATCH /api/ai-decisions/{id}/status` (admin/operator)

**Actuator Logs** — read-only:
`GET /api/actuator-logs`, `GET /api/actuator-logs/{id}`

**Notifications**:
`GET /api/notifications`, `PATCH /api/notifications/{id}/read`, `POST /api/notifications/read-all`, `GET /api/notifications/unread-count`, `DELETE /api/notifications/{id}`

**Knowledge Base** — read semua, write admin/operator:
`GET /api/knowledge-base`, `GET /api/knowledge-base/{id}`, `GET /api/knowledge-base-for-ai`, `POST /api/knowledge-base`, `PUT /api/knowledge-base/{id}`, `DELETE /api/knowledge-base/{id}`

**AI Conversations**:
`GET /api/conversations`, `GET /api/conversations/{sessionId}`, `POST /api/conversations`, `POST /api/conversations/new-session`, `PATCH /api/conversations/{id}/feedback`

---

## Halaman Web (UI)

### Dashboard (`/`)

Halaman utama. Menampilkan: jumlah device online/total, batch aktif, keputusan AI hari ini, ringkasan status batch (waiting/drying/completed/failed), data sensor terbaru, chart historis 20 reading terakhir (suhu dalam, suhu luar, kelembaban dalam), status aktuator 24 jam terakhir, 6 log aktuator terbaru, 5 keputusan AI terbaru, daftar batch aktif, dan skor OEE keseluruhan (Availability, Performance, Quality, OEE Score). Semua data sensor dan keputusan AI diperbarui real-time via WebSocket.

### Sensor Readings (`/sensor-readings`)

Tabel historis semua pembacaan sensor dengan pagination 20 per halaman. Filter tersedia. Ekspor ke Excel atau CSV via `GET /sensor-readings/export`.

### Weather (`/weather`)

Tampilan data cuaca aktual dan historis dari OpenWeatherMap. Termasuk kondisi angin, kelembaban, curah hujan, dan awan. Ekspor tersedia.

### AI Decisions (`/ai/decisions`)

Daftar semua keputusan AI dengan filter per device, decision_type, dan execution_status. Setiap baris menampilkan confidence score, model AI, dan status eksekusi. Klik detail untuk melihat full reasoning, snapshot input_data JSON, output_action, dan log aktuator terkait. Ekspor ke Excel, CSV, dan PDF tersedia. Tombol **Trigger AI Decision** (admin/operator) memicu keputusan manual tanpa menunggu jadwal n8n.

### AI Chat (`/ai/chat`)

Antarmuka percakapan interaktif dengan Gemini. Setiap pesan otomatis mendapat konteks real-time: sensor terbaru, cuaca, batch aktif, 5 knowledge base tertinggi. AI menjawab dalam Bahasa Indonesia. Histori percakapan disimpan per session ID.

### AI Summary (`/ai/summary`)

Dashboard analitik keputusan AI: statistik total/executed/pending/failed/overridden, rata-rata confidence score, distribusi confidence (bucket 0–20%, 20–40%, dst), distribusi per `decision_type`, distribusi per `execution_status`, tren harian 14 hari terakhir (count + avg confidence), penggunaan model AI, dan 5 override terbaru oleh operator.

### Batches (`/batches`)

List semua batch dengan filter status, pencarian kode batch, dan filter tanggal. Membuat batch baru via form (device, kode, varietas, berat, kadar air awal, target). Detail batch menampilkan chart sensor readings selama batch berlangsung, 10 keputusan AI terkait, dan 10 log aktuator terkait. Ekspor ke Excel, CSV, PDF.

### Devices (`/devices`)

Manajemen perangkat ESP32 terdaftar: nama, serial number, lokasi, firmware version, IP address, status online/offline, dan `last_seen`. CRUD tersedia untuk admin/operator.

### Knowledge Base (`/knowledge`)

Admin mengelola basis pengetahuan yang disuntikkan ke system prompt AI. Dapat mengatur `priority_weight` — record dengan nilai tertinggi lebih sering masuk ke prompt.

### Notifications (`/notifications`)

Semua notifikasi masuk ke akun user yang login. Notifikasi real-time muncul lewat WebSocket tanpa refresh. Tandai baca satu per satu atau semua sekaligus.

### System Logs (`/logs`)

Audit trail aktivitas sistem.

### Profile (`/profile`)

Edit profil user yang sedang login.

### Admin Panel (`/admin`)

Khusus role admin: manajemen user dan role assignment.

---

## Role & Access Control

Tiga role tersedia, dikontrol middleware `EnsureRole` (`app/Http/Middleware/EnsureRole.php`):

- **admin** — full access ke semua fitur termasuk manajemen user, device, knowledge base, dan semua operasi CRUD
- **operator** — bisa trigger AI decision manual, kelola batch, kelola device; tidak bisa kelola user
- **viewer** — hanya bisa membaca data: dashboard, sensor, cuaca, AI decisions, batches; tidak bisa membuat atau mengubah data

Middleware diregistrasi sebagai `role` alias dan digunakan di route groups: `Route::middleware('role:admin,operator')`.

---

## OEE (Overall Equipment Effectiveness)

Dashboard menampilkan tiga komponen OEE dihitung dari 30 hari terakhir:

- **Availability** = (batch tidak gagal) / (semua batch yang sudah dimulai) × 100%
- **Performance** = rata-rata progress reduksi kadar air per batch — `(initial_moisture - current_moisture) / (initial_moisture - target_moisture)`, di-cap 0–100%
- **Quality** = (batch completed) / (completed + failed) × 100%
- **OEE Score** = (A × P × Q) / 10.000

Semua kalkulasi ada di static method `DryingBatch::oeeAvailability()`, `oeePerformance()`, `oeeQuality()`, `oeeScore()`. Method `oeeBatchTrend()` mengembalikan performance score per batch untuk chart tren.

---

## Real-Time Broadcasting (Laravel Reverb + Echo)

Empat event di-broadcast ke browser:

### `SensorUpdated` (channel publik)

Dipicu setiap kali ESP32 kirim data sensor baru. Di-broadcast ke dua channel sekaligus: `sensor-updates` (semua browser) dan `device.{device_id}` (per device). Payload berisi semua field sensor reading termasuk `recorded_at` dalam ISO 8601.

### `AiDecisionMade` (channel publik)

Dipicu setiap keputusan AI baru disimpan. Channel: `ai-decisions` dan `device.{device_id}`. Payload berisi `decision_type`, `reasoning`, `confidence_score`, `ai_model`, `execution_status`, `decided_at`.

### `AiReplyReceived` (channel publik)

Dipicu saat AI selesai membalas pesan chat. Digunakan untuk update UI chat secara real-time.

### `NotificationSent` (channel privat)

Dipicu saat notifikasi baru dibuat untuk user. Channel: `PrivateChannel("notifications.{user_id}")` — hanya user yang bersangkutan yang menerima. Membutuhkan autentikasi channel di `routes/channels.php`.

---

## Logika ESP32 Detail

### Threshold Mode (default)

Saat tidak ada perintah AI pending, ESP32 mengontrol relay berdasarkan nilai hardcoded dengan hysteresis untuk mencegah relay switching terlalu cepat:

- **Heater**: ON jika suhu < 40°C, OFF jika ≥ 50°C, OFF paksa jika ≥ 58°C (suhu kritis)
- **Fan**: ON jika suhu ≥ 38°C, OFF jika < 35°C
- **Exhaust**: ON jika RH > 65%, OFF jika < 55%

### AI Override Mode

Saat ESP32 menerima perintah dari server, `aiOverride = true` dan relay dikontrol sepenuhnya oleh perintah AI. Pengecualian: exhaust selalu dikendalikan threshold kelembaban meski dalam AI override mode — ini memastikan udara lembab tetap dibuang bahkan ketika AI sedang mengendalikan heater dan fan.

Setelah eksekusi, ESP32 segera mengirim ACK ke server. Tidak ada timer reset otomatis; polling 30 detik berikutnya akan memeriksa apakah ada perintah baru atau kembali ke threshold mode jika `command = null`.

### LCD 3-Halaman (rotasi setiap 3 detik)

- **Halaman 0**: `Suhu : XX.X C` / `RH   : XX.X %`
- **Halaman 1**: `H:ON/OFF F:ON/OFF` / `Ex:ON/OFF AI/TH` (TH = threshold mode, AI = AI override)
- **Halaman 2**: `WiFi:OK/X` / `AI:{decision_type substring 12 char}`

---

## Struktur Folder

```
SolarDryerAI/
├── app/
│   ├── Events/
│   │   ├── AiDecisionMade.php       # broadcast ke channel ai-decisions
│   │   ├── AiReplyReceived.php      # broadcast balasan chat AI
│   │   ├── NotificationSent.php     # broadcast notifikasi privat per user
│   │   └── SensorUpdated.php        # broadcast data sensor baru
│   ├── Exports/
│   │   ├── AiDecisionExport.php     # export AI decisions ke Excel/CSV
│   │   └── BatchExport.php          # export batches ke Excel/CSV
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AIAgentController.php     # /api/ai/* — endpoint n8n
│   │   │   │   ├── IoTCommandController.php  # /api/iot/pending-command & ack
│   │   │   │   ├── SensorReadingController.php
│   │   │   │   ├── AiDecisionController.php
│   │   │   │   ├── ActuatorLogController.php
│   │   │   │   ├── DryingBatchController.php
│   │   │   │   ├── DeviceController.php
│   │   │   │   ├── WeatherDataController.php
│   │   │   │   ├── KnowledgeBaseController.php
│   │   │   │   ├── AiConversationController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   └── AuthController.php
│   │   │   └── Web/
│   │   │       ├── DashboardController.php      # homepage + OEE + chart data
│   │   │       ├── AiDecisionWebController.php  # AI decisions + manual trigger
│   │   │       ├── AiChatWebController.php      # chat interaktif dengan Gemini
│   │   │       ├── AiSummaryController.php      # analitik keputusan AI
│   │   │       ├── BatchWebController.php       # CRUD batch + export
│   │   │       ├── DeviceWebController.php
│   │   │       ├── SensorWebController.php
│   │   │       ├── WeatherWebController.php
│   │   │       ├── KnowledgeWebController.php
│   │   │       ├── NotificationWebController.php
│   │   │       ├── SystemLogWebController.php
│   │   │       ├── ProfileController.php
│   │   │       ├── RoleController.php
│   │   │       └── AuthWebController.php
│   │   └── Middleware/
│   │       ├── EnsureRole.php    # role:admin,operator,viewer
│   │       └── SetLocale.php     # set bahasa dari session
│   ├── Models/
│   │   ├── User.php
│   │   ├── Device.php
│   │   ├── DryingBatch.php       # + OEE static methods
│   │   ├── SensorReading.php
│   │   ├── WeatherData.php
│   │   ├── AiDecision.php        # + markCommandSent, markAcknowledged
│   │   ├── ActuatorLog.php
│   │   ├── AiConversation.php
│   │   ├── KnowledgeBase.php
│   │   ├── Notification.php
│   │   └── SystemLog.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── AiService.php          # Gemini + Groq fallback, chat + decision
│       ├── GroqService.php        # Groq API client (llama-3.1-8b-instant)
│       └── OpenWeatherService.php # cuaca aktual + forecast + cache
├── database/
│   ├── migrations/                # 17 migration files
│   ├── seeders/                   # 11 seeder files (data contoh)
│   └── database.sqlite
├── resources/
│   ├── views/
│   │   ├── dashboard.blade.php    # 49KB — dashboard utama
│   │   ├── welcome.blade.php      # 72KB — landing page
│   │   ├── layouts/               # layout utama (sidebar, navbar)
│   │   ├── auth/                  # login, register
│   │   ├── ai/                    # decisions, chat, summary
│   │   ├── batches/               # index, show, create, edit
│   │   ├── devices/
│   │   ├── sensor/
│   │   ├── weather/
│   │   ├── knowledge/
│   │   ├── notifications/
│   │   ├── logs/
│   │   ├── profile/
│   │   ├── admin/
│   │   └── exports/               # template PDF
│   ├── js/
│   │   ├── app.js                 # Echo setup, real-time listeners
│   │   └── bootstrap.js
│   └── css/
│       └── app.css                # 30KB stylesheet kustom
├── routes/
│   ├── web.php                    # semua route halaman web + role middleware
│   ├── api.php                    # semua REST API endpoint
│   ├── channels.php               # auth channel WebSocket
│   └── console.php
├── config/
│   ├── reverb.php                 # konfigurasi Reverb WebSocket
│   ├── services.php               # API key Gemini, Groq, OpenWeather
│   └── broadcasting.php
├── lang/
│   ├── id/app.php                 # terjemahan Bahasa Indonesia
│   └── en/app.php                 # terjemahan Bahasa Inggris
├── esp32_solardryerai.ino         # firmware ESP32 lengkap
├── n8n-workflow.json              # workflow multi-agent n8n
└── public/
    └── images/
        └── logo.jpeg
```

---

## Cara Menjalankan

### Prasyarat

- PHP 8.3+
- Composer
- Node.js + npm
- n8n (self-hosted atau cloud)
- API key: Google Gemini, Groq (opsional), OpenWeatherMap
- Arduino IDE untuk upload firmware ESP32

### Setup Server Laravel

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Isi nilai di .env:
# GEMINI_API_KEY=...
# GEMINI_MODEL=gemini-2.0-flash
# GROQ_API_KEY=...           (opsional, untuk fallback)
# OPENWEATHER_API_KEY=...
# OPENWEATHER_LAT=-7.0271    (sesuaikan lokasi)
# OPENWEATHER_LON=107.5892

# Buat database dan jalankan migration + seeder
php artisan migrate --seed

# Build aset frontend
npm run build

# Jalankan semua service sekaligus (development)
composer dev
# Menjalankan parallel: php artisan serve + queue:listen + pail + npm run dev
```

### Setup ESP32

1. Buka `esp32_solardryerai.ino` di Arduino IDE
2. Ubah `WIFI_SSID`, `WIFI_PASSWORD`, dan `SERVER_URL` (IP server di jaringan lokal yang sama)
3. Sesuaikan `DEVICE_ID` dengan ID device di database
4. Install library via Library Manager: DHT sensor library, Adafruit Unified Sensor, LiquidCrystal I2C, ArduinoJson
5. Upload ke board ESP32

### Setup n8n

1. Import `n8n-workflow.json` ke n8n instance
2. Pastikan URL di node HTTP Request mengarah ke server Laravel yang benar
3. Aktifkan workflow — akan berjalan otomatis setiap 15 menit

---

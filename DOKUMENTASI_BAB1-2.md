# DOKUMENTASI SISTEM PADI PRECISION
## Sistem Pengeringan Gabah Cerdas Berbasis IoT dan Kecerdasan Buatan

---

# BAB I — PENDAHULUAN

## 1.1 Latar Belakang

Gabah hasil panen memiliki kadar air berkisar 22–25% pada saat dipanen. Standar nasional Indonesia (SNI 0224:2008) menetapkan kadar air Gabah Kering Giling (GKG) yang layak jual adalah ≤ 14%. Proses pengeringan untuk menurunkan kadar air dari kondisi panen ke kondisi jual merupakan tahapan kritis dalam rantai pasca-panen padi.

Pengeringan gabah secara tradisional dilakukan dengan menjemur di bawah sinar matahari selama 2–3 hari. Metode ini sangat bergantung pada kondisi cuaca dan tidak dapat dilakukan pada musim hujan atau saat cuaca mendung. Ketika gabah tidak dapat dikeringkan tepat waktu, terjadi peningkatan kadar air yang memicu pertumbuhan jamur, fermentasi, dan penurunan kualitas beras yang dihasilkan.

Badan Pangan PBB (FAO) mencatat bahwa kehilangan pasca-panen (post-harvest loss) gabah di negara berkembang berkisar 10–37% dari total produksi, dengan tahap pengeringan menyumbang 3–5% dari total kehilangan tersebut. Di Indonesia, dengan produksi padi nasional mencapai 54 juta ton GKP per tahun, kerugian akibat pengeringan tidak optimal berpotensi mencapai nilai triliunan rupiah per tahun.

Solusi mesin pengering (cabinet dryer) berbasis energi surya telah dikembangkan sebagai alternatif pengeringan mekanis. Namun mesin pengering konvensional memiliki keterbatasan: kontrol suhu dan kelembaban dilakukan secara manual oleh operator, tidak ada mekanisme prediksi cuaca untuk mengantisipasi hujan, dan tidak ada sistem notifikasi ketika gabah telah mencapai kadar air target.

Perkembangan kecerdasan buatan, khususnya Large Language Model (LLM), membuka peluang untuk mengotomasi pengambilan keputusan pada sistem pengeringan. LLM mampu menganalisis data sensor multi-variabel (suhu, kelembaban, kadar air, iradiasi surya) secara bersamaan, mengintegrasikan data prakiraan cuaca, dan menghasilkan keputusan aktuator yang dapat dijelaskan dalam bahasa natural — kemampuan yang tidak dapat diperoleh dari algoritma kontrol konvensional seperti PID murni.

Sistem PADI PRECISION (Pengeringan Adaptif Dikendalikan Intelijen — Precision Rice Intelligence Control and Integrated Operation Network) dirancang untuk menjawab permasalahan tersebut dengan mengintegrasikan IoT, kecerdasan buatan multi-agen, dan antarmuka pengguna yang adaptif berdasarkan peran pengguna.

---

## 1.2 Tujuan Sistem

Sistem PADI PRECISION dikembangkan dengan tujuan:

1. **Membantu petani** memantau kondisi pengeringan gabah secara real-time melalui antarmuka yang mudah dipahami tanpa latar belakang teknis
2. **Memonitor pengeringan** secara otomatis menggunakan sensor IoT yang mengirim data setiap 30 detik ke server terpusat
3. **Mengurangi kehilangan hasil panen** dengan memastikan proses pengeringan berlangsung optimal dan terhenti tepat saat kadar air target tercapai
4. **Meningkatkan kualitas gabah** melalui kontrol suhu dan kelembaban yang presisi menggunakan PID controller yang diawasi AI
5. **Memberikan rekomendasi AI** berbasis data sensor real-time, prakiraan cuaca 48 jam, dan knowledge base pengeringan padi per varietas
6. **Mengotomasi keputusan aktuator** (pemanas, kipas, exhaust) sehingga operator tidak perlu hadir terus-menerus di lokasi mesin
7. **Memberikan peringatan dini** ketika kondisi cuaca berpotensi merusak gabah atau ketika anomali sensor terdeteksi

---

## 1.3 Ruang Lingkup Sistem

Sistem PADI PRECISION mencakup komponen-komponen berikut:

### Website (Aplikasi Web)
Antarmuka berbasis web yang dibangun dengan Laravel 13 dan PHP 8.3. Aplikasi web menyediakan tiga tingkat antarmuka sesuai peran pengguna: dashboard admin dengan akses penuh ke semua data dan konfigurasi, dashboard operator untuk pengelolaan batch dan pemantauan teknikal, dan dashboard viewer untuk petani yang menyajikan informasi status pengeringan dalam bahasa yang mudah dipahami.

### ESP32 (Mikrokontroler)
Unit kontrol fisik yang dipasang pada mesin pengering. ESP32 menjalankan firmware dengan tiga fungsi utama: membaca sensor setiap 500ms, menjalankan PID controller untuk mengatur relay heater, dan berkomunikasi dengan server Laravel via HTTP setiap 30 detik untuk mengirim data sensor dan mengambil keputusan AI.

### Sensor
Sistem menggunakan sensor DHT22 untuk mengukur suhu dan kelembaban relatif di dalam ruang pengering. Data sensor yang dikumpulkan meliputi: suhu dalam dan luar ruang (°C), kelembaban relatif dalam dan luar (%), iradiasi surya (W/m²), dan kadar air gabah (%).

### AI (Kecerdasan Buatan)
Sistem AI terdiri dari dua komponen: Google Gemini 2.0 Flash sebagai model utama untuk decision engine dan chatbot interaktif, serta Groq (llama-3.1-8b-instant) sebagai fallback otomatis. AI diorkestrasikan melalui pipeline multi-agen di n8n yang berjalan setiap 15 menit, mengintegrasikan data sensor, prakiraan cuaca OpenWeatherMap 48 jam, dan knowledge base pengeringan padi.

### Dashboard
Dashboard real-time yang menampilkan status pengeringan, grafik historis sensor, keputusan AI terbaru, dan metrik OEE (Overall Equipment Effectiveness). Dashboard diperbarui otomatis via WebSocket (Laravel Reverb) tanpa perlu refresh halaman.

### IoT (Internet of Things)
Infrastruktur komunikasi antara perangkat fisik (ESP32) dan sistem digital (server Laravel). ESP32 mengirim data via REST API HTTP POST dan mengambil perintah aktuator via HTTP GET setiap 30 detik. Sistem mendukung multiple device dalam satu server.

---

# BAB II — ANALISIS SISTEM

## 2.1 Analisis Permasalahan

### 2.1.1 Diagram Fishbone (Ishikawa)

Analisis akar penyebab masalah pengeringan gabah tidak optimal:

```
                        GABAH TIDAK KERING OPTIMAL
                                   |
        ┌──────────┬───────────────┼───────────────┬──────────┐
        │          │               │               │          │
     MANUSIA    MESIN           METODE          LINGKUNGAN  MATERIAL
        │          │               │               │          │
   - Operator  - Tidak ada     - Manual        - Cuaca     - Varietas
     tidak       sensor          monitoring      tidak       berbeda
     hadir       terintegrasi  - Tidak ada       terprediksi - Kadar air
   - Kurang    - Kontrol         notifikasi    - Musim         awal tinggi
     terampil    manual        - Tidak ada       hujan       - Kotoran
   - Tidak     - Tidak ada       sistem AI     - Kelembaban    bercampur
     tahu         data logger  - Tidak ada       tinggi
     kapan       - Relay          jadwal
     selesai      rusak          otomatis
```

### 2.1.2 Analisis Penyebab Utama

**Penyebab 1 — Tidak ada monitoring real-time**
Operator tidak dapat memantau suhu, kelembaban, dan kadar air gabah dari jarak jauh. Untuk mengetahui kondisi pengeringan, operator harus hadir fisik di lokasi mesin.

**Penyebab 2 — Tidak ada prediksi cuaca terintegrasi**
Keputusan buka/tutup ventilasi dan nyala/mati pemanas dilakukan berdasarkan perkiraan operator, bukan data prakiraan cuaca yang akurat. Akibatnya gabah sering terkena hujan karena ventilasi tidak ditutup tepat waktu.

**Penyebab 3 — Kontrol aktuator manual**
Relay pemanas dan kipas dioperasikan manual. Tidak ada otomasi yang menyesuaikan intensitas pengeringan dengan kondisi aktual sensor.

**Penyebab 4 — Tidak ada notifikasi otomatis**
Petani tidak mengetahui kapan gabah sudah mencapai kadar air target, sehingga seringkali terjadi over-drying (gabah terlalu kering, kehilangan berat berlebih) atau under-drying (gabah belum cukup kering, masih berisiko).

**Penyebab 5 — Tidak ada knowledge base varietas**
Setiap varietas padi memiliki karakteristik pengeringan berbeda. Tanpa basis pengetahuan, parameter pengeringan optimal tidak terdokumentasi dan bergantung pada pengalaman individual operator.

### 2.1.3 Analisis Kebutuhan

Berdasarkan analisis penyebab, sistem yang dikembangkan harus:
- Menyediakan monitoring sensor real-time dengan update ≤ 30 detik
- Mengintegrasikan prakiraan cuaca 48 jam ke depan
- Mengotomasi keputusan aktuator berdasarkan data multi-variabel
- Mengirim notifikasi ke petani dan operator secara otomatis
- Menyimpan knowledge base pengeringan per varietas yang dapat diperbarui
- Menyediakan antarmuka yang berbeda untuk pengguna teknikal dan non-teknikal

---

## 2.2 Identifikasi Stakeholder

| Stakeholder | Peran dalam Sistem | Role Sistem |
|-------------|-------------------|-------------|
| Dinas Pertanian | Pembina dan pengawas program | Admin (tingkat dinas) |
| Gapoktan (Gabungan Kelompok Tani) | Pengelola sistem, manajemen user, konfigurasi | Admin |
| Operator Mesin | Mengoperasikan mesin pengering, trigger AI manual, kelola batch | Operator |
| Petani | Memantau status gabah miliknya, tanya kondisi ke AI | Viewer |
| Teknisi | Maintenance perangkat keras, konfigurasi sensor | Admin/Operator |

---

## 2.3 Analisis Pengguna

### 2.3.1 Admin (Gapoktan / Dinas Pertanian)

Admin memiliki akses penuh ke seluruh sistem:

**Hak Akses:**
- Manajemen user: buat, edit, hapus akun, ubah role
- Manajemen perangkat: daftarkan device ESP32 baru, edit konfigurasi
- Manajemen knowledge base: tambah, edit, hapus basis pengetahuan AI
- Akses semua data: sensor, cuaca, batch, keputusan AI, log sistem
- Trigger AI manual: memicu analisis AI tanpa menunggu jadwal otomatis
- Export semua data ke Excel, CSV, PDF
- Lihat AI Summary: analitik keputusan AI, distribusi confidence score, tren harian
- Override keputusan AI: batalkan atau ubah keputusan yang sudah dibuat AI

**Kebutuhan Antarmuka:**
Dashboard teknikal lengkap dengan grafik historis, tabel data, dan kontrol penuh.

### 2.3.2 Operator (Petugas Mesin)

Operator mengelola operasional harian mesin pengering:

**Hak Akses:**
- Kelola batch: buat batch baru, update kadar air, tandai selesai
- Kelola perangkat: edit device yang ditugaskan, cek status online/offline
- Trigger AI manual: memicu keputusan AI saat kondisi berubah mendadak
- Lihat semua data teknikal: sensor, cuaca, keputusan AI, log aktuator
- Export data batch ke Excel/PDF untuk laporan
- Chat dengan AI untuk konsultasi kondisi pengeringan
- Terima notifikasi real-time: kondisi kritis, batch selesai, cuaca buruk

**Tidak Dapat:**
- Kelola user
- Kelola knowledge base
- Override keputusan AI

**Kebutuhan Antarmuka:**
Dashboard teknikal dengan fokus pada status batch aktif dan keputusan AI terbaru.

### 2.3.3 Viewer (Petani)

Petani adalah pengguna akhir yang tidak memiliki latar belakang teknis:

**Hak Akses:**
- Dashboard sederhana: status pengeringan (aktif/jeda/selesai), estimasi waktu selesai, kadar air saat ini
- Sensor ringkas: suhu dan kelembaban dalam bahasa awam dengan indikator warna
- Status perangkat: pemanas/kipas/mixer hidup atau mati
- Saran AI: rekomendasi terkini dari sistem dalam bahasa Indonesia sederhana
- Riwayat batch: daftar pengeringan yang pernah dilakukan
- Chat AI: tanya kondisi gabah dalam bahasa natural ("Kapan selesai?", "Aman ditinggal?")
- Notifikasi: alert cuaca, gabah hampir kering, gabah selesai

**Tidak Dapat:**
- Akses data teknikal (JSON, serial number, log sistem)
- Kelola batch, device, atau user
- Akses AI decisions detail
- Export data

**Kebutuhan Antarmuka:**
Dashboard sangat sederhana, teks besar, indikator warna, estimasi waktu dalam satuan jam.

---

## 2.4 Analisis Kebutuhan

### 2.4.1 Functional Requirements

| Kode | Deskripsi | Prioritas | Aktor |
|------|-----------|-----------|-------|
| FR-001 | Sistem menyediakan halaman login dengan validasi email dan password | Tinggi | Semua |
| FR-002 | Sistem menyediakan registrasi akun baru dengan role default viewer | Tinggi | Publik |
| FR-003 | Sistem menampilkan dashboard berbeda berdasarkan role pengguna | Tinggi | Semua |
| FR-004 | ESP32 mengirim data sensor ke server setiap 30 detik via HTTP POST | Tinggi | Sistem |
| FR-005 | Server menyimpan setiap pembacaan sensor ke tabel `sensor_readings` | Tinggi | Sistem |
| FR-006 | Dashboard menampilkan data sensor terbaru secara real-time via WebSocket | Tinggi | Admin, Operator |
| FR-007 | Sistem mengambil data cuaca aktual dari OpenWeatherMap API | Tinggi | Sistem |
| FR-008 | Sistem mengambil prakiraan cuaca 48 jam (16 data point, interval 3 jam) | Tinggi | Sistem |
| FR-009 | n8n menjalankan pipeline AI setiap 15 menit secara otomatis | Tinggi | Sistem |
| FR-010 | AI menganalisis data sensor, cuaca, batch, dan knowledge base untuk buat keputusan | Tinggi | Sistem |
| FR-011 | Keputusan AI disimpan dengan `decision_type`, `reasoning`, `confidence_score` | Tinggi | Sistem |
| FR-012 | ESP32 polling perintah AI dari server setiap 30 detik | Tinggi | Sistem |
| FR-013 | ESP32 mengeksekusi relay sesuai `decision_type` yang diterima | Tinggi | Sistem |
| FR-014 | ESP32 mengirim konfirmasi (ACK) setelah eksekusi perintah | Tinggi | Sistem |
| FR-015 | Sistem menolak keputusan AI dengan `confidence_score < 0.6` | Tinggi | Sistem |
| FR-016 | Admin dapat membuat, mengedit, dan menghapus akun pengguna | Tinggi | Admin |
| FR-017 | Admin dapat mengubah role pengguna (admin/operator/viewer) | Tinggi | Admin |
| FR-018 | Admin/Operator dapat membuat batch pengeringan baru | Tinggi | Admin, Operator |
| FR-019 | Sistem otomatis menandai batch `completed` saat kadar air ≤ target | Tinggi | Sistem |
| FR-020 | Sistem mengirim notifikasi ke petani saat batch selesai | Tinggi | Sistem |
| FR-021 | Sistem mengirim notifikasi saat kondisi kritis terdeteksi | Tinggi | Sistem |
| FR-022 | Notifikasi muncul real-time di sidebar tanpa refresh halaman | Sedang | Semua |
| FR-023 | Admin/Operator dapat menambah dan mengedit knowledge base AI | Sedang | Admin, Operator |
| FR-024 | Operator dapat memicu keputusan AI secara manual | Sedang | Admin, Operator |
| FR-025 | Sistem menyediakan chatbot AI untuk operator dengan konteks real-time | Sedang | Admin, Operator |
| FR-026 | Sistem menyediakan chatbot AI untuk viewer dengan bahasa sederhana | Sedang | Viewer |
| FR-027 | Dashboard viewer menampilkan estimasi waktu selesai pengeringan | Sedang | Viewer |
| FR-028 | Dashboard viewer diperbarui otomatis setiap 30 detik (polling) | Sedang | Viewer |
| FR-029 | Admin/Operator dapat export data ke Excel, CSV, dan PDF | Sedang | Admin, Operator |
| FR-030 | Dashboard menampilkan grafik historis 20 pembacaan sensor terakhir | Sedang | Admin, Operator |
| FR-031 | Sistem menghitung OEE (Availability, Performance, Quality) dari 30 hari terakhir | Sedang | Admin |
| FR-032 | Sistem mendukung dua bahasa: Indonesia dan Inggris | Rendah | Semua |
| FR-033 | Sistem mencatat semua aktivitas ke `system_logs` untuk audit trail | Sedang | Sistem |
| FR-034 | AI fallback otomatis dari Gemini ke Groq saat rate limit (HTTP 429) | Tinggi | Sistem |
| FR-035 | ESP32 fallback ke setpoint default 45°C saat offline > 15 menit | Tinggi | Sistem |
| FR-036 | Hardware safety cutoff: heater mati paksa saat suhu ≥ 58°C | Kritis | Sistem |
| FR-037 | Viewer dapat melihat riwayat batch pengeringan | Sedang | Viewer |
| FR-038 | Viewer dapat melihat dan menandai notifikasi terbaca | Sedang | Viewer |
| FR-039 | Sistem menampilkan analitik keputusan AI (AI Summary) | Rendah | Admin |
| FR-040 | ESP32 menampilkan status sistem pada LCD 16×2 secara lokal | Sedang | Sistem |
| FR-041 | Sistem menyimpan histori percakapan chatbot per session | Sedang | Sistem |
| FR-042 | Output AI dikunci ke JSON schema terstruktur, free text ditolak | Kritis | Sistem |
| FR-043 | Sistem memvalidasi `decision_type` AI terhadap 12 nilai yang diizinkan | Kritis | Sistem |
| FR-044 | Admin/Operator dapat mendaftarkan perangkat ESP32 baru | Sedang | Admin, Operator |
| FR-045 | Sistem mendeteksi device offline jika tidak ada heartbeat > threshold | Sedang | Sistem |
| FR-046 | Sistem mendukung bulk ingest sensor data (hingga 100 reading sekaligus) | Rendah | Sistem |
| FR-047 | Sistem menyimpan log aktuator setiap eksekusi relay fisik | Sedang | Sistem |
| FR-048 | Admin dapat mengubah priority weight knowledge base | Rendah | Admin |
| FR-049 | Pengguna dapat mengubah profil dan password | Rendah | Semua |
| FR-050 | Sistem menyimpan `tokens_used` setiap panggilan AI untuk monitoring biaya | Sedang | Sistem |

### 2.4.2 Non-Functional Requirements

**Performance**
- Response time API endpoint ≤ 2 detik untuk 95% request
- WebSocket broadcast latency ≤ 1 detik dari event ke browser
- AI decision response time ≤ 5 detik (Gemini) atau ≤ 3 detik (Groq fallback)
- ESP32 PID loop: 500ms, sensor send: 30 detik, command poll: 30 detik

**Availability**
- Sistem backend: uptime ≥ 99% selama jam operasional (06:00–20:00)
- ESP32 threshold mode aktif 100% waktu, independen dari koneksi internet
- Offline fallback aktif otomatis setelah 15 menit tanpa kontak server
- AI fallback (Gemini → Groq) otomatis, tanpa intervensi manual

**Security**
- Semua web route dilindungi `auth` middleware Laravel session
- API endpoint protected via Sanctum Bearer Token
- Role-based access control via `EnsureRole` middleware
- Password di-hash menggunakan bcrypt via `Hash::make()`
- CSRF protection aktif pada semua web form
- Session regeneration setelah login (cegah session fixation)
- AI output dikunci ke JSON schema — mencegah prompt injection via free text

**Usability**
- Dashboard viewer: informasi utama terlihat dalam 3 detik tanpa scroll
- Indikator warna konsisten: hijau = normal, kuning = perhatian, merah = kritis
- Teks dalam Bahasa Indonesia untuk semua pengguna akhir
- Auto-refresh 30 detik — tidak perlu refresh manual
- Chatbot dengan pertanyaan cepat (quick reply) untuk viewer

**Maintainability**
- Arsitektur MVC Laravel dengan separation of concerns
- Service layer terpisah: `AiService`, `GroqService`, `OpenWeatherService`, `NotificationService`
- Konfigurasi eksternal via `.env` — tidak ada hardcoded secret di kode
- Database migration untuk semua perubahan schema
- Knowledge base dapat diperbarui tanpa deploy ulang

**Scalability**
- Arsitektur mendukung multiple device ESP32 dalam satu server
- Database schema menggunakan `device_id` sebagai foreign key di semua tabel utama
- Caching cuaca (10 menit aktual, 30 menit forecast) mengurangi API call
- n8n workflow early exit saat tidak ada batch aktif — menghemat token AI

# KARYA TULIS ILMIAH

---

## SISTEM PENGERINGAN GABAH CERDAS BERBASIS IoT DAN LARGE LANGUAGE MODEL
### (PADI PRECISION: Pengeringan Adaptif Dikendalikan Intelijen — Precision Rice Intelligence Control and Integrated Operation Network)

---

**Disusun Oleh:**
Ihsan Alfarisi

**Program Studi:** Teknik Informatika
**Politeknik Negeri Subang**
**2026**

---

---

# ABSTRAK

Proses pengeringan gabah merupakan tahapan kritis dalam rantai pasca-panen padi yang menentukan kualitas dan nilai jual hasil panen. Pengeringan tradisional bergantung pada cuaca dan pengawasan manual, sehingga rentan terhadap kehilangan pasca-panen (post-harvest loss) yang mencapai 3–5% dari total produksi. Karya tulis ini memaparkan perancangan dan implementasi sistem PADI PRECISION — sebuah sistem pengering gabah cerdas berbasis IoT yang mengintegrasikan Large Language Model (LLM) sebagai decision engine, PID controller sebagai sistem kontrol fisik real-time, dan arsitektur multi-agen terorkestrasikan untuk mengotomasi seluruh siklus pengeringan.

Sistem dibangun menggunakan mikrokontroler ESP32 sebagai unit sensor dan kontrol fisik, server backend Laravel 13 sebagai pusat pengolahan data, Google Gemini 2.0 Flash sebagai model bahasa utama untuk analisis keputusan multi-variabel, dan n8n sebagai orkestrator pipeline AI yang berjalan setiap 15 menit. LLM dipilih sebagai decision engine karena kemampuannya melakukan reasoning lintas variabel (suhu, kelembaban, kadar air gabah, forecast cuaca, varietas padi) yang tidak dapat dimodelkan dengan algoritma PID konvensional secara efisien. Arsitektur hybrid LLM + PID diterapkan: LLM berperan sebagai supervisor yang menentukan setpoint suhu optimal, sementara PID di ESP32 mengeksekusi kontrol relay secara presisi pada interval 500ms.

Untuk menjawab sifat non-deterministik LLM, sistem menerapkan lima layer mitigasi: JSON schema enforcement, temperature rendah (0.3), whitelist decision_type, confidence score threshold (≥0.6), dan hardware safety cutoff di ESP32 (58°C). Mekanisme offline fallback berlapis memastikan pengeringan tetap berjalan meski internet putus, dengan ESP32 beralih otomatis ke threshold mode dan PID setpoint default 45°C.

Antarmuka pengguna dirancang berbasis peran (RBAC) dengan tiga tingkat: admin untuk pengelolaan sistem penuh, operator untuk manajemen batch dan monitoring teknikal, dan viewer khusus untuk petani dengan tampilan sederhana berindikator warna tanpa istilah teknis. Evaluasi sistem menunjukkan tingkat keberhasilan fungsional 100% (20/20 skenario uji), akurasi keputusan AI 86,7%, dan validitas output JSON 100%. Analisis biaya menunjukkan ROI positif dalam satu musim tanam dengan potensi mencegah kerugian Rp 3.000.000 per hektar dibandingkan total biaya implementasi Rp 255.000.

**Kata kunci:** IoT, Large Language Model, PID Controller, Pengeringan Gabah, Decision Engine, PADI PRECISION, Gemini AI, ESP32, ADDIE

---

---

# DAFTAR ISI

- BAB I — PENDAHULUAN
  - 1.1 Latar Belakang
  - 1.2 Rumusan Masalah
  - 1.3 Tujuan Penelitian
  - 1.4 Manfaat Penelitian
  - 1.5 Batasan Masalah
  - 1.6 Sistematika Penulisan
- BAB II — TINJAUAN PUSTAKA
  - 2.1 Pengeringan Gabah
  - 2.2 Internet of Things (IoT)
  - 2.3 Large Language Model (LLM)
  - 2.4 PID Controller
  - 2.5 Perbandingan LLM vs PID sebagai Decision Engine
  - 2.6 Arsitektur Multi-Agen
  - 2.7 Role-Based Access Control (RBAC)
  - 2.8 Penelitian Terkait
- BAB III — METODOLOGI
  - 3.1 Model Pengembangan ADDIE
  - 3.2 Tahap Analysis
  - 3.3 Tahap Design
  - 3.4 Tahap Development
  - 3.5 Tahap Implementation
  - 3.6 Tahap Evaluation
- BAB IV — HASIL DAN PEMBAHASAN
  - 4.1 Arsitektur Sistem PADI PRECISION
  - 4.2 Implementasi Perangkat Keras (ESP32)
  - 4.3 Implementasi Perangkat Lunak (Backend Laravel)
  - 4.4 Implementasi AI Decision Engine
  - 4.5 Implementasi Antarmuka Pengguna
  - 4.6 Pengujian Sistem
  - 4.7 Analisis Biaya dan Efisiensi
- BAB V — PENUTUP
  - 5.1 Kesimpulan
  - 5.2 Saran
- DAFTAR PUSTAKA

---

---

# BAB I — PENDAHULUAN

## 1.1 Latar Belakang

Gabah hasil panen memiliki kadar air berkisar 22–25% pada saat dipanen. Standar nasional Indonesia (SNI 0224:2008) menetapkan kadar air Gabah Kering Giling (GKG) yang layak jual adalah ≤ 14%. Proses pengeringan untuk menurunkan kadar air dari kondisi panen ke kondisi siap jual merupakan tahapan yang paling kritis dan paling rentan terhadap kesalahan dalam seluruh rantai pasca-panen padi.

Pengeringan gabah secara tradisional dilakukan dengan menjemur di bawah sinar matahari selama 2–3 hari. Metode ini sangat bergantung pada kondisi cuaca dan tidak dapat dilakukan pada musim hujan atau saat cuaca mendung. Ketika gabah tidak dapat dikeringkan tepat waktu, terjadi peningkatan kadar air yang memicu pertumbuhan jamur, fermentasi, perubahan warna beras, dan penurunan kualitas yang signifikan. Badan Pangan PBB (FAO) mencatat bahwa kehilangan pasca-panen gabah di negara berkembang berkisar 10–37% dari total produksi, dengan tahap pengeringan menyumbang 3–5% dari total kehilangan tersebut. Di Indonesia, dengan produksi padi nasional mencapai 54 juta ton GKP per tahun (BPS, 2023), kerugian akibat pengeringan tidak optimal berpotensi mencapai nilai yang sangat signifikan.

Solusi mesin pengering (cabinet dryer) berbasis energi listrik telah dikembangkan sebagai alternatif pengeringan mekanis yang tidak bergantung cuaca. Namun mesin pengering konvensional memiliki keterbatasan mendasar: kontrol suhu dan kelembaban dilakukan secara manual oleh operator, tidak ada mekanisme prediksi cuaca untuk mengantisipasi hujan secara proaktif, dan tidak ada sistem notifikasi otomatis ketika gabah telah mencapai kadar air target. Akibatnya, operator harus hadir fisik di lokasi mesin sepanjang proses pengeringan, dan kesalahan dalam penentuan waktu penghentian menyebabkan over-drying (gabah terlalu kering, berat berkurang berlebih) atau under-drying (gabah belum cukup kering, berisiko berjamur).

Perkembangan teknologi kecerdasan buatan, khususnya Large Language Model (LLM), membuka peluang baru untuk mengotomasi pengambilan keputusan pada sistem pengeringan yang melibatkan banyak variabel. LLM mampu menganalisis data sensor multi-variabel (suhu, kelembaban, kadar air, iradiasi surya) secara bersamaan, mengintegrasikan data prakiraan cuaca dari API meteorologi, dan menghasilkan keputusan aktuator yang dapat dijelaskan dalam bahasa natural. Kemampuan reasoning multi-konteks ini tidak dapat diperoleh secara efisien dari algoritma kontrol konvensional seperti PID (Proportional-Integral-Derivative) yang dirancang untuk sistem single-variable dengan setpoint tetap.

Di sisi lain, LLM memiliki keterbatasan inheren: bersifat non-deterministik (output dapat berbeda untuk input yang sama), membutuhkan koneksi internet dan API eksternal, serta berpotensi menghasilkan output yang tidak akurat (halusinasi). Untuk sistem yang mengendalikan perangkat fisik seperti pemanas dan kipas, halusinasi AI berpotensi menyebabkan kerusakan pada gabah atau bahkan kebakaran. Oleh karena itu, diperlukan arsitektur yang mengombinasikan keunggulan LLM dalam reasoning kontekstual dengan keandalan PID controller dalam kontrol fisik real-time, serta mekanisme keamanan berlapis yang tidak dapat diterobos oleh output AI yang tidak valid.

Sistem PADI PRECISION (Pengeringan Adaptif Dikendalikan Intelijen — Precision Rice Intelligence Control and Integrated Operation Network) dirancang untuk menjawab seluruh permasalahan tersebut. Sistem ini mengintegrasikan IoT (ESP32 dengan sensor DHT22), backend web berbasis Laravel, AI multi-agen (Gemini + Groq), dan antarmuka pengguna adaptif berbasis peran (RBAC) dalam satu ekosistem yang koheren.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang di atas, rumusan masalah dalam penelitian ini adalah:

1. Bagaimana merancang sistem monitoring dan kontrol otomatis untuk mesin pengering gabah yang dapat memantau kondisi pengeringan secara real-time tanpa kehadiran fisik operator?
2. Bagaimana mengintegrasikan Large Language Model (LLM) sebagai decision engine yang mampu menganalisis data multi-variabel dan menghasilkan keputusan aktuator yang aman dan dapat diandalkan?
3. Bagaimana mengatasi sifat non-deterministik LLM agar keputusan berbahaya tidak mencapai relay fisik yang mengendalikan pemanas?
4. Bagaimana memastikan sistem tetap berfungsi mengamankan gabah ketika koneksi internet atau layanan AI tidak tersedia?
5. Bagaimana merancang antarmuka yang dapat digunakan oleh petani tanpa latar belakang teknis, sekaligus menyediakan data teknikal lengkap bagi operator dan admin?

## 1.3 Tujuan Penelitian

Penelitian ini bertujuan untuk:

1. Merancang dan mengimplementasikan sistem monitoring IoT real-time untuk mesin pengering gabah menggunakan mikrokontroler ESP32 dan sensor DHT22
2. Mengembangkan arsitektur hybrid LLM + PID Controller yang memanfaatkan keunggulan masing-masing: LLM untuk reasoning multi-konteks, PID untuk kontrol fisik presisi
3. Mengimplementasikan lima layer mitigasi keamanan untuk mencegah output AI yang berbahaya mencapai relay fisik
4. Merancang mekanisme offline fallback berlapis agar pengeringan tetap berlangsung aman saat koneksi internet putus
5. Mengembangkan antarmuka pengguna adaptif berbasis RBAC dengan tiga tingkat tampilan sesuai peran (admin, operator, viewer/petani)
6. Melakukan evaluasi fungsional, keandalan AI, dan analisis biaya sistem terhadap potensi kerugian pasca-panen yang dapat dicegah

## 1.4 Manfaat Penelitian

**Bagi Petani:**
- Dapat memantau kondisi gabah dari jarak jauh melalui antarmuka yang sederhana dan mudah dipahami
- Menerima notifikasi otomatis ketika gabah sudah kering atau terjadi kondisi kritis
- Mengurangi risiko kehilangan gabah akibat over-drying atau under-drying
- Menghemat waktu dan tenaga dengan tidak perlu hadir fisik di lokasi mesin sepanjang waktu

**Bagi Operator dan Gapoktan:**
- Mendapatkan analisis kondisi pengeringan berbasis data yang objektif dan dapat dipertanggungjawabkan
- Memiliki histori lengkap setiap batch pengeringan untuk keperluan laporan dan evaluasi
- Meningkatkan efisiensi operasional mesin pengering dengan kontrol otomatis berbasis AI

**Bagi Pengembangan Ilmu:**
- Memberikan kajian empiris tentang penerapan LLM sebagai decision engine pada sistem kontrol IoT
- Menyajikan arsitektur hybrid LLM + PID yang menjawab keterbatasan masing-masing teknologi
- Mendokumentasikan strategi mitigasi halusinasi AI pada sistem yang mengendalikan perangkat fisik

## 1.5 Batasan Masalah

1. Sistem dikembangkan dan diuji pada lingkungan pengembangan lokal (Laragon) menggunakan database SQLite untuk pengujian dan PostgreSQL untuk skema produksi
2. Sensor yang digunakan adalah DHT22 untuk suhu dan kelembaban; pembacaan kadar air gabah menggunakan nilai dari seeder simulasi (sensor kadar air fisik tidak termasuk dalam ruang lingkup implementasi hardware)
3. Model LLM yang digunakan adalah Google Gemini 2.0 Flash (API free tier) dengan fallback Groq llama-3.1-8b-instant
4. Pengujian pengeringan dilakukan menggunakan data simulasi dari seeder database, bukan pengujian fisik dengan gabah nyata di lapangan
5. Sistem tidak mencakup komponen panel surya aktual; referensi "energi surya" mengacu pada data iradiasi surya dari sensor dan prakiraan cuaca untuk penentuan setpoint optimal
6. Antarmuka sistem dibuat dalam Bahasa Indonesia dan Inggris, dengan fokus evaluasi pada versi Bahasa Indonesia

## 1.6 Sistematika Penulisan

**BAB I — PENDAHULUAN** memaparkan latar belakang masalah, rumusan masalah, tujuan, manfaat, batasan masalah, dan sistematika penulisan karya tulis ini.

**BAB II — TINJAUAN PUSTAKA** menguraikan landasan teori yang menjadi dasar pengembangan sistem, mencakup teknologi pengeringan gabah, IoT, Large Language Model, PID Controller, perbandingan LLM vs PID, arsitektur multi-agen, dan penelitian terkait.

**BAB III — METODOLOGI** menjelaskan pendekatan pengembangan sistem menggunakan model ADDIE secara lengkap meliputi tahap Analysis, Design, Development, Implementation, dan Evaluation.

**BAB IV — HASIL DAN PEMBAHASAN** menyajikan arsitektur sistem yang dibangun, implementasi komponen perangkat keras dan perangkat lunak, hasil pengujian fungsional, evaluasi keandalan AI, dan analisis biaya.

**BAB V — PENUTUP** menyampaikan kesimpulan yang menjawab rumusan masalah dan saran pengembangan sistem ke depan.

**DAFTAR PUSTAKA** memuat seluruh referensi yang digunakan dalam penulisan karya tulis ini.

---



---

# BAB II — TINJAUAN PUSTAKA

## 2.1 Pengeringan Gabah

### 2.1.1 Karakteristik Kadar Air Gabah

Gabah Kering Panen (GKP) yang baru dipanen memiliki kadar air 22–28% tergantung varietas, kondisi irigasi, dan waktu panen. Standar Nasional Indonesia SNI 0224:2008 menetapkan tiga kategori:

- **GKP (Gabah Kering Panen):** kadar air ≤ 25%, diterima di penggilingan dengan potongan harga
- **GKG (Gabah Kering Giling):** kadar air ≤ 14%, harga standar penuh
- **Gabah konsumsi:** kadar air ≤ 12%, untuk penyimpanan jangka panjang

Perbedaan kadar air 1% pada gabah 5.000 kg setara sekitar 50 kg bobot basah. Pengeringan yang tidak tepat menyebabkan dua risiko: (1) under-drying — gabah masih lembab, pertumbuhan jamur Aspergillus flavus yang menghasilkan aflatoksin, (2) over-drying — kadar air terlalu rendah, beras mudah patah saat penggilingan (beras menir bertambah), penurunan rendemen giling.

### 2.1.2 Prinsip Pengeringan Mekanis

Pengeringan mekanis menggunakan heater (elemen pemanas) dan kipas untuk menciptakan aliran udara panas yang mengevaporasi kandungan air dari gabah. Parameter kritis:

- **Suhu pengeringan:** 40–55°C untuk padi. Di atas 55°C, protein gabah mengalami denaturasi dan kecambah menjadi tidak viable (untuk gabah benih). Di atas 60°C, risiko kebakaran pada tumpukan gabah.
- **Kelembaban relatif (RH):** udara dengan RH rendah memiliki kapasitas lebih tinggi untuk menyerap uap air dari gabah. Target RH exhaust: < 70%.
- **Laju pengeringan:** tidak boleh terlalu cepat (> 2% per jam) untuk mencegah retakan pada biji padi akibat gradien kelembaban.

### 2.1.3 Post-Harvest Loss

Menurut FAO (2019), kehilangan pasca-panen gabah di Asia Tenggara mencapai 14–37% dari total produksi. Kehilangan pada tahap pengeringan disebabkan oleh:
1. Gabah berjamur akibat kadar air terlalu tinggi saat disimpan
2. Penurunan kualitas beras (patah) akibat over-drying
3. Keterlambatan pengeringan yang menyebabkan fermentasi

Kementerian Pertanian RI (2022) melaporkan susut pengeringan gabah nasional sebesar 2,13%, atau setara sekitar 1,1 juta ton per tahun dari total produksi.

---

## 2.2 Internet of Things (IoT)

### 2.2.1 Definisi dan Arsitektur

Internet of Things (IoT) adalah paradigma di mana objek fisik dilengkapi sensor, perangkat lunak, dan konektivitas jaringan untuk mengumpulkan dan bertukar data (ITU-T, 2012). Arsitektur IoT umumnya terdiri dari tiga lapisan:

1. **Perception Layer (Lapisan Persepsi):** perangkat sensor yang mengumpulkan data fisik (suhu, kelembaban, cahaya)
2. **Network Layer (Lapisan Jaringan):** infrastruktur komunikasi yang mentransfer data ke server (WiFi, MQTT, HTTP)
3. **Application Layer (Lapisan Aplikasi):** platform yang memproses data dan menyediakan antarmuka pengguna

### 2.2.2 ESP32 sebagai Platform IoT

ESP32 adalah mikrokontroler System-on-Chip (SoC) buatan Espressif Systems yang mengintegrasikan prosesor dual-core Xtensa LX6 240MHz, WiFi 802.11 b/g/n, dan Bluetooth 4.2 dalam satu chip. Karakteristik yang menjadikan ESP32 ideal untuk aplikasi IoT pertanian:

- Kemampuan menjalankan PID controller dengan loop 500ms di satu core, sementara core lain menangani komunikasi WiFi
- Konsumsi daya rendah: 80–240mA saat aktif, dapat dikonfigurasi ke deep sleep
- Harga terjangkau (~Rp 85.000 per unit)
- Dukungan ekosistem Arduino IDE yang luas
- ADC 12-bit untuk pembacaan sensor analog

### 2.2.3 Protokol Komunikasi

Sistem PADI menggunakan REST API HTTP untuk komunikasi antara ESP32 dan server Laravel karena:
- Kemudahan implementasi dengan library `HTTPClient` Arduino
- Kompatibilitas dengan Laravel backend tanpa dependency tambahan
- Stateless: setiap request independen, cocok untuk polling berkala
- Debugging mudah menggunakan tools standar (curl, Postman)

Alternatif MQTT memiliki overhead lebih rendah dan cocok untuk frekuensi tinggi, namun membutuhkan broker MQTT terpisah (Mosquitto/HiveMQ) yang menambah kompleksitas deployment.

---

## 2.3 Large Language Model (LLM)

### 2.3.1 Definisi dan Kemampuan

Large Language Model (LLM) adalah model kecerdasan buatan berbasis Transformer yang dilatih pada korpus teks berskala besar untuk mempelajari pola bahasa dan pengetahuan dunia. LLM generasi terbaru seperti GPT-4, Google Gemini, dan LLaMA mampu melakukan:

- **Reasoning multi-langkah:** menganalisis masalah kompleks dengan mempertimbangkan banyak faktor sekaligus
- **In-context learning:** menyesuaikan perilaku berdasarkan instruksi dan contoh yang diberikan dalam prompt
- **Structured output:** menghasilkan teks dalam format terstruktur (JSON, tabel) sesuai schema yang ditentukan
- **Domain adaptation:** menerapkan pengetahuan umum ke domain spesifik melalui system prompt

### 2.3.2 Google Gemini 2.0 Flash

Google Gemini 2.0 Flash adalah model LLM multimodal yang dioptimasi untuk kecepatan inferensi dengan trade-off performa yang minimal dibanding model Gemini Pro. Karakteristik relevan untuk sistem PADI:

- **Kecepatan:** rata-rata 1–2 detik per request untuk output 200 token
- **JSON mode:** parameter `responseMimeType: "application/json"` memaksa output JSON murni tanpa markdown atau teks tambahan
- **Temperature control:** parameter `temperature` (0–2) mengontrol tingkat kreativitas vs determinisme output
- **Free tier:** 1.500 request/hari, 32.000 token/hari — cukup untuk operasional satu unit pengering

### 2.3.3 Sifat Non-Deterministik LLM

LLM bersifat non-deterministik secara inheren karena proses sampling probabilistik dalam generasi token. Dengan temperature > 0, model dapat menghasilkan output berbeda untuk input yang identik. Dalam konteks sistem kontrol fisik, non-determinisme ini membutuhkan mitigasi eksplisit:

- **Temperature rendah (0.1–0.3):** mengurangi varians output dengan memilih token berpeluang tertinggi
- **Schema enforcement:** `responseMimeType: "application/json"` membatasi ruang output ke format yang valid
- **Validasi output:** setiap output AI divalidasi sebelum dieksekusi ke hardware
- **Confidence thresholding:** keputusan dengan confidence rendah tidak dieksekusi

### 2.3.4 Groq sebagai Fallback LLM

Groq adalah platform inferensi LLM yang menggunakan hardware LPU (Language Processing Unit) khusus untuk menghasilkan kecepatan inferensi yang sangat tinggi. Model `llama-3.1-8b-instant` pada platform Groq menawarkan:

- Kecepatan rata-rata 0.3–0.8 detik per response
- Free tier 500.000 token/hari — signifikan lebih besar dari Gemini free tier
- Kompatibel dengan format prompt yang sama dengan Gemini (setelah adaptasi format)

---

## 2.4 PID Controller

### 2.4.1 Prinsip Kerja PID

PID (Proportional-Integral-Derivative) controller adalah algoritma kontrol umpan balik tertutup (closed-loop) yang menghitung output aktuator berdasarkan perbedaan antara nilai yang diinginkan (setpoint) dan nilai aktual (process variable):

```
u(t) = Kp·e(t) + Ki·∫e(t)dt + Kd·de(t)/dt
```

Di mana:
- `e(t)` = error = setpoint − temperature_actual
- `Kp` = Proportional gain (respons terhadap besaran error saat ini)
- `Ki` = Integral gain (respons terhadap akumulasi error masa lalu)
- `Kd` = Derivative gain (respons terhadap laju perubahan error)

### 2.4.2 Implementasi PID dalam ESP32

Dalam sistem PADI, PID controller diimplementasikan dalam firmware ESP32 dengan:
- **Loop time:** 500ms (ditentukan oleh `millis()` timer)
- **Output:** nilai 0–255 yang dikontraskan ke relay heater (ON/OFF thresholding)
- **Setpoint:** nilai suhu target yang dikirim oleh AI supervisor (35–55°C)
- **Anti-windup:** integral dibatasi dan di-reset saat safety cutoff aktif

PID bekerja secara mandiri dan tidak bergantung pada koneksi internet. Selama ESP32 beroperasi, PID menjalankan kontrol heater setiap 500ms terlepas dari ketersediaan server atau AI.

---

## 2.5 Perbandingan LLM vs PID sebagai Decision Engine

### 2.5.1 Domain Optimal PID

PID controller mencapai performa optimal untuk sistem yang memenuhi karakteristik:
- **Single-variable:** satu variabel yang dikontrol (suhu) dengan satu aktuator (heater)
- **Deterministik:** hubungan input-output dapat dimodelkan secara matematis
- **Setpoint tetap:** target yang ingin dicapai tidak berubah-ubah berdasarkan konteks eksternal
- **Real-time ketat:** keputusan kontrol dibutuhkan setiap milidetik

Untuk pengeringan gabah dengan hanya mempertahankan suhu 45°C, PID adalah solusi optimal — presisi, cepat, dan tidak membutuhkan internet.

### 2.5.2 Keterbatasan PID pada Konteks Pengeringan Multi-Variabel

Pengeringan gabah melibatkan lebih dari satu variabel yang saling berinteraksi dan tidak semua dapat dijadikan setpoint PID secara langsung:

| Faktor | PID Murni | Keterangan |
|--------|-----------|------------|
| Suhu ruang pengering | ✅ Optimal | Setpoint tunggal |
| Kelembaban relatif dalam | ⚠️ Butuh loop PID kedua | Multi-loop, parameter coupling |
| Kadar air gabah | ❌ Tidak bisa | Perlu model fisik evaporasi |
| Forecast hujan 6 jam ke depan | ❌ Tidak bisa | PID hanya reaktif |
| Adaptasi per varietas padi | ❌ Tidak bisa | Tidak ada mekanisme knowledge |
| Reasoning yang dapat dijelaskan | ❌ Tidak ada | Output numerik, bukan narasi |
| Chat interaktif dengan operator | ❌ Tidak relevan | Di luar domain kontrol |

Menangani semua variabel ini dengan PID murni membutuhkan **cascade multi-loop PID** dengan cross-coupling compensation — kompleksitas yang tidak sebanding untuk skala sistem ini.

### 2.5.3 Keunggulan LLM sebagai High-Level Decision Engine

LLM dapat melakukan **reasoning multi-variabel** secara natural karena telah mengasimilasi pengetahuan pertanian, fisika termal, dan meteorologi selama pretraining. Dengan system prompt yang tepat, LLM dapat mempertimbangkan semua variabel secara bersamaan dalam satu inferensi, menghasilkan keputusan yang:
- Kontekstual terhadap forecast cuaca
- Adaptif terhadap varietas padi via knowledge base
- Dapat dijelaskan dalam bahasa natural (field `reasoning`)
- Mencakup rekomendasi jangka pendek (2–6 jam) bukan hanya kontrol instan

### 2.5.4 Arsitektur Hybrid LLM + PID

Sistem PADI menerapkan arsitektur **dua tingkat**:

```
Tingkat Supervisi (tiap 15 menit):
LLM (Gemini) → analisis multi-variabel → tentukan setpoint suhu optimal

Tingkat Eksekusi (tiap 500ms):
PID (ESP32) → kejar setpoint via relay heater

Tingkat Keamanan (selalu aktif):
Threshold ESP32 → cutoff heater paksa jika suhu ≥ 58°C
```

Analogi: LLM seperti **air traffic controller** yang memberikan instruksi ketinggian target; PID seperti **autopilot** yang secara presisi mencapai ketinggian tersebut. ATC tidak perlu memberi perintah setiap detik; mereka menetapkan tujuan, autopilot menangani eksekusi.

### 2.5.5 Trade-off yang Diakui

| Aspek | PID Murni | LLM + PID Hybrid |
|-------|-----------|-----------------|
| Latensi kontrol | Sub-milidetik | 1–3 detik (AI), 500ms (PID loop) |
| Ketergantungan internet | Tidak | Ya (AI), Tidak (PID/threshold) |
| Determinisme | Tinggi | Sedang (mitigasi: temperature 0.3, JSON schema) |
| Kemampuan prediksi | Tidak ada | Ada (via forecast API) |
| Kemampuan penjelasan | Tidak ada | Ada (field `reasoning`) |
| Biaya operasional | Rp 0 | ~Rp 0–86.400/bulan |
| Ketahanan offline | Penuh | Degraded (threshold mode) |

---

## 2.6 Arsitektur Multi-Agen dengan n8n

### 2.6.1 n8n sebagai Workflow Orchestrator

n8n adalah platform workflow automation open-source yang memungkinkan pembuatan pipeline multi-langkah dengan antarmuka visual. Dalam sistem PADI, n8n mengorkestrasi pipeline AI decision-making yang berjalan otomatis setiap 15 menit melalui Schedule Trigger.

Pipeline n8n terdiri dari 10 node yang dieksekusi secara sekuensial:
1. **Schedule Trigger** — pemicu waktu otomatis setiap 15 menit
2. **Get Context** — ambil data sensor, cuaca, batch aktif dari Laravel API
3. **Check Active Batch** — early exit jika tidak ada batch aktif (hemat token)
4. **Get Weather Forecast** — ambil forecast 48 jam dari OpenWeatherMap via Laravel
5. **Build Prompt** — susun prompt dengan semua context data
6. **Call Gemini** — kirim prompt ke Gemini API, terima JSON keputusan
7. **Validate JSON** — validasi struktur output AI
8. **Save Decision** — simpan keputusan ke database Laravel via API
9. **Trigger Notification** — kirim notifikasi jika ada alert
10. **Log Execution** — catat hasil ke system_logs

### 2.6.2 Keuntungan Arsitektur Multi-Agen Berbasis n8n

Pemisahan pipeline AI dari aplikasi utama (Laravel) memberikan keuntungan:
- **Decoupling:** pipeline AI dapat dimodifikasi tanpa mengubah kode backend
- **Visibility:** setiap eksekusi dapat dipantau di UI n8n dengan log per-node
- **Retry logic:** n8n built-in retry dan error handling per node
- **Scheduling:** jadwal eksekusi dapat diubah tanpa deploy ulang aplikasi

---

## 2.7 Role-Based Access Control (RBAC)

RBAC adalah model keamanan yang membatasi akses sistem berdasarkan peran pengguna. NIST (2004) mendefinisikan RBAC sebagai kebijakan di mana hak akses diberikan kepada role, bukan kepada individual pengguna secara langsung.

Sistem PADI mengimplementasikan tiga role dengan prinsip **least privilege**:

| Role | Hak Akses | Target Pengguna |
|------|-----------|-----------------|
| Admin | Semua fitur + manajemen user + knowledge base | Gapoktan / Dinas Pertanian |
| Operator | Batch, device, AI trigger, chat, export | Petugas mesin pengering |
| Viewer | Dashboard sederhana, chat, notifikasi, batch (read-only) | Petani |

Implementasi teknis menggunakan Laravel middleware `EnsureRole` yang memeriksa field `role` di tabel `users` pada setiap request yang membutuhkan otorisasi.

---

## 2.8 Penelitian Terkait

**Mulyani & Suryana (2022)** mengembangkan sistem monitoring suhu dan kelembaban mesin pengering gabah berbasis ESP8266 dengan notifikasi SMS. Sistem berhasil memantau parameter pengeringan namun tidak memiliki kemampuan pengambilan keputusan otomatis atau integrasi data cuaca.

**Prasetyo et al. (2023)** merancang kontrol PID untuk mesin pengering gabah berbasis mikrokontroler dengan setpoint suhu 45°C. Penelitian menunjukkan PID efektif untuk kontrol suhu single-variable namun tidak mempertimbangkan variabel kontekstual seperti kadar air gabah dan forecast cuaca.

**Ahmad & Hidayat (2023)** menerapkan fuzzy logic controller pada sistem pengeringan gabah untuk mempertimbangkan dua variabel (suhu dan kelembaban). Fuzzy logic memberikan fleksibilitas lebih dari PID namun aturan (rules) harus dikodekan secara manual dan tidak dapat beradaptasi terhadap knowledge baru tanpa modifikasi program.

**Distinctiveness of PADI PRECISION:** Sistem yang dikembangkan dalam karya tulis ini berbeda dari penelitian-penelitian tersebut dalam tiga aspek utama: (1) penggunaan LLM sebagai decision engine yang mengintegrasikan forecast cuaca dan knowledge base varietas, (2) arsitektur hybrid LLM + PID yang mempertahankan keandalan kontrol fisik sambil menambah kecerdasan kontekstual, dan (3) antarmuka berbasis RBAC yang secara eksplisit memisahkan kebutuhan petani awam dengan operator teknikal.

---



---

# BAB III — METODOLOGI

## 3.1 Model Pengembangan ADDIE

Pengembangan sistem PADI PRECISION menggunakan model ADDIE (Analysis, Design, Development, Implementation, Evaluation). ADDIE adalah model pengembangan sistematis yang membagi proses menjadi lima tahap berurutan dengan mekanisme feedback antar tahap. Model ini dipilih karena:

1. Setiap tahap menghasilkan deliverable yang konkret dan terverifikasi
2. Tahap Evaluation di akhir memvalidasi bahwa sistem memenuhi kebutuhan yang diidentifikasi di tahap Analysis
3. Cocok untuk proyek yang melibatkan komponen hardware dan software secara bersamaan

```
[Analysis] → [Design] → [Development] → [Implementation] → [Evaluation]
      ↑_____________________feedback____________________________|
```

---

## 3.2 Tahap Analysis

### 3.2.1 Analisis Permasalahan

Analisis permasalahan dilakukan dengan mengidentifikasi akar penyebab kegagalan proses pengeringan gabah menggunakan diagram Fishbone (Ishikawa):

**Kategori Manusia:**
- Operator tidak hadir di lokasi pengering selama proses berlangsung
- Ketidakpastian dalam menentukan kapan gabah sudah cukup kering
- Ketergantungan pada pengalaman individual, bukan data objektif

**Kategori Mesin:**
- Tidak ada sensor terintegrasi untuk monitoring real-time
- Kontrol relay pemanas dan kipas dilakukan manual
- Tidak ada data logger untuk analitik historis

**Kategori Metode:**
- Tidak ada sistem notifikasi otomatis ke petani
- Tidak ada integrasi dengan data prakiraan cuaca
- Tidak ada knowledge base varietas padi yang terdokumentasi

**Kategori Lingkungan:**
- Cuaca tidak dapat diprediksi secara manual dengan akurat
- Musim hujan membuat pengeringan konvensional tidak dapat dilakukan

**Masalah Utama yang Disimpulkan:**
> Pengeringan gabah konvensional tidak memiliki mekanisme otomatis untuk monitoring, pengambilan keputusan berbasis data multi-variabel, dan notifikasi, sehingga kualitas pengeringan bergantung sepenuhnya pada kehadiran fisik dan pengalaman operator.

### 3.2.2 Analisis Kebutuhan Pengguna

Identifikasi tiga tipe pengguna dengan kebutuhan yang berbeda:

**Admin (Gapoktan / Dinas Pertanian):**
- Manajemen user, perangkat, dan knowledge base AI
- Akses penuh ke semua data teknikal dan analitik
- Export laporan ke Excel/PDF
- Monitoring penggunaan token AI dan biaya operasional

**Operator (Petugas Mesin Pengering):**
- Manajemen batch pengeringan (buat, update, selesaikan)
- Monitoring real-time sensor dan keputusan AI
- Trigger analisis AI secara manual jika diperlukan
- Chat dengan AI untuk konsultasi kondisi pengeringan

**Viewer (Petani):**
- Dashboard sederhana dengan status pengeringan yang mudah dipahami
- Indikator warna (hijau/kuning/merah) tanpa istilah teknis
- Estimasi waktu selesai dalam satuan jam
- Notifikasi ketika gabah sudah kering atau terjadi kondisi darurat
- Chat AI dalam bahasa warung

**Prinsip Desain Antarmuka Viewer:**
- *Progressive disclosure:* informasi ringkas ditampilkan dulu; detail hanya jika diminta
- *Visual hierarchy:* status besar di atas, angka sensor di tengah, histori di bawah
- *Familiar terminology:* "Kelembaban Dalam Ruang" bukan "humidity_inside"
- *Color coding konsisten:* hijau = normal, kuning = perhatian, merah = kritis

### 3.2.3 Analisis Komponen Sistem

Berdasarkan kebutuhan, sistem memerlukan komponen-komponen berikut:

| Komponen | Teknologi | Fungsi |
|----------|-----------|--------|
| Mikrokontroler | ESP32 | Baca sensor, jalankan PID, kontrol relay |
| Sensor suhu/RH | DHT22 | Ukur suhu dan kelembaban dalam/luar |
| Backend | Laravel 13 / PHP 8.3 | API server, logika bisnis, penyimpanan data |
| Database | PostgreSQL (prod) / SQLite (dev) | Simpan semua data sensor, keputusan, log |
| Real-time | Laravel Reverb (WebSocket) | Push update ke dashboard tanpa refresh |
| AI Decision Engine | Gemini 2.0 Flash | Analisis multi-variabel, tentukan setpoint |
| AI Fallback | Groq (llama-3.1-8b-instant) | Backup AI saat Gemini rate limit |
| Workflow Orchestrator | n8n | Pipeline AI otomatis setiap 15 menit |
| Cuaca API | OpenWeatherMap | Data cuaca aktual + forecast 48 jam |
| Frontend | Blade + TailwindCSS + Alpine.js | Antarmuka web responsif |

---

## 3.3 Tahap Design

### 3.3.1 Arsitektur Sistem Keseluruhan

Sistem PADI PRECISION terdiri dari empat lapisan:

**Lapisan 1 — Physical Layer:**
ESP32 + DHT22 + Relay 4-channel + LCD I2C 16×2. Firmware menjalankan PID loop 500ms, kirim data ke server tiap 30 detik, poll command tiap 30 detik.

**Lapisan 2 — Application Layer:**
Laravel 13 menyediakan REST API untuk IoT device, web dashboard untuk pengguna, WebSocket server untuk real-time update, dan scheduler untuk trigger pembaruan data cuaca.

**Lapisan 3 — Intelligence Layer:**
n8n workflow (Schedule: 15 menit) → ambil context via API → panggil Gemini → simpan keputusan → notifikasi. Gemini menghasilkan setpoint suhu dan konfigurasi aktuator dalam JSON terstruktur.

**Lapisan 4 — Presentation Layer:**
Dashboard web adaptif berdasarkan role. Admin dan operator: tampilan teknikal lengkap. Viewer: tampilan sederhana dengan indikator warna.

### 3.3.2 Perancangan Skema Database

Skema database terdiri dari 11 tabel utama:

| Tabel | Fungsi | Field Kunci |
|-------|--------|-------------|
| `users` | Akun pengguna dengan RBAC | `email`, `role` (admin/operator/viewer) |
| `devices` | Daftar ESP32 terdaftar | `serial_number`, `device_api_key`, `status` |
| `sensor_readings` | Histori pembacaan sensor | `device_id`, `temperature_inside`, `humidity_inside`, `grain_moisture`, `solar_irradiance`, `pid_setpoint` |
| `drying_batches` | Siklus pengeringan gabah | `device_id`, `rice_variety`, `initial_moisture`, `current_moisture`, `target_moisture`, `status`, `petani_name` |
| `ai_decisions` | Keputusan AI tiap siklus | `device_id`, `decision_type`, `reasoning`, `confidence_score`, `output_action`, `execution_status` |
| `actuator_logs` | Histori eksekusi relay fisik | `device_id`, `actuator_type`, `state`, `triggered_by` |
| `weather_data` | Data cuaca dari OpenWeatherMap | `temperature`, `humidity`, `solar_irradiance`, `type` (actual/forecast) |
| `ai_conversations` | Histori chat AI | `session_id`, `role`, `message`, `tokens_used` |
| `knowledge_bases` | Basis pengetahuan AI | `category`, `title`, `content`, `priority_weight` |
| `notifications` | Notifikasi ke pengguna | `user_id`, `type`, `title`, `message`, `read_at` |
| `system_logs` | Audit trail aktivitas | `level`, `message`, `context` |

### 3.3.3 Perancangan Alur Komunikasi ESP32 ↔ Server

```
ESP32 (tiap 30 detik):
  POST /api/iot/sensor
  {
    "device_api_key": "...",
    "temperature_inside": 45.2,
    "humidity_inside": 62.1,
    "grain_moisture": 18.5,
    "solar_irradiance": 450.0,
    "pid_setpoint": 47.0
  }
  → Respons: HTTP 201

ESP32 (tiap 30 detik):
  GET /api/iot/pending-command?device_id=1&api_key=...
  → Respons: {
    "has_command": true,
    "decision_id": 42,
    "decision_type": "adjust_temperature",
    "target_temperature": 48,
    "fan": false,
    "mode": "auto"
  }

ESP32 (setelah eksekusi relay):
  POST /api/iot/command-ack
  {
    "decision_id": 42,
    "status": "success",
    "temperature_at_execution": 45.8
  }
```

### 3.3.4 Perancangan Pipeline AI Multi-Agen (n8n)

Pipeline n8n dirancang dengan alur berikut:

1. **Schedule Trigger** (setiap 15 menit) → mulai eksekusi
2. **HTTP: Get Context** → `GET /api/ai/context?device_id=1`
   - Kembalikan: sensor terbaru, cuaca aktual, forecast 48 jam, batch aktif, knowledge base top-5
3. **IF: Active Batch?** → jika tidak ada batch aktif, **Stop** (early exit, hemat token)
4. **HTTP: AI Decision** → `POST /api/ai/decide` dengan payload context lengkap
   - Backend Laravel memanggil Gemini API dengan system prompt dan context
   - Gemini mengembalikan JSON keputusan
   - Backend menyimpan keputusan ke `ai_decisions`
5. **IF: Has Alert?** → jika `risk_level: high` atau `alerts` tidak kosong
6. **HTTP: Send Notification** → `POST /api/ai/notify`
7. **HTTP: Log Execution** → catat hasil ke `system_logs`

### 3.3.5 Perancangan Keamanan Berlapis

Keamanan sistem dirancang dalam lima layer untuk mencegah keputusan AI berbahaya mencapai relay fisik:

**Layer 1 — JSON Schema Enforcement (API Level)**
Parameter `responseMimeType: "application/json"` pada Gemini API dan system prompt eksplisit memaksa output JSON terstruktur. Output non-JSON ditolak di level API.

**Layer 2 — Temperature Deterministik (Model Level)**
`temperature: 0.3` pada konfigurasi Gemini untuk mode decision (vs 0.7 untuk chat) mengurangi varians output.

**Layer 3 — Whitelist Validasi (Server Level)**
`decision_type` divalidasi terhadap 12 nilai yang diizinkan. Nilai tidak dikenal di-fallback ke `other` — tidak ada relay action berbahaya.

**Layer 4 — Confidence Threshold (API Level)**
Endpoint polling command hanya mengembalikan keputusan dengan `confidence_score ≥ 0.6`. Keputusan "tidak yakin" dari AI tidak dikirim ke ESP32.

**Layer 5 — Hardware Safety Cutoff (Firmware Level)**
`safetyCheck()` di ESP32 memaksa heater mati jika `temperature ≥ 58°C`, berjalan setiap 500ms, tidak dapat di-override dari luar.

---

## 3.4 Tahap Development

### 3.4.1 Pengembangan Firmware ESP32

Firmware dikembangkan menggunakan Arduino IDE dengan bahasa C++. Struktur utama firmware:

**Inisialisasi:**
- Konfigurasi pin relay (OUTPUT), sensor DHT22, LCD I2C
- Sambung WiFi dengan auto-reconnect `ensureWiFi()`
- Inisialisasi variabel PID (setpoint, integral, lastError)

**Loop Utama (500ms):**
1. `safetyCheck()` — cek suhu kritis, matikan heater jika ≥ 58°C
2. `offlineSafetyCheck()` — cek timeout server (>15 menit), reset ke default
3. `readDHT()` — baca sensor setiap 2 detik (batas refresh rate DHT22)
4. `computePID()` — hitung output PID berdasarkan setpoint dan suhu aktual
5. `applyHeaterControl()` — eksekusi relay heater berdasarkan output PID
6. `controlFanExhaust()` — kontrol kipas dan exhaust berdasarkan threshold RH
7. `sendSensorData()` — kirim ke server tiap 30 detik (jika WiFi OK)
8. `pollCommand()` — ambil command AI dari server tiap 30 detik
9. `updateLCD()` — perbarui display LCD

**Konstanta Keamanan yang Hardcoded:**
```cpp
const float TEMP_CRITICAL_OFF  = 58.0;  // °C — matikan heater paksa
const float TEMP_SETPOINT_MIN  = 35.0;  // °C — batas bawah setpoint yang diterima dari AI
const float TEMP_SETPOINT_MAX  = 55.0;  // °C — batas atas setpoint yang diterima dari AI
const float TEMP_FAN_ON        = 38.0;  // °C — fan ON otomatis
const float RH_EXHAUST_ON      = 65.0;  // %  — exhaust ON otomatis
const long  OFFLINE_TIMEOUT    = 900000; // ms = 15 menit
```

### 3.4.2 Pengembangan Backend Laravel

Backend dikembangkan menggunakan arsitektur MVC dengan Service Layer terpisah:

**Controller Layer:**
- `IoTSensorController` — menerima data sensor dari ESP32, simpan ke DB, broadcast WebSocket
- `IoTCommandController` — kembalikan pending command untuk ESP32 (filtered by confidence)
- `AiDecisionController` — endpoint untuk n8n: ambil context, simpan keputusan
- `DashboardController` — sajikan data ke view dashboard
- `ViewerDashboardController` — sajikan data sederhana ke view petani
- `AiChatController` — endpoint chatbot untuk operator dan viewer

**Service Layer:**
- `AiService` — integrasi Gemini API, fallback ke Groq, parsing dan validasi JSON
- `GroqService` — integrasi Groq API sebagai LLM alternatif
- `OpenWeatherService` — ambil cuaca aktual dan forecast, dengan caching 10/30 menit
- `NotificationService` — kirim notifikasi ke pengguna yang relevan

**Model Layer dengan Scope:**
- `SensorReading::valid()` — hanya data valid (is_valid = true)
- `SensorReading::forDevice($id)` — filter per device
- `DryingBatch::active()` — hanya batch status active
- `AiDecision::pending()` — hanya keputusan belum dieksekusi

### 3.4.3 Pengembangan Dashboard Viewer (Petani)

Dashboard khusus viewer dikembangkan dengan filosofi **progressive disclosure** — hanya menampilkan informasi yang relevan dan actionable bagi petani:

**Kartu Status Pengeringan:**
- Status besar (AKTIF / JEDA / SELESAI) dengan warna indikator
- Kadar air sekarang vs target dalam format persentase besar
- Progress bar visual dari awal ke target
- Estimasi waktu tersisa (dalam satuan jam)

**Kartu Kondisi Mesin:**
- Suhu dalam mesin (°C) — angka besar, bukan label teknis
- Kelembaban dalam mesin (%) — dengan keterangan awam ("Cukup Kering" / "Agak Lembab")
- Status pemanas: gambar api hijau (ON) atau abu-abu (OFF)
- Status kipas: gambar kipas berputar atau berhenti

**Rekomendasi AI Terbaru:**
- Teks rekomendasi dari field `reasoning` keputusan AI terbaru
- Dalam Bahasa Indonesia sederhana
- Dengan timestamp "5 menit lalu", "1 jam lalu"

**Auto-refresh:** data diperbarui setiap 30 detik via JavaScript polling ke endpoint `/viewer/dashboard/poll` tanpa reload halaman penuh.

---

## 3.5 Tahap Implementation

### 3.5.1 Deployment Server Laravel

Server backend di-deploy pada lingkungan pengembangan menggunakan Laragon di sistem operasi Windows. Proses deployment:

1. Clone repositori dan install dependency PHP:
   ```bash
   composer install
   ```
2. Install dependency frontend dan build aset:
   ```bash
   npm install && npm run build
   ```
3. Konfigurasi file `.env`:
   - `GEMINI_API_KEY` — API key Google Gemini
   - `GROQ_API_KEY` — API key Groq
   - `OPENWEATHER_API_KEY` — API key OpenWeatherMap
   - `WEATHER_LAT` dan `WEATHER_LON` — koordinat lokasi (Margahurip, Banjaran)
   - `REVERB_APP_KEY`, `REVERB_APP_SECRET` — konfigurasi WebSocket
4. Jalankan migrasi database dan seeder:
   ```bash
   php artisan migrate --seed
   ```
5. Jalankan semua service secara paralel:
   ```bash
   composer dev
   # Menjalankan: php artisan serve + queue:listen + reverb:start + npm run dev
   ```

Server berjalan di `http://127.0.0.1:8000` dan dapat diakses oleh ESP32 dalam jaringan lokal yang sama via IP address server.

### 3.5.2 Upload Firmware ke ESP32

Firmware `esp32_solardryerai.ino` di-upload menggunakan Arduino IDE 2.x dengan konfigurasi:

- Board: **ESP32 Dev Module**
- Upload Speed: **921600 baud**
- CPU Frequency: **240MHz (WiFi/BT)**

Library yang diinstall via Arduino Library Manager:
- DHT sensor library by Adafruit (v1.4.6)
- Adafruit Unified Sensor (v1.1.9)
- LiquidCrystal I2C by Frank de Brabander (v1.1.2)
- ArduinoJson by Benoit Blanchon (v6.21.4)
- HTTPClient (built-in ESP32)

Sebelum upload, tiga parameter dikonfigurasi sesuai lingkungan deployment:
```cpp
const char* WIFI_SSID     = "nama_wifi_anda";
const char* WIFI_PASSWORD = "password_wifi";
const char* SERVER_URL    = "http://192.168.x.x:8000";
```

Setelah upload berhasil, ESP32 otomatis terhubung ke WiFi, mendaftarkan diri dengan device API key, dan mulai mengirim data sensor setiap 30 detik.

### 3.5.3 Konfigurasi Workflow n8n

File `n8n-workflow.json` diimport ke instance n8n lokal (berjalan di `http://localhost:5678`). Dua URL dikonfigurasi sesuai server:
- Node "Get Context": `GET http://127.0.0.1:8000/api/ai/context?device_id=1`
- Node "Save Decision": `POST http://127.0.0.1:8000/api/ai/decide`

Workflow diuji manual dengan tombol **"Execute Workflow"** untuk memverifikasi semua 10 node berjalan tanpa error. Setelah verifikasi berhasil, Schedule Trigger diaktifkan untuk eksekusi otomatis setiap 15 menit.

### 3.5.4 Pengujian Koneksi End-to-End

Setelah semua komponen terpasang, dilakukan pengujian koneksi end-to-end:

1. **ESP32 → Server:** verifikasi data sensor tersimpan di tabel `sensor_readings` dengan tool database browser
2. **Server → n8n → AI:** eksekusi manual workflow, verifikasi keputusan tersimpan di `ai_decisions`
3. **Server → ESP32:** verifikasi ESP32 menerima dan mengeksekusi command dari server (pantau LCD ESP32)
4. **WebSocket:** verifikasi dashboard browser memperbarui data secara real-time saat data sensor masuk
5. **Notifikasi:** simulasikan kondisi batch selesai, verifikasi notifikasi muncul di sidebar

---

## 3.6 Tahap Evaluation

### 3.6.1 Metodologi Pengujian

Evaluasi sistem dilakukan menggunakan tiga metode pengujian yang saling melengkapi:

1. **Pengujian Fungsional (Black-box Testing)** — memverifikasi setiap fitur menghasilkan output sesuai spesifikasi, tanpa memeriksa implementasi internal
2. **Pengujian Keandalan AI** — mengukur konsistensi dan akurasi output AI decision engine pada berbagai skenario input
3. **Pengujian Keamanan** — memverifikasi semua layer mitigasi keamanan bekerja sesuai rancangan

### 3.6.2 Rencana Pengujian Fungsional

Skenario pengujian mencakup seluruh alur kerja sistem:

**Kelompok Autentikasi & Otorisasi:**
- Login dengan role berbeda (admin, operator, viewer)
- Akses halaman yang tidak diizinkan oleh role tertentu (harus ditolak)

**Kelompok IoT:**
- ESP32 kirim data sensor valid dan invalid
- ESP32 polling command dan menerima keputusan AI
- ESP32 kirim ACK setelah eksekusi relay

**Kelompok AI:**
- AI menghasilkan keputusan tepat untuk kondisi suhu kritis
- AI menghasilkan keputusan tepat saat forecast hujan tinggi
- Keputusan dengan confidence rendah tidak dikirim ke ESP32
- Fallback Gemini → Groq saat rate limit

**Kelompok Safety:**
- Hardware cutoff ESP32 aktif saat suhu 59°C
- Offline fallback aktif setelah 15 menit tanpa server
- Output AI JSON tidak valid tidak disimpan/dieksekusi

**Kelompok Dashboard:**
- Auto-refresh viewer dashboard setiap 30 detik
- Export data ke Excel berfungsi
- Notifikasi real-time via WebSocket

### 3.6.3 Kriteria Keberhasilan

| Aspek | Kriteria Minimum |
|-------|-----------------|
| Fungsional | ≥ 90% skenario pengujian berhasil |
| Keandalan AI (JSON valid) | 100% output JSON valid |
| Keandalan AI (whitelist type) | 100% decision_type dalam whitelist |
| Keandalan AI (akurasi keputusan) | ≥ 80% keputusan tepat sesuai kondisi |
| Safety cutoff | 100% aktif saat suhu ≥ 58°C |
| Fallback AI | 100% berhasil saat Gemini 429 |

---



---

# BAB IV — HASIL DAN PEMBAHASAN

## 4.1 Arsitektur Sistem PADI PRECISION

### 4.1.1 Gambaran Umum Arsitektur

Sistem PADI PRECISION dibangun dalam arsitektur empat lapisan yang saling terintegrasi:

```
┌─────────────────────────────────────────────────────────┐
│  PRESENTATION LAYER                                     │
│  Dashboard Admin/Operator (teknikal) │ Dashboard Viewer │
│  Chat AI Operator                    │ Chat AI Petani   │
└───────────────────┬─────────────────────────────────────┘
                    │ HTTP / WebSocket
┌───────────────────▼─────────────────────────────────────┐
│  APPLICATION LAYER (Laravel 13)                         │
│  REST API IoT │ Web Routes │ WebSocket (Reverb)         │
│  AiService │ GroqService │ OpenWeatherService           │
└───────┬───────────────────────────────┬─────────────────┘
        │ HTTP (n8n)                    │ HTTP (ESP32)
┌───────▼───────────┐       ┌───────────▼─────────────────┐
│  INTELLIGENCE     │       │  PHYSICAL LAYER             │
│  LAYER (n8n)      │       │  ESP32 + DHT22 + Relay      │
│  Schedule: 15mnt  │       │  PID Loop: 500ms            │
│  Gemini → Groq    │       │  Safety cutoff: 58°C        │
└───────────────────┘       └─────────────────────────────┘
```

### 4.1.2 Alur Data Lengkap

1. ESP32 membaca sensor DHT22 setiap 500ms dan menjalankan PID loop
2. Setiap 30 detik, ESP32 mengirim snapshot data sensor ke `POST /api/iot/sensor`
3. Laravel menyimpan data ke `sensor_readings` dan broadcast via WebSocket ke dashboard
4. n8n (setiap 15 menit) mengambil context lengkap dari `GET /api/ai/context`
5. n8n meneruskan context ke Laravel `POST /api/ai/decide`, yang memanggil Gemini API
6. Gemini mengembalikan JSON keputusan; Laravel menyimpan ke `ai_decisions`
7. ESP32 mengambil pending command via `GET /api/iot/pending-command`
8. ESP32 mengeksekusi relay sesuai `target_temperature` dan mengupdate setpoint PID
9. ESP32 mengirim ACK ke `POST /api/iot/command-ack`; Laravel update status keputusan

---

## 4.2 Implementasi Perangkat Keras

### 4.2.1 Komponen Hardware

| Komponen | Spesifikasi | Fungsi |
|----------|-------------|--------|
| ESP32 Dev Module | Dual-core 240MHz, WiFi 802.11n | Mikrokontroler utama |
| DHT22 × 2 | Suhu ±0.5°C, RH ±2–5% | Sensor dalam dan luar ruang |
| Relay Module 4-ch | 5V trigger, 10A/250VAC | Kontrol heater, kipas, exhaust |
| LCD I2C 16×2 | Alamat 0x27, backlight biru | Display status lokal |
| PCB + kabel | — | Wiring komponen |
| Casing plastik | IP54 | Pelindung dari debu dan percikan air |

**Total biaya hardware: Rp 255.000**

### 4.2.2 Konfigurasi Pin ESP32

| Pin | Komponen | Mode |
|-----|----------|------|
| GPIO 4 | DHT22 (dalam) | INPUT |
| GPIO 5 | DHT22 (luar) | INPUT |
| GPIO 26 | Relay Heater | OUTPUT |
| GPIO 27 | Relay Kipas | OUTPUT |
| GPIO 14 | Relay Exhaust | OUTPUT |
| GPIO 21 | LCD SDA (I2C) | I2C |
| GPIO 22 | LCD SCL (I2C) | I2C |

### 4.2.3 Implementasi PID Controller

PID controller diimplementasikan dalam fungsi `computePID()` dengan parameter:
- **Kp = 2.0** — respons proporsional terhadap selisih suhu vs setpoint
- **Ki = 0.1** — koreksi akumulasi error jangka panjang
- **Kd = 0.5** — peredam osilasi (derivative)
- **Loop time:** 500ms
- **Anti-windup:** integral di-clamp pada rentang ±100 dan di-reset saat safety cutoff aktif

Relay heater dikendalikan dengan PWM-like thresholding: jika output PID > 127, relay ON; jika ≤ 127, relay OFF. Pendekatan ini cukup untuk kontrol suhu dengan inersia termal tinggi pada cabinet dryer.

### 4.2.4 Mekanisme Offline Fallback ESP32

ESP32 menerapkan dua level fallback:

**Level 1 — Setpoint Terakhir (0–15 menit offline):**
Jika server tidak merespons saat polling, ESP32 melanjutkan dengan setpoint terakhir yang berhasil diterima dari AI. PID tetap berjalan normal.

**Level 2 — Default Safety Mode (>15 menit offline):**
Jika server tidak dapat dihubungi selama lebih dari 15 menit:
```cpp
aiActive = false;
pidSetpoint = TEMP_SETPOINT_DEF;  // 45.0°C
fanOverride = false;               // fan kembali ke threshold otomatis
```
LCD menampilkan "!SERVER OFFLINE! / OFFLINE Xm DEF". Pengeringan tetap berlanjut di setpoint aman 45°C tanpa membutuhkan internet.



---

## 4.3 Implementasi Perangkat Lunak Backend

### 4.3.1 Struktur Aplikasi Laravel

Aplikasi dibangun menggunakan pola MVC dengan Service Layer. Struktur direktori utama:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── IoTSensorController.php    — terima data sensor dari ESP32
│   │   │   ├── IoTCommandController.php   — kembalikan pending command ke ESP32
│   │   │   └── AiDecisionController.php   — endpoint untuk n8n
│   │   ├── DashboardController.php
│   │   ├── ViewerDashboardController.php  — dashboard khusus petani
│   │   └── AiChatController.php
│   └── Middleware/
│       └── EnsureRole.php                — RBAC middleware
├── Services/
│   ├── AiService.php                     — Gemini API + fallback Groq
│   ├── GroqService.php                   — Groq API
│   ├── OpenWeatherService.php            — cuaca aktual + forecast
│   └── NotificationService.php          — kirim notifikasi ke pengguna
└── Models/
    ├── SensorReading.php
    ├── DryingBatch.php
    ├── AiDecision.php
    └── ...
```

### 4.3.2 Implementasi RBAC

Middleware `EnsureRole` memeriksa field `role` pengguna yang terautentikasi:

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
        return redirect()->route('dashboard')
            ->with('error', 'Akses tidak diizinkan.');
    }
    return $next($request);
}
```

Route group dipisah berdasarkan role:
- Route tanpa `EnsureRole` — semua role dapat akses (dashboard utama, profil)
- `middleware(['auth', 'role:admin'])` — hanya admin
- `middleware(['auth', 'role:admin,operator'])` — admin dan operator
- `middleware(['auth', 'role:viewer'])` — khusus viewer dengan prefix `/viewer`

### 4.3.3 Implementasi Confidence Threshold

Endpoint `GET /api/iot/pending-command` hanya mengembalikan keputusan dengan confidence cukup:

```php
$decision = AiDecision::where('device_id', $deviceId)
    ->where('execution_status', 'pending')
    ->whereNull('command_sent_at')
    ->where('confidence_score', '>=', 0.6)  // Layer 4 mitigasi halusinasi
    ->latest('decided_at')
    ->first();
```

Keputusan dengan `confidence_score < 0.6` tetap tersimpan di database untuk keperluan analitik dan audit, namun tidak pernah dikirim ke ESP32.

### 4.3.4 Implementasi WebSocket Real-Time

Laravel Reverb digunakan sebagai WebSocket server. Saat data sensor baru masuk dari ESP32:

```php
// IoTSensorController::store()
broadcast(new SensorUpdated($reading, $device))->toOthers();
```

Event `SensorUpdated` membawa data sensor terbaru ke semua browser yang terhubung via channel `device.{id}`. Dashboard JavaScript menerima event ini dan memperbarui tampilan tanpa reload halaman.

---

## 4.4 Implementasi AI Decision Engine

### 4.4.1 System Prompt Decision Engine

System prompt dirancang dengan tiga komponen utama:

1. **Definisi peran:** AI sebagai Supervisory Controller, bukan direct relay controller
2. **Format output wajib:** JSON schema lengkap dengan semua field yang diharapkan
3. **Panduan keputusan:** aturan berdasarkan kondisi (kadar air, forecast hujan, suhu kritis, iradiasi surya)

Bagian kritis yang menegaskan arsitektur hybrid:
> *"ESP32 menjalankan PID controller yang mengatur heater secara real-time (tiap 500ms). Tugas Anda: tentukan SETPOINT SUHU OPTIMAL yang akan dikejar PID. Anda TIDAK mengontrol relay heater langsung — PID yang mengurus naik/turun heater."*

### 4.4.2 Alur Pemrosesan Keputusan AI

```
n8n → POST /api/ai/decide (context JSON)
  ↓
AiDecisionController::store()
  ↓
AiService::analyzeAndDecide($context)
  ├─ buildDecisionSystemPrompt()  — system prompt
  ├─ buildDecisionPrompt($context) — prompt dengan data aktual
  ├─ callGeminiDecision()          — temperature: 0.3, responseMimeType: json
  │     ├─ Response OK  → parseDecisionJson()
  │     └─ HTTP 429/503 → callGroqDecision() (fallback)
  └─ parseDecisionJson()
        ├─ Validasi JSON syntax
        ├─ Cek field 'decision_type' wajib ada
        └─ Whitelist 12 nilai decision_type
  ↓
Simpan ke ai_decisions (termasuk confidence_score, tokens_used, model)
  ↓
Respons ke n8n: { decision_id, decision_type, confidence_score }
```

### 4.4.3 Implementasi 5 Layer Mitigasi Keamanan AI

**Layer 1 — JSON Schema Enforcement:**
```php
'generationConfig' => [
    'temperature'      => 0.3,
    'maxOutputTokens'  => 1024,
    'responseMimeType' => 'application/json',  // paksa output JSON murni
],
```

**Layer 2 — Temperature Rendah:**
Mode decision menggunakan `temperature: 0.3` vs mode chat yang menggunakan `temperature: 0.7`. Nilai lebih rendah = distribusi probabilitas token lebih "peaked" = output lebih konsisten dan deterministik.

**Layer 3 — Whitelist Validasi:**
```php
$validTypes = [
    'start_heater','stop_heater','start_fan','stop_fan',
    'adjust_temperature','adjust_airflow','pause_drying','resume_drying',
    'alert_operator','open_roof','close_roof','other',
];
if (!in_array($decision['decision_type'], $validTypes)) {
    $decision['decision_type'] = 'other';  // fallback aman
}
```

**Layer 4 — Confidence Threshold (diimplementasikan di IoTCommandController):**
Query polling command di-filter dengan `where('confidence_score', '>=', 0.6)`.

**Layer 5 — Hardware Safety Cutoff (firmware ESP32):**
```cpp
void safetyCheck() {
    if (temperature >= TEMP_CRITICAL_OFF) {  // 58.0°C
        setRelay(PIN_RELAY_HEATER, false);
        heaterState = false;
        pidIntegral = 0;  // reset anti-windup
    }
}
```
Fungsi ini dipanggil **pertama** di setiap iterasi loop 500ms sebelum komputasi PID apapun.

### 4.4.4 Analisis Konsumsi Token AI

Setiap siklus keputusan n8n mengonsumsi token sebagai berikut:

| Komponen | Token Input (est.) |
|----------|--------------------|
| System prompt decision engine | ~600 |
| Data sensor terbaru (7 field) | ~80 |
| Data cuaca aktual (8 field) | ~100 |
| Forecast summary (6 field) | ~80 |
| Status batch aktif (5 field) | ~60 |
| **Total input** | **~920** |
| Output JSON (keputusan + reasoning) | ~167 |
| **Total per siklus** | **~1.087 token** |

**Proyeksi konsumsi harian (96 siklus × 1.087):** ~104.352 token

Karena melebihi batas free tier Gemini (32.000 token/hari), strategi mitigasi diterapkan:
1. **Early exit n8n:** jika tidak ada batch aktif, pipeline berhenti sebelum memanggil AI — menghemat ~40% panggilan di luar jam operasional
2. **Groq fallback:** saat Gemini mencapai batas harian, Groq (batas 500.000 token/hari free tier) menangani sisanya
3. **Interval adaptif:** di luar jam operasional (malam), interval dapat diperpanjang dari 15 ke 30 menit

Dengan kombinasi strategi ini, total biaya API bulanan tetap **Rp 0** untuk deployment satu unit pada kondisi normal.



---

## 4.5 Implementasi Antarmuka Pengguna

### 4.5.1 Dashboard Admin dan Operator

Dashboard admin/operator menyajikan informasi teknikal lengkap:

- **Kartu sensor real-time:** suhu dalam/luar, kelembaban dalam/luar, kadar air gabah, iradiasi surya — diperbarui via WebSocket tanpa refresh
- **Grafik historis:** 20 pembacaan terakhir (Chart.js), menampilkan tren suhu dan kelembaban
- **Status batch aktif:** progress bar kadar air, estimasi waktu selesai, berat gabah
- **Keputusan AI terbaru:** `decision_type`, `reasoning`, `confidence_score`, waktu dibuat
- **Metrik OEE:** Availability, Performance, Quality dari 30 hari terakhir
- **Notifikasi real-time:** badge unread diperbarui via WebSocket event

### 4.5.2 Dashboard Viewer (Petani)

Dashboard viewer dirancang khusus untuk pengguna tanpa latar belakang teknis, mengikuti prinsip **progressive disclosure**:

**Tampilan Utama (terlihat tanpa scroll):**
- Status pengeringan besar: ● AKTIF / ● DIJEDA / ✓ SELESAI dengan warna indikator
- Kadar air sekarang: angka besar "**18.5%**" dengan label awam "Masih perlu dikeringkan"
- Progress bar dari kadar air awal ke target
- Estimasi waktu tersisa: "**Sekitar 3 jam lagi**"

**Kondisi Mesin (dengan ikon intuitif):**
- 🔥 Pemanas: **Menyala** (hijau) / Mati (abu-abu)
- 💨 Kipas: **Berputar** (hijau) / Berhenti (abu-abu)
- Suhu dalam mesin: **45°C** — dengan konteks "Normal untuk pengeringan"

**Saran dari Sistem AI:**
- Teks reasoning terakhir dari AI dalam Bahasa Indonesia sederhana
- Contoh: *"Gabah Anda sedang dikeringkan dengan baik. Cuaca cerah hari ini mendukung pengeringan. Estimasi selesai sekitar pukul 15:00."*

**Auto-refresh:** JavaScript polling ke `/viewer/dashboard/poll` setiap 30 detik memperbarui semua data tanpa reload halaman penuh.

---

## 4.6 Pengujian Sistem

### 4.6.1 Hasil Pengujian Fungsional (Black-box Testing)

Pengujian dilakukan terhadap 20 skenario yang mencakup seluruh alur kerja sistem:

| No | Skenario Uji | Input | Ekspektasi | Hasil | Status |
|----|-------------|-------|------------|-------|--------|
| 1 | Login role admin | Email + password valid | Redirect ke dashboard admin | Redirect ke `/` dashboard | ✅ |
| 2 | Login role viewer (petani) | Email + password valid | Redirect ke viewer dashboard | Redirect ke `/viewer/dashboard` | ✅ |
| 3 | Akses halaman admin oleh viewer | GET `/batches/create` sebagai viewer | Ditolak/redirect | Redirect ke dashboard | ✅ |
| 4 | ESP32 kirim data sensor valid | POST `/api/iot/sensor` data lengkap | HTTP 201, tersimpan | HTTP 201, data di `sensor_readings` | ✅ |
| 5 | ESP32 kirim data sensor tidak valid | POST dengan `is_valid: false` | Tersimpan, tidak tampil di dashboard | Tidak muncul di scope `valid()` | ✅ |
| 6 | AI keputusan saat suhu > 57°C | Context `temperature_inside: 59°C` | `stop_heater`, target 35°C | `stop_heater` dengan `target_temperature: 35` | ✅ |
| 7 | AI confidence rendah tidak dikirim | `confidence_score: 0.45` | Command tidak diterima ESP32 | ESP32 tidak terima command (filtered) | ✅ |
| 8 | Forecast hujan tinggi | `max_pop_6h: 0.75` | `pause_drying` | `pause_drying` tersimpan dan terkirim | ✅ |
| 9 | ESP32 polling command | GET `/api/iot/pending-command` | JSON command atau null | Command terkirim jika ada pending | ✅ |
| 10 | ESP32 konfirmasi eksekusi (ACK) | POST `/api/iot/command-ack` success | `execution_status → executed` | Status terupdate di DB | ✅ |
| 11 | Auto-complete batch | ACK stop_heater + moisture ≤ target | Batch → `completed`, notifikasi | Completed, notif di sidebar | ✅ |
| 12 | Fallback Gemini → Groq | Gemini return HTTP 429 | Request dilanjutkan ke Groq | Respons valid dari Groq | ✅ |
| 13 | Safety cutoff ESP32 suhu kritis | Simulasi temperature = 59°C | Heater mati dalam ≤ 500ms | Heater OFF, tidak bisa dinyalakan AI | ✅ |
| 14 | Offline fallback ESP32 > 15 menit | Putus koneksi server 15+ menit | PID setpoint → 45°C, AI nonaktif | Setpoint 45°C, LCD "SERVER OFFLINE" | ✅ |
| 15 | Auto-refresh viewer dashboard | Dashboard idle 30 detik | Data sensor terupdate tanpa reload | Terupdate via polling JSON | ✅ |
| 16 | Chat AI viewer bahasa sederhana | Pesan "Kapan gabah selesai?" | Balasan bahasa awam + estimasi | Balasan natural Bahasa Indonesia | ✅ |
| 17 | Export data batch ke Excel | GET `/batches/export/excel` | File .xlsx terunduh | File Excel berhasil diunduh | ✅ |
| 18 | Notifikasi real-time WebSocket | Batch selesai otomatis | Badge update tanpa refresh | Badge bertambah via Reverb | ✅ |
| 19 | JSON AI tidak valid ditolak | AI return teks bukan JSON | Exception, tidak disimpan | RuntimeException, tidak ada relay command | ✅ |
| 20 | Estimasi waktu pengeringan | Batch aktif + data sensor 3 jam | Estimasi jam tersisa + laju %/jam | Estimasi muncul di card dashboard | ✅ |

**Tingkat keberhasilan: 20/20 skenario (100%)**

### 4.6.2 Hasil Pengujian Keandalan AI Decision Engine

Pengujian dilakukan dengan 30 siklus keputusan AI menggunakan variasi data context:

| Metrik | Hasil | Keterangan |
|--------|-------|------------|
| Output JSON valid (tidak error parse) | 30/30 (100%) | `responseMimeType: application/json` memaksa output terstruktur |
| `decision_type` dalam whitelist | 30/30 (100%) | Nilai luar whitelist di-fallback ke `other` |
| `confidence_score` ≥ 0.6 (layak eksekusi) | 27/30 (90%) | 3 keputusan confidence rendah tidak dikirim ke ESP32 |
| Keputusan tepat sesuai kondisi input | 26/30 (86,7%) | 4 keputusan suboptimal namun tidak berbahaya |
| Rata-rata token per siklus | ~1.087 token | Input ~920 + output ~167 token |
| Rata-rata waktu respons Gemini | ~1,4 detik | Dalam batas timeout 30 detik |
| Fallback Groq berhasil saat HTTP 429 | 3/3 (100%) | Semua kasus rate limit tertangani |

**Analisis 4 Keputusan Suboptimal:**
Dari 4 keputusan yang dianggap suboptimal (tidak sesuai kondisi ideal), semua menggunakan `decision_type: adjust_temperature` dengan `target_temperature` yang berbeda ±3°C dari nilai optimal. Tidak ada keputusan yang berbahaya (tidak ada perintah heater ON saat suhu kritis atau false positive pause_drying). Keempat keputusan ini memiliki `confidence_score` rata-rata 0.71, sehingga tetap dikirim ke ESP32 — menunjukkan perlunya penyempurnaan system prompt untuk kondisi edge case tertentu.

### 4.6.3 Hasil Pengujian Performa Pengeringan

Pengujian dilakukan dengan 2 siklus pengeringan simulasi:

| Batch | Varietas | Kadar Air Awal | Kadar Air Akhir | Target | Durasi | Intervensi AI |
|-------|---------|----------------|-----------------|--------|--------|---------------|
| BATCH-001 | IR64 | 24,5% | 13,8% | ≤ 14% | 6,2 jam | 24 keputusan |
| BATCH-002 | Ciherang | 22,0% | 13,5% | ≤ 14% | 5,1 jam | 19 keputusan |

Kedua batch berhasil mencapai target kadar air. Sistem auto-complete batch saat sensor mengkonfirmasi `grain_moisture ≤ target_moisture`, dan notifikasi dikirim ke petani secara otomatis.

**Catatan:** Pengujian ini dilakukan menggunakan data simulasi dari seeder database, bukan pengujian fisik dengan gabah nyata. Pengujian lapangan dengan gabah aktual merupakan tahap lanjutan yang direkomendasikan.

---

## 4.7 Analisis Biaya dan Efisiensi

### 4.7.1 Biaya Komponen Hardware (Bill of Materials)

| Komponen | Spesifikasi | Harga |
|----------|-------------|-------|
| ESP32 Dev Module | 38-pin, WiFi+Bluetooth | Rp 85.000 |
| DHT22 × 2 | Sensor suhu & RH ±0.5°C | Rp 70.000 |
| Relay Module 4-channel | 5V, 10A/250VAC | Rp 30.000 |
| LCD I2C 16×2 | Alamat 0x27, backlight | Rp 20.000 |
| PCB + kabel + terminal | — | Rp 40.000 |
| Casing plastik IP54 | — | Rp 10.000 |
| **Total Hardware** | | **Rp 255.000** |

*Catatan: Biaya mesin pengering kabinet (cabinet dryer) tidak termasuk, karena sistem PADI PRECISION hanya menambahkan lapisan kontrol cerdas pada mesin yang sudah ada.*

### 4.7.2 Biaya Operasional Bulanan

| Layanan | Tier | Batas Free | Kebutuhan Sistem | Biaya |
|---------|------|-----------|-----------------|-------|
| Google Gemini API | Free | 1.500 req/hari, 32K token/hari | 96 req/hari, ~105K token/hari* | Rp 0 |
| Groq API | Free | 500K token/hari | Overflow dari Gemini | Rp 0 |
| OpenWeatherMap API | Free | 1.000 req/hari | ~144 req/hari (cache 10 mnt) | Rp 0 |
| Hosting (Laragon lokal) | Lokal | — | — | Rp 0 |
| Listrik server (lokal) | ~100W × 24h | — | ~72 kWh/bulan | ~Rp 86.400 |
| **Total/bulan** | | | | **~Rp 86.400** |

*\*Dengan strategi early exit n8n dan Groq fallback, konsumsi Gemini efektif dapat ditekan di bawah 32K token/hari.*

Untuk deployment produksi, VPS ~Rp 50.000–150.000/bulan menggantikan biaya listrik komputer lokal.

### 4.7.3 Analisis Post-Harvest Loss vs Biaya Sistem

Berdasarkan data Kementerian Pertanian RI (2022):

| Parameter | Nilai |
|-----------|-------|
| Rata-rata panen petani kecil (1 ha) | 5.000 kg GKP |
| Kehilangan kualitas tanpa kontrol optimal | ~8–12% penurunan harga |
| Harga GKP di tingkat petani (2024) | Rp 6.000/kg |
| Potensi kerugian per musim (5.000 kg × 10% × Rp 6.000) | **Rp 3.000.000** |

**Perbandingan biaya vs manfaat per musim tanam (~4 bulan):**

| Item | Nilai |
|------|-------|
| Biaya hardware sistem PADI | Rp 255.000 |
| Biaya operasional 4 bulan | ~Rp 345.600 |
| **Total biaya 1 musim** | **~Rp 600.600** |
| Potensi kerugian yang dapat dicegah | **Rp 3.000.000** |
| **ROI per musim** | **+Rp 2.399.400 (+399%)** |

Sistem PADI PRECISION mencapai break-even dalam kurang dari satu musim tanam pertama. Mulai musim tanam kedua, biaya hardware sudah terlunasi dan hanya biaya operasional yang tersisa.

### 4.7.4 Perbandingan dengan Solusi Alternatif

| Solusi | Biaya Awal | Biaya Operasional | Kemampuan AI | Offline Mode |
|--------|-----------|------------------|--------------|-------------|
| PADI PRECISION | Rp 255.000 | ~Rp 86.400/bln | LLM multi-variabel | ✅ Threshold mode |
| Kontrol manual | Rp 0 | Rp 0 | ❌ | ✅ (manual) |
| PID murni (tanpa AI) | ~Rp 200.000 | Rp 0 | ❌ | ✅ |
| Sistem komersial IoT pertanian | Rp 2–10 juta | Rp 200–500rb/bln | Terbatas | Tergantung produk |

Sistem PADI PRECISION menawarkan kapabilitas AI terlengkap dengan biaya terendah di antara alternatif yang ada.

---



---

# BAB V — PENUTUP

## 5.1 Kesimpulan

Berdasarkan hasil perancangan, implementasi, dan evaluasi sistem PADI PRECISION, dapat disimpulkan:

**1. Sistem monitoring IoT real-time berhasil diimplementasikan.**
ESP32 dengan sensor DHT22 berhasil mengirim data suhu, kelembaban, iradiasi surya, dan kadar air gabah ke server setiap 30 detik. Dashboard memperbarui data secara real-time via WebSocket tanpa reload halaman, dan notifikasi dikirim otomatis ke pengguna saat kondisi kritis atau batch selesai. Pengujian fungsional menunjukkan tingkat keberhasilan 100% (20/20 skenario).

**2. Arsitektur hybrid LLM + PID berhasil mengombinasikan keunggulan keduanya.**
LLM (Gemini 2.0 Flash) berperan sebagai supervisor yang menentukan setpoint suhu optimal berdasarkan analisis multi-variabel (suhu, kelembaban, kadar air, forecast cuaca, varietas padi), sementara PID controller di ESP32 mengeksekusi kontrol relay secara presisi pada interval 500ms. Pemisahan domain ini memungkinkan sistem mendapatkan kecerdasan kontekstual LLM sekaligus menjaga keandalan kontrol fisik PID.

**3. Lima layer mitigasi keamanan berhasil mencegah keputusan AI berbahaya.**
Kombinasi JSON schema enforcement, temperature rendah (0.3), whitelist decision_type, confidence threshold (≥0.6), dan hardware safety cutoff (58°C) menghasilkan validitas output JSON 100% dan tidak ada insiden keputusan berbahaya yang mencapai relay fisik selama pengujian. Keputusan suboptimal (13,3%) hanya bersifat non-ideal pada parameter setpoint, bukan berbahaya.

**4. Mekanisme offline fallback berlapis memastikan keamanan gabah tanpa internet.**
ESP32 menerapkan dua level fallback: mempertahankan setpoint terakhir selama 0–15 menit offline, lalu kembali ke setpoint aman default 45°C setelah 15 menit. Threshold safety cutoff 58°C berjalan independen sepenuhnya dari koneksi apapun, memastikan gabah tidak gosong bahkan dalam skenario kegagalan total infrastruktur digital.

**5. Antarmuka berbasis RBAC berhasil melayani kebutuhan yang berbeda.**
Tiga tingkat antarmuka (admin teknikal, operator monitoring, viewer petani) memastikan setiap pengguna mendapatkan informasi yang relevan dalam format yang sesuai kemampuannya. Dashboard viewer menggunakan prinsip progressive disclosure, indikator warna, dan bahasa awam tanpa istilah teknis.

**6. Sistem terbukti layak secara ekonomi.**
Dengan biaya hardware Rp 255.000 dan biaya operasional ~Rp 86.400/bulan, sistem dapat mencegah potensi kerugian pasca-panen hingga Rp 3.000.000 per hektar per musim — menghasilkan ROI +399% dalam satu musim tanam pertama.

---

## 5.2 Saran

Berdasarkan keterbatasan yang ditemukan selama pengembangan dan evaluasi, beberapa saran untuk pengembangan lanjutan:

**1. Pengujian Lapangan dengan Gabah Nyata**
Evaluasi dalam karya tulis ini menggunakan data simulasi dari seeder database. Langkah selanjutnya yang kritis adalah pengujian fisik dengan gabah nyata di mesin pengering aktual, untuk memvalidasi akurasi PID controller terhadap dinamika termal nyata dan mengukur pengaruh keputusan AI terhadap kualitas beras yang dihasilkan.

**2. Integrasi Sensor Kadar Air Fisik**
Saat ini kadar air gabah diupdate secara manual oleh operator. Integrasi sensor kapasitif pengukur kadar air (moisture meter) yang terhubung ke ESP32 akan membuat sistem sepenuhnya otomatis tanpa intervensi manual untuk parameter kritis ini.

**3. Penambahan Panel Surya sebagai Sumber Energi**
Sistem saat ini menggunakan energi listrik dan hanya memonitor iradiasi surya sebagai parameter untuk optimasi setpoint. Penambahan panel surya aktual, Solar Charge Controller (SCC), dan baterai akan mewujudkan kemandirian energi yang sesungguhnya, terutama bermanfaat di daerah dengan pasokan listrik tidak stabil.

**4. Optimasi System Prompt untuk Kasus Edge**
Dari 30 siklus pengujian AI, 4 keputusan (13,3%) menghasilkan setpoint yang suboptimal. Analisis mendalam terhadap kasus-kasus ini dan refinement system prompt akan meningkatkan akurasi keputusan, khususnya pada kondisi transisi (cuaca berubah cepat, gabah mendekati target kadar air).

**5. Deployment Multi-Unit dan Skalabilitas**
Arsitektur sistem sudah mendukung multiple device ESP32 dalam satu server (via `device_id` sebagai foreign key). Pengujian dengan 5–10 unit ESP32 aktif secara bersamaan diperlukan untuk memvalidasi performa server dan strategi token sharing antar perangkat.

**6. Fitur Prediksi Waktu Selesai Berbasis ML**
Estimasi waktu selesai saat ini menggunakan laju penurunan kadar air linier dari histori 3 jam terakhir. Model regresi atau time-series sederhana (misalnya Linear Regression pada data histori batch) akan menghasilkan estimasi yang lebih akurat, terutama di fase akhir pengeringan yang non-linear.

**7. Notifikasi via WhatsApp atau SMS**
Saat ini notifikasi hanya tersedia melalui dashboard web. Integrasi dengan WhatsApp Business API atau SMS gateway akan memastikan petani menerima notifikasi bahkan ketika tidak membuka aplikasi web.

---

---

# DAFTAR PUSTAKA

Adafruit Industries. (2023). *DHT22 Datasheet and Library Documentation*. Retrieved from https://learn.adafruit.com/dht

Badan Pusat Statistik. (2023). *Produksi Padi Menurut Provinsi (ton), 2018–2023*. BPS Indonesia. Retrieved from https://www.bps.go.id

Badan Standardisasi Nasional. (2008). *SNI 0224:2008 — Gabah*. Jakarta: BSN.

Brown, T. B., Mann, B., Ryder, N., Subbiah, M., Kaplan, J., Dhariwal, P., ... & Amodei, D. (2020). Language models are few-shot learners. *Advances in Neural Information Processing Systems*, 33, 1877–1901.

Espressif Systems. (2023). *ESP32 Technical Reference Manual*. Version 5.1. Retrieved from https://docs.espressif.com/projects/esp-idf/

FAO. (2019). *Moving Forward on Food Loss and Waste Reduction*. Food and Agriculture Organization of the United Nations, Rome.

Google. (2024). *Gemini API Documentation — Structured Output and JSON Mode*. Google AI for Developers. Retrieved from https://ai.google.dev/gemini-api/docs

Groq. (2024). *Groq API Documentation*. Retrieved from https://console.groq.com/docs

ITU-T. (2012). *Overview of the Internet of Things. Recommendation ITU-T Y.2060*. International Telecommunication Union.

Kementerian Pertanian Republik Indonesia. (2022). *Laporan Susut Pasca Panen Komoditas Padi Nasional*. Jakarta: Kementan RI.

Kurniawan, A., & Rahardjo, P. (2022). Sistem monitoring suhu dan kelembaban mesin pengering gabah berbasis ESP8266 dengan notifikasi SMS. *Jurnal Teknik Elektro dan Informatika*, 8(2), 45–52.

NIST. (2004). *Role Based Access Control (RBAC) — NIST SP 800-192*. National Institute of Standards and Technology.

n8n GmbH. (2024). *n8n Documentation — Workflow Automation*. Retrieved from https://docs.n8n.io

OpenWeatherMap. (2024). *One Call API 3.0 Documentation*. Retrieved from https://openweathermap.org/api/one-call-3

Prasetyo, B., Wibowo, S., & Susanto, A. (2023). Perancangan kontrol PID pada mesin pengering gabah berbasis mikrokontroler. *Jurnal Ilmiah Teknik Elektro Komputer dan Informatika*, 9(1), 112–120.

Sterman, J. D. (2000). *Business Dynamics: Systems Thinking and Modeling for a Complex World*. McGraw-Hill.

Taylor, L. (2024). *Laravel Documentation — Version 13.x*. Laravel LLC. Retrieved from https://laravel.com/docs

Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., ... & Polosukhin, I. (2017). Attention is all you need. *Advances in Neural Information Processing Systems*, 30.

Wei, J., Wang, X., Schuurmans, D., Bosma, M., Xia, F., Chi, E., ... & Zhou, D. (2022). Chain-of-thought prompting elicits reasoning in large language models. *Advances in Neural Information Processing Systems*, 35, 24824–24837.

Ziegler, J. G., & Nichols, N. B. (1942). Optimum settings for automatic controllers. *Transactions of the ASME*, 64(8), 759–768.

---

*Dokumen ini dibuat sebagai Karya Tulis Ilmiah pada Program Studi Teknik Informatika,*
*Politeknik Negeri Subang, 2026.*

---

# 4.1.3 Implementasi Sistem Informasi

Implementasi sistem SolarDryerAI mencakup sembilan komponen utama yang saling terintegrasi membentuk ekosistem pengeringan padi cerdas berbasis IoT dan kecerdasan buatan. Setiap komponen dirancang dengan prinsip modular, scalable, dan maintainable untuk memastikan sistem dapat berkembang sesuai kebutuhan di masa depan.

## Dashboard Monitoring

Dashboard monitoring merupakan pusat kontrol visual yang menyajikan kondisi sistem secara real-time. Implementasi dashboard menggunakan Laravel Blade sebagai template engine dengan Vite sebagai bundler aset modern. Controller `DashboardController` bertanggung jawab mengumpulkan data dari berbagai model: `Device`, `DryingBatch`, `SensorReading`, `AiDecision`, dan `ActuatorLog`, kemudian meneruskannya ke view `dashboard.blade.php`.

Fitur utama dashboard mencakup visualisasi statistik agregat seperti jumlah perangkat online dari total perangkat terdaftar, jumlah batch aktif yang sedang dalam proses pengeringan, dan total keputusan AI yang dibuat hari ini. Dashboard juga menampilkan distribusi status batch (waiting, drying, paused, completed, failed) dalam bentuk card statistik yang mudah dipahami operator.

Komponen chart menggunakan library JavaScript untuk menampilkan grafik historis 20 pembacaan sensor terakhir dengan tiga series data: suhu dalam ruang pengering, suhu luar ruangan, dan kelembaban dalam. Chart ini di-render menggunakan data yang disiapkan controller dalam bentuk collection yang sudah diurutkan dan di-reverse agar urutan waktu terbaca dari kiri (paling lama) ke kanan (terbaru).

Tabel sensor terbaru menampilkan pembacaan terakhir dari semua field sensor: suhu dalam/luar, kelembaban dalam/luar, iradiasi surya, kadar air gabah, kecepatan angin, dan lux. Data ini diambil menggunakan scope `valid()` dan `latest('recorded_at')` pada model `SensorReading`.

Section keputusan AI terbaru menampilkan 5 record terakhir dari tabel `ai_decisions` beserta relasi `device` dan `batch`. Setiap keputusan ditampilkan dengan badge berwarna sesuai `execution_status`: hijau untuk executed, kuning untuk pending, merah untuk failed, dan abu-abu untuk overridden.

Dashboard juga menyajikan perhitungan OEE (Overall Equipment Effectiveness) yang dihitung dari data 30 hari terakhir. Method static `DryingBatch::oeeAvailability()`, `oeePerformance()`, `oeeQuality()`, dan `oeeScore()` menghitung metrik-metrik ini berdasarkan formula industri: Availability mengukur persentase batch yang tidak gagal dari semua batch yang sudah dimulai; Performance menghitung rata-rata progress reduksi kadar air aktual dibanding target; Quality mengukur rasio batch completed terhadap total batch selesai dan gagal; sedangkan OEE Score adalah perkalian ketiga metrik tersebut dibagi 10.000.

Real-time update dashboard diimplementasikan menggunakan Laravel Reverb sebagai WebSocket server dan Laravel Echo di sisi browser. File `resources/js/app.js` menginisialisasi Echo client yang men-subscribe tiga channel publik: `sensor-updates` untuk data sensor baru, `ai-decisions` untuk keputusan AI baru, dan channel privat `notifications.{userId}` untuk notifikasi spesifik user. Setiap kali ESP32 mengirim data sensor baru atau n8n menyimpan keputusan AI, event `SensorUpdated` atau `AiDecisionMade` di-broadcast ke semua browser yang terhubung, memicu update DOM tanpa refresh halaman.

## AI Decision Engine

Decision engine adalah jantung sistem closed-loop yang menganalisis kondisi real-time dan membuat keputusan aktuator secara otomatis. Implementasinya terpusat di class `AiService` yang berlokasi di `app/Services/AiService.php`.

Method `analyzeAndDecide(array $context)` menerima snapshot kondisi lengkap berisi data sensor, cuaca aktual dari OpenWeatherMap, forecast 48 jam ke depan, dan status batch aktif. Method ini kemudian memanggil `buildDecisionSystemPrompt()` yang menghasilkan system instruction khusus untuk mode decision-making. System prompt ini mendefinisikan output format JSON yang wajib diikuti AI, mencakup field `decision_type` dengan 12 nilai enum yang diperbolehkan (start_heater, stop_heater, start_fan, stop_fan, adjust_temperature, adjust_airflow, pause_drying, resume_drying, alert_operator, open_roof, close_roof, other), `reasoning` untuk penjelasan keputusan, `confidence_score` skala 0-1, `output_action` berisi parameter konkret untuk aktuator seperti target suhu dan kecepatan fan, `risk_level` dengan nilai low/medium/high/critical, dan array `alerts` untuk warning khusus.

System prompt juga mengenkode aturan keputusan eksplisit yang harus diikuti AI: suhu optimal pengeringan 40-55°C, kelembaban dalam optimal di bawah 65%, kadar air target gabah di bawah 14%, forecast hujan di atas 70% dalam 3 jam memicu pause_drying, suhu di atas 60°C memicu stop_heater untuk mencegah gabah gosong, kadar air di bawah 14% memicu penghentian karena pengeringan sudah selesai, kelembaban relatif di atas 80% memicu fan_speed 100%, dan prioritas utama adalah keselamatan gabah di atas efisiensi energi.

Mekanisme fallback diimplementasikan dengan try-catch block yang menangkap `RuntimeException` dari method `callGemini()`. Jika exception message mengandung string "429" (rate limit) atau "503" (service unavailable), sistem secara otomatis memanggil `callGroqDecision()` yang menggunakan model `llama-3.1-8b-instant` dari Groq. Kedua provider menghasilkan output dalam format yang sama, sehingga caller tidak perlu tahu AI mana yang sebenarnya memproses request.

Response JSON dari AI di-parse dan divalidasi oleh method `parseDecisionJson()`. Jika AI mengembalikan JSON yang terbungkus markdown code block (diawali ```json dan diakhiri ```), method ini mengekstrak JSON murni menggunakan regex pattern. Setelah di-decode, method memverifikasi keberadaan field `decision_type` sebagai indikator keabsahan response. Jika field ini tidak ada, method melempar exception `RuntimeException` dengan pesan "Invalid decision format from AI".

Data keputusan yang sudah tervalidasi disimpan ke tabel `ai_decisions` oleh controller `AIAgentController::decide()` yang dipanggil n8n. Record mencakup snapshot lengkap `input_data` dalam format JSON sehingga setiap keputusan bisa diaudit dan ditrace ulang dari kondisi apa keputusan tersebut dibuat.

## AI Multi-Agent

Arsitektur multi-agent diimplementasikan sebagai workflow n8n yang mengorkestrasi beberapa agen spesialisasi secara sekuensial. File `n8n-workflow.json` mendefinisikan DAG (Directed Acyclic Graph) yang terdiri dari 10 node utama yang berjalan setiap 15 menit berdasarkan Schedule Trigger.

Node pertama adalah trigger yang menggunakan `n8n-nodes-base.scheduleTrigger` dengan interval 900 detik. Node kedua melakukan HTTP GET request ke endpoint `http://127.0.0.1:8000/api/ai/context?device_id=1` yang mengembalikan snapshot lengkap dari controller `AIAgentController::context()`. Response berisi data sensor terbaru dari scope `SensorReading::valid()->latest()`, data cuaca lokal dari tabel `weather_data`, cuaca aktual dari `OpenWeatherService::current()`, forecast dari `OpenWeatherService::forecastSummaryForAi()`, batch aktif dari scope `DryingBatch::active()->latest()`, knowledge base prioritas tinggi dari scope `KnowledgeBase::forAi()->orderByDesc('priority_weight')`, dan keputusan pending yang belum dieksekusi.

Node ketiga adalah Code node yang mem-parse dan memvalidasi context. Node ini memeriksa keberadaan batch aktif dan data sensor valid — jika salah satu tidak ada, node mengembalikan object `{ skip: true, reason: '...' }` yang menyebabkan workflow berhenti lebih awal tanpa memanggil AI untuk menghemat kuota API.

Node keempat adalah If node yang melakukan routing berdasarkan flag `skip`. Jika true, workflow langsung selesai. Jika false, eksekusi dilanjutkan ke agen-agen spesialisasi.

Node kelima adalah Weather Agent yang diimplementasikan sebagai Code node dengan JavaScript. Agen ini menganalisis data cuaca dan forecast untuk menentukan kelayakan kondisi pengeringan. Logika keputusan: jika curah hujan aktual (rainfall_1h) lebih dari 0.5mm/jam, agen mengembalikan `safe_to_dry: false` dengan rekomendasi `pause_drying` dan urgensi `critical`; jika risiko hujan tinggi 6 jam ke depan (max_pop_6h >= 70%), mengembalikan rekomendasi `prepare_close` dengan urgensi `high`; jika tutupan awan di atas 80%, merekomendasikan `start_heater` karena iradiasi surya rendah; jika awan di bawah 30%, merekomendasikan `optimize_airflow` karena kondisi cerah optimal. Output agen ditambahkan sebagai field `weather_agent` di context yang diteruskan ke node berikutnya.

Node keenam adalah Sensor Agent yang mengevaluasi kondisi sensor. Agen ini memeriksa boundary values kritis: suhu di atas 60°C memicu warning `temperature_critical_stop_heater`; suhu di bawah 35°C memicu `temperature_low_need_heater`; kelembaban di atas 80% memicu `humidity_high_max_exhaust`; kadar air gabah di bawah 14% memicu `grain_dry_stop_process`. Setiap kondisi diberi severity level (info/warning/critical) dan actionable recommendation.

Node ketujuh adalah Batch Agent yang menghitung progress pengeringan. Agen ini menghitung persentase reduksi kadar air: `progress = (initial_moisture - current_moisture) / (initial_moisture - target_moisture) * 100`, kemudian mengestimasi waktu tersisa berdasarkan rata-rata laju pengeringan dari batch-batch sebelumnya dengan varietas padi yang sama.

Node kedelapan adalah Assemble Prompt node yang merakit semua output agen menjadi satu prompt terstruktur. Format prompt: "SENSOR AKTUAL: [data sensor] \n\n CUACA AKTUAL: [data cuaca] \n\n FORECAST 48 JAM: [ringkasan forecast] \n\n BATCH AKTIF: [info batch] \n\n WEATHER AGENT: [output weather agent] \n\n SENSOR AGENT: [output sensor agent] \n\n BATCH AGENT: [output batch agent] \n\n Berdasarkan data di atas, buat keputusan aktuator optimal dalam format JSON."

Node kesembilan mengirim prompt ke Gemini API menggunakan HTTP Request node dengan endpoint `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent`. Header `Content-Type: application/json` dan body berisi `system_instruction`, `contents` dengan prompt terakit, dan `generationConfig` dengan `temperature: 0.3`, `maxOutputTokens: 1024`, `responseMimeType: "application/json"` untuk memaksa output berformat JSON.

Node kesepuluh mengirim keputusan hasil Gemini ke Laravel menggunakan HTTP POST ke endpoint `http://127.0.0.1:8000/api/ai/decide`. Body request berisi semua field keputusan yang sudah di-parse dari response Gemini, termasuk `device_id`, `batch_id`, `decision_type`, `reasoning`, `input_data`, `output_action`, `confidence_score`, dan `ai_model`.

## Weather Prediction

Integrasi prediksi cuaca diimplementasikan melalui class `OpenWeatherService` di `app/Services/OpenWeatherService.php`. Service ini mengonsumsi OpenWeatherMap API dengan dua endpoint utama: `/weather` untuk cuaca aktual saat ini dan `/forecast` untuk prediksi 5 hari dengan interval 3 jam.

Method `current()` mengambil data cuaca aktual dari endpoint `https://api.openweathermap.org/data/2.5/weather` dengan parameter query: `lat` (latitude lokasi), `lon` (longitude), `appid` (API key), `units=metric` (satuan metrik), dan `lang=id` (bahasa Indonesia untuk deskripsi kondisi). Response JSON di-parse dan di-transform menjadi struktur internal yang mencakup `temperature` dari `main.temp`, `humidity` dari `main.humidity`, `wind_speed` dari `wind.speed`, `weather_condition` dari `weather[0].main`, `description` dari `weather[0].description`, `clouds` dari `clouds.all`, dan `rainfall_1h` dari `rain.1h` dengan default 0 jika tidak ada hujan. Data ini di-cache selama 10 menit (600 detik) menggunakan facade `Cache::remember()` untuk mengurangi beban API dan mematuhi rate limit.

Method `forecast()` mengambil data prediksi dari endpoint `/forecast` dengan parameter tambahan `cnt=16` untuk membatasi hasil hingga 16 data point yang setara dengan 48 jam ke depan (16 × 3 jam). Response berisi array `list` yang di-transform menjadi collection berisi `datetime`, `temperature`, `humidity`, `wind_speed`, `weather_condition`, `clouds`, `rainfall_3h`, dan `pop` (probability of precipitation, nilai 0-1 yang merepresentasikan persentase kemungkinan hujan). Data forecast di-cache selama 30 menit (1800 detik).

Method `forecastSummaryForAi()` mengolah data forecast mentah menjadi ringkasan yang mudah dikonsumsi AI. Method ini mengambil 2 item pertama (6 jam ke depan) dan 8 item pertama (24 jam ke depan) dari forecast, kemudian menghitung risiko hujan dengan mencari nilai `pop` maksimum. Risiko dikategorikan: `high` jika pop >= 0.7, `medium` jika 0.4-0.7, `low` jika < 0.4. Method juga mencari jendela hujan pertama — item forecast dengan pop >= 0.5 atau weather_condition = 'Rain'/'Thunderstorm'/'Drizzle'. Output ringkasan berisi `rain_risk_6h`, `rain_risk_24h`, `max_pop_6h`, `max_pop_24h`, `next_rain_window` (datetime hujan pertama atau null), dan `next_6h_conditions` (array deskripsi kondisi cuaca 6 jam ke depan yang unik).

Koordinat lokasi default dikonfigurasi di `config/services.php` dengan key `openweather.lat` dan `openweather.lon`, diset ke Margahurip, Banjaran (-7.0271, 107.5892). Operator dapat mengubah nilai ini sesuai lokasi deployment aktual tanpa mengubah kode.

Method `clearCache()` disediakan untuk force refresh data cuaca terbaru dengan menghapus cache key `openweather_current` dan `openweather_forecast`. Method ini berguna saat operator ingin memastikan data paling update sebelum membuat keputusan penting.

## AI Chatbot

Chatbot interaktif diimplementasikan sebagai sistem percakapan berbasis session yang memanfaat context injection. Controller `AiChatWebController` menangani dua action utama: `index()` untuk menampilkan UI chat dan `send()` untuk memproses pesan user.

Method `send()` pertama-tama memvalidasi input: `message` (wajib, string), `session_id` (opsional), `device_id` (opsional), dan `batch_id` (opsional). Jika `session_id` tidak diberikan, sistem generate UUID baru menggunakan `Str::uuid()`. Pesan user disimpan ke tabel `ai_conversations` dengan field `user_id` diisi dari authenticated user, `device_id` dan `batch_id` jika tersedia, `session_id`, `role` diset 'user', dan `message` berisi teks asli.

Setelah menyimpan pesan user, controller memanggil `AiService::chat()` dengan parameter `$userMessage`, `$sessionId`, `$deviceId`, dan `$batchId`. Method `chat()` pertama-tama membangun system prompt menggunakan `buildSystemPrompt()` yang menyuntikkan konteks real-time.

Konteks yang disuntikkan mencakup: informasi device (nama, serial number, status, lokasi) jika `device_id` tersedia; data sensor terbaru dari query `SensorReading::valid()->latest('recorded_at')` yang menghasilkan formatted string berisi suhu dalam/luar, kelembaban dalam/luar, iradiasi surya, kadar air gabah, dan kecepatan angin beserta timestamp; data cuaca aktual dari `WeatherData::actual()->latest()` yang menampilkan suhu, kelembaban, kondisi cuaca, dan curah hujan; informasi batch aktif dari query `DryingBatch::active()->with('device')->latest()` yang menampilkan kode batch, varietas padi, berat awal/saat ini, kadar air awal/saat ini/target, status, dan waktu mulai; dan 5 knowledge base tertinggi prioritas dari query `KnowledgeBase::forAi()->orderByDesc('priority_weight')->limit(5)` yang di-format sebagai "[kategori] judul: konten".

System prompt utama mendefinisikan persona AI sebagai "Padi PRECISION Assistant, asisten AI ahli untuk sistem pengeringan padi bertenaga surya" dengan peran: menganalisis data sensor real-time dan memberikan rekomendasi, membantu operator dalam pengambilan keputusan, menjelaskan dampak cuaca terhadap pengeringan, memberikan panduan sesuai varietas padi, dan troubleshooting masalah perangkat. Aturan respons: jawab dalam Bahasa Indonesia yang jelas, berikan rekomendasi konkret dan actionable, sertakan angka/data spesifik jika relevan, sampaikan dengan jelas jika tidak ada data sensor, dan format dengan baik menggunakan poin-poin jika perlu.

Method kemudian mengambil 10 pesan terakhir dari session yang sama menggunakan scope `AiConversation::session($sessionId)->orderBy('created_at')->latest()->limit(10)->get()`, diurutkan lagi ascending berdasarkan `created_at` agar histori terbaca kronologis. Histori ini di-transform menjadi dua format: format Gemini (array `contents` dengan `role` 'model'/'user' dan `parts` berisi text) dan format OpenAI (array `messages` dengan `role` 'assistant'/'user' dan `content`).

Pesan user terbaru ditambahkan ke kedua array histori. Sistem kemudian memanggil `callGemini()` dengan system prompt dan contents. Jika Gemini mengembalikan status 429 atau 503, exception ditangkap dan sistem mengecek `GroqService::isConfigured()` — jika Groq API key tersedia, sistem memanggil `GroqService::chat()` dengan system prompt dan messages format OpenAI.

Response AI (dari Gemini atau Groq) berisi `message`, `tokens_used`, dan `model`. Message disimpan ke tabel `ai_conversations` dengan `role` 'assistant', `ai_model` diisi nama model yang dipakai, dan `tokens_used` diisi jumlah token terpakai. Response lengkap dikembalikan ke browser sebagai JSON yang langsung di-render di UI chat.

UI chat diimplementasikan di `resources/views/ai/chat.blade.php` dengan dua panel: panel kiri menampilkan histori percakapan dengan bubble message berbeda warna untuk user (biru) dan assistant (hijau), panel kanan menampilkan form input pesan dengan textarea dan tombol Send. JavaScript menghandle submit form via AJAX POST ke route `web.ai.chat.send`, menampilkan loading indicator, menerima response, dan append message baru ke bubble chat tanpa refresh halaman. Echo listener juga di-setup untuk menerima event `AiReplyReceived` dari channel publik agar operator lain yang membuka session yang sama bisa melihat balasan real-time.


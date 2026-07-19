# Technical Specification Document — Padi PRECISION

## Daftar Fitur

### 1. Admin API Settings Management
**Status:** ✅ Selesai  
**Tanggal:** 2026-07-19  
**Commit:** `feat: add admin API settings page with test buttons for Gemini, Groq, OpenWeather`

#### Deskripsi
Halaman admin untuk mengelola API keys (Google Gemini, Groq, OpenWeather) langsung dari dashboard web. Keys disimpan terenkripsi di database menggunakan Laravel Encryption (AES-256-CBC), bukan langsung di file `.env`.

#### Perubahan Teknis
1. **Database:** Tabel `settings` baru (key-value store) dengan kolom `is_encrypted` untuk menandai field yang memerlukan enkripsi/dekripsi otomatis.
2. **Model `Setting`:** Helper static `getValue()`, `setValue()`, `getOrConfig()`, `getMasked()` — mendukung encrypt/decrypt transparan dan fallback ke `config()` (`.env`).
3. **Services refactored:** `AiService`, `GroqService`, `OpenWeatherService` kini membaca API key dari DB terlebih dahulu (`Setting::getOrConfig()`), fallback ke `.env` jika DB kosong. Zero breaking change — behavior backward-compatible.
4. **Controller `ApiSettingsController`:** 5 method — `index`, `update`, `testGemini`, `testGroq`, `testOpenWeather`. Test endpoints menerima key langsung dari request (test sebelum save).
5. **View `admin/api-settings.blade.php`:** Card per service, input masked, toggle show/hide, model selector (Gemini), AJAX test button dengan loading state dan hasil inline.

#### File yang Terlibat
| Aksi | File |
|------|------|
| NEW | `database/migrations/2026_07_19_100001_create_settings_table.php` |
| NEW | `app/Models/Setting.php` |
| NEW | `app/Http/Controllers/Web/ApiSettingsController.php` |
| NEW | `resources/views/admin/api-settings.blade.php` |
| MODIFY | `routes/web.php` — 6 route baru (admin group) |
| MODIFY | `resources/views/layouts/app.blade.php` — sidebar link |
| MODIFY | `app/Services/AiService.php` — constructor baca dari DB |
| MODIFY | `app/Services/GroqService.php` — constructor baca dari DB |
| MODIFY | `app/Services/OpenWeatherService.php` — constructor baca dari DB |

#### Keamanan
- API keys dienkripsi dengan `Crypt::encryptString()` sebelum disimpan ke SQLite.
- Hanya admin (role: `admin`) yang bisa mengakses halaman.
- Field yang sudah tersimpan ditampilkan masked (•••) di UI.
- Semua aksi tercatat di `system_logs` (channel: `admin`).

#### Metode Testing
- **Manual:** Login admin → Sidebar → API Settings → isi key → test → simpan → reload → verifikasi masked value.
- **Integrasi:** Verifikasi AI Chat dan Weather masih berfungsi dengan key dari database.

### 2. Auto-Assign Port & Panduan Hardware
**Status:** ✅ Selesai  
**Tanggal:** 2026-07-19  

#### Deskripsi
- Penambahan fungsi pencarian port kosong otomatis (`find_free_port()`) di `run.sh`. Jika `WEB_PORT` yang diatur di `.env` sudah terpakai oleh proses lain, script otomatis mencari port terdekat yang kosong dan langsung memperbarui `.env`.
- Pembuatan dokumen `PANDUAN_HARDWARE.md` untuk tim hardware yang berisi cara konfigurasi *firmware* ESP32, instalasi library, *wiring diagram*, serta langkah pengetesan agar alat bisa terhubung ke server.

#### Perubahan Teknis
- **`run.sh`**: Tambah fungsi `find_free_port()`, modifikasi fungsi `check_port()` agar memperbarui `WEB_PORT` via `set_env_value`.
- **`PANDUAN_HARDWARE.md`**: File panduan Markdown baru.

#### Metode Testing
- **Manual (Port):** Jalankan proses di port 8097, deploy ulang dengan `./run.sh deploy`, pastikan script menemukan port baru (misal 8098) dan memperbarui `.env`.
- **Manual (Panduan):** Verifikasi konten panduan sesuai dengan *codebase* terkini.

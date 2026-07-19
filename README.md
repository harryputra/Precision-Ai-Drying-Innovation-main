# SolarDryerAI — Padi PRECISION

Sistem pengering padi tenaga surya berbasis IoT + AI (closed-loop):
**ESP32** (sensor DHT22 + PID + relay) → **Laravel 13** (API + dashboard real-time
Reverb) → **n8n multi-agent** (tiap 15 menit) → **Gemini/Groq** (keputusan setpoint)
→ kembali ke ESP32 via polling. Dokumentasi lengkap sistem: [read.md](read.md).

## Menjalankan (satu perintah)

```bash
./run.sh          # DEMO lokal  : data contoh realistis + Quick-Login aktif
./run.sh deploy   # PRODUKSI    : bersih tanpa data contoh, admin dari .env
./run.sh help     # semua subcommand
```

Windows: `run.bat` (subcommand sama). Semua service jalan di Docker — host tidak
butuh PHP/Node/Composer.

Demo dan produksi adalah **stack terisolasi** (`solardryer-demo` vs
`solardryer-prod`; volume, network, dan port terpisah — demo `8098`, prod `8097`)
sehingga bisa jalan bersamaan tanpa saling menyentuh data.

| Service | Isi |
| --- | --- |
| `app` | Laravel + Apache (port host `WEB_PORT`, bind 127.0.0.1) + proxy WS `/app` → reverb |
| `queue` | `php artisan queue:work` (broadcast event, job) |
| `scheduler` | `php artisan schedule:work` (retensi data mingguan) |
| `reverb` | WebSocket server (dashboard real-time) — internal saja |
| `n8n` | AI agent workflow (port host `N8N_PORT`, bind 127.0.0.1) |

Database **SQLite** (WAL) di volume `dbdata` — ringan untuk VM 4 GB RAM.

## Deploy ke server trin (produksi)

```bash
ssh trin@172.16.67.5
cd /home/trin/docker/apps
git clone <repo> solardryer-app && cd solardryer-app
./run.sh deploy        # .env dibuat otomatis; secret digenerate otomatis
nano .env              # WAJIB isi: ADMIN_EMAIL, ADMIN_PASSWORD, GEMINI_API_KEY,
                       # GROQ_API_KEY, OPENWEATHER_API_KEY → lalu ./run.sh deploy lagi
```

Catatan: `deploy` = build produksi (vite build + composer no-dev), **detached**,
`restart: unless-stopped` (tahan reboot, tidak mati saat SSH ditutup). Entrypoint
otomatis menjalankan migrasi + seed esensial idempoten — tanpa langkah manual.

**Publikasi via Cloudflare Tunnel** (bukan port-forward): Cloudflare dashboard →
Tunnels → `proxmox-server` → Public Hostname → tambah
`solardryer.trin-polman.id` → `http://localhost:8097`. Tunnel meng-handle HTTPS
dan WebSocket (dashboard real-time ikut jalan — WS diproksikan same-origin di
path `/app`).

**Verifikasi setelah deploy:**

```bash
curl -s http://127.0.0.1:8097/api/health          # {"status":"ok","db":"ok",...}
curl -sI https://solardryer.trin-polman.id | head  # cek header keamanan ter-apply
curl -s -o /dev/null -w "TLS:%{time_appconnect} TTFB:%{time_starttransfer} Total:%{time_total}\n" \
  https://solardryer.trin-polman.id/api/health
```

Membaca TTFB: ≤ ~150 ms = app sehat; TTFB rendah tapi Total tinggi → jaringan/
proxy, bukan app; hit pertama lambat lalu cepat → ciri cold start/dev mode
(tidak boleh terjadi — stack ini selalu build produksi).

**Update / redeploy:**

```bash
cd /home/trin/docker/apps/solardryer-app
git pull
./run.sh deploy        # rebuild image + migrasi otomatis, data aman di volume
```

**Pengelolaan:** `./run.sh prod-logs` (Ctrl+C keluar log, app tetap jalan),
`prod-restart`, `prod-down`, `status`. Reset total (HATI-HATI, hapus data):
`./run.sh reset`.

## Setup n8n (AI decision engine)

1. Buka UI n8n: dari laptop `ssh -L 5681:127.0.0.1:5681 trin@<ip>` lalu
   `http://localhost:5681` (atau tambah Public Hostname Cloudflare sendiri).
2. Buat akun owner (pertama kali) → **Import from File** → `n8n-workflow.json`.
3. URL node sudah menunjuk `http://app/...` (jaringan internal Docker) dan header
   auth memakai `{{ $env.AI_WEBHOOK_KEY }}` / `{{ $env.IOT_DEVICE_KEY }}` yang
   sudah di-inject compose dari `.env`. Bila n8n memblokir akses `$env`, ganti
   value header dengan nilai key dari `.env` secara manual.
4. Isi API key **Gemini** pada node "9. Gemini API" (credential n8n).
5. **Activate** workflow — jalan tiap 15 menit; berhenti sendiri (early-exit)
   bila tidak ada batch aktif/sensor.

## Firmware ESP32 (`esp32_solardryerai.ino`)

Sudah disesuaikan untuk server produksi:

- `SERVER_URL = "https://solardryer.trin-polman.id"` — HTTPS via Cloudflare;
  ESP32 yang inisiasi semua koneksi (POST sensor 30 detik + polling command
  30 detik), jadi tidak butuh port masuk ke perangkat.
- `DEVICE_KEY` — **WAJIB** diisi nilai `IOT_DEVICE_KEY` dari `.env` server
  (ditampilkan di ringkasan `./run.sh deploy`). Dikirim sebagai header
  `X-Device-Key`; tanpa key valid endpoint `/api/iot/*` menolak (401).
- TLS memakai `setInsecure()` (tanpa verifikasi sertifikat) — autentikasi tetap
  dijaga device key; opsional hardening: pin root CA.
- Fallback aman tetap ada: offline > 15 menit → PID kembali ke setpoint default.

Isi `WIFI_SSID`, `WIFI_PASSWORD`, `DEVICE_KEY`, upload via Arduino IDE (board
ESP32 Dev Module).

## Mode demo & Quick-Login

`./run.sh` (demo) men-seed data contoh realistis dan **mengaktifkan Quick-Login**:
tombol per-role (admin/operator/viewer) tampil di halaman login, plus URL rahasia
`/q/<token>` (ditampilkan di ringkasan). Akun contoh — password semua `password`:

- `admin@solardryerai.test` · `operator@solardryerai.test` · `viewer@solardryerai.test`

Di **produksi** Quick-Login default **nonaktif** (semua endpoint-nya 404). Admin
dapat mengaktifkannya sementara (dengan expiry) dari menu **Quick Login** di
sidebar admin — token acak 128-bit, validasi constant-time, semua percobaan
teraudit di System Logs.

## Keamanan (ringkas)

- Endpoint ESP32 (`/api/iot/*`) → header `X-Device-Key`; endpoint n8n
  (`/api/ai/*`) → header `X-AI-Webhook-Key`; keduanya digenerate otomatis run.sh.
- Header keamanan HTTP global (X-Frame-Options DENY, nosniff, HSTS,
  Referrer-Policy, Permissions-Policy, CSP frame-ancestors, no-store untuk
  halaman personal) + `expose_php Off` + `ServerTokens Prod`.
- Rate limit: login 15/menit, register 5/menit, quick-login 10/menit; password
  register min 8 + huruf besar/kecil + angka (maks 72 — batas bcrypt).
- Port DB tidak pernah diekspos; semua port host bind `127.0.0.1` (publik hanya
  lewat Cloudflare Tunnel). Cookie session `Secure` di produksi.
- CATATAN: form register publik belum ber-CAPTCHA — bila spam jadi masalah,
  tambahkan Cloudflare Turnstile.

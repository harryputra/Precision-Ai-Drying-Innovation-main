# Panduan Setup Hardware & Firmware ESP32 (SolarDryerAI)

Dokumen ini ditujukan untuk tim hardware yang akan melakukan *flashing* firmware ESP32 dan merakit komponen kelistrikan untuk sistem **Padi PRECISION (SolarDryerAI)**.

## 1. Persiapan Firmware (`esp32_solardryerai.ino`)
File firmware sudah disesuaikan dengan arsitektur server (menggunakan Cloudflare Tunnel `https://solardryer.trin-polman.id`), namun **wajib mengubah 3 baris konfigurasi** berikut sebelum di-upload ke ESP32.

Buka file `esp32_solardryerai.ino` di Arduino IDE, lalu cari bagian **KONFIGURASI** (sekitar baris 49):

```cpp
const char* WIFI_SSID     = "NAMA_WIFI_KAMU";           // Ganti dengan SSID WiFi di lokasi
const char* WIFI_PASSWORD = "PASSWORD_WIFI_KAMU";       // Ganti dengan Password WiFi
const char* SERVER_URL    = "https://solardryer.trin-polman.id"; // BIARKAN SEPERTI INI
const char* DEVICE_KEY    = "ISI_IOT_DEVICE_KEY_DARI_ENV_SERVER"; // GANTI dengan IOT_DEVICE_KEY dari .env server
```

**Penting:** Minta nilai `IOT_DEVICE_KEY` dari tim IT/Server. Tanpa key ini, server akan menolak data sensor dari ESP32 (Error 401 Unauthorized).

## 2. Library Arduino IDE yang Dibutuhkan
Pastikan library berikut sudah terinstal via **Library Manager** di Arduino IDE sebelum *compile/upload*:
1. **DHT sensor library** by Adafruit
2. **Adafruit Unified Sensor** by Adafruit
3. **LiquidCrystal I2C** by Frank de Brabander
4. **ArduinoJson** by Benoit Blanchon (versi 6.x)

## 3. Skema Pinout Hardware (Wiring)
Sesuaikan *wiring* komponen dengan pin ESP32 berikut:

| Komponen | Pin ESP32 | Keterangan |
| :--- | :--- | :--- |
| **Sensor DHT22** | `GPIO 4` | Pin Data (berikan resistor pull-up 10k jika modul belum punya) |
| **LCD 16x2 I2C** | `SDA: 21`<br>`SCL: 22` | Alamat I2C default: `0x27` (bisa diubah di kode jika beda) |
| **Relay 1 (Exhaust)** | `GPIO 25` | Menyala jika kelembaban (RH) > 65% |
| **Relay 2 (Heater)** | `GPIO 26` | Dikontrol otomatis oleh sistem PID (Setpoint dari AI) |
| **Relay 3 (Kipas/Fan)**| `GPIO 27` | Menyala jika suhu > 38°C (atau di-override oleh AI) |
| **Relay 4 (Motor Mixer)**| `GPIO 14` | Menyala otomatis selama pengeringan berlangsung |

*(Catatan: Relay menggunakan logika "Aktif LOW", artinya pin berstatus LOW = Relay ON. Hal ini sudah ditangani di dalam kode firmware).*

## 4. Alur Pengetesan (Testing)
Setelah di-upload, nyalakan alat dan perhatikan LCD I2C atau Serial Monitor (Baudrate `115200`):
1. **WiFi Connect:** ESP32 akan mencoba terhubung ke WiFi. Jika berhasil, LCD akan menampilkan IP Address.
2. **Pembacaan Sensor:** Suhu dan Kelembaban (T & RH) akan muncul di layar. Coba pegang sensor DHT22, pastikan angka suhu/kelembaban naik.
3. **Test Koneksi Server:** Jika koneksi berhasil, di Serial Monitor akan muncul `[HTTP] Sensor sent OK`. Jika error, pastikan koneksi internet bagus dan `DEVICE_KEY` benar.
4. **Test Relay:** Relay heater akan "cetek" (ON/OFF) tergantung selisih suhu aktual dan Setpoint (SP). 

Jika saat dinyalakan LCD menampilkan teks `!SERVER OFFLINE!`, artinya alat gagal menghubungi server. Sistem keamanan offline akan aktif (mematikan heater jika suhu berlebih).

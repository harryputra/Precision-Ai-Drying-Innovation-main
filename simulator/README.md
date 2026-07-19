# Simulator Mesin Pengering Gabah

Simulator standalone yang meniru perilaku ESP32 SolarDryerAI. Mengirim data sensor ke dashboard melalui HTTP POST ke endpoint Laravel `/api/iot/sensor`, sama seperti firmware asli di `esp32_solardryerai.ino`.

## Komunikasi Data

Proyek ini **saat ini menerima data mesin via HTTP**, bukan MQTT:

| Aspek | Detail |
|-------|--------|
| Protokol | HTTP/HTTPS |
| Endpoint | `POST /api/iot/sensor` |
| Auth | Header `X-Device-Key` (dari `IOT_DEVICE_KEY` di `.env`) |
| Real-time dashboard | Laravel Reverb WebSocket (`sensor-updates` channel) |
| Contoh klien | `esp32_solardryerai.ino` |

Jika ingin menambahkan MQTT, diperlukan **MQTT broker + bridge** yang menerima MQTT dari mesin lalu forward ke HTTP endpoint Laravel.

## Parameter yang Dikirim

Parameter sesuai validasi di `SensorReadingController::store()`:

- `device_id` — wajib, ID device di tabel `devices`
- `temperature_inside` — suhu dalam ruang pengering (°C)
- `humidity_inside` — kelembaban dalam (%)
- `temperature_outside` — suhu lingkungan (°C)
- `humidity_outside` — kelembaban lingkungan (%)
- `solar_irradiance` — radiasi matahari (W/m²)
- `lux` — intensitas cahaya
- `grain_moisture` — kadar air gabah (%)
- `grain_weight` — berat gabah (kg)
- `wind_speed` — kecepatan angin (m/s)
- `wind_direction` — arah angin (0-359°)
- `pid_setpoint` — setpoint PID (°C)
- `pid_output` — output PID
- `ai_active` — status AI aktif (boolean)
- `is_valid` — validasi data (boolean)

## Cara Menjalankan

Pastikan server Laravel sudah berjalan. Untuk dev lokal:

```bash
php artisan serve --port=8097
```

Jalankan simulator:

```bash
python simulator.py --url http://localhost:8097 --device 1 --key demo-device-key
```

Atau di Windows:

```bash
python simulator.py --url http://localhost:8097 --device 1 --key demo-device-key
```

### Opsi CLI

| Opsi | Default | Keterangan |
|------|---------|------------|
| `--url` | `http://localhost:8097` | Base URL server |
| `--device` | `1` | ID device di database |
| `--key` | `demo-device-key` | `X-Device-Key` |
| `--interval` | `30` | Interval kirim sensor (detik) |
| `--poll` | `false` | Aktifkan polling perintah AI dari server |
| `--target-moisture` | `13.0` | Target kadar air akhir (%) |

Contoh dengan polling perintah AI dan interval 10 detik:

```bash
python simulator.py --url http://localhost:8097 --device 1 --key demo-device-key --interval 10 --poll
```

## Prasyarat

- Python 3.8 atau lebih baru.
- Tidak perlu library eksternal; simulator hanya menggunakan modul bawaan Python.
- Device ID yang dipakai harus sudah ada di tabel `devices` (bisa dibuat lewat seeder `DeviceSeeder` atau DemoSeeder).

## Integrasi MQTT (Opsional)

Jika ingin mesin benar-benar pakai MQTT, tambahkan komponen berikut:

1. **MQTT broker** (Mosquitto, EMQX, HiveMQ, atau AWS IoT Core).
2. **Bridge/forwarder** yang subscribe topik mesin, misalnya `solardryer/{device_id}/sensor`, lalu POST ke `/api/iot/sensor` dengan header `X-Device-Key`.
3. **Ubah firmware ESP32** agar publish ke MQTT, bukan HTTP.

File `simulator.py` yang ada ini fokus menguji arsitektur HTTP yang sudah berjalan. Bridge MQTT bisa ditambahkan di file terpisah jika diperlukan.

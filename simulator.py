#!/usr/bin/env python3
"""
SolarDryerAI — Grain Dryer Simulator
====================================
Simulator mesin pengering gabah yang mengirim data sensor ke dashboard
melalui HTTP POST ke endpoint Laravel `/api/iot/sensor`, persis seperti
ESP32 asli (esp32_solardryerai.ino).

Cara pakai:
    python simulator.py --url http://localhost:8097 --device 1 --key secret

Simulator ini mensimulasikan:
- Suhu & kelembaban dalam ruang pengering (dengan noise realistis)
- PID setpoint yang dapat diubah
- Kadar air gabah yang menurun perlahan selama drying
- Data cuaca luar (suhu, RH, radiasi matahari, angin)
- Status aktuator (heater, fan, exhaust, mixer)
- Polling perintah AI dari `/api/iot/pending-command` (opsional)

Ketergantungan: Python 3.8+ (tanpa library eksternal, pakai urllib bawaan).
"""

from __future__ import annotations

import argparse
import json
import math
import random
import sys
import time
import urllib.error
import urllib.request
from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Any


# ---------------------------------------------------------------------------
# Konfigurasi default
# ---------------------------------------------------------------------------
DEFAULT_SERVER_URL = "http://localhost:8097"
DEFAULT_DEVICE_ID = 1
DEFAULT_DEVICE_KEY = "demo-device-key"
DEFAULT_INTERVAL = 30  # detik

# Batasan fisik & safety (sama dengan ESP32)
TEMP_SETPOINT_DEF = 45.0
TEMP_SETPOINT_MIN = 35.0
TEMP_SETPOINT_MAX = 55.0
TEMP_CRITICAL_OFF = 58.0
TEMP_FAN_ON = 38.0
TEMP_FAN_OFF = 35.0
RH_EXHAUST_ON = 65.0
RH_EXHAUST_OFF = 55.0


# ---------------------------------------------------------------------------
# State simulator
# ---------------------------------------------------------------------------
@dataclass
class MachineState:
    """State mesin pengering yang akan disimulasikan."""

    # Sensor dalam ruang pengering
    temperature_inside: float = 32.0
    humidity_inside: float = 70.0
    grain_moisture: float = 24.0
    grain_weight: float = 100.0

    # Sensor luar (cuaca)
    temperature_outside: float = 30.0
    humidity_outside: float = 65.0
    solar_irradiance: float = 450.0
    lux: float = 8000.0
    wind_speed: float = 2.5
    wind_direction: int = 180

    # PID / AI state
    pid_setpoint: float = TEMP_SETPOINT_DEF
    pid_output: float = 0.0
    ai_active: bool = False

    # Aktuator
    heater_on: bool = False
    fan_on: bool = False
    exhaust_on: bool = False
    mixer_on: bool = False

    # Tracking waktu & siklus
    drying_active: bool = True
    target_moisture: float = 13.0
    elapsed_minutes: float = 0.0
    last_update: float = field(default_factory=time.time)

    def to_payload(self) -> dict[str, Any]:
        return {
            "device_id": self.device_id,
            "temperature_inside": round(self.temperature_inside, 1),
            "humidity_inside": round(self.humidity_inside, 1),
            "temperature_outside": round(self.temperature_outside, 1),
            "humidity_outside": round(self.humidity_outside, 1),
            "solar_irradiance": round(self.solar_irradiance, 1),
            "lux": round(self.lux, 1),
            "grain_moisture": round(self.grain_moisture, 1),
            "grain_weight": round(self.grain_weight, 2),
            "wind_speed": round(self.wind_speed, 1),
            "wind_direction": int(self.wind_direction) % 360,
            "pid_setpoint": round(self.pid_setpoint, 1),
            "pid_output": round(self.pid_output, 1),
            "ai_active": self.ai_active,
            "is_valid": True,
        }

    device_id: int = DEFAULT_DEVICE_ID


# ---------------------------------------------------------------------------
# Fungsi bantu HTTP (tanpa requests agar tidak perlu install dependency)
# ---------------------------------------------------------------------------
def http_post(url: str, payload: dict, key: str) -> tuple[int, dict | None]:
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-Device-Key": key,
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            body = resp.read().decode("utf-8")
            return resp.status, json.loads(body) if body else None
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8")
        try:
            return e.code, json.loads(body)
        except json.JSONDecodeError:
            return e.code, {"message": body}


def http_get(url: str, key: str) -> tuple[int, dict | None]:
    req = urllib.request.Request(
        url,
        headers={"Accept": "application/json", "X-Device-Key": key},
        method="GET",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            body = resp.read().decode("utf-8")
            return resp.status, json.loads(body) if body else None
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8")
        try:
            return e.code, json.loads(body)
        except json.JSONDecodeError:
            return e.code, {"message": body}


# ---------------------------------------------------------------------------
# Simulasi fisik mesin
# ---------------------------------------------------------------------------
def compute_pid(state: MachineState) -> None:
    """Hitung PID output sederhana berdasarkan error setpoint vs suhu aktual."""
    kp, ki, kd = 2.0, 0.5, 0.1
    dt = 0.5  # simulasi PID bekerja tiap 500 ms

    error = state.pid_setpoint - state.temperature_inside

    # Sederhana: output proporsional + integral + derivative palsu
    # Untuk simulator, kita anggap error sekarang sebagai basis output.
    state.pid_output = max(-100.0, min(100.0, kp * error + ki * error * dt))

    # Heater ON jika output PID positif dan suhu belum kritis
    state.heater_on = (
        state.drying_active and state.pid_output > 10.0 and state.temperature_inside < TEMP_CRITICAL_OFF
    )


def update_weather(state: MachineState, dt: float) -> None:
    """Ubah parameter cuaca luar secara perlahan (sinusoidal + noise)."""
    now = datetime.now(timezone.utc)
    minute_of_day = now.hour * 60 + now.minute + now.second / 60.0

    # Suhu luar ikut siklus harian
    daily_temp = 30.0 + 4.0 * math.sin(2 * math.pi * (minute_of_day - 360) / 1440)
    state.temperature_outside = clamp(
        state.temperature_outside + (daily_temp - state.temperature_outside) * 0.02 * dt,
        22.0,
        40.0,
    )

    state.humidity_outside = clamp(state.humidity_outside + random.uniform(-0.5, 0.5) * dt, 40.0, 95.0)

    # Radiasi matahari naik siang, turun malam
    irradiance_base = max(0.0, 950.0 * math.sin(2 * math.pi * minute_of_day / 1440))
    state.solar_irradiance = clamp(irradiance_base + random.uniform(-30, 30), 0.0, 1200.0)
    state.lux = clamp(state.solar_irradiance * 18 + random.uniform(-500, 500), 0.0, 12000.0)

    state.wind_speed = clamp(state.wind_speed + random.uniform(-0.3, 0.3) * dt, 0.0, 8.0)
    state.wind_direction = (state.wind_direction + int(random.uniform(-5, 5))) % 360


def update_drying(state: MachineState, dt: float) -> None:
    """Simulasikan pengeringan gabah."""
    if not state.drying_active:
        state.heater_on = False
        state.fan_on = False
        state.mixer_on = False
        return

    compute_pid(state)

    # Fan: ON jika suhu tinggi atau heater panas, OFF jika suhu rendah
    if not state.fan_on and state.temperature_inside >= TEMP_FAN_ON:
        state.fan_on = True
    elif state.fan_on and state.temperature_inside < TEMP_FAN_OFF:
        state.fan_on = False

    # Exhaust: ON jika RH dalam tinggi
    if not state.exhaust_on and state.humidity_inside > RH_EXHAUST_ON:
        state.exhaust_on = True
    elif state.exhaust_on and state.humidity_inside < RH_EXHAUST_OFF:
        state.exhaust_on = False

    # Suhu dalam ruang: naik jika heater ON, turun jika fan ON, konveksi ke luar
    heat_gain = (1.5 if state.heater_on else 0.0) + (state.solar_irradiance / 1200.0) * 0.5
    heat_loss = (0.4 if state.fan_on else 0.1) * (state.temperature_inside - state.temperature_outside)
    state.temperature_inside = clamp(
        state.temperature_inside + (heat_gain - heat_loss + random.uniform(-0.1, 0.1)) * dt,
        state.temperature_outside - 2.0,
        TEMP_CRITICAL_OFF + 2.0,
    )

    # Kelembaban dalam: turun jika exhaust ON, naik jika gabah lepas uap
    moisture_release = 0.0 if state.grain_moisture <= state.target_moisture else 0.15 * dt
    exhaust_reduction = (0.8 if state.exhaust_on else 0.1) * dt
    state.humidity_inside = clamp(
        state.humidity_inside + (moisture_release - exhaust_reduction + random.uniform(-0.2, 0.2)) * dt,
        20.0,
        95.0,
    )

    # Kadar air gabah menurun selama panas dan kering
    if state.grain_moisture > state.target_moisture:
        drying_rate = (
            0.015
            * (state.temperature_inside / 50.0)
            * (1.0 - state.humidity_inside / 100.0)
            * dt
        )
        state.grain_moisture = max(state.target_moisture, state.grain_moisture - drying_rate)
        state.grain_weight = max(50.0, state.grain_weight - drying_rate * 0.15)

    # Mixer: nyala selama drying aktif dan ada pemanasan/putaran udara
    state.mixer_on = state.heater_on or state.fan_on

    state.elapsed_minutes += dt / 60.0


def apply_ai_command(state: MachineState, command: dict) -> None:
    """Terapkan perintah AI yang diterima dari server."""
    if not command:
        return

    decision_type = command.get("decision_type", "other")
    actions = command.get("actions", {})
    target_temp = actions.get("target_temp", TEMP_SETPOINT_DEF)
    cmd_mode = actions.get("mode", "auto")
    cmd_fan = actions.get("fan", False)

    state.ai_active = True

    if decision_type == "pause_drying":
        state.drying_active = False
        state.heater_on = False
        state.fan_on = False
        state.mixer_on = False
        print("[AI] PAUSE drying — semua aktuator OFF")
    elif decision_type == "resume_drying":
        state.drying_active = True
        state.pid_setpoint = clamp(target_temp, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX)
        print(f"[AI] RESUME drying — setpoint {state.pid_setpoint}°C")
    elif decision_type == "stop_heater":
        state.heater_on = False
        state.pid_setpoint = TEMP_SETPOINT_MIN
        state.fan_on = cmd_fan
        print("[AI] STOP heater — fan", "ON" if state.fan_on else "OFF")
    else:
        state.drying_active = True
        state.pid_setpoint = clamp(target_temp, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX)
        if cmd_mode != "auto":
            state.fan_on = cmd_fan
        print(f"[AI] Setpoint {state.pid_setpoint}°C | mode={cmd_mode}")


def clamp(value: float, min_val: float, max_val: float) -> float:
    return max(min_val, min(max_val, value))


# ---------------------------------------------------------------------------
# Main loop
# ---------------------------------------------------------------------------
def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Simulator mesin pengering gabah SolarDryerAI",
    )
    parser.add_argument(
        "--url",
        default=DEFAULT_SERVER_URL,
        help=f"Base URL server Laravel (default: {DEFAULT_SERVER_URL})",
    )
    parser.add_argument(
        "--device",
        type=int,
        default=DEFAULT_DEVICE_ID,
        help=f"ID device di database (default: {DEFAULT_DEVICE_ID})",
    )
    parser.add_argument(
        "--key",
        default=DEFAULT_DEVICE_KEY,
        help="X-Device-Key untuk autentikasi endpoint IoT",
    )
    parser.add_argument(
        "--interval",
        type=int,
        default=DEFAULT_INTERVAL,
        help=f"Interval kirim sensor dalam detik (default: {DEFAULT_INTERVAL})",
    )
    parser.add_argument(
        "--poll",
        action="store_true",
        help="Aktifkan polling perintah AI dari server",
    )
    parser.add_argument(
        "--target-moisture",
        type=float,
        default=13.0,
        help="Target kadar air gabah % (default: 13.0)",
    )
    return parser


def main() -> int:
    parser = build_arg_parser()
    args = parser.parse_args()

    base_url = args.url.rstrip("/")
    sensor_url = f"{base_url}/api/iot/sensor"
    command_url = f"{base_url}/api/iot/pending-command?device_id={args.device}"

    state = MachineState()
    state.device_id = args.device
    state.target_moisture = args.target_moisture
    state.pid_setpoint = TEMP_SETPOINT_DEF

    print("=" * 60)
    print("SolarDryerAI — Grain Dryer Simulator")
    print("=" * 60)
    print(f"Server    : {base_url}")
    print(f"Endpoint  : {sensor_url}")
    print(f"Device ID : {args.device}")
    print(f"Interval  : {args.interval}s")
    print(f"Poll AI   : {'ya' if args.poll else 'tidak'}")
    print("Tekan Ctrl+C untuk berhenti.")
    print("=" * 60)

    last_sensor_send = 0.0
    last_poll = 0.0

    try:
        while True:
            now = time.time()
            dt = now - state.last_update
            state.last_update = now

            update_weather(state, dt)
            update_drying(state, dt)

            # Kirim sensor tiap interval
            if now - last_sensor_send >= args.interval:
                last_sensor_send = now
                payload = state.to_payload()
                status, resp = http_post(sensor_url, payload, args.key)
                ts = datetime.now().strftime("%H:%M:%S")
                if status in (200, 201):
                    print(
                        f"[{ts}] SENSOR OK → T_in={payload['temperature_inside']}°C "
                        f"RH_in={payload['humidity_inside']}% "
                        f"Moisture={payload['grain_moisture']}% "
                        f"SP={payload['pid_setpoint']}°C "
                        f"H/F/M={int(state.heater_on)}/{int(state.fan_on)}/{int(state.mixer_on)}"
                    )
                else:
                    print(f"[{ts}] SENSOR FAILED ({status}): {resp}")

            # Poll perintah AI dari server
            if args.poll and now - last_poll >= 60:
                last_poll = now
                status, resp = http_get(command_url, args.key)
                if status == 200 and resp and resp.get("status") and resp.get("command"):
                    apply_ai_command(state, resp["command"])

            # Simulasi internal jalan tiap 0.5 detik
            time.sleep(0.5)
    except KeyboardInterrupt:
        print("\n[Simulator] Dihentikan oleh user.")
    return 0


if __name__ == "__main__":
    sys.exit(main())

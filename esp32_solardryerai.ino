/*
 * SolarDryerAI — ESP32 Controller
 * 
 * Arsitektur kontrol:
 *   - PID Controller : mengatur heater agar suhu stabil di setpoint
 *   - LLM (AI)       : supervisor — menentukan setpoint optimal (bukan kontrol relay langsung)
 *   - Threshold      : safety layer independen — override semua jika suhu kritis
 * 
 * Alur kontrol:
 *   Sensor (500ms) → PID hitung output → relay heater
 *                        ↑
 *               setpoint dari LLM (tiap 15 menit via server)
 *                        ↑
 *               fallback: setpoint default 45°C jika tidak ada AI
 * 
 * Hardware:
 *   - DHT22         : GPIO 4
 *   - LCD I2C 16x2  : SDA=21, SCL=22 (addr 0x27)
 *   - Relay Exhaust : GPIO 25 (kontrol kelembaban, threshold independen)
 *   - Relay Heater  : GPIO 26 (dikontrol PID)
 *   - Relay Fan     : GPIO 27 (dikontrol threshold + AI)
 * 
 * Library yang dibutuhkan (install via Library Manager):
 *   - DHT sensor library by Adafruit
 *   - Adafruit Unified Sensor
 *   - LiquidCrystal I2C by Frank de Brabander
 *   - ArduinoJson by Benoit Blanchon
 *
 * Komunikasi server (deploy di server trin, publik via Cloudflare Tunnel):
 *   - SERVER_URL = domain HTTPS (bukan IP lokal). ESP32 yang inisiasi semua
 *     koneksi (POST sensor + polling command) — tidak butuh port masuk.
 *   - TLS memakai setInsecure() (tanpa verifikasi sertifikat) — cukup untuk
 *     kanal ini karena server tetap memverifikasi DEVICE_KEY di tiap request.
 *   - DEVICE_KEY dikirim di header X-Device-Key — WAJIB sama dengan
 *     IOT_DEVICE_KEY di .env server (lihat ringkasan `./run.sh deploy`).
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ============================================================
// KONFIGURASI — sesuaikan dengan jaringan dan server kamu
// ============================================================
const char* WIFI_SSID     = "NAMA_WIFI_KAMU";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_KAMU";

// Server produksi (Cloudflare Tunnel → server trin).
// Untuk pengujian lokal boleh diganti "http://192.168.x.x:8098" (mode demo).
const char* SERVER_URL    = "https://solardryer.trin-polman.id";

// WAJIB diisi: nilai IOT_DEVICE_KEY dari .env server
// (ditampilkan ringkasan `./run.sh deploy`). Kosong = header tidak dikirim
// (hanya cocok untuk dev lokal yang IOT_DEVICE_KEY-nya juga kosong).
const char* DEVICE_KEY    = "ISI_IOT_DEVICE_KEY_DARI_ENV_SERVER";

const int   DEVICE_ID     = 1;
const int   BATCH_ID      = 0;  // 0 = ambil batch aktif otomatis dari server

// ============================================================
// PIN DEFINITION
// ============================================================
#define PIN_DHT           4
#define PIN_RELAY_EXHAUST 25   // LOW = ON (relay aktif low)
#define PIN_RELAY_HEATER  26
#define PIN_RELAY_FAN     27
#define PIN_RELAY_MIXER   14   // Motor mixer (gear motor) — relay ke-4
#define DHT_TYPE          DHT22

// LCD I2C
#define LCD_ADDR  0x27
#define LCD_COLS  16
#define LCD_ROWS  2

// ============================================================
// PID PARAMETER
// Kp, Ki, Kd perlu di-tuning sesuai karakteristik ruang pengering
// ============================================================
const float PID_KP = 2.0;   // Proportional — respons cepat ke error
const float PID_KI = 0.5;   // Integral     — eliminasi steady-state error
const float PID_KD = 0.1;   // Derivative   — redam overshoot

const float PID_INTEGRAL_MAX  =  20.0;  // anti-windup: batas akumulasi integral
const float PID_INTEGRAL_MIN  = -20.0;
const float PID_OUTPUT_MAX    = 100.0;  // output PID max (mapped ke relay duty)
const float PID_OUTPUT_MIN    = -100.0;
const float PID_DEADBAND      =   1.0;  // °C — tidak koreksi jika error dalam ±1°C

// ============================================================
// SAFETY THRESHOLD (independen dari PID dan AI — selalu aktif)
// ============================================================
const float TEMP_CRITICAL_OFF  = 58.0;  // °C — matikan heater paksa, tidak bisa di-override
const float TEMP_SETPOINT_MIN  = 35.0;  // °C — setpoint minimum yang diizinkan dari AI
const float TEMP_SETPOINT_MAX  = 55.0;  // °C — setpoint maksimum yang diizinkan dari AI
const float TEMP_SETPOINT_DEF  = 45.0;  // °C — setpoint default jika tidak ada AI

// Fan threshold
const float TEMP_FAN_ON   = 38.0;  // °C
const float TEMP_FAN_OFF  = 35.0;  // °C

// Exhaust threshold (selalu threshold, tidak diubah AI)
const float RH_EXHAUST_ON  = 65.0;  // %
const float RH_EXHAUST_OFF = 55.0;  // %

// ============================================================
// OFFLINE SAFETY TIMEOUT
// Jika tidak ada koneksi server selama OFFLINE_SAFE_TIMEOUT ms,
// sistem fallback ke safe state: heater off, setpoint default.
// PID tetap jalan di setpoint default — tidak mati total.
// ============================================================
const unsigned long OFFLINE_SAFE_TIMEOUT = 15UL * 60UL * 1000UL;  // 15 menit

// ============================================================
// INTERVAL (milliseconds)
// ============================================================
const unsigned long INTERVAL_SENSOR   = 30000;  // kirim sensor tiap 30 detik
const unsigned long INTERVAL_POLLING  = 30000;  // polling command tiap 30 detik
const unsigned long INTERVAL_LCD      =  3000;  // refresh LCD tiap 3 detik
const unsigned long INTERVAL_PID      =   500;  // hitung PID tiap 500ms

// ============================================================
// OBJEK
// ============================================================
DHT dht(PIN_DHT, DHT_TYPE);
LiquidCrystal_I2C lcd(LCD_ADDR, LCD_COLS, LCD_ROWS);

// Client HTTP/HTTPS — dipilih otomatis dari skema SERVER_URL
WiFiClientSecure tlsClient;   // untuk https:// (Cloudflare)
WiFiClient       plainClient; // untuk http:// (dev lokal)

// ============================================================
// HELPER HTTP — begin() sesuai skema + header auth device key
// ============================================================
bool beginRequest(HTTPClient& http, const String& url) {
  bool okBegin;
  if (url.startsWith("https://")) {
    okBegin = http.begin(tlsClient, url);
  } else {
    okBegin = http.begin(plainClient, url);
  }
  if (!okBegin) return false;

  http.setConnectTimeout(8000);
  http.setTimeout(10000);
  http.addHeader("Accept", "application/json");
  if (strlen(DEVICE_KEY) > 0) {
    http.addHeader("X-Device-Key", DEVICE_KEY);
  }
  return true;
}

// ============================================================
// STATE — SENSOR
// ============================================================
float temperature = 0.0;
float humidity    = 0.0;

// ============================================================
// STATE — PID CONTROLLER
// ============================================================
float pidSetpoint = TEMP_SETPOINT_DEF;  // diupdate oleh AI
float pidIntegral = 0.0;
float pidPrevError = 0.0;
float pidOutput   = 0.0;

// ============================================================
// STATE — RELAY & AI
// ============================================================
bool  heaterState   = false;
bool  fanState      = false;
bool  exhaustState  = false;
bool  mixerState    = false;   // Motor mixer — nyala saat drying, mati saat pause
bool  aiActive      = false;   // true = setpoint dari AI, false = default
bool  dryingPaused  = false;   // true = AI minta pause semua (hujan, dll)
bool  fanOverride   = false;   // true = AI atur fan langsung
bool  fanAiState    = false;   // state fan yang diminta AI

String aiDecisionType = "none";
String wifiStatus     = "Connecting...";
int    lcdPage        = 0;

// ============================================================
// STATE — OFFLINE TRACKING
// ============================================================
unsigned long lastServerContact   = 0;      // millis() saat terakhir server merespons OK
bool          offlineSafeTriggered = false;  // true = sudah masuk safe state karena offline

// ============================================================
// TIMER
// ============================================================
unsigned long lastSensorSend  = 0;
unsigned long lastPolling     = 0;
unsigned long lastLcdRefresh  = 0;
unsigned long lastPidUpdate   = 0;

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  Serial.println("\n[SolarDryerAI] Booting — LLM+PID Hybrid Mode");

  // Init relay pin — HIGH = OFF (relay aktif low)
  pinMode(PIN_RELAY_EXHAUST, OUTPUT);
  pinMode(PIN_RELAY_HEATER,  OUTPUT);
  pinMode(PIN_RELAY_FAN,     OUTPUT);
  pinMode(PIN_RELAY_MIXER,   OUTPUT);
  setRelay(PIN_RELAY_EXHAUST, false);
  setRelay(PIN_RELAY_HEATER,  false);
  setRelay(PIN_RELAY_FAN,     false);
  setRelay(PIN_RELAY_MIXER,   false);

  dht.begin();

  // TLS tanpa verifikasi sertifikat — autentikasi tetap dijaga DEVICE_KEY
  // (opsional hardening: ganti dengan setCACert(root CA ISRG Root X1))
  tlsClient.setInsecure();

  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  lcdPrint("SolarDryerAI", "LLM+PID Mode");
  delay(1500);

  connectWiFi();
}

// ============================================================
// LOOP
// ============================================================
void loop() {
  unsigned long now = millis();

  // 1. Baca sensor
  readSensor();

  // 2. Safety check — selalu jalan, tidak bisa di-override apapun
  safetyCheck();

  // 2b. Offline safety — fallback ke setpoint default jika server tidak terjangkau > 15 menit
  offlineSafetyCheck();

  // 3. Hitung PID dan kontrol heater (tiap 500ms)
  if (!dryingPaused && now - lastPidUpdate >= INTERVAL_PID) {
    lastPidUpdate = now;
    computePID();
    applyHeaterControl();
  }

  // 4. Kontrol fan dan exhaust (threshold)
  controlFanExhaust();

  // 5. Kirim sensor ke server (tiap 30 detik)
  if (now - lastSensorSend >= INTERVAL_SENSOR) {
    lastSensorSend = now;
    sendSensorData();
  }

  // 6. Polling command AI dari server (tiap 30 detik)
  if (now - lastPolling >= INTERVAL_POLLING) {
    lastPolling = now;
    pollCommand();
  }

  // 7. Refresh LCD (tiap 3 detik)
  if (now - lastLcdRefresh >= INTERVAL_LCD) {
    lastLcdRefresh = now;
    updateLCD();
    lcdPage = (lcdPage + 1) % 3;
  }

  delay(500);
}

// ============================================================
// SAFETY CHECK — independen, selalu aktif
// Matikan heater paksa jika suhu kritis
// ============================================================
void safetyCheck() {
  if (temperature >= TEMP_CRITICAL_OFF) {
    setRelay(PIN_RELAY_HEATER, false);
    heaterState = false;
    pidIntegral = 0;  // reset integral agar tidak windup saat emergency
    Serial.printf("[SAFETY] Suhu KRITIS %.1f°C >= %.1f°C — heater OFF paksa\n",
      temperature, TEMP_CRITICAL_OFF);
  }
}

// ============================================================
// OFFLINE SAFETY CHECK
// Jika lastServerContact belum pernah diisi (0) atau sudah
// melebihi OFFLINE_SAFE_TIMEOUT, masuk safe state:
//   - heater OFF (setpoint turun ke minimum)
//   - dryingPaused = false → PID tetap jalan di setpoint default
//   - aiActive = false → setpoint kembali ke TEMP_SETPOINT_DEF
// Ketika server kembali, flag di-reset otomatis di applyAiCommand().
// ============================================================
void offlineSafetyCheck() {
  // Lewati pengecekan jika belum pernah terhubung sama sekali
  if (lastServerContact == 0) return;

  unsigned long offlineDuration = millis() - lastServerContact;

  if (offlineDuration >= OFFLINE_SAFE_TIMEOUT) {
    if (!offlineSafeTriggered) {
      offlineSafeTriggered = true;
      aiActive      = false;
      dryingPaused  = false;     // biarkan PID tetap jalan — jangan matikan total
      fanOverride   = false;     // kembalikan fan ke threshold otomatis
      pidSetpoint   = TEMP_SETPOINT_DEF;  // setpoint aman default
      pidIntegral   = 0;

      Serial.printf("[OFFLINE-SAFETY] Tidak ada kontak server %.1f menit — "
                    "setpoint kembali ke default %.1f°C, AI dinonaktifkan\n",
                    offlineDuration / 60000.0, TEMP_SETPOINT_DEF);

      lcdPrint("SERVER OFFLINE", "PID default mode");
    }
  } else {
    // Koneksi pulih — reset flag agar AI bisa aktif lagi
    if (offlineSafeTriggered) {
      offlineSafeTriggered = false;
      Serial.println("[OFFLINE-SAFETY] Koneksi pulih — AI dapat aktif kembali");
    }
  }
}

// ============================================================
// PID COMPUTATION
// Hitung output PID berdasarkan error antara setpoint dan suhu aktual
// Output: pidOutput (positif = perlu pemanasan, negatif = terlalu panas)
// ============================================================
void computePID() {
  float error = pidSetpoint - temperature;

  // Deadband — tidak koreksi jika error sangat kecil
  if (abs(error) < PID_DEADBAND) {
    pidOutput = 0;
    return;
  }

  // Proportional
  float P = PID_KP * error;

  // Integral dengan anti-windup
  pidIntegral += error * (INTERVAL_PID / 1000.0);  // dt = 0.5 detik
  pidIntegral = constrain(pidIntegral, PID_INTEGRAL_MIN, PID_INTEGRAL_MAX);
  float I = PID_KI * pidIntegral;

  // Derivative
  float derivative = (error - pidPrevError) / (INTERVAL_PID / 1000.0);
  float D = PID_KD * derivative;

  pidPrevError = error;
  pidOutput = constrain(P + I + D, PID_OUTPUT_MIN, PID_OUTPUT_MAX);

  Serial.printf("[PID] Setpoint=%.1f Temp=%.1f Error=%.2f P=%.2f I=%.2f D=%.2f Output=%.2f\n",
    pidSetpoint, temperature, error, P, I, D, pidOutput);
}

// ============================================================
// APPLY HEATER CONTROL
// Konversi output PID ke on/off relay heater
// Relay heater ON jika pidOutput > 0 (perlu pemanasan)
// Implementasi on/off berdasarkan threshold output PID
// ============================================================
void applyHeaterControl() {
  if (temperature >= TEMP_CRITICAL_OFF) return;  // safety check sudah handle

  // PID output > 0: suhu di bawah setpoint → nyalakan heater
  // PID output <= 0: suhu di atas setpoint → matikan heater
  bool shouldHeat = (pidOutput > 10.0);  // threshold 10 untuk hysteresis ringan

  if (shouldHeat != heaterState) {
    setRelay(PIN_RELAY_HEATER, shouldHeat);
    heaterState = shouldHeat;
    Serial.printf("[PID] Heater %s (output=%.1f, setpoint=%.1f, temp=%.1f)\n",
      shouldHeat ? "ON" : "OFF", pidOutput, pidSetpoint, temperature);
  }
}

// ============================================================
// KONTROL FAN, EXHAUST, DAN MIXER
// Fan: threshold suhu (atau override AI jika aiActive)
// Exhaust: selalu threshold RH, tidak bisa diubah AI
// Mixer: nyala saat drying aktif, mati saat pause/stop
// ============================================================
void controlFanExhaust() {
  // === FAN ===
  if (fanOverride) {
    if (fanAiState != fanState) {
      setRelay(PIN_RELAY_FAN, fanAiState);
      fanState = fanAiState;
    }
  } else {
    if (!fanState && temperature >= TEMP_FAN_ON) {
      setRelay(PIN_RELAY_FAN, true);
      fanState = true;
      Serial.printf("[THRESHOLD] Fan ON — suhu %.1f°C\n", temperature);
    } else if (fanState && temperature < TEMP_FAN_OFF) {
      setRelay(PIN_RELAY_FAN, false);
      fanState = false;
      Serial.printf("[THRESHOLD] Fan OFF — suhu %.1f°C\n", temperature);
    }
  }

  // === EXHAUST — selalu threshold RH ===
  if (!exhaustState && humidity > RH_EXHAUST_ON) {
    setRelay(PIN_RELAY_EXHAUST, true);
    exhaustState = true;
    Serial.printf("[THRESHOLD] Exhaust ON — RH %.1f%%\n", humidity);
  } else if (exhaustState && humidity < RH_EXHAUST_OFF) {
    setRelay(PIN_RELAY_EXHAUST, false);
    exhaustState = false;
    Serial.printf("[THRESHOLD] Exhaust OFF — RH %.1f%%\n", humidity);
  }

  // === MIXER — ikut status drying ===
  // Nyala saat pengeringan aktif (bukan pause, bukan stop)
  // Tujuan: putar gabah agar kering merata, cegah gosong di bagian bawah
  bool shouldMix = !dryingPaused && (heaterState || fanState);
  if (shouldMix != mixerState) {
    setRelay(PIN_RELAY_MIXER, shouldMix);
    mixerState = shouldMix;
    Serial.printf("[MIXER] Motor mixer %s\n", shouldMix ? "ON" : "OFF");
  }
}

// ============================================================
// BACA SENSOR DHT22
// ============================================================
void readSensor() {
  float t = dht.readTemperature();
  float h = dht.readHumidity();

  if (!isnan(t) && !isnan(h)) {
    temperature = t;
    humidity    = h;
  } else {
    Serial.println("[DHT] Read failed — using last value");
  }
}

// ============================================================
// KIRIM DATA SENSOR KE LARAVEL
// ============================================================
void sendSensorData() {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(SERVER_URL) + "/api/iot/sensor";
  if (!beginRequest(http, url)) {
    Serial.println("[HTTP] begin() gagal: " + url);
    return;
  }
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<512> doc;
  doc["device_id"]          = DEVICE_ID;
  doc["temperature_inside"] = round(temperature * 10.0) / 10.0;
  doc["humidity_inside"]    = round(humidity * 10.0) / 10.0;
  doc["is_valid"]           = true;
  doc["pid_setpoint"]       = pidSetpoint;
  doc["pid_output"]         = round(pidOutput * 10.0) / 10.0;
  doc["ai_active"]          = aiActive;
  doc["mixer_on"]           = mixerState;  // status motor mixer untuk monitoring

  if (BATCH_ID > 0) doc["batch_id"] = BATCH_ID;

  String payload;
  serializeJson(doc, payload);

  Serial.println("[HTTP] POST /api/iot/sensor: " + payload);

  int httpCode = http.POST(payload);
  if (httpCode == 200 || httpCode == 201) {
    Serial.println("[HTTP] Sensor sent OK");
    lastServerContact = millis();  // update kontak terakhir
  } else {
    Serial.println("[HTTP] Sensor FAILED: " + String(httpCode));
  }

  http.end();
}

// ============================================================
// POLLING PERINTAH AI DARI LARAVEL
// ESP32 menerima SETPOINT bukan relay command langsung
// ============================================================
void pollCommand() {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) {
    // Offline: PID tetap jalan di setpoint terakhir
    Serial.println("[WiFi] Offline — PID jalan di setpoint terakhir: " + String(pidSetpoint));
    return;
  }

  HTTPClient http;
  String url = String(SERVER_URL) + "/api/iot/pending-command?device_id=" + String(DEVICE_ID);
  if (!beginRequest(http, url)) {
    Serial.println("[HTTP] begin() gagal: " + url);
    return;
  }

  int httpCode = http.GET();

  if (httpCode == 200) {
    String response = http.getString();
    Serial.println("[HTTP] Command response: " + response);
    lastServerContact = millis();  // server merespons — update kontak terakhir

    StaticJsonDocument<1024> doc;
    DeserializationError err = deserializeJson(doc, response);

    if (!err && doc["status"] == true) {
      JsonObject command = doc["command"];

      if (!command.isNull()) {
        applyAiCommand(command);
      } else {
        // Tidak ada command baru — PID tetap jalan di setpoint terakhir
        Serial.println("[AI] No pending command — PID tetap di setpoint " + String(pidSetpoint));
      }
    }
  } else {
    Serial.println("[HTTP] Poll FAILED: " + String(httpCode) + " — PID pakai setpoint terakhir");
  }

  http.end();
}

// ============================================================
// TERAPKAN PERINTAH AI
// AI kirim setpoint baru — PID yang urus relay heater
// ============================================================
void applyAiCommand(JsonObject command) {
  int    decisionId   = command["decision_id"] | 0;
  String decisionType = command["decision_type"] | "other";
  float  targetTemp   = command["actions"]["target_temp"] | TEMP_SETPOINT_DEF;
  bool   cmdFan       = command["actions"]["fan"] | false;
  String cmdMode      = command["actions"]["mode"] | "auto";

  Serial.printf("[AI] Decision: %s | target_temp=%.1f | mode=%s\n",
    decisionType.c_str(), targetTemp, cmdMode.c_str());

  aiDecisionType = decisionType;

  // Koneksi server aktif — reset offline safe flag
  offlineSafeTriggered = false;

  // === KASUS KHUSUS ===

  if (decisionType == "pause_drying") {
    // Pause semua — hujan, dll
    dryingPaused = true;
    aiActive = true;
    setRelay(PIN_RELAY_HEATER, false);
    setRelay(PIN_RELAY_FAN,    false);
    setRelay(PIN_RELAY_MIXER,  false);  // mixer berhenti saat pause
    heaterState = false;
    fanState    = false;
    mixerState  = false;
    fanOverride = false;
    pidIntegral = 0;
    Serial.println("[AI] PAUSE — semua aktuator OFF termasuk mixer");

  } else if (decisionType == "resume_drying") {
    // Resume — AI juga bisa kasih setpoint baru
    dryingPaused = false;
    aiActive     = true;
    float newSetpoint = constrain(targetTemp, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);
    if (newSetpoint != pidSetpoint) {
      pidSetpoint = newSetpoint;
      pidIntegral = 0;  // reset integral saat setpoint berubah signifikan
      Serial.printf("[AI] RESUME — setpoint baru: %.1f°C\n", pidSetpoint);
    }

  } else if (decisionType == "stop_heater") {
    // Matikan heater, fan tetap jalan
    dryingPaused = false;
    aiActive     = true;
    setRelay(PIN_RELAY_HEATER, false);
    heaterState  = false;
    // Set setpoint sangat rendah agar PID tidak nyalakan heater lagi
    pidSetpoint  = TEMP_SETPOINT_MIN;
    pidIntegral  = 0;
    fanOverride  = true;
    fanAiState   = cmdFan;
    Serial.println("[AI] STOP_HEATER — setpoint diturunkan ke min, fan mengikuti AI");

  } else {
    // Normal: AI update setpoint, PID yang kontrol heater
    dryingPaused = false;
    aiActive     = true;

    // Clamp setpoint ke range yang aman
    float newSetpoint = constrain(targetTemp, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);

    if (abs(newSetpoint - pidSetpoint) > 1.0) {
      // Reset integral hanya jika setpoint berubah > 1°C untuk hindari windup
      pidIntegral = 0;
      Serial.printf("[AI] Setpoint berubah %.1f → %.1f°C — integral reset\n",
        pidSetpoint, newSetpoint);
    }

    pidSetpoint = newSetpoint;
    fanOverride = (cmdMode != "auto");  // AI kontrol fan jika mode bukan "auto"
    fanAiState  = cmdFan;

    Serial.printf("[AI] Setpoint aktif: %.1f°C (dari AI: %.1f°C)\n", pidSetpoint, targetTemp);
  }

  // Kirim ACK ke Laravel
  if (decisionId > 0) {
    sendCommandAck(decisionId, true,
      "PID setpoint updated to " + String(pidSetpoint) + "C");
  }
}

// ============================================================
// KIRIM ACK KE LARAVEL
// ============================================================
void sendCommandAck(int decisionId, bool success, String message) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(SERVER_URL) + "/api/iot/command-ack";
  if (!beginRequest(http, url)) {
    Serial.println("[HTTP] begin() gagal: " + url);
    return;
  }
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> doc;
  doc["decision_id"] = decisionId;
  doc["device_id"]   = DEVICE_ID;
  doc["status"]      = success ? "success" : "failed";
  doc["message"]     = message;

  String payload;
  serializeJson(doc, payload);

  int httpCode = http.POST(payload);
  Serial.println("[HTTP] ACK sent: " + String(httpCode));

  http.end();
}

// ============================================================
// WIFI
// ============================================================
void connectWiFi() {
  lcdPrint("Connecting WiFi", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int attempt = 0;

  while (WiFi.status() != WL_CONNECTED && attempt < 20) {
    delay(500);
    Serial.print(".");
    attempt++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    wifiStatus = WiFi.localIP().toString();
    Serial.println("\n[WiFi] Connected: " + wifiStatus);
    lcdPrint("WiFi Connected", wifiStatus);
  } else {
    wifiStatus = "OFFLINE";
    Serial.println("\n[WiFi] FAILED — PID jalan di setpoint default " + String(TEMP_SETPOINT_DEF) + "C");
    lcdPrint("WiFi FAILED", "PID default mode");
  }
  delay(1500);
}

void ensureWiFi() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(3000);
  }
}

// ============================================================
// KONTROL RELAY (aktif LOW)
// ============================================================
void setRelay(int pin, bool state) {
  digitalWrite(pin, state ? LOW : HIGH);
}

// ============================================================
// UPDATE LCD — rotate 3 halaman
// ============================================================
void updateLCD() {
  switch (lcdPage) {
    case 0: {
      // Halaman 1: Suhu aktual vs setpoint
      char line1[17], line2[17];
      snprintf(line1, 17, "T:%.1f SP:%.1f", temperature, pidSetpoint);
      snprintf(line2, 17, "RH:%.1f%% %s", humidity, dryingPaused ? "PAUSE" : (aiActive ? "AI" : "DEF"));
      lcdPrint(String(line1), String(line2));
      break;
    }

    case 1: {
      // Halaman 2: Status relay + mixer + PID output
      String line1 = "H:" + String(heaterState ? "ON " : "OFF");
      line1 += " F:" + String(fanState ? "ON " : "OFF");
      String line2 = "M:" + String(mixerState ? "ON " : "OFF");
      line2 += " PID:" + String((int)pidOutput);
      lcdPrint(line1, line2);
      break;
    }

    case 2: {
      // Halaman 3: WiFi + AI decision / offline warning
      if (offlineSafeTriggered) {
        unsigned long offMin = (millis() - lastServerContact) / 60000UL;
        char line2[17];
        snprintf(line2, 17, "OFFLINE %lum DEF", offMin);
        lcdPrint("!SERVER OFFLINE!", String(line2));
      } else {
        String line1 = "WiFi:" + (WiFi.status() == WL_CONNECTED ? wifiStatus.substring(wifiStatus.lastIndexOf('.') + 1) : "X");
        String line2 = aiDecisionType.substring(0, 16);
        lcdPrint(line1, line2);
      }
      break;
    }
  }
}

void lcdPrint(String line1, String line2) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1.substring(0, 16));
  lcd.setCursor(0, 1);
  lcd.print(line2.substring(0, 16));
}

-- ============================================================
-- SolarDryerAI — PostgreSQL Schema
-- Generated from Laravel migrations
-- ============================================================

-- Enable extensions (opsional tapi berguna)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- ENUM TYPES
-- ============================================================

CREATE TYPE device_status AS ENUM ('online', 'offline', 'maintenance');

CREATE TYPE batch_status AS ENUM ('waiting', 'drying', 'paused', 'completed', 'failed');

CREATE TYPE weather_source AS ENUM ('sensor', 'api', 'manual');

CREATE TYPE decision_type AS ENUM (
    'open_roof', 'close_roof',
    'start_fan', 'stop_fan',
    'start_heater', 'stop_heater',
    'pause_drying', 'resume_drying',
    'alert_operator', 'adjust_temperature',
    'adjust_airflow', 'other'
);

CREATE TYPE execution_status AS ENUM ('pending', 'executed', 'failed', 'skipped', 'overridden');

CREATE TYPE ack_status AS ENUM ('waiting', 'acked', 'timeout', 'failed');

CREATE TYPE actuator_type AS ENUM ('roof', 'fan', 'heater', 'ventilation', 'pump', 'conveyor', 'other');

CREATE TYPE actuator_command AS ENUM ('on', 'off', 'open', 'close', 'adjust');

CREATE TYPE triggered_by AS ENUM ('ai', 'manual', 'schedule', 'safety');

CREATE TYPE actuator_status AS ENUM ('success', 'failed', 'timeout');

CREATE TYPE notification_type AS ENUM ('info', 'warning', 'alert', 'success', 'error');

CREATE TYPE notification_category AS ENUM (
    'moisture_alert', 'temperature_alert', 'weather_alert',
    'device_offline', 'batch_complete', 'batch_failed',
    'ai_decision', 'system', 'other'
);

CREATE TYPE knowledge_category AS ENUM (
    'drying_rules', 'rice_varieties', 'weather_patterns',
    'equipment_specs', 'troubleshooting', 'best_practices', 'other'
);

CREATE TYPE conversation_role AS ENUM ('user', 'assistant', 'system');

CREATE TYPE log_level AS ENUM (
    'debug', 'info', 'notice', 'warning',
    'error', 'critical', 'alert', 'emergency'
);

-- ============================================================
-- TABLES
-- ============================================================

-- users
CREATE TABLE users (
    id                  BIGSERIAL PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at   TIMESTAMP NULL,
    password            VARCHAR(255) NOT NULL,
    remember_token      VARCHAR(100) NULL,
    role                VARCHAR(255) NOT NULL DEFAULT 'viewer',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);

-- password_reset_tokens
CREATE TABLE password_reset_tokens (
    email       VARCHAR(255) PRIMARY KEY,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NULL
);

-- sessions
CREATE TABLE sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX sessions_user_id_idx ON sessions (user_id);
CREATE INDEX sessions_last_activity_idx ON sessions (last_activity);

-- cache
CREATE TABLE cache (
    key        VARCHAR(255) PRIMARY KEY,
    value      TEXT NOT NULL,
    expiration BIGINT NOT NULL
);
CREATE INDEX cache_expiration_idx ON cache (expiration);

CREATE TABLE cache_locks (
    key        VARCHAR(255) PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration BIGINT NOT NULL
);
CREATE INDEX cache_locks_expiration_idx ON cache_locks (expiration);

-- jobs
CREATE TABLE jobs (
    id           BIGSERIAL PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      TEXT NOT NULL,
    attempts     SMALLINT NOT NULL,
    reserved_at  INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at   INTEGER NOT NULL
);
CREATE INDEX jobs_queue_idx ON jobs (queue);

CREATE TABLE job_batches (
    id              VARCHAR(255) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    total_jobs      INTEGER NOT NULL,
    pending_jobs    INTEGER NOT NULL,
    failed_jobs     INTEGER NOT NULL,
    failed_job_ids  TEXT NOT NULL,
    options         TEXT NULL,
    cancelled_at    INTEGER NULL,
    created_at      INTEGER NOT NULL,
    finished_at     INTEGER NULL
);

CREATE TABLE failed_jobs (
    id         BIGSERIAL PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL UNIQUE,
    connection VARCHAR(255) NOT NULL,
    queue      VARCHAR(255) NOT NULL,
    payload    TEXT NOT NULL,
    exception  TEXT NOT NULL,
    failed_at  TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX failed_jobs_connection_queue_idx ON failed_jobs (connection, queue, failed_at);

-- personal_access_tokens (Sanctum)
CREATE TABLE personal_access_tokens (
    id              BIGSERIAL PRIMARY KEY,
    tokenable_type  VARCHAR(255) NOT NULL,
    tokenable_id    BIGINT NOT NULL,
    name            TEXT NOT NULL,
    token           VARCHAR(64) NOT NULL UNIQUE,
    abilities       TEXT NULL,
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
CREATE INDEX pat_tokenable_idx ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE INDEX pat_expires_at_idx ON personal_access_tokens (expires_at);

-- devices
CREATE TABLE devices (
    id               BIGSERIAL PRIMARY KEY,
    device_name      VARCHAR(255) NOT NULL,
    serial_number    VARCHAR(255) NOT NULL UNIQUE,
    firmware_version VARCHAR(255) NULL,
    ip_address       VARCHAR(255) NULL,
    location         VARCHAR(255) NULL,
    status           device_status NOT NULL DEFAULT 'offline',
    last_seen        TIMESTAMP NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);

-- drying_batches
CREATE TABLE drying_batches (
    id               BIGSERIAL PRIMARY KEY,
    device_id        BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,
    batch_code       VARCHAR(255) NOT NULL UNIQUE,
    rice_type        VARCHAR(255) NOT NULL,
    rice_variety     VARCHAR(255) NULL,
    initial_weight   DECIMAL(8,2) NOT NULL,
    current_weight   DECIMAL(8,2) NULL,
    initial_moisture DECIMAL(5,2) NOT NULL,
    current_moisture DECIMAL(5,2) NULL,
    target_moisture  DECIMAL(5,2) NOT NULL,
    drying_method    VARCHAR(255) NOT NULL DEFAULT 'Hybrid',
    operator_name    VARCHAR(255) NULL,
    petani_name      VARCHAR(255) NULL,       -- nama petani pemilik gabah
    petani_phone     VARCHAR(255) NULL,       -- no. HP untuk notifikasi WA/SMS
    start_time       TIMESTAMP NULL,
    end_time         TIMESTAMP NULL,
    status           batch_status NOT NULL DEFAULT 'waiting',
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);
CREATE INDEX drying_batches_device_id_idx  ON drying_batches (device_id);
CREATE INDEX drying_batches_batch_code_idx ON drying_batches (batch_code);
CREATE INDEX drying_batches_status_idx     ON drying_batches (status);

-- sensor_readings
CREATE TABLE sensor_readings (
    id                   BIGSERIAL PRIMARY KEY,
    device_id            BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,
    batch_id             BIGINT NULL REFERENCES drying_batches(id) ON DELETE SET NULL,
    temperature_inside   DECIMAL(5,2) NULL,   -- °C dalam ruang pengering
    temperature_outside  DECIMAL(5,2) NULL,   -- °C luar
    humidity_inside      DECIMAL(5,2) NULL,   -- % RH dalam
    humidity_outside     DECIMAL(5,2) NULL,   -- % RH luar
    solar_irradiance     DECIMAL(7,2) NULL,   -- W/m²
    lux                  DECIMAL(10,2) NULL,  -- lux
    grain_moisture       DECIMAL(5,2) NULL,   -- %
    grain_weight         DECIMAL(8,2) NULL,   -- kg
    wind_speed           DECIMAL(5,2) NULL,   -- m/s
    wind_direction       SMALLINT NULL,       -- derajat 0-359
    pid_setpoint         DECIMAL(5,2) NULL,   -- setpoint PID heater aktif (°C)
    pid_output           DECIMAL(6,2) NULL,   -- output PID controller
    ai_active            BOOLEAN NOT NULL DEFAULT FALSE, -- true = setpoint dari AI
    is_valid             BOOLEAN NOT NULL DEFAULT TRUE,
    error_message        VARCHAR(255) NULL,
    recorded_at          TIMESTAMP NOT NULL,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL
);
CREATE INDEX sensor_readings_device_id_idx  ON sensor_readings (device_id);
CREATE INDEX sensor_readings_batch_id_idx   ON sensor_readings (batch_id);
CREATE INDEX sensor_readings_recorded_at_idx ON sensor_readings (recorded_at);

-- weather_data
CREATE TABLE weather_data (
    id                BIGSERIAL PRIMARY KEY,
    device_id         BIGINT NULL REFERENCES devices(id) ON DELETE SET NULL,
    source            weather_source NOT NULL DEFAULT 'api',
    location          VARCHAR(255) NULL,
    latitude          DECIMAL(10,7) NULL,
    longitude         DECIMAL(10,7) NULL,
    temperature       DECIMAL(5,2) NULL,      -- °C
    humidity          DECIMAL(5,2) NULL,      -- % RH
    solar_irradiance  DECIMAL(7,2) NULL,      -- W/m²
    wind_speed        DECIMAL(5,2) NULL,      -- m/s
    wind_direction    SMALLINT NULL,          -- derajat
    rainfall          DECIMAL(6,2) NULL,      -- mm
    cloud_cover       DECIMAL(5,2) NULL,      -- %
    uv_index          DECIMAL(4,2) NULL,
    is_forecast       BOOLEAN NOT NULL DEFAULT FALSE,
    forecast_for      TIMESTAMP NULL,
    weather_condition VARCHAR(255) NULL,
    weather_icon      VARCHAR(255) NULL,
    recorded_at       TIMESTAMP NOT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL
);
CREATE INDEX weather_data_device_id_idx   ON weather_data (device_id);
CREATE INDEX weather_data_recorded_at_idx ON weather_data (recorded_at);
CREATE INDEX weather_data_source_idx      ON weather_data (source);

-- ai_decisions
CREATE TABLE ai_decisions (
    id               BIGSERIAL PRIMARY KEY,
    device_id        BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,
    batch_id         BIGINT NULL REFERENCES drying_batches(id) ON DELETE SET NULL,
    decision_type    decision_type NOT NULL,
    reasoning        TEXT NOT NULL,
    input_data       JSONB NULL,
    output_action    JSONB NULL,
    confidence_score DECIMAL(4,3) NULL,       -- 0.000 - 1.000
    ai_model         VARCHAR(255) NULL,
    execution_status execution_status NOT NULL DEFAULT 'pending',
    override_reason  VARCHAR(255) NULL,
    overridden_by    BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    decided_at       TIMESTAMP NOT NULL,
    executed_at      TIMESTAMP NULL,
    command_sent_at  TIMESTAMP NULL,
    acknowledged_at  TIMESTAMP NULL,
    esp32_command    JSONB NULL,
    ack_status       ack_status NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);
CREATE INDEX ai_decisions_device_id_idx        ON ai_decisions (device_id);
CREATE INDEX ai_decisions_batch_id_idx         ON ai_decisions (batch_id);
CREATE INDEX ai_decisions_decision_type_idx    ON ai_decisions (decision_type);
CREATE INDEX ai_decisions_execution_status_idx ON ai_decisions (execution_status);
CREATE INDEX ai_decisions_decided_at_idx       ON ai_decisions (decided_at);

-- actuator_logs
CREATE TABLE actuator_logs (
    id                BIGSERIAL PRIMARY KEY,
    device_id         BIGINT NOT NULL REFERENCES devices(id) ON DELETE CASCADE,
    batch_id          BIGINT NULL REFERENCES drying_batches(id) ON DELETE SET NULL,
    ai_decision_id    BIGINT NULL REFERENCES ai_decisions(id) ON DELETE SET NULL,
    actuator_type     actuator_type NOT NULL,
    actuator_name     VARCHAR(255) NULL,
    command           actuator_command NOT NULL,
    set_value         DECIMAL(7,2) NULL,
    actual_value      DECIMAL(7,2) NULL,
    unit              VARCHAR(255) NULL,
    triggered_by      triggered_by NOT NULL DEFAULT 'ai',
    triggered_by_user BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status            actuator_status NOT NULL DEFAULT 'success',
    error_message     VARCHAR(255) NULL,
    response_time_ms  INTEGER NULL,
    executed_at       TIMESTAMP NOT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL
);
CREATE INDEX actuator_logs_device_id_idx    ON actuator_logs (device_id);
CREATE INDEX actuator_logs_batch_id_idx     ON actuator_logs (batch_id);
CREATE INDEX actuator_logs_actuator_type_idx ON actuator_logs (actuator_type);
CREATE INDEX actuator_logs_executed_at_idx  ON actuator_logs (executed_at);

-- notifications
CREATE TABLE notifications (
    id           BIGSERIAL PRIMARY KEY,
    user_id      BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    device_id    BIGINT NULL REFERENCES devices(id) ON DELETE SET NULL,
    batch_id     BIGINT NULL REFERENCES drying_batches(id) ON DELETE SET NULL,
    type         notification_type NOT NULL DEFAULT 'info',
    category     notification_category NOT NULL DEFAULT 'system',
    title        VARCHAR(255) NOT NULL,
    message      TEXT NOT NULL,
    data         JSONB NULL,
    via_app      BOOLEAN NOT NULL DEFAULT TRUE,
    via_email    BOOLEAN NOT NULL DEFAULT FALSE,
    via_sms      BOOLEAN NOT NULL DEFAULT FALSE,
    via_whatsapp BOOLEAN NOT NULL DEFAULT FALSE,
    read_at      TIMESTAMP NULL,
    sent_at      TIMESTAMP NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL
);
CREATE INDEX notifications_user_id_idx  ON notifications (user_id);
CREATE INDEX notifications_device_id_idx ON notifications (device_id);
CREATE INDEX notifications_type_idx    ON notifications (type);
CREATE INDEX notifications_read_at_idx ON notifications (read_at);

-- knowledge_bases
CREATE TABLE knowledge_bases (
    id              BIGSERIAL PRIMARY KEY,
    category        knowledge_category NOT NULL DEFAULT 'drying_rules',
    title           VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL UNIQUE,
    content         TEXT NOT NULL,
    tags            JSONB NULL,
    metadata        JSONB NULL,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    use_for_ai      BOOLEAN NOT NULL DEFAULT TRUE,
    priority_weight DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    version         INTEGER NOT NULL DEFAULT 1,
    created_by      BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    updated_by      BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
CREATE INDEX knowledge_bases_category_idx   ON knowledge_bases (category);
CREATE INDEX knowledge_bases_is_active_idx  ON knowledge_bases (is_active);
CREATE INDEX knowledge_bases_use_for_ai_idx ON knowledge_bases (use_for_ai);

-- ai_conversations
CREATE TABLE ai_conversations (
    id           BIGSERIAL PRIMARY KEY,
    user_id      BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    device_id    BIGINT NULL REFERENCES devices(id) ON DELETE SET NULL,
    batch_id     BIGINT NULL REFERENCES drying_batches(id) ON DELETE SET NULL,
    session_id   VARCHAR(255) NOT NULL,
    role         conversation_role NOT NULL,
    message      TEXT NOT NULL,
    context_data JSONB NULL,
    ai_model     VARCHAR(255) NULL,
    tokens_used  INTEGER NULL,
    is_helpful   BOOLEAN NULL,
    feedback_note TEXT NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL
);

-- system_logs
CREATE TABLE system_logs (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    device_id     BIGINT NULL REFERENCES devices(id) ON DELETE SET NULL,
    level         log_level NOT NULL DEFAULT 'info',
    channel       VARCHAR(255) NOT NULL DEFAULT 'app',
    event         VARCHAR(255) NOT NULL,
    message       TEXT NOT NULL,
    context       JSONB NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    VARCHAR(255) NULL,
    url           VARCHAR(255) NULL,
    method        VARCHAR(10) NULL,
    loggable_type VARCHAR(255) NULL,   -- polymorphic
    loggable_id   BIGINT NULL,         -- polymorphic
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL
);
CREATE INDEX system_logs_level_idx      ON system_logs (level);
CREATE INDEX system_logs_channel_idx    ON system_logs (channel);
CREATE INDEX system_logs_user_id_idx    ON system_logs (user_id);
CREATE INDEX system_logs_device_id_idx  ON system_logs (device_id);
CREATE INDEX system_logs_created_at_idx ON system_logs (created_at);

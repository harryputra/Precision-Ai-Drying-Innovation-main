#!/bin/sh
# ======================================================================
# Entrypoint produksi SolarDryerAI.
# Container "web" (CONTAINER_ROLE=web) menjalankan: migrasi + seed
# ESENSIAL idempoten (referensi + admin dari .env) sebelum Apache start.
# Seed data contoh/demo TIDAK PERNAH dipanggil di sini — hanya lewat
# `./run.sh` mode demo (DemoSeeder).
# Container lain (queue/scheduler/reverb) langsung exec command-nya dan
# menunggu web sehat via depends_on healthcheck di docker-compose.
# ======================================================================
set -e
cd /var/www/html

ROLE="${CONTAINER_ROLE:-web}"

if [ "$ROLE" = "web" ]; then
    DB_FILE="${DB_DATABASE:-/data/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    [ -f "$DB_FILE" ] || touch "$DB_FILE"

    if [ -z "${APP_KEY:-}" ]; then
        echo "[entrypoint] FATAL: APP_KEY kosong. Jalankan lewat ./run.sh agar di-generate otomatis." >&2
        exit 1
    fi

    case "${ADMIN_PASSWORD:-}" in
        ""|GANTI*|ganti*|password|changeme|admin123)
            echo "[entrypoint] ⚠ PERINGATAN: ADMIN_PASSWORD masih placeholder/lemah — GANTI di .env sebelum dipakai nyata!" >&2 ;;
    esac
    if [ -z "${IOT_DEVICE_KEY:-}" ]; then
        echo "[entrypoint] ⚠ PERINGATAN: IOT_DEVICE_KEY kosong — endpoint /api/iot/* TIDAK terproteksi." >&2
    fi

    echo "[entrypoint] Migrasi database..."
    php artisan migrate --force

    echo "[entrypoint] Seed esensial (idempoten, tanpa data contoh)..."
    php artisan db:seed --class=EssentialSeeder --force

    php artisan storage:link >/dev/null 2>&1 || true

    echo "[entrypoint] Cache config/view/event..."
    php artisan config:cache
    php artisan view:cache || true
    php artisan event:cache || true

    # Pastikan semua service (jalan sebagai www-data) bisa tulis SQLite + storage
    chown -R www-data:www-data storage bootstrap/cache "$(dirname "$DB_FILE")"
fi

exec "$@"

#!/usr/bin/env bash
# ======================================================================
# SolarDryerAI (Padi PRECISION) — one-click runner
# Mengikuti runner baku E:/AntiGravityProject/_template-runner/
#
#   ./run.sh            # = demo (lokal): data contoh + Quick-Login aktif
#   ./run.sh deploy     # = produksi (server trin): BERSIH, admin dari .env
#   ./run.sh help
#
# Demo & produksi = STACK TERPISAH (solardryer-demo vs solardryer-prod,
# volume & port beda: 8098 vs 8097) — bisa jalan bersamaan tanpa saling
# menyentuh data. Semua jalan di Docker (server trin tidak punya Node/PHP).
# ======================================================================
set -euo pipefail
cd "$(dirname "$0")"

# ========================= KONFIG PROJECT =============================
APP_NAME="solardryer"             # dasar COMPOSE_PROJECT_NAME (-demo / -prod)
APP_SVC="app"                     # nama service app di docker-compose.yml
HEALTH_PATH="/api/health"         # health-check (lewat WEB_PORT)

MIGRATE_CMD="php artisan migrate --force"
SEED_ESSENTIAL_CMD="php artisan db:seed --class=EssentialSeeder --force"
SEED_DEMO_CMD="php artisan db:seed --class=DemoSeeder --force"
# ======================================================================

# ---------- warna ----------
if [ -t 1 ]; then R='\033[0;31m';G='\033[0;32m';Y='\033[1;33m';B='\033[0;34m';C='\033[0;36m';N='\033[0m';BOLD='\033[1m'
else R='';G='';Y='';B='';C='';N='';BOLD=''; fi
log(){ echo -e "${C}▶${N} $*"; }; ok(){ echo -e "${G}✔${N} $*"; }
warn(){ echo -e "${Y}⚠${N} $*"; }; err(){ echo -e "${R}✖${N} $*" >&2; }
hr(){ echo -e "${B}────────────────────────────────────────────────────────${N}"; }

# ---------- compose & mode ----------
DC_BIN=()
detect_dc(){ if docker compose version >/dev/null 2>&1; then DC_BIN=(docker compose)
  elif command -v docker-compose >/dev/null 2>&1; then DC_BIN=(docker-compose); fi; }
MODE=""; ENV_FILE=""; ENV_EX=""
set_mode(){
  MODE="$1"
  if [ "$MODE" = "demo" ]; then
    ENV_FILE=".env.demo"; ENV_EX=".env.demo.example"
    export COMPOSE_PROJECT_NAME="${APP_NAME}-demo"
  else
    ENV_FILE=".env"; ENV_EX=".env.example"
    export COMPOSE_PROJECT_NAME="${APP_NAME}-prod"
  fi
  export ENV_FILE   # dipakai docker-compose.yml (env_file: ${ENV_FILE})
}
dc(){ "${DC_BIN[@]}" --env-file "$ENV_FILE" "$@"; }
app_exec(){ dc exec -T "$APP_SVC" sh -lc "$1"; }

need_docker(){
  command -v docker >/dev/null 2>&1 || { err "Docker belum terpasang. Install: https://docs.docker.com/engine/install/"; exit 1; }
  docker info >/dev/null 2>&1 || { err "Docker daemon mati. Aktifkan Docker dulu."; exit 1; }
  detect_dc; [ "${#DC_BIN[@]}" -gt 0 ] || { err "docker compose tidak ada."; exit 1; }
}

ensure_env(){ [ -f "$ENV_FILE" ] || { cp "$ENV_EX" "$ENV_FILE"; ok "$ENV_FILE dibuat dari $ENV_EX."; }; }
load_env(){ set -a; . "./$ENV_FILE"; set +a; WEB_PORT="${WEB_PORT:-8097}"; N8N_PORT="${N8N_PORT:-5681}"; }

# ---------- auto-generate secret kosong (APP_KEY, REVERB, API key) ----------
set_env_value(){ # key value
  if grep -qE "^${1}=" "$ENV_FILE"; then
    sed -i "s|^${1}=.*|${1}=${2}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$1" "$2" >> "$ENV_FILE"
  fi
}
env_value(){ grep -E "^${1}=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r'; }
ensure_secret(){ # key value-jika-kosong
  if [ -z "$(env_value "$1")" ]; then set_env_value "$1" "$2"; ok "$1 digenerate otomatis."; fi
}
ensure_secrets(){
  command -v openssl >/dev/null 2>&1 || { warn "openssl tidak ada — isi APP_KEY/REVERB_*/IOT_DEVICE_KEY manual di $ENV_FILE"; return; }
  ensure_secret APP_KEY           "base64:$(openssl rand -base64 32)"
  ensure_secret REVERB_APP_KEY    "$(openssl rand -hex 16)"
  ensure_secret REVERB_APP_SECRET "$(openssl rand -hex 32)"
  ensure_secret AI_WEBHOOK_KEY    "$(openssl rand -hex 32)"
  ensure_secret IOT_DEVICE_KEY    "$(openssl rand -hex 32)"
}

# ---------- cek secret placeholder (produksi) ----------
check_secrets(){
  local warned=0 key val cur
  while IFS='=' read -r key val; do
    case "$key" in
      *PASSWORD*|*SECRET*|*TOKEN*|*_KEY|*APIKEY*)
        cur="$(env_value "$key")"
        if [ -n "$val" ] && [ "$cur" = "$val" ]; then
          warn "Secret ${BOLD}${key}${N} masih sama dgn contoh — GANTI sebelum produksi."; warned=1
        fi ;;
    esac
  done < <(grep -vE '^\s*#' "$ENV_EX" 2>/dev/null | grep -E '^[A-Za-z0-9_]+=')
  if [ "$warned" = 1 ]; then
    if [ -t 0 ]; then read -r -p "Lanjut deploy dgn secret default? [y/N] " a; case "${a:-N}" in y|Y);; *) err "Dibatalkan."; exit 1;; esac
    else warn "Non-interaktif: lanjut, tapi GANTI secret!"; fi
  else ok "Secret sudah diganti dari contoh."; fi
}

# ---------- cek port bentrok sebelum start ----------
port_in_use(){ (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null && { exec 3>&- 3<&- || true; return 0; } || return 1; }
find_free_port(){
  local port=$1
  while port_in_use "$port"; do
    port=$((port+1))
  done
  echo "$port"
}
check_port(){
  load_env
  if port_in_use "$WEB_PORT"; then
    if [ -n "$(dc ps -q "$APP_SVC" 2>/dev/null)" ]; then
      log "Port ${WEB_PORT} dipakai stack ini sendiri — container akan di-update in-place."
    else
      warn "Port ${WEB_PORT} sudah dipakai proses lain (bukan stack ${COMPOSE_PROJECT_NAME})."
      local new_port
      new_port=$(find_free_port $((WEB_PORT+1)))
      log "Mencari port kosong... Menemukan port ${new_port}."
      set_env_value "WEB_PORT" "$new_port"
      ok "WEB_PORT di ${ENV_FILE} otomatis diupdate ke ${new_port}."
      export WEB_PORT="$new_port"
    fi
  fi
}

wait_ready(){
  load_env
  local url="http://127.0.0.1:${WEB_PORT}${HEALTH_PATH}" t=90
  log "Menunggu app siap (${url})..."
  while [ $t -gt 0 ]; do curl -fsS "$url" >/dev/null 2>&1 && { ok "App sehat."; return 0; }; sleep 2; t=$((t-1)); done
  warn "Belum merespon setelah 3 menit. Cek log: ./run.sh $([ "$MODE" = demo ] && echo logs demo || echo prod-logs)"
}

quick_login_url(){
  local tok
  tok="$(app_exec "sqlite3 /data/database.sqlite \"select token from quick_login_configs where id=1 and enabled=1;\"" 2>/dev/null | tr -d '\r\n' || true)"
  [ -n "$tok" ] && echo "http://localhost:${WEB_PORT}/q/${tok}" || echo "-"
}

summary(){
  load_env
  hr; echo -e "${BOLD}${G}  SolarDryerAI — mode ${MODE^^}${N}  (project: ${COMPOSE_PROJECT_NAME})"; hr
  echo -e "  Web       : ${C}http://localhost:${WEB_PORT}${N}"
  echo -e "  Health    : ${C}http://localhost:${WEB_PORT}${HEALTH_PATH}${N}"
  echo -e "  n8n (AI)  : ${C}http://localhost:${N8N_PORT}${N}  → import n8n-workflow.json (lihat README)"
  if [ "$MODE" = "demo" ]; then
    echo
    echo -e "  ${BOLD}MODE DEMO (lokal)${N} — data contoh realistis ter-seed."
    echo -e "  Akun contoh (password semua: ${BOLD}password${N}):"
    echo -e "    admin@solardryerai.test / operator@solardryerai.test / viewer@solardryerai.test"
    echo -e "  Quick-Login : ${C}$(quick_login_url)${N}"
    echo -e "               (tombol per-role juga tampil di halaman login)"
    echo -e "  Reset data  : ${Y}./run.sh demo-reset${N}   Stop: ${Y}./run.sh demo-down${N}"
    echo
    echo -e "  ${Y}Ini mode DEV/DEMO lokal — BUKAN untuk server. Di server pakai: ${BOLD}./run.sh deploy${N}"
  else
    echo
    echo -e "  ${BOLD}MODE PRODUKSI${N} — bersih tanpa data contoh; persisten (restart: unless-stopped,"
    echo -e "  tahan reboot server). Login: ${BOLD}ADMIN_EMAIL/ADMIN_PASSWORD${N} dari .env ($(env_value ADMIN_EMAIL))."
    echo -e "  Quick-Login default NONAKTIF (404)."
    echo
    echo -e "  ESP32: set di firmware esp32_solardryerai.ino →"
    echo -e "    SERVER_URL = ${C}$(env_value APP_URL)${N}"
    echo -e "    DEVICE_KEY = ${C}$(env_value IOT_DEVICE_KEY)${N}"
    echo
    echo -e "  Publikasi: Cloudflare dashboard → Tunnels → proxmox-server → Public Hostname"
    echo -e "    ${C}solardryer.trin-polman.id → http://localhost:${WEB_PORT}${N}"
    echo -e "  Ukur TTFB (app sehat bila ≤ ~150ms):"
    echo -e "    ${C}curl -s -o /dev/null -w \"TLS:%{time_appconnect} TTFB:%{time_starttransfer} Total:%{time_total}\\n\" https://solardryer.trin-polman.id/api/health${N}"
    echo
    echo -e "  Kelola: ${Y}./run.sh prod-logs${N} (Ctrl+C keluar log, app tetap jalan) | ${Y}prod-restart${N} | ${Y}prod-down${N}"
  fi
  hr
}

# ---------- aksi inti ----------
start_stack(){ ensure_env; ensure_secrets; check_port; log "Build & start stack (${MODE})..."; dc up -d --build; }

do_demo(){
  need_docker; set_mode demo; start_stack; wait_ready
  log "Migrasi + seed esensial + seed DEMO..."
  app_exec "$MIGRATE_CMD"        || warn "migrate gagal — cek ./run.sh logs demo"
  app_exec "$SEED_ESSENTIAL_CMD" || warn "seed esensial gagal"
  app_exec "$SEED_DEMO_CMD"      || warn "seed demo gagal"
  summary
}

do_deploy(){
  need_docker; set_mode prod; ensure_env; ensure_secrets
  hr; echo -e "${BOLD}  Mode PRODUKSI (bersih, tanpa data contoh)${N}"; hr
  check_secrets
  check_port
  log "Build & start stack (prod, detached + auto-restart)..."
  dc up -d --build
  wait_ready
  log "Migrasi + seed ESENSIAL saja (admin dari .env). TIDAK ada seed demo."
  app_exec "$MIGRATE_CMD"        || warn "migrate gagal"
  app_exec "$SEED_ESSENTIAL_CMD" || warn "seed esensial gagal"
  summary
}

do_down(){ need_docker; set_mode "$1"; ensure_env; dc down; ok "Stack ${1} dihentikan (data aman di volume)."; }
do_restart(){ need_docker; set_mode "$1"; ensure_env; dc restart; ok "Stack ${1} direstart."; }
do_logs(){ need_docker; set_mode "$1"; ensure_env; shift; dc logs -f --tail=100 "$@"; }
do_reset(){
  need_docker; set_mode "$1"; ensure_env
  warn "Menghapus SEMUA data volume stack ${BOLD}${1}${N} (${COMPOSE_PROJECT_NAME})."
  [ "$1" = "prod" ] && warn "${R}INI DATA PRODUKSI!${N}"
  if [ -t 0 ]; then read -r -p "Ketik 'HAPUS' untuk konfirmasi: " a; [ "$a" = HAPUS ] || { err "Dibatalkan."; exit 1; }; fi
  dc down -v; ok "Stack ${1} + volume dihapus."
}
do_status(){
  need_docker
  for m in demo prod; do set_mode "$m"; [ -f "$ENV_FILE" ] || continue
    echo -e "${BOLD}— ${COMPOSE_PROJECT_NAME} —${N}"; dc ps 2>/dev/null || true; echo
  done
}
do_doctor(){
  hr; echo -e "${BOLD}  Doctor${N}"; hr
  command -v docker >/dev/null 2>&1 && ok "docker: $(docker --version)" || err "docker: TIDAK ADA"
  detect_dc; [ "${#DC_BIN[@]}" -gt 0 ] && ok "compose: ${DC_BIN[*]}" || err "compose: TIDAK ADA"
  command -v openssl >/dev/null 2>&1 && ok "openssl: ada (auto-generate secret)" || warn "openssl: tidak ada"
  command -v curl >/dev/null 2>&1 && ok "curl: ada" || warn "curl: tidak ada (wait_ready butuh curl)"
  for f in .env.example .env.demo.example docker-compose.yml Dockerfile; do
    [ -f "$f" ] && ok "$f ada" || err "$f TIDAK ADA"
  done
  hr
}

usage(){ cat <<EOF
$(echo -e "${BOLD}SolarDryerAI — runner baku (demo/deploy + isolasi stack)${N}")
  ${BOLD}Demo (lokal)${N}     : (kosong)|up|demo, demo-down, demo-reset, logs demo [svc]
  ${BOLD}Produksi (server)${N}: deploy|prod, prod-down, prod-restart, prod-logs [svc]
  ${BOLD}Umum${N}             : status, doctor, help
  demo = data contoh + Quick-Login  •  deploy = BERSIH, admin dari .env
  Stack: app (Laravel+Apache), queue, scheduler, reverb (WebSocket), n8n (AI agent)
EOF
}

case "${1:-up}" in
  ""|up|demo|start) do_demo ;;
  deploy|prod)      do_deploy ;;
  demo-down)        do_down demo ;;
  demo-reset)       do_reset demo ;;
  prod-down)        do_down prod ;;
  prod-restart)     do_restart prod ;;
  prod-logs)        shift; do_logs prod "$@" ;;
  down)             do_down prod ;;
  restart)          do_restart prod ;;
  logs)             shift
                    if [ "${1:-}" = "demo" ]; then shift; do_logs demo "$@"
                    else do_logs prod "$@"; fi ;;
  reset)            do_reset prod ;;
  status|ps)        do_status ;;
  doctor)           do_doctor ;;
  help|-h|--help)   usage ;;
  *) err "Perintah tak dikenal: $1"; echo; usage; exit 1 ;;
esac

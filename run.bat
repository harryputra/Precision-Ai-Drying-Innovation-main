@echo off
rem ======================================================================
rem SolarDryerAI (Padi PRECISION) - one-click runner (Windows)
rem Mirror dari run.sh. Subcommand:
rem   run.bat            = demo (lokal): data contoh + Quick-Login
rem   run.bat deploy     = produksi: BERSIH, admin dari .env
rem   run.bat help       = daftar perintah
rem Demo & produksi = stack terpisah (solardryer-demo vs solardryer-prod).
rem ======================================================================
setlocal EnableExtensions
cd /d "%~dp0"

set "APP_NAME=solardryer"
set "CMD=%~1"
if "%CMD%"=="" set "CMD=demo"

rem ---------- prasyarat ----------
where docker >nul 2>&1
if errorlevel 1 (
    echo [X] Docker belum terpasang. Install Docker Desktop: https://docs.docker.com/desktop/
    goto :akhir
)
docker info >nul 2>&1
if errorlevel 1 (
    echo [X] Docker daemon mati. Jalankan Docker Desktop dulu, lalu ulangi.
    goto :akhir
)

if /i "%CMD%"=="demo"         goto :demo
if /i "%CMD%"=="up"           goto :demo
if /i "%CMD%"=="start"        goto :demo
if /i "%CMD%"=="deploy"       goto :deploy
if /i "%CMD%"=="prod"         goto :deploy
if /i "%CMD%"=="demo-down"    goto :demo_down
if /i "%CMD%"=="demo-reset"   goto :demo_reset
if /i "%CMD%"=="prod-down"    goto :prod_down
if /i "%CMD%"=="prod-restart" goto :prod_restart
if /i "%CMD%"=="prod-logs"    goto :prod_logs
if /i "%CMD%"=="down"         goto :prod_down
if /i "%CMD%"=="restart"      goto :prod_restart
if /i "%CMD%"=="logs"         goto :logs
if /i "%CMD%"=="reset"        goto :prod_reset
if /i "%CMD%"=="status"       goto :status
if /i "%CMD%"=="ps"           goto :status
if /i "%CMD%"=="doctor"       goto :doctor
if /i "%CMD%"=="help"         goto :help
echo [X] Perintah tak dikenal: %CMD%
goto :help

rem ======================= MODE HELPERS =================================
:set_demo
set "ENV_FILE=.env.demo"
set "ENV_EX=.env.demo.example"
set "COMPOSE_PROJECT_NAME=%APP_NAME%-demo"
goto :eof

:set_prod
set "ENV_FILE=.env"
set "ENV_EX=.env.example"
set "COMPOSE_PROJECT_NAME=%APP_NAME%-prod"
goto :eof

:ensure_env
if not exist "%ENV_FILE%" (
    copy /y "%ENV_EX%" "%ENV_FILE%" >nul
    echo [OK] %ENV_FILE% dibuat dari %ENV_EX%.
)
rem Auto-generate secret yang masih kosong (APP_KEY, REVERB_*, API key)
powershell -NoProfile -Command "$f='%ENV_FILE%'; $rng=New-Object Security.Cryptography.RNGCryptoServiceProvider; function Hex($n){ $b=New-Object byte[] $n; $rng.GetBytes($b); -join ($b | ForEach-Object { $_.ToString('x2') }) }; function B64($n){ $b=New-Object byte[] $n; $rng.GetBytes($b); [Convert]::ToBase64String($b) }; $lines=Get-Content $f; $pairs=[ordered]@{'APP_KEY'=('base64:'+(B64 32));'REVERB_APP_KEY'=(Hex 16);'REVERB_APP_SECRET'=(Hex 32);'AI_WEBHOOK_KEY'=(Hex 32);'IOT_DEVICE_KEY'=(Hex 32)}; $changed=$false; foreach($k in $pairs.Keys){ for($i=0;$i -lt $lines.Count;$i++){ if($lines[$i] -match ('^'+$k+'=\s*$')){ $lines[$i]=$k+'='+$pairs[$k]; $changed=$true; Write-Host ('[OK] '+$k+' digenerate otomatis.') } } }; if($changed){ Set-Content -Path $f -Value $lines -Encoding ASCII }"
for /f "usebackq tokens=1,* delims==" %%a in ("%ENV_FILE%") do (
    if "%%a"=="WEB_PORT" set "WEB_PORT=%%b"
    if "%%a"=="N8N_PORT" set "N8N_PORT=%%b"
    if "%%a"=="ADMIN_EMAIL" set "ADMIN_EMAIL=%%b"
    if "%%a"=="APP_URL" set "APP_URL=%%b"
    if "%%a"=="IOT_DEVICE_KEY" set "IOT_KEY=%%b"
)
if "%WEB_PORT%"=="" set "WEB_PORT=8097"
goto :eof

:wait_ready
echo [..] Menunggu app siap (http://localhost:%WEB_PORT%/api/health)...
set /a _tries=0
:wait_loop
curl -fsS "http://127.0.0.1:%WEB_PORT%/api/health" >nul 2>&1
if not errorlevel 1 (
    echo [OK] App sehat.
    goto :eof
)
set /a _tries+=1
if %_tries% geq 90 (
    echo [!] Belum merespon setelah ~3 menit. Cek: run.bat logs
    goto :eof
)
timeout /t 2 /nobreak >nul
goto :wait_loop

rem ======================= DEMO =========================================
:demo
call :set_demo
call :ensure_env
echo [..] Build ^& start stack DEMO (project: %COMPOSE_PROJECT_NAME%)...
docker compose --env-file "%ENV_FILE%" up -d --build
if errorlevel 1 (
    echo [X] Gagal start. Kemungkinan port %WEB_PORT% bentrok - ganti WEB_PORT di %ENV_FILE%.
    goto :akhir
)
call :wait_ready
echo [..] Migrasi + seed esensial + seed DEMO...
docker compose --env-file "%ENV_FILE%" exec -T app php artisan migrate --force
docker compose --env-file "%ENV_FILE%" exec -T app php artisan db:seed --class=EssentialSeeder --force
docker compose --env-file "%ENV_FILE%" exec -T app php artisan db:seed --class=DemoSeeder --force
echo.
echo ==============================================================
echo   SolarDryerAI - MODE DEMO (lokal)
echo   Web       : http://localhost:%WEB_PORT%
echo   Health    : http://localhost:%WEB_PORT%/api/health
echo   n8n (AI)  : http://localhost:%N8N_PORT%  (import n8n-workflow.json)
echo.
echo   Akun contoh (password semua: password):
echo     admin@solardryerai.test  /  operator@solardryerai.test  /  viewer@solardryerai.test
echo   Quick-Login aktif - tombol per-role ada di halaman login.
echo.
echo   Reset data: run.bat demo-reset      Stop: run.bat demo-down
echo   Ini mode DEV/DEMO lokal - BUKAN untuk server (server: ./run.sh deploy)
echo ==============================================================
goto :akhir

rem ======================= DEPLOY (PRODUKSI) ============================
:deploy
call :set_prod
call :ensure_env
echo ==============================================================
echo   Mode PRODUKSI (bersih, tanpa data contoh)
echo ==============================================================
echo %ADMIN_EMAIL% | findstr /i "trin-polman.id" >nul && echo [!] ADMIN_EMAIL masih default (%ADMIN_EMAIL%) - pastikan sudah benar.
echo [..] Build ^& start stack PROD (detached + auto-restart)...
docker compose --env-file "%ENV_FILE%" up -d --build
if errorlevel 1 (
    echo [X] Gagal start. Kemungkinan port %WEB_PORT% bentrok - cek run.bat status.
    goto :akhir
)
call :wait_ready
echo [..] Migrasi + seed ESENSIAL saja (admin dari .env). TIDAK ada seed demo.
docker compose --env-file "%ENV_FILE%" exec -T app php artisan migrate --force
docker compose --env-file "%ENV_FILE%" exec -T app php artisan db:seed --class=EssentialSeeder --force
echo.
echo ==============================================================
echo   SolarDryerAI - MODE PRODUKSI (project: %COMPOSE_PROJECT_NAME%)
echo   Web    : http://localhost:%WEB_PORT%   (publik via Cloudflare Tunnel)
echo   Login  : %ADMIN_EMAIL% (password dari .env - GANTI bila masih placeholder)
echo   ESP32  : SERVER_URL=%APP_URL%   DEVICE_KEY=%IOT_KEY%
echo.
echo   Kelola : run.bat prod-logs ^| prod-restart ^| prod-down
echo ==============================================================
goto :akhir

rem ======================= PENGELOLAAN ==================================
:demo_down
call :set_demo
call :ensure_env
docker compose --env-file "%ENV_FILE%" down
echo [OK] Stack demo dihentikan (data aman di volume).
goto :akhir

:demo_reset
call :set_demo
call :ensure_env
set /p _confirm="Hapus SEMUA data volume stack DEMO? Ketik HAPUS untuk lanjut: "
if not "%_confirm%"=="HAPUS" ( echo Dibatalkan. & goto :akhir )
docker compose --env-file "%ENV_FILE%" down -v
echo [OK] Stack demo + volume dihapus.
goto :akhir

:prod_down
call :set_prod
call :ensure_env
docker compose --env-file "%ENV_FILE%" down
echo [OK] Stack produksi dihentikan (data aman di volume).
goto :akhir

:prod_restart
call :set_prod
call :ensure_env
docker compose --env-file "%ENV_FILE%" restart
echo [OK] Stack produksi direstart.
goto :akhir

:prod_logs
call :set_prod
call :ensure_env
echo (Ctrl+C untuk keluar dari log - app TETAP jalan)
docker compose --env-file "%ENV_FILE%" logs -f --tail=100 %2 %3
goto :akhir

:prod_reset
call :set_prod
call :ensure_env
echo [!!] INI DATA PRODUKSI!
set /p _confirm="Hapus SEMUA data volume stack PRODUKSI? Ketik HAPUS untuk lanjut: "
if not "%_confirm%"=="HAPUS" ( echo Dibatalkan. & goto :akhir )
docker compose --env-file "%ENV_FILE%" down -v
echo [OK] Stack produksi + volume dihapus.
goto :akhir

:logs
if /i "%~2"=="demo" (
    call :set_demo
    call :ensure_env
    docker compose --env-file "%ENV_FILE%" logs -f --tail=100 %3 %4
) else (
    call :set_prod
    call :ensure_env
    docker compose --env-file "%ENV_FILE%" logs -f --tail=100 %2 %3
)
goto :akhir

:status
call :set_demo
if exist "%ENV_FILE%" (
    echo --- %COMPOSE_PROJECT_NAME% ---
    docker compose --env-file "%ENV_FILE%" ps
    echo.
)
call :set_prod
if exist "%ENV_FILE%" (
    echo --- %COMPOSE_PROJECT_NAME% ---
    docker compose --env-file "%ENV_FILE%" ps
)
goto :akhir

:doctor
echo ============ Doctor ============
docker --version
docker compose version
if exist ".env.example" ( echo [OK] .env.example ada ) else ( echo [X] .env.example TIDAK ADA )
if exist ".env.demo.example" ( echo [OK] .env.demo.example ada ) else ( echo [X] .env.demo.example TIDAK ADA )
if exist "docker-compose.yml" ( echo [OK] docker-compose.yml ada ) else ( echo [X] docker-compose.yml TIDAK ADA )
if exist "Dockerfile" ( echo [OK] Dockerfile ada ) else ( echo [X] Dockerfile TIDAK ADA )
goto :akhir

:help
echo.
echo SolarDryerAI - runner baku (demo/deploy + isolasi stack)
echo   Demo (lokal)     : (kosong)^|up^|demo, demo-down, demo-reset, logs demo [svc]
echo   Produksi (server): deploy^|prod, prod-down, prod-restart, prod-logs [svc]
echo   Umum             : status, doctor, help
echo   demo = data contoh + Quick-Login  -  deploy = BERSIH, admin dari .env
echo   Stack: app (Laravel+Apache), queue, scheduler, reverb (WebSocket), n8n (AI)
goto :akhir

:akhir
echo.
pause
endlocal

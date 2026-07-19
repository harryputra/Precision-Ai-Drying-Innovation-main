@extends('layouts.app')

@section('title', __('app.simulator_title'))

@section('content')
<style>
    /* === Simulator-specific animations === */
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes spin-reverse { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }
    @keyframes pulse-glow {
        0%, 100% { filter: drop-shadow(0 0 4px #f97316); opacity: 0.7; }
        50% { filter: drop-shadow(0 0 12px #f97316); opacity: 1; }
    }
    @keyframes grain-move {
        0% { transform: translateY(0) rotate(0deg); }
        25% { transform: translateY(-3px) rotate(5deg); }
        50% { transform: translateY(0) rotate(0deg); }
        75% { transform: translateY(3px) rotate(-5deg); }
        100% { transform: translateY(0) rotate(0deg); }
    }
    @keyframes float-cloud {
        0% { transform: translateX(-10px); }
        50% { transform: translateX(10px); }
        100% { transform: translateX(-10px); }
    }
    @keyframes steam-rise {
        0% { opacity: 0; transform: translateY(0) scale(0.8); }
        50% { opacity: 0.6; }
        100% { opacity: 0; transform: translateY(-40px) scale(1.3); }
    }
    @keyframes beam-pulse {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.7; }
    }
    @keyframes led-blink {
        0%, 100% { opacity: 1; box-shadow: 0 0 6px currentColor; }
        50% { opacity: 0.5; box-shadow: 0 0 2px currentColor; }
    }
    @keyframes data-packet {
        0% { opacity: 1; transform: translateX(0) translateY(0) scale(1); }
        100% { opacity: 0; transform: translateX(120px) translateY(-80px) scale(0.4); }
    }

    .fan-blade { transform-origin: center; }
    .fan-blade.on { animation: spin 0.6s linear infinite; }
    .fan-blade.exhaust { animation: spin-reverse 0.4s linear infinite; }

    .heater-coil { transition: all 0.4s ease; }
    .heater-coil.on { animation: pulse-glow 1.2s ease-in-out infinite; }

    .grain-particle { transform-origin: center; }
    .grain-particle.on { animation: grain-move 0.8s ease-in-out infinite; }
    .grain-particle.on:nth-child(odd) { animation-duration: 1.1s; }
    .grain-particle.on:nth-child(even) { animation-duration: 0.9s; animation-delay: 0.2s; }

    .cloud { animation: float-cloud 8s ease-in-out infinite; }
    .cloud:nth-child(2) { animation-duration: 12s; animation-delay: -3s; }
    .cloud:nth-child(3) { animation-duration: 10s; animation-delay: -6s; }

    .steam-particle { opacity: 0; }
    .steam-particle.on { animation: steam-rise 2s ease-out infinite; }
    .steam-particle.on:nth-child(2) { animation-delay: 0.4s; }
    .steam-particle.on:nth-child(3) { animation-delay: 0.8s; }

    .sun-beam { animation: beam-pulse 3s ease-in-out infinite; }
    .sun-beam:nth-child(2) { animation-delay: 0.5s; }
    .sun-beam:nth-child(3) { animation-delay: 1s; }

    .led { width: 10px; height: 10px; border-radius: 50%; display: inline-block; transition: all 0.3s; }
    .led.on { animation: led-blink 1.5s ease-in-out infinite; }
    .led.off { background: #94a3b8 !important; box-shadow: none !important; opacity: 0.4; }

    .data-packet { opacity: 0; }
    .data-packet.sending { animation: data-packet 0.8s ease-out forwards; }

    .simulator-metric-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 1rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .simulator-metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }

    .log-terminal {
        background: #0f172a;
        border-radius: 14px;
        color: #e2e8f0;
        font-family: 'SFMono-Regular', Consolas, Monaco, monospace;
        font-size: 0.75rem;
        padding: 1rem;
        max-height: 240px;
        overflow-y: auto;
    }
    .log-entry { padding: 3px 0; border-bottom: 1px solid #1e293b; }
    .log-entry.ok { color: #4ade80; }
    .log-entry.error { color: #f87171; }
    .log-entry.info { color: #60a5fa; }
    .log-entry.warn { color: #fbbf24; }

    .dark .simulator-metric-card { background: #1e293b; border-color: #334155; }
    .dark .log-terminal { background: #020617; }
</style>

{{-- ═══════════════════════════════════════════════
     PAGE HEADER BANNER
═══════════════════════════════════════════════ --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);flex-shrink:0;box-shadow:0 4px 16px rgba(0,0,0,0.2);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <h2 style="font-size:1.6rem;font-weight:900;color:#fff;margin:0;letter-spacing:0.04em;line-height:1;">@lang('app.simulator_title')</h2>
                <p style="font-size:0.68rem;color:rgba(255,255,255,0.65);margin:0.25rem 0 0;font-weight:600;letter-spacing:0.12em;">@lang('app.simulator_subtitle')</p>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;">
                    <span id="status-badge" class="badge badge-gray" style="font-size:0.65rem;display:flex;align-items:center;gap:0.25rem;">
                        <span id="status-dot" class="led off" style="width:6px;height:6px;"></span>
                        <span id="status-text">IDLE</span>
                    </span>
                    <span id="live-sync" class="badge badge-blue" style="font-size:0.65rem;display:none;align-items:center;gap:0.25rem;">
                        <span class="pulse-green" style="width:5px;height:5px;"></span>
                        Live Sync
                    </span>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:0.5rem 1rem;backdrop-filter:blur(6px);">
                <span class="pulse-green" style="width:8px;height:8px;flex-shrink:0;"></span>
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:#fff;line-height:1.2;">@lang('app.online')</div>
                    <div style="font-size:0.6rem;color:rgba(255,255,255,0.55);">HTTP / Reverb</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════
     MAIN GRID: VISUALIZATION + CONTROLS
═══════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1.25rem;">
<style>@media(min-width:1024px){.simulator-grid{grid-template-columns:1.4fr 1fr!important;}}</style>
<div class="simulator-grid" style="display:grid;grid-template-columns:1fr;gap:1rem;">

    {{-- LEFT: Animated Grain Dryer Visualization --}}
    <div class="glass-card" style="padding:1.25rem;min-height:480px;position:relative;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 class="card-header-title" style="font-size:0.88rem;">Visualisasi Mesin</h3>
            <span class="badge badge-green" style="font-size:0.65rem;">Real-time</span>
        </div>

        <div style="position:relative;width:100%;height:380px;background:linear-gradient(180deg,#dbeafe 0%,#f0f9ff 50%,#f0fdf4 100%);border-radius:16px;overflow:hidden;border:1px solid #bfdbfe;">
            {{-- Sun --}}
            <svg style="position:absolute;top:16px;right:24px;width:70px;height:70px;z-index:2;" viewBox="0 0 70 70">
                <circle cx="35" cy="35" r="14" fill="#fbbf24" style="filter:drop-shadow(0 0 8px #fbbf24aa);"/>
                <g stroke="#fbbf24" stroke-width="3" stroke-linecap="round">
                    <line x1="35" y1="7" x2="35" y2="13" class="sun-beam"/>
                    <line x1="35" y1="57" x2="35" y2="63" class="sun-beam"/>
                    <line x1="7" y1="35" x2="13" y2="35" class="sun-beam"/>
                    <line x1="57" y1="35" x2="63" y2="35" class="sun-beam"/>
                    <line x1="15" y1="15" x2="19" y2="19" class="sun-beam"/>
                    <line x1="51" y1="51" x2="55" y2="55" class="sun-beam"/>
                    <line x1="15" y1="55" x2="19" y2="51" class="sun-beam"/>
                    <line x1="51" y1="19" x2="55" y2="15" class="sun-beam"/>
                </g>
            </svg>

            {{-- Clouds --}}
            <svg style="position:absolute;top:28px;left:20px;width:80px;height:40px;z-index:1;opacity:0.8;" viewBox="0 0 80 40" class="cloud">
                <path d="M15 28 Q8 28 8 20 Q8 12 18 12 Q20 4 32 4 Q44 4 46 12 Q56 12 56 20 Q56 28 47 28 Z" fill="#fff"/>
            </svg>
            <svg style="position:absolute;top:48px;right:100px;width:60px;height:30px;z-index:1;opacity:0.7;" viewBox="0 0 60 30" class="cloud">
                <path d="M10 22 Q5 22 5 15 Q5 10 14 10 Q16 4 24 4 Q32 4 34 10 Q42 10 42 15 Q42 22 35 22 Z" fill="#fff"/>
            </svg>

            {{-- Ground --}}
            <div style="position:absolute;bottom:0;left:0;right:0;height:70px;background:linear-gradient(180deg,#86efac,#16a34a);z-index:1;"></div>
            <div style="position:absolute;bottom:55px;left:0;right:0;height:4px;background:#15803d;z-index:1;opacity:0.3;"></div>

            {{-- Drying Machine SVG --}}
            <svg style="position:absolute;bottom:40px;left:50%;transform:translateX(-50%);width:320px;height:280px;z-index:3;" viewBox="0 0 320 280">
                {{-- Solar panel on roof --}}
                <g transform="translate(60, -10) rotate(-15)">
                    <rect x="0" y="0" width="80" height="50" rx="4" fill="#1e3a8a" stroke="#1e40af" stroke-width="2"/>
                    <line x1="0" y1="16" x2="80" y2="16" stroke="#3b82f6" stroke-width="1" opacity="0.5"/>
                    <line x1="0" y1="32" x2="80" y2="32" stroke="#3b82f6" stroke-width="1" opacity="0.5"/>
                    <line x1="20" y1="0" x2="20" y2="50" stroke="#3b82f6" stroke-width="1" opacity="0.5"/>
                    <line x1="40" y1="0" x2="40" y2="50" stroke="#3b82f6" stroke-width="1" opacity="0.5"/>
                    <line x1="60" y1="0" x2="60" y2="50" stroke="#3b82f6" stroke-width="1" opacity="0.5"/>
                </g>

                {{-- Main drying chamber --}}
                <defs>
                    <linearGradient id="chamberGrad" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#64748b"/>
                        <stop offset="50%" stop-color="#94a3b8"/>
                        <stop offset="100%" stop-color="#64748b"/>
                    </linearGradient>
                    <linearGradient id="chamberInner" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#1e293b"/>
                        <stop offset="100%" stop-color="#334155"/>
                    </linearGradient>
                    <linearGradient id="grainGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#d97706"/>
                        <stop offset="100%" stop-color="#f59e0b"/>
                    </linearGradient>
                </defs>

                {{-- Chamber body --}}
                <rect x="80" y="60" width="160" height="180" rx="20" fill="url(#chamberGrad)" stroke="#475569" stroke-width="3"/>
                <rect x="90" y="70" width="140" height="160" rx="15" fill="url(#chamberInner)"/>

                {{-- Grain particles --}}
                <g id="grain-particles">
                    <circle cx="110" cy="190" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="135" cy="200" r="6" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="160" cy="185" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="185" cy="205" r="6" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="210" cy="190" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="125" cy="170" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="150" cy="165" r="6" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="175" cy="175" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="200" cy="165" r="6" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="145" cy="145" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="170" cy="150" r="6" fill="url(#grainGrad)" class="grain-particle"/>
                    <circle cx="115" cy="155" r="5" fill="url(#grainGrad)" class="grain-particle"/>
                </g>

                {{-- Heater coils (bottom of chamber) --}}
                <g id="heater-coils" transform="translate(95, 215)">
                    <path d="M0 10 Q15 0 30 10 Q45 20 60 10 Q75 0 90 10 Q105 20 120 10" fill="none" stroke="#475569" stroke-width="4" class="heater-coil"/>
                    <path d="M0 20 Q15 10 30 20 Q45 30 60 20 Q75 10 90 20 Q105 30 120 20" fill="none" stroke="#475569" stroke-width="4" class="heater-coil"/>
                </g>

                {{-- Temperature gauge on chamber --}}
                <g transform="translate(120, 85)">
                    <rect x="0" y="0" width="40" height="20" rx="4" fill="#0f172a" stroke="#475569" stroke-width="1"/>
                    <text id="svg-temp-inside" x="20" y="14" text-anchor="middle" fill="#fbbf24" font-size="11" font-weight="700" font-family="Inter,sans-serif">32.0°</text>
                </g>

                {{-- Fan on left side --}}
                <g transform="translate(30, 110)">
                    <circle cx="30" cy="30" r="26" fill="#e2e8f0" stroke="#475569" stroke-width="3"/>
                    <g class="fan-blade" id="fan-blade">
                        <path d="M30 30 L30 8 L34 8 L34 30 Z" fill="#475569"/>
                        <path d="M30 30 L48 30 L48 34 L30 34 Z" fill="#475569"/>
                        <path d="M30 30 L30 52 L26 52 L26 30 Z" fill="#475569"/>
                        <path d="M30 30 L12 30 L12 26 L30 26 Z" fill="#475569"/>
                    </g>
                    <circle cx="30" cy="30" r="5" fill="#64748b"/>
                </g>

                {{-- Exhaust fan on right side --}}
                <g transform="translate(250, 110)">
                    <circle cx="10" cy="30" r="20" fill="#e2e8f0" stroke="#475569" stroke-width="2"/>
                    <g class="fan-blade exhaust" id="exhaust-blade">
                        <path d="M10 30 L10 14 L13 14 L13 30 Z" fill="#475569"/>
                        <path d="M10 30 L22 30 L22 33 L10 33 Z" fill="#475569"/>
                        <path d="M10 30 L10 46 L7 46 L7 30 Z" fill="#475569"/>
                        <path d="M10 30 L-2 30 L-2 27 L10 27 Z" fill="#475569"/>
                    </g>
                    <circle cx="10" cy="30" r="4" fill="#64748b"/>
                </g>

                {{-- Steam from exhaust --}}
                <g id="steam-group" transform="translate(275, 125)">
                    <circle cx="0" cy="0" r="5" fill="#cbd5e1" class="steam-particle"/>
                    <circle cx="8" cy="-5" r="4" fill="#cbd5e1" class="steam-particle"/>
                    <circle cx="-5" cy="-8" r="4" fill="#cbd5e1" class="steam-particle"/>
                </g>

                {{-- Mixer motor icon at bottom --}}
                <g transform="translate(150, 248)">
                    <circle cx="12" cy="12" r="12" fill="#1e293b" stroke="#475569" stroke-width="2"/>
                    <g id="mixer-icon" transform="translate(12,12)">
                        <circle cx="0" cy="0" r="4" fill="#94a3b8"/>
                        <line x1="0" y1="-8" x2="0" y2="8" stroke="#94a3b8" stroke-width="2" class="mixer-arm"/>
                        <line x1="-8" y1="0" x2="8" y2="0" stroke="#94a3b8" stroke-width="2" class="mixer-arm"/>
                    </g>
                </g>

                {{-- Data packet animation (to server) --}}
                <g id="data-packet" transform="translate(240, 60)" class="data-packet">
                    <circle cx="0" cy="0" r="6" fill="#16a34a" style="filter:drop-shadow(0 0 4px #16a34a);"/>
                    <text x="0" y="3" text-anchor="middle" fill="#fff" font-size="6" font-weight="700">IoT</text>
                </g>
            </svg>

            {{-- Actuator status pills floating --}}
            <div style="position:absolute;bottom:12px;left:12px;right:12px;display:flex;gap:0.5rem;flex-wrap:wrap;justify-content:center;z-index:4;">
                <div style="display:flex;align-items:center;gap:0.375rem;background:rgba(255,255,255,0.92);border:1px solid #e2e8f0;border-radius:20px;padding:0.375rem 0.75rem;backdrop-filter:blur(4px);">
                    <span id="led-heater" class="led off" style="color:#f97316;"></span>
                    <span style="font-size:0.72rem;font-weight:700;color:#374151;">Heater</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.375rem;background:rgba(255,255,255,0.92);border:1px solid #e2e8f0;border-radius:20px;padding:0.375rem 0.75rem;backdrop-filter:blur(4px);">
                    <span id="led-fan" class="led off" style="color:#3b82f6;"></span>
                    <span style="font-size:0.72rem;font-weight:700;color:#374151;">Fan</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.375rem;background:rgba(255,255,255,0.92);border:1px solid #e2e8f0;border-radius:20px;padding:0.375rem 0.75rem;backdrop-filter:blur(4px);">
                    <span id="led-exhaust" class="led off" style="color:#64748b;"></span>
                    <span style="font-size:0.72rem;font-weight:700;color:#374151;">Exhaust</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.375rem;background:rgba(255,255,255,0.92);border:1px solid #e2e8f0;border-radius:20px;padding:0.375rem 0.75rem;backdrop-filter:blur(4px);">
                    <span id="led-mixer" class="led off" style="color:#d97706;"></span>
                    <span style="font-size:0.72rem;font-weight:700;color:#374151;">Mixer</span>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;font-size:0.72rem;color:#64748b;">
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#d97706;margin-right:4px;"></span>Gabah</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f97316;margin-right:4px;"></span>Heater</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3b82f6;margin-right:4px;"></span>Ventilasi</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a;margin-right:4px;"></span>Data ke Server</span>
        </div>
    </div>

    {{-- RIGHT: Control Panel --}}
    <div class="glass-card" style="padding:0;overflow:hidden;">
        <div class="card-header">
            <h3 class="card-header-title" style="font-size:0.88rem;">Panel Kontrol</h3>
            <span style="font-size:0.65rem;color:#64748b;">HTTP → /api/iot/sensor</span>
        </div>
        <div style="padding:1.25rem;">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                {{-- Device --}}
                <div>
                    <label class="label-dark">@lang('app.simulator_device')</label>
                    <select id="device-select" class="input-dark">
                        @foreach($devices as $device)
                        <option value="{{ $device->id }}" {{ $device->id == 1 ? 'selected' : '' }}>
                            {{ $device->device_name }} (ID: {{ $device->id }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Setpoint & Target Moisture --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div>
                        <label class="label-dark">Setpoint Suhu (°C)</label>
                        <input type="number" id="setpoint-input" class="input-dark" value="45" min="35" max="55" step="0.5">
                    </div>
                    <div>
                        <label class="label-dark">Target Moisture (%)</label>
                        <input type="number" id="target-moisture-input" class="input-dark" value="13" min="10" max="20" step="0.5">
                    </div>
                </div>

                {{-- Interval & Speed --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div>
                        <label class="label-dark">Interval Kirim (detik)</label>
                        <input type="number" id="interval-input" class="input-dark" value="10" min="3" max="300" step="1">
                    </div>
                    <div>
                        <label class="label-dark">Sim Speed (x)</label>
                        <select id="speed-select" class="input-dark">
                            <option value="1">1x</option>
                            <option value="2">2x</option>
                            <option value="5" selected>5x</option>
                            <option value="10">10x</option>
                        </select>
                    </div>
                </div>

                {{-- Toggles --}}
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;color:#374151;">
                        <input type="checkbox" id="ai-active" checked style="accent-color:#16a34a;width:16px;height:16px;">
                        <span>AI Active</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;color:#374151;">
                        <input type="checkbox" id="poll-command" style="accent-color:#16a34a;width:16px;height:16px;">
                        <span>Poll Command AI</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;color:#374151;">
                        <input type="checkbox" id="simulate-error" style="accent-color:#16a34a;width:16px;height:16px;">
                        <span>Simulasi Error Sensor</span>
                    </label>
                </div>

                {{-- Buttons --}}
                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                    <button type="button" id="btn-start" class="btn-primary" style="flex:1;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        @lang('app.simulator_start')
                    </button>
                    <button type="button" id="btn-pause" class="btn-secondary" style="flex:1;justify-content:center;display:none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>
                        </svg>
                        @lang('app.simulator_pause')
                    </button>
                    <button type="button" id="btn-reset" class="btn-secondary" style="flex:1;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M3 12a9 9 0 1 1 9 9"/><polyline points="3,12 3,6 9,6"/>
                        </svg>
                        @lang('app.simulator_reset')
                    </button>
                </div>

                {{-- Send once button --}}
                <button type="button" id="btn-send-once" class="btn-secondary" style="justify-content:center;width:100%;margin-top:0.25rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9 22,2"/>
                    </svg>
                    Kirim Sekali
                </button>
            </div>
        </div>
    </div>
</div>
</div>

{{-- ═══════════════════════════════════════════════
     METRICS CARDS
═══════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;margin-bottom:1.25rem;">
<style>@media(min-width:768px){.metrics-grid{grid-template-columns:repeat(3,1fr)!important;}}</style>
<style>@media(min-width:1024px){.metrics-grid{grid-template-columns:repeat(6,1fr)!important;}}</style>
<div class="metrics-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;">
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Suhu Dalam</div>
        <div style="font-size:1.6rem;font-weight:900;color:#f97316;line-height:1;margin-top:0.25rem;"><span id="metric-temp-in">32.0</span><span style="font-size:0.75rem;">°C</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-temp-in" style="width:40%;height:100%;background:linear-gradient(90deg,#f97316,#fb923c);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Kelembaban Dalam</div>
        <div style="font-size:1.6rem;font-weight:900;color:#3b82f6;line-height:1;margin-top:0.25rem;"><span id="metric-hum-in">70.0</span><span style="font-size:0.75rem;">%</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-hum-in" style="width:70%;height:100%;background:linear-gradient(90deg,#3b82f6,#60a5fa);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Kadar Air Gabah</div>
        <div style="font-size:1.6rem;font-weight:900;color:#0891b2;line-height:1;margin-top:0.25rem;"><span id="metric-moisture">24.0</span><span style="font-size:0.75rem;">%</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-moisture" style="width:80%;height:100%;background:linear-gradient(90deg,#0891b2,#22d3ee);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Berat Gabah</div>
        <div style="font-size:1.6rem;font-weight:900;color:#7c3aed;line-height:1;margin-top:0.25rem;"><span id="metric-weight">100.0</span><span style="font-size:0.75rem;">kg</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-weight" style="width:100%;height:100%;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Suhu Luar</div>
        <div style="font-size:1.6rem;font-weight:900;color:#d97706;line-height:1;margin-top:0.25rem;"><span id="metric-temp-out">30.0</span><span style="font-size:0.75rem;">°C</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-temp-out" style="width:60%;height:100%;background:linear-gradient(90deg,#d97706,#fbbf24);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
    <div class="simulator-metric-card">
        <div style="font-size:0.7rem;color:#64748b;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Radiasi Matahari</div>
        <div style="font-size:1.6rem;font-weight:900;color:#ea580c;line-height:1;margin-top:0.25rem;"><span id="metric-solar">450</span><span style="font-size:0.75rem;">W/m²</span></div>
        <div style="height:4px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-top:0.5rem;"><div id="bar-solar" style="width:45%;height:100%;background:linear-gradient(90deg,#ea580c,#fb923c);border-radius:99px;transition:width 0.5s;"></div></div>
    </div>
</div>
</div>

{{-- ═══════════════════════════════════════════════
     LOG TERMINAL
═══════════════════════════════════════════════ --}}
<div class="glass-card" style="padding:0;overflow:hidden;margin-bottom:1.25rem;">
    <div class="card-header">
        <h3 class="card-header-title" style="font-size:0.88rem;">Log Transmisi</h3>
        <button type="button" id="btn-clear-log" class="btn-sm btn-secondary" style="padding:0.3rem 0.75rem;">Clear</button>
    </div>
    <div style="padding:1rem;">
        <div id="log-terminal" class="log-terminal">
            <div class="log-entry info">[INFO] Simulator siap. Tekan Start untuk mengirim data.</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ── Configuration from backend ──
    const DEVICE_KEY = @json($deviceKey);
    const IOT_URL = '/api/iot/sensor';
    const COMMAND_URL = '/api/iot/pending-command';

    // ── Constants (match ESP32 firmware) ──
    const TEMP_SETPOINT_MIN = 35.0;
    const TEMP_SETPOINT_MAX = 55.0;
    const TEMP_CRITICAL_OFF = 58.0;
    const TEMP_FAN_ON = 38.0;
    const TEMP_FAN_OFF = 35.0;
    const RH_EXHAUST_ON = 65.0;
    const RH_EXHAUST_OFF = 55.0;

    // ── DOM refs ──
    const els = {
        btnStart: document.getElementById('btn-start'),
        btnPause: document.getElementById('btn-pause'),
        btnReset: document.getElementById('btn-reset'),
        btnSendOnce: document.getElementById('btn-send-once'),
        btnClearLog: document.getElementById('btn-clear-log'),
        deviceSelect: document.getElementById('device-select'),
        setpointInput: document.getElementById('setpoint-input'),
        targetMoistureInput: document.getElementById('target-moisture-input'),
        intervalInput: document.getElementById('interval-input'),
        speedSelect: document.getElementById('speed-select'),
        aiActive: document.getElementById('ai-active'),
        pollCommand: document.getElementById('poll-command'),
        simulateError: document.getElementById('simulate-error'),
        statusBadge: document.getElementById('status-badge'),
        statusDot: document.getElementById('status-dot'),
        statusText: document.getElementById('status-text'),
        liveSync: document.getElementById('live-sync'),
        logTerminal: document.getElementById('log-terminal'),
        svgTempInside: document.getElementById('svg-temp-inside'),
        ledHeater: document.getElementById('led-heater'),
        ledFan: document.getElementById('led-fan'),
        ledExhaust: document.getElementById('led-exhaust'),
        ledMixer: document.getElementById('led-mixer'),
        fanBlade: document.getElementById('fan-blade'),
        exhaustBlade: document.getElementById('exhaust-blade'),
        heaterCoils: document.querySelectorAll('.heater-coil'),
        grainParticles: document.querySelectorAll('.grain-particle'),
        steamParticles: document.querySelectorAll('.steam-particle'),
        dataPacket: document.getElementById('data-packet'),
    };

    // ── State ──
    let state = {
        temperatureInside: 32.0,
        humidityInside: 70.0,
        grainMoisture: 24.0,
        grainWeight: 100.0,
        temperatureOutside: 30.0,
        humidityOutside: 65.0,
        solarIrradiance: 450.0,
        lux: 8000.0,
        windSpeed: 2.5,
        windDirection: 180,
        pidSetpoint: 45.0,
        pidOutput: 0.0,
        aiActive: true,
        dryingActive: true,
        heaterOn: false,
        fanOn: false,
        exhaustOn: false,
        mixerOn: false,
        elapsedMinutes: 0.0,
        lastUpdate: performance.now(),
        lastSend: 0,
        lastPoll: 0,
        running: false,
        tickMs: 500,
    };

    let loopId = null;
    let sendIntervalId = null;
    let pollIntervalId = null;

    // ── Helpers ──
    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
    const rand = (min, max) => Math.random() * (max - min) + min;
    const round1 = (v) => Math.round(v * 10) / 10;
    const round2 = (v) => Math.round(v * 100) / 100;

    function log(message, type = 'info') {
        const div = document.createElement('div');
        div.className = `log-entry ${type}`;
        const t = new Date().toLocaleTimeString('id-ID', { hour12: false });
        div.textContent = `[${t}] ${message}`;
        els.logTerminal.appendChild(div);
        els.logTerminal.scrollTop = els.logTerminal.scrollHeight;
        while (els.logTerminal.children.length > 50) {
            els.logTerminal.removeChild(els.logTerminal.firstChild);
        }
    }

    function updateStatus(status, text) {
        if (status === 'running') {
            els.statusBadge.className = 'badge badge-green';
            els.statusDot.className = 'led on';
            els.statusDot.style.color = '#10b981';
            els.statusText.textContent = 'RUNNING';
        } else if (status === 'paused') {
            els.statusBadge.className = 'badge badge-yellow';
            els.statusDot.className = 'led on';
            els.statusDot.style.color = '#f59e0b';
            els.statusText.textContent = 'PAUSED';
        } else {
            els.statusBadge.className = 'badge badge-gray';
            els.statusDot.className = 'led off';
            els.statusText.textContent = 'IDLE';
        }
        if (text) els.statusText.textContent = text;
    }

    // ── Physics ──
    function computePid() {
        const error = state.pidSetpoint - state.temperatureInside;
        const kp = 2.0, ki = 0.5;
        state.pidOutput = clamp(kp * error + ki * error * 0.5, -100, 100);
        state.heaterOn = state.dryingActive && state.pidOutput > 10 && state.temperatureInside < TEMP_CRITICAL_OFF;
    }

    function updateWeather(dt) {
        const now = new Date();
        const minuteOfDay = now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60;
        const dailyTemp = 30.0 + 4.0 * Math.sin(2 * Math.PI * (minuteOfDay - 360) / 1440);
        state.temperatureOutside = clamp(state.temperatureOutside + (dailyTemp - state.temperatureOutside) * 0.02 * dt, 22, 40);
        state.humidityOutside = clamp(state.humidityOutside + rand(-0.5, 0.5) * dt, 40, 95);
        const irradianceBase = Math.max(0, 950 * Math.sin(2 * Math.PI * minuteOfDay / 1440));
        state.solarIrradiance = clamp(irradianceBase + rand(-30, 30), 0, 1200);
        state.lux = clamp(state.solarIrradiance * 18 + rand(-500, 500), 0, 12000);
        state.windSpeed = clamp(state.windSpeed + rand(-0.3, 0.3) * dt, 0, 8);
        state.windDirection = (state.windDirection + Math.floor(rand(-5, 5))) % 360;
    }

    function updateDrying(dt) {
        if (!state.dryingActive) {
            state.heaterOn = false;
            state.fanOn = false;
            state.mixerOn = false;
            return;
        }

        computePid();

        if (!state.fanOn && state.temperatureInside >= TEMP_FAN_ON) state.fanOn = true;
        else if (state.fanOn && state.temperatureInside < TEMP_FAN_OFF) state.fanOn = false;

        if (!state.exhaustOn && state.humidityInside > RH_EXHAUST_ON) state.exhaustOn = true;
        else if (state.exhaustOn && state.humidityInside < RH_EXHAUST_OFF) state.exhaustOn = false;

        const heatGain = (state.heaterOn ? 1.5 : 0) + (state.solarIrradiance / 1200) * 0.5;
        const heatLoss = (state.fanOn ? 0.4 : 0.1) * (state.temperatureInside - state.temperatureOutside);
        state.temperatureInside = clamp(
            state.temperatureInside + (heatGain - heatLoss + rand(-0.1, 0.1)) * dt,
            state.temperatureOutside - 2,
            TEMP_CRITICAL_OFF + 2
        );

        const moistureRelease = state.grainMoisture <= parseFloat(els.targetMoistureInput.value) ? 0 : 0.15 * dt;
        const exhaustReduction = (state.exhaustOn ? 0.8 : 0.1) * dt;
        state.humidityInside = clamp(
            state.humidityInside + (moistureRelease - exhaustReduction + rand(-0.2, 0.2)) * dt,
            20, 95
        );

        if (state.grainMoisture > parseFloat(els.targetMoistureInput.value)) {
            const dryingRate = 0.015 * (state.temperatureInside / 50) * (1 - state.humidityInside / 100) * dt;
            state.grainMoisture = Math.max(parseFloat(els.targetMoistureInput.value), state.grainMoisture - dryingRate);
            state.grainWeight = Math.max(50, state.grainWeight - dryingRate * 0.15);
        }

        state.mixerOn = state.heaterOn || state.fanOn;
        state.elapsedMinutes += dt / 60;
    }

    // ── Visual updates ──
    function updateVisuals() {
        els.svgTempInside.textContent = round1(state.temperatureInside) + '°';

        updateLed(els.ledHeater, state.heaterOn, '#f97316');
        updateLed(els.ledFan, state.fanOn, '#3b82f6');
        updateLed(els.ledExhaust, state.exhaustOn, '#64748b');
        updateLed(els.ledMixer, state.mixerOn, '#d97706');

        els.heaterCoils.forEach(el => el.classList.toggle('on', state.heaterOn));
        els.fanBlade.classList.toggle('on', state.fanOn);
        els.exhaustBlade.classList.toggle('on', state.exhaustOn);
        els.grainParticles.forEach(el => el.classList.toggle('on', state.mixerOn));
        els.steamParticles.forEach(el => el.classList.toggle('on', state.exhaustOn));

        document.getElementById('metric-temp-in').textContent = round1(state.temperatureInside);
        document.getElementById('metric-hum-in').textContent = round1(state.humidityInside);
        document.getElementById('metric-moisture').textContent = round1(state.grainMoisture);
        document.getElementById('metric-weight').textContent = round2(state.grainWeight);
        document.getElementById('metric-temp-out').textContent = round1(state.temperatureOutside);
        document.getElementById('metric-solar').textContent = Math.round(state.solarIrradiance);

        document.getElementById('bar-temp-in').style.width = clamp((state.temperatureInside / 80) * 100, 0, 100) + '%';
        document.getElementById('bar-hum-in').style.width = clamp(state.humidityInside, 0, 100) + '%';
        document.getElementById('bar-moisture').style.width = clamp((state.grainMoisture / 30) * 100, 0, 100) + '%';
        document.getElementById('bar-weight').style.width = clamp((state.grainWeight / 100) * 100, 0, 100) + '%';
        document.getElementById('bar-temp-out').style.width = clamp((state.temperatureOutside / 50) * 100, 0, 100) + '%';
        document.getElementById('bar-solar').style.width = clamp((state.solarIrradiance / 1200) * 100, 0, 100) + '%';
    }

    function updateLed(el, on, color) {
        el.classList.toggle('on', on);
        el.classList.toggle('off', !on);
        el.style.background = on ? color : '#94a3b8';
        el.style.color = color;
    }

    // ── Networking ──
    function buildPayload() {
        const isValid = !els.simulateError.checked;
        return {
            device_id: parseInt(els.deviceSelect.value),
            temperature_inside: round1(state.temperatureInside),
            humidity_inside: round1(state.humidityInside),
            temperature_outside: round1(state.temperatureOutside),
            humidity_outside: round1(state.humidityOutside),
            solar_irradiance: round1(state.solarIrradiance),
            lux: round1(state.lux),
            grain_moisture: round1(state.grainMoisture),
            grain_weight: round2(state.grainWeight),
            wind_speed: round1(state.windSpeed),
            wind_direction: (Math.floor(state.windDirection) + 360) % 360,
            pid_setpoint: round1(state.pidSetpoint),
            pid_output: round1(state.pidOutput),
            ai_active: els.aiActive.checked,
            is_valid: isValid,
            error_message: isValid ? null : 'Simulated sensor error',
        };
    }

    async function sendSensorData() {
        if (!DEVICE_KEY) {
            log('ERROR: IOT_DEVICE_KEY tidak dikonfigurasi. Simulator tidak bisa mengirim.', 'error');
            return;
        }

        const payload = buildPayload();

        // Trigger packet animation
        els.dataPacket.classList.remove('sending');
        void els.dataPacket.offsetWidth;
        els.dataPacket.classList.add('sending');

        try {
            const res = await fetch(IOT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Device-Key': DEVICE_KEY,
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });

            const text = await res.text();
            let json = null;
            try { json = JSON.parse(text); } catch (e) {}

            if (res.ok) {
                log(`SENT 201 → T_in=${payload.temperature_inside}°C RH_in=${payload.humidity_inside}% Moisture=${payload.grain_moisture}%`, 'ok');
            } else {
                log(`FAILED ${res.status}: ${json?.message || text.slice(0, 100)}`, 'error');
            }
        } catch (err) {
            log(`NETWORK ERROR: ${err.message}`, 'error');
        }
    }

    async function pollCommand() {
        if (!DEVICE_KEY || !els.pollCommand.checked) return;
        try {
            const deviceId = els.deviceSelect.value;
            const res = await fetch(`${COMMAND_URL}?device_id=${deviceId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Device-Key': DEVICE_KEY,
                },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (json.status && json.command) {
                applyCommand(json.command);
            }
        } catch (err) {
            log(`POLL ERROR: ${err.message}`, 'error');
        }
    }

    function applyCommand(command) {
        const type = command.decision_type || 'other';
        const actions = command.actions || {};
        const target = actions.target_temp ?? 45;
        log(`AI COMMAND received: ${type}`, 'info');

        if (type === 'pause_drying') {
            state.dryingActive = false;
            updateStatus('paused', 'AI PAUSE');
        } else if (type === 'resume_drying') {
            state.dryingActive = true;
            state.pidSetpoint = clamp(target, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);
            els.setpointInput.value = state.pidSetpoint;
        } else if (type === 'stop_heater') {
            state.pidSetpoint = TEMP_SETPOINT_MIN;
            els.setpointInput.value = state.pidSetpoint;
        } else {
            state.dryingActive = true;
            state.pidSetpoint = clamp(target, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);
            els.setpointInput.value = state.pidSetpoint;
        }
    }

    // ── Main loop ──
    function tick() {
        const now = performance.now();
        let dt = (now - state.lastUpdate) / 1000; // seconds
        state.lastUpdate = now;

        // Apply simulation speed
        const speed = parseInt(els.speedSelect.value);
        dt *= speed;

        updateWeather(dt);
        updateDrying(dt);
        updateVisuals();
    }

    function start() {
        if (state.running) return;
        state.running = true;
        state.dryingActive = true;
        state.lastUpdate = performance.now();

        updateStatus('running');
        els.btnStart.style.display = 'none';
        els.btnPause.style.display = 'flex';

        // Physics loop 500ms
        loopId = setInterval(tick, 500);

        // Send loop based on interval input
        const intervalSec = Math.max(3, parseInt(els.intervalInput.value));
        sendIntervalId = setInterval(sendSensorData, intervalSec * 1000);

        // Poll command every 60s
        if (els.pollCommand.checked) {
            pollIntervalId = setInterval(pollCommand, 60000);
        }

        log('SIMULATOR STARTED', 'ok');
        sendSensorData(); // send immediately
    }

    function pause() {
        if (!state.running) return;
        state.running = false;
        state.dryingActive = false;
        clearInterval(loopId);
        clearInterval(sendIntervalId);
        clearInterval(pollIntervalId);
        loopId = null;
        sendIntervalId = null;
        pollIntervalId = null;
        updateStatus('paused', 'PAUSED');
        els.btnStart.style.display = 'flex';
        els.btnPause.style.display = 'none';
        log('SIMULATOR PAUSED', 'warn');
    }

    function reset() {
        pause();
        state = {
            temperatureInside: 32.0,
            humidityInside: 70.0,
            grainMoisture: 24.0,
            grainWeight: 100.0,
            temperatureOutside: 30.0,
            humidityOutside: 65.0,
            solarIrradiance: 450.0,
            lux: 8000.0,
            windSpeed: 2.5,
            windDirection: 180,
            pidSetpoint: parseFloat(els.setpointInput.value) || 45.0,
            pidOutput: 0.0,
            aiActive: els.aiActive.checked,
            dryingActive: false,
            heaterOn: false,
            fanOn: false,
            exhaustOn: false,
            mixerOn: false,
            elapsedMinutes: 0.0,
            lastUpdate: performance.now(),
            lastSend: 0,
            lastPoll: 0,
            running: false,
            tickMs: 500,
        };
        updateStatus('idle');
        updateVisuals();
        log('SIMULATOR RESET', 'info');
    }

    // ── Event listeners ──
    els.btnStart.addEventListener('click', start);
    els.btnPause.addEventListener('click', pause);
    els.btnReset.addEventListener('click', reset);
    els.btnSendOnce.addEventListener('click', () => { sendSensorData(); });
    els.btnClearLog.addEventListener('click', () => { els.logTerminal.innerHTML = ''; });

    els.setpointInput.addEventListener('change', () => {
        state.pidSetpoint = clamp(parseFloat(els.setpointInput.value), TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);
        els.setpointInput.value = state.pidSetpoint;
    });

    els.intervalInput.addEventListener('change', () => {
        if (state.running) {
            clearInterval(sendIntervalId);
            const intervalSec = Math.max(3, parseInt(els.intervalInput.value));
            sendIntervalId = setInterval(sendSensorData, intervalSec * 1000);
        }
    });

    els.pollCommand.addEventListener('change', () => {
        if (state.running) {
            clearInterval(pollIntervalId);
            if (els.pollCommand.checked) pollIntervalId = setInterval(pollCommand, 60000);
        }
    });

    // ── WebSocket sync indicator ──
    if (window.Echo) {
        window.Echo.channel('sensor-updates')
            .listen('SensorUpdated', () => {
                els.liveSync.style.display = 'inline-flex';
                setTimeout(() => { els.liveSync.style.display = 'none'; }, 2000);
            });
    }

    // ── Initial render ──
    state.pidSetpoint = clamp(parseFloat(els.setpointInput.value) || 45, TEMP_SETPOINT_MIN, TEMP_SETPOINT_MAX);
    els.setpointInput.value = state.pidSetpoint;
    updateVisuals();

    if (!DEVICE_KEY) {
        log('WARNING: IOT_DEVICE_KEY kosong. Endpoint IoT akan bypass autentikasi di dev lokal.', 'warn');
    }
})();
</script>
@endpush

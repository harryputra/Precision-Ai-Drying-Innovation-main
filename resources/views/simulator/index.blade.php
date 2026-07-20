@extends('layouts.app')

@section('title', __('app.simulator_title'))

@section('content')
<style>
    /* === Simulator-specific animations === */
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes spin-reverse { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }
    @keyframes pulse-glow {
        0%, 100% { filter: drop-shadow(0 0 3px #f97316); opacity: 0.65; }
        50% { filter: drop-shadow(0 0 10px #f97316); opacity: 1; }
    }
    @keyframes grain-move {
        0% { transform: translateY(0) rotate(0deg); }
        25% { transform: translateY(-2px) rotate(4deg); }
        50% { transform: translateY(0) rotate(0deg); }
        75% { transform: translateY(2px) rotate(-4deg); }
        100% { transform: translateY(0) rotate(0deg); }
    }
    @keyframes float-cloud {
        0% { transform: translateX(-12px); }
        50% { transform: translateX(12px); }
        100% { transform: translateX(-12px); }
    }
    @keyframes steam-rise {
        0% { opacity: 0; transform: translateY(0) scale(0.7); }
        40% { opacity: 0.55; }
        100% { opacity: 0; transform: translateY(-50px) scale(1.5); }
    }
    @keyframes beam-pulse {
        0%, 100% { opacity: 0.35; }
        50% { opacity: 0.75; }
    }
    @keyframes led-blink {
        0%, 100% { opacity: 1; box-shadow: 0 0 6px currentColor; }
        50% { opacity: 0.55; box-shadow: 0 0 2px currentColor; }
    }
    @keyframes data-packet {
        0% { opacity: 1; transform: translateX(0) translateY(0) scale(1); }
        100% { opacity: 0; transform: translateX(140px) translateY(-90px) scale(0.3); }
    }
    @keyframes mixer-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @keyframes airflow-dash {
        to { stroke-dashoffset: -20; }
    }
    @keyframes heat-shimmer {
        0% { opacity: 0; transform: translateY(0) scaleY(1); }
        50% { opacity: 0.25; transform: translateY(-6px) scaleY(1.1); }
        100% { opacity: 0; transform: translateY(-12px) scaleY(1.2); }
    }
    @keyframes machine-pulse {
        0% { transform: scale(1); filter: brightness(1); }
        50% { transform: scale(1.008); filter: brightness(1.08); }
        100% { transform: scale(1); filter: brightness(1); }
    }
    @keyframes progress-fill {
        0% { width: 0%; }
        100% { width: 100%; }
    }

    /* PENTING (anti-glitch): pada SVG, transform-origin:center default-nya
       mengacu ke pusat KANVAS (view-box) — elemen berputar mengorbit layar
       ("terbang"). transform-box:fill-box membuat origin = pusat elemen
       itu sendiri, sehingga kipas/mixer berputar di porosnya. */
    .fan-blade, .grain-particle, .mixer-icon, .machine-pulse,
    .data-packet, .steam-particle {
        transform-box: fill-box;
        transform-origin: center;
    }

    .fan-blade.on { animation: spin 0.55s linear infinite; }
    .fan-blade.exhaust.on { animation: spin-reverse 0.4s linear infinite; }

    .heater-coil { transition: all 0.35s ease; }
    .heater-coil.on { animation: pulse-glow 1.1s ease-in-out infinite; }
    .heater-glow { opacity: 0; transition: opacity 0.4s ease; }
    .heater-glow.on { opacity: 0.85; }

    .grain-particle.on { animation: grain-move 0.9s ease-in-out infinite; }
    .grain-particle.on:nth-child(odd) { animation-duration: 1.15s; }
    .grain-particle.on:nth-child(even) { animation-duration: 0.85s; animation-delay: 0.18s; }

    .cloud { animation: float-cloud 9s ease-in-out infinite; }
    .cloud:nth-child(2) { animation-duration: 13s; animation-delay: -4s; }
    .cloud:nth-child(3) { animation-duration: 11s; animation-delay: -7s; }

    .steam-particle { opacity: 0; }
    .steam-particle.on { animation: steam-rise 2.2s ease-out infinite; }
    .steam-particle.on:nth-child(2) { animation-delay: 0.5s; }
    .steam-particle.on:nth-child(3) { animation-delay: 1s; }

    .sun-beam { animation: beam-pulse 3.2s ease-in-out infinite; }
    .sun-beam:nth-child(2) { animation-delay: 0.6s; }
    .sun-beam:nth-child(3) { animation-delay: 1.2s; }

    .led { width: 10px; height: 10px; border-radius: 50%; display: inline-block; transition: all 0.3s; }
    .led.on { animation: led-blink 1.4s ease-in-out infinite; }
    .led.off { background: #94a3b8 !important; box-shadow: none !important; opacity: 0.4; }

    .data-packet { opacity: 0; }
    .data-packet.sending { animation: data-packet 0.85s ease-out forwards; }

    .mixer-icon.on { animation: mixer-spin 1s linear infinite; }

    .airflow-arrow { stroke-dasharray: 6 6; opacity: 0; transition: opacity 0.4s ease; }
    .airflow-arrow.on { animation: airflow-dash 0.9s linear infinite; opacity: 0.4; }
    .airflow-arrow.on.exhaust { opacity: 0.35; }

    .heat-shimmer { opacity: 0; }
    .heat-shimmer.on { animation: heat-shimmer 1.6s ease-out infinite; }
    .heat-shimmer.on:nth-child(2) { animation-delay: 0.5s; }
    .heat-shimmer.on:nth-child(3) { animation-delay: 1s; }

    .machine-pulse.sending { animation: machine-pulse 0.45s ease-out; }

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
    <div class="glass-card" style="padding:1.25rem;min-height:520px;position:relative;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 class="card-header-title" style="font-size:0.88rem;">Visualisasi Mesin</h3>
            <span class="badge badge-green" style="font-size:0.65rem;">Real-time</span>
        </div>

        <div style="position:relative;width:100%;height:420px;background:linear-gradient(180deg,#e0f2fe 0%,#f0f9ff 55%,#ecfdf5 100%);border-radius:16px;overflow:hidden;border:1px solid #bfdbfe;box-shadow:inset 0 0 40px rgba(59,130,246,0.08);">
            {{-- Sun with layered rays --}}
            <svg style="position:absolute;top:14px;right:28px;width:90px;height:90px;z-index:2;" viewBox="0 0 90 90">
                <defs>
                    <radialGradient id="sunGrad" cx="0.5" cy="0.5" r="0.5">
                        <stop offset="0%" stop-color="#fef08a"/>
                        <stop offset="60%" stop-color="#fbbf24"/>
                        <stop offset="100%" stop-color="#f59e0b"/>
                    </radialGradient>
                </defs>
                <circle cx="45" cy="45" r="18" fill="url(#sunGrad)" style="filter:drop-shadow(0 0 14px #fbbf24cc);"/>
                <g stroke="#fbbf24" stroke-width="3" stroke-linecap="round" opacity="0.85">
                    <line x1="45" y1="8" x2="45" y2="15" class="sun-beam"/>
                    <line x1="45" y1="75" x2="45" y2="82" class="sun-beam"/>
                    <line x1="8" y1="45" x2="15" y2="45" class="sun-beam"/>
                    <line x1="75" y1="45" x2="82" y2="45" class="sun-beam"/>
                    <line x1="18" y1="18" x2="23" y2="23" class="sun-beam"/>
                    <line x1="67" y1="67" x2="72" y2="72" class="sun-beam"/>
                    <line x1="18" y1="72" x2="23" y2="67" class="sun-beam"/>
                    <line x1="67" y1="23" x2="72" y2="18" class="sun-beam"/>
                </g>
            </svg>

            {{-- Clouds --}}
            <svg style="position:absolute;top:34px;left:28px;width:110px;height:50px;z-index:1;opacity:0.92;" viewBox="0 0 110 50" class="cloud">
                <defs>
                    <linearGradient id="cloudGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffffff"/>
                        <stop offset="100%" stop-color="#f1f5f9"/>
                    </linearGradient>
                </defs>
                <path d="M20 38 Q10 38 10 26 Q10 16 24 16 Q27 6 43 6 Q60 6 63 16 Q78 16 78 26 Q78 38 65 38 Z" fill="url(#cloudGrad)" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.06))"/>
            </svg>
            <svg style="position:absolute;top:58px;right:120px;width:80px;height:38px;z-index:1;opacity:0.85;" viewBox="0 0 80 38" class="cloud">
                <path d="M15 29 Q8 29 8 20 Q8 12 19 12 Q22 5 33 5 Q44 5 46 12 Q57 12 57 20 Q57 29 48 29 Z" fill="url(#cloudGrad)" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.06))"/>
            </svg>

            {{-- Ground with grass blades --}}
            <div style="position:absolute;bottom:0;left:0;right:0;height:80px;background:linear-gradient(180deg,#86efac 0%,#16a34a 70%,#15803d 100%);z-index:1;">
                <svg style="position:absolute;bottom:0;left:0;right:0;height:40px;width:100%;opacity:0.25;" preserveAspectRatio="none" viewBox="0 0 100 40">
                    <path d="M0 40 Q5 25 10 40 M12 40 Q16 20 20 40 M25 40 Q28 22 32 40 M38 40 Q42 18 46 40 M52 40 Q56 24 60 40 M65 40 Q68 20 72 40 M78 40 Q82 22 86 40 M90 40 Q94 26 100 40" fill="none" stroke="#14532d" stroke-width="1"/>
                </svg>
            </div>
            <div style="position:absolute;bottom:64px;left:0;right:0;height:4px;background:#15803d;z-index:1;opacity:0.25;"></div>

            {{-- Drying Machine SVG --}}
            <svg style="position:absolute;bottom:50px;left:50%;transform:translateX(-50%);width:420px;height:300px;z-index:3;" viewBox="0 0 420 300">
                <defs>
                    {{-- Metal frame gradient --}}
                    <linearGradient id="frameMetal" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#475569"/>
                        <stop offset="25%" stop-color="#94a3b8"/>
                        <stop offset="50%" stop-color="#cbd5e1"/>
                        <stop offset="75%" stop-color="#94a3b8"/>
                        <stop offset="100%" stop-color="#475569"/>
                    </linearGradient>
                    <linearGradient id="legMetal" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#334155"/>
                        <stop offset="50%" stop-color="#64748b"/>
                        <stop offset="100%" stop-color="#334155"/>
                    </linearGradient>
                    {{-- Transparent polycarbonate roof --}}
                    <linearGradient id="roofGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#60a5fa" stop-opacity="0.35"/>
                        <stop offset="50%" stop-color="#bfdbfe" stop-opacity="0.25"/>
                        <stop offset="100%" stop-color="#dbeafe" stop-opacity="0.15"/>
                    </linearGradient>
                    <linearGradient id="roofShine" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.5"/>
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                    </linearGradient>
                    {{-- Grain realistic --}}
                    <radialGradient id="grainReal" cx="0.4" cy="0.4" r="0.7">
                        <stop offset="0%" stop-color="#fde68a"/>
                        <stop offset="50%" stop-color="#d97706"/>
                        <stop offset="100%" stop-color="#92400e"/>
                    </radialGradient>
                    <linearGradient id="grainPile" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="100%" stop-color="#b45309"/>
                    </linearGradient>
                    {{-- Heater glow --}}
                    <radialGradient id="heaterGlow" cx="0.5" cy="0.5" r="0.5">
                        <stop offset="0%" stop-color="#fef3c7" stop-opacity="0.9"/>
                        <stop offset="40%" stop-color="#f97316" stop-opacity="0.6"/>
                        <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                    </radialGradient>
                    {{-- Solar panel --}}
                    <linearGradient id="solarCell" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#1e40af"/>
                        <stop offset="100%" stop-color="#172554"/>
                    </linearGradient>
                    <linearGradient id="solarFrame" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#e2e8f0"/>
                        <stop offset="100%" stop-color="#94a3b8"/>
                    </linearGradient>
                    {{-- Shadows --}}
                    <filter id="dropShadow" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#000000" flood-opacity="0.18"/>
                    </filter>
                    <filter id="innerShadow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur in="SourceAlpha" stdDeviation="3" result="blur"/>
                        <feOffset dx="0" dy="2" result="offsetBlur"/>
                        <feComposite in="offsetBlur" in2="SourceAlpha" operator="arithmetic" k2="-1" k3="1" result="shadowDiff"/>
                        <feFlood flood-color="#000000" flood-opacity="0.3"/>
                        <feComposite in2="shadowDiff" operator="in"/>
                        <feComposite in2="SourceGraphic" operator="over"/>
                    </filter>
                </defs>

                {{-- Shadow under the machine --}}
                <ellipse cx="210" cy="272" rx="165" ry="14" fill="#000000" opacity="0.18"/>

                {{-- Legs / support frame --}}
                <g id="machine-legs">
                    <rect x="65" y="220" width="12" height="50" fill="url(#legMetal)" rx="2"/>
                    <rect x="343" y="220" width="12" height="50" fill="url(#legMetal)" rx="2"/>
                    <rect x="95" y="240" width="12" height="30" fill="url(#legMetal)" rx="2"/>
                    <rect x="313" y="240" width="12" height="30" fill="url(#legMetal)" rx="2"/>
                    <rect x="55" y="265" width="310" height="6" fill="#334155" rx="3" opacity="0.6"/>
                </g>

                {{-- Main drying tunnel body ( greenhouse style ) --}}
                <g filter="url(#dropShadow)">
                    {{-- Lower metal frame --}}
                    <rect x="55" y="140" width="310" height="100" rx="8" fill="url(#frameMetal)" stroke="#334155" stroke-width="2"/>
                    {{-- Side panels --}}
                    <rect x="65" y="150" width="290" height="80" fill="#f8fafc" opacity="0.9"/>
                    {{-- Curved roof (polycarbonate) --}}
                    <path d="M55 140 Q55 70 210 70 Q365 70 365 140 L365 140 L55 140 Z" fill="url(#roofGrad)" stroke="#60a5fa" stroke-width="2" stroke-opacity="0.4"/>
                    {{-- Roof shine highlight --}}
                    <path d="M70 130 Q75 85 210 82 Q300 85 340 130" fill="none" stroke="url(#roofShine)" stroke-width="6" stroke-linecap="round" opacity="0.6"/>
                    {{-- Roof frame ribs --}}
                    <line x1="120" y1="78" x2="120" y2="140" stroke="#93c5fd" stroke-width="2" opacity="0.5"/>
                    <line x1="210" y1="70" x2="210" y2="140" stroke="#93c5fd" stroke-width="2" opacity="0.5"/>
                    <line x1="300" y1="78" x2="300" y2="140" stroke="#93c5fd" stroke-width="2" opacity="0.5"/>
                </g>

                {{-- Solar panel array on roof --}}
                <g transform="translate(210, 68) rotate(-8)" filter="url(#dropShadow)">
                    <rect x="-80" y="-22" width="160" height="44" rx="3" fill="url(#solarFrame)" stroke="#475569" stroke-width="1.5"/>
                    <rect x="-75" y="-18" width="150" height="36" rx="1" fill="url(#solarCell)"/>
                    <g stroke="#3b82f6" stroke-width="0.8" opacity="0.6">
                        <line x1="-75" y1="-6" x2="75" y2="-6"/>
                        <line x1="-75" y1="6" x2="75" y2="6"/>
                        <line x1="-45" y1="-18" x2="-45" y2="18"/>
                        <line x1="-15" y1="-18" x2="-15" y2="18"/>
                        <line x1="15" y1="-18" x2="15" y2="18"/>
                        <line x1="45" y1="-18" x2="45" y2="18"/>
                    </g>
                    <text x="0" y="4" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7" font-weight="700" font-family="Inter,sans-serif">SOLAR PV</text>
                </g>

                {{-- Drying bed with grain pile --}}
                <g>
                    {{-- Bed frame --}}
                    <rect x="75" y="200" width="270" height="22" rx="3" fill="#64748b" stroke="#475569" stroke-width="1"/>
                    <rect x="80" y="203" width="260" height="16" rx="2" fill="#475569"/>
                    {{-- Grain pile (upper surface) --}}
                    <path d="M85 205 Q120 180 160 195 Q190 185 220 198 Q250 188 290 198 Q325 190 335 205 Z" fill="url(#grainPile)" filter="url(#dropShadow)"/>
                    {{-- Individual grain particles --}}
                    <g id="grain-particles">
                        <ellipse cx="105" cy="198" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="125" cy="192" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="145" cy="200" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="165" cy="190" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="185" cy="198" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="205" cy="192" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="225" cy="200" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="245" cy="190" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="265" cy="198" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="285" cy="192" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="305" cy="200" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="115" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="135" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="155" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="175" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="195" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="215" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="235" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="255" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="275" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="295" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                        <ellipse cx="315" cy="205" rx="4" ry="2.5" fill="url(#grainReal)" class="grain-particle"/>
                    </g>
                </g>

                {{-- Heater coils under bed --}}
                <g id="heater-coils" transform="translate(85, 232)">
                    <ellipse cx="125" cy="8" rx="120" ry="8" fill="url(#heaterGlow)" class="heater-coil" opacity="0"/>
                    <path d="M10 8 Q30 2 50 8 Q70 14 90 8 Q110 2 130 8 Q150 14 170 8 Q190 2 210 8 Q230 14 250 8" fill="none" stroke="#475569" stroke-width="3" stroke-linecap="round" class="heater-coil"/>
                    <path d="M10 14 Q30 8 50 14 Q70 20 90 14 Q110 8 130 14 Q150 20 170 14 Q190 8 210 14 Q230 20 250 14" fill="none" stroke="#475569" stroke-width="3" stroke-linecap="round" class="heater-coil"/>
                </g>

                {{-- Digital temperature display on side --}}
                <g transform="translate(175, 115)" filter="url(#dropShadow)">
                    <rect x="0" y="0" width="70" height="28" rx="6" fill="#0f172a" stroke="#334155" stroke-width="2"/>
                    <rect x="3" y="3" width="64" height="22" rx="4" fill="#020617"/>
                    <text id="svg-temp-inside" x="35" y="18" text-anchor="middle" fill="#fbbf24" font-size="14" font-weight="900" font-family="monospace">32.0°</text>
                    <text x="35" y="38" text-anchor="middle" fill="#64748b" font-size="7" font-weight="700" font-family="Inter,sans-serif">INTERNAL TEMP</text>
                </g>

                {{-- Intake fan (left) with guard --}}
                <g transform="translate(28, 135)" filter="url(#dropShadow)">
                    <circle cx="36" cy="36" r="34" fill="#f1f5f9" stroke="#64748b" stroke-width="3"/>
                    <circle cx="36" cy="36" r="29" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                    <g class="fan-blade" id="fan-blade">
                        <path d="M36 36 L36 14 L40 14 L40 36 Z" fill="#475569"/>
                        <path d="M36 36 L54 36 L54 40 L36 40 Z" fill="#475569"/>
                        <path d="M36 36 L36 58 L32 58 L32 36 Z" fill="#475569"/>
                        <path d="M36 36 L18 36 L18 32 L36 32 Z" fill="#475569"/>
                        <path d="M36 36 L50 22 L53 25 L36 36 Z" fill="#475569"/>
                        <path d="M36 36 L50 50 L47 53 L36 36 Z" fill="#475569"/>
                        <path d="M36 36 L22 50 L19 47 L36 36 Z" fill="#475569"/>
                        <path d="M36 36 L22 22 L25 19 L36 36 Z" fill="#475569"/>
                    </g>
                    <circle cx="36" cy="36" r="6" fill="#64748b"/>
                    {{-- Guard mesh --}}
                    <circle cx="36" cy="36" r="34" fill="none" stroke="#94a3b8" stroke-width="1" stroke-dasharray="2 4"/>
                </g>

                {{-- Exhaust fan (right) with guard --}}
                <g transform="translate(320, 145)" filter="url(#dropShadow)">
                    <circle cx="26" cy="26" r="24" fill="#f1f5f9" stroke="#64748b" stroke-width="2"/>
                    <circle cx="26" cy="26" r="20" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                    <g class="fan-blade exhaust" id="exhaust-blade">
                        <path d="M26 26 L26 10 L29 10 L29 26 Z" fill="#475569"/>
                        <path d="M26 26 L38 26 L38 29 L26 29 Z" fill="#475569"/>
                        <path d="M26 26 L26 42 L23 42 L23 26 Z" fill="#475569"/>
                        <path d="M26 26 L14 26 L14 23 L26 23 Z" fill="#475569"/>
                        <path d="M26 26 L36 16 L38 18 L26 26 Z" fill="#475569"/>
                        <path d="M26 26 L36 36 L34 38 L26 26 Z" fill="#475569"/>
                        <path d="M26 26 L16 36 L14 34 L26 26 Z" fill="#475569"/>
                        <path d="M26 26 L16 16 L18 14 L26 26 Z" fill="#475569"/>
                    </g>
                    <circle cx="26" cy="26" r="4" fill="#64748b"/>
                    <circle cx="26" cy="26" r="24" fill="none" stroke="#94a3b8" stroke-width="1" stroke-dasharray="2 3"/>
                </g>

                {{-- Steam from exhaust --}}
                <g id="steam-group" transform="translate(360, 160)">
                    <circle cx="0" cy="0" r="6" fill="#cbd5e1" class="steam-particle"/>
                    <circle cx="10" cy="-6" r="5" fill="#cbd5e1" class="steam-particle"/>
                    <circle cx="-6" cy="-10" r="4" fill="#cbd5e1" class="steam-particle"/>
                </g>

                {{-- Mixer motor at bottom center --}}
                <g transform="translate(198, 248)" filter="url(#dropShadow)">
                    <rect x="0" y="0" width="24" height="24" rx="4" fill="#334155" stroke="#475569" stroke-width="2"/>
                    {{-- translate posisi di wrapper luar — animasi CSS transform
                         akan MENIMPA atribut transform elemen yang sama --}}
                    <g transform="translate(12,12)">
                        <g id="mixer-icon" class="mixer-icon">
                            <circle cx="0" cy="0" r="4" fill="#94a3b8"/>
                            <line x1="0" y1="-8" x2="0" y2="8" stroke="#94a3b8" stroke-width="2.5" class="mixer-arm"/>
                            <line x1="-8" y1="0" x2="8" y2="0" stroke="#94a3b8" stroke-width="2.5" class="mixer-arm"/>
                        </g>
                    </g>
                    <text x="12" y="34" text-anchor="middle" fill="#64748b" font-size="6" font-weight="700" font-family="Inter,sans-serif">MIXER</text>
                </g>

                {{-- Airflow arrows (subtle) --}}
                <g id="airflow-arrows" opacity="0.35">
                    <path d="M70 180 Q110 175 150 180" fill="none" stroke="#3b82f6" stroke-width="1.5" stroke-dasharray="4 4" stroke-linecap="round"/>
                    <polygon points="150,176 158,180 150,184" fill="#3b82f6"/>
                    <path d="M270 180 Q310 175 340 175" fill="none" stroke="#64748b" stroke-width="1.5" stroke-dasharray="4 4" stroke-linecap="round"/>
                    <polygon points="340,171 348,175 340,179" fill="#64748b"/>
                </g>

                {{-- Data packet to server — translate posisi di wrapper luar,
                     animasi terbang (translateX/Y) di elemen dalam --}}
                <g transform="translate(330, 90)">
                    <g id="data-packet" class="data-packet">
                        <circle cx="0" cy="0" r="8" fill="#16a34a" style="filter:drop-shadow(0 0 5px #16a34a);"/>
                        <text x="0" y="2" text-anchor="middle" fill="#fff" font-size="5" font-weight="700" font-family="Inter,sans-serif">IoT</text>
                        <path d="M-6 10 L-10 14 M-3 12 L-6 17 M2 12 L5 17" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round"/>
                    </g>
                </g>
            </svg>

            {{-- Actuator status pills floating --}}
            <div style="position:absolute;bottom:14px;left:14px;right:14px;display:flex;gap:0.6rem;flex-wrap:wrap;justify-content:center;z-index:4;">
                <div style="display:flex;align-items:center;gap:0.4rem;background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:20px;padding:0.4rem 0.85rem;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <span id="led-heater" class="led off" style="color:#f97316;"></span>
                    <span style="font-size:0.74rem;font-weight:700;color:#374151;">Heater</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.4rem;background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:20px;padding:0.4rem 0.85rem;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <span id="led-fan" class="led off" style="color:#3b82f6;"></span>
                    <span style="font-size:0.74rem;font-weight:700;color:#374151;">Fan</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.4rem;background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:20px;padding:0.4rem 0.85rem;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <span id="led-exhaust" class="led off" style="color:#64748b;"></span>
                    <span style="font-size:0.74rem;font-weight:700;color:#374151;">Exhaust</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.4rem;background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:20px;padding:0.4rem 0.85rem;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <span id="led-mixer" class="led off" style="color:#d97706;"></span>
                    <span style="font-size:0.74rem;font-weight:700;color:#374151;">Mixer</span>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div style="margin-top:1rem;display:flex;gap:1.25rem;flex-wrap:wrap;justify-content:center;font-size:0.74rem;color:#64748b;">
            <span style="display:flex;align-items:center;gap:0.35rem;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#d97706,#f59e0b);margin-right:2px;box-shadow:0 1px 3px rgba(0,0,0,0.1);"></span>Gabah</span>
            <span style="display:flex;align-items:center;gap:0.35rem;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;margin-right:2px;box-shadow:0 1px 3px rgba(0,0,0,0.1);"></span>Heater</span>
            <span style="display:flex;align-items:center;gap:0.35rem;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#3b82f6;margin-right:2px;box-shadow:0 1px 3px rgba(0,0,0,0.1);"></span>Ventilasi</span>
            <span style="display:flex;align-items:center;gap:0.35rem;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#16a34a;margin-right:2px;box-shadow:0 1px 3px rgba(0,0,0,0.1);"></span>Data ke Server</span>
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
        mixerIcon: document.getElementById('mixer-icon'),
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
        els.mixerIcon.classList.toggle('on', state.mixerOn);
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

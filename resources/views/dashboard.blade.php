@extends('layouts.app')

@section('title', __('app.dashboard'))

@section('content')

{{-- ═══════════════════════════════════════════════
     1. PAGE HEADER BANNER
═══════════════════════════════════════════════ --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);flex-shrink:0;box-shadow:0 4px 16px rgba(0,0,0,0.2);">
                <img src="{{ asset('images/logo.jpeg') }}" alt="PADI" style="width:36px;height:36px;border-radius:9px;object-fit:cover;">
            </div>
            <div>
                <h2 style="font-size:1.6rem;font-weight:900;color:#fff;margin:0;letter-spacing:0.04em;line-height:1;text-shadow:0 2px 8px rgba(0,0,0,0.2);">PADI</h2>
                <p style="font-size:0.68rem;color:rgba(255,255,255,0.65);margin:0.25rem 0 0;font-weight:600;letter-spacing:0.12em;">PRECISION · AI · DRYING · INNOVATION</p>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;">
                    <span style="font-size:0.65rem;color:rgba(255,255,255,0.55);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">Dashboard</span>
                    <span style="font-size:0.65rem;color:rgba(255,255,255,0.45);">·</span>
                    <span style="font-size:0.65rem;color:rgba(255,255,255,0.55);">{{ now()->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            {{-- Cuaca widget mini --}}
            <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(212,160,23,0.2);border:1px solid rgba(212,160,23,0.35);border-radius:10px;padding:0.5rem 0.875rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fde047" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                </svg>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:#fef08a;">{{ $latestSensor ? number_format($latestSensor->temperature_outside ?? 0,1).'°C' : '—' }}</div>
                    <div style="font-size:0.58rem;color:rgba(255,255,255,0.55);">Luar</div>
                </div>
            </div>
            {{-- Status online --}}
            <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:0.5rem 1rem;backdrop-filter:blur(6px);">
                <span class="pulse-green" style="width:8px;height:8px;flex-shrink:0;"></span>
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:#fff;line-height:1.2;">{{ __('app.online') }}</div>
                    <div style="font-size:0.6rem;color:rgba(255,255,255,0.55);">Sistem Aktif</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════
     2. STAT CARDS — 5 kolom
═══════════════════════════════════════════════ --}}
<div class="stat-cards-grid" style="margin-bottom:1.25rem;">

    <div class="metric-card" style="padding:1.5rem 1.25rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ __('app.total_batch') }}</span>
            <div class="metric-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg></div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;">{{ $totalBatches }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.7);margin-top:8px;position:relative;z-index:1;">{{ __('app.all_batches') }}</div>
    </div>

    <div class="metric-card-orange" style="padding:1.5rem 1.25rem;" id="card-waiting">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ __('app.waiting') }}</span>
            <div class="metric-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;" id="stat-waiting">{{ $waitingBatches }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.7);margin-top:8px;position:relative;z-index:1;">{{ __('app.not_started') }}</div>
    </div>

    <div class="metric-card-cyan" style="padding:1.5rem 1.25rem;" id="card-drying">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ __('app.running') }}</span>
            <div class="metric-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;" id="stat-active-batches">{{ $dryingBatches }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.7);margin-top:8px;position:relative;z-index:1;">{{ __('app.in_drying') }}</div>
    </div>

    <div class="metric-card-green" style="padding:1.5rem 1.25rem;" id="card-completed">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ __('app.completed') }}</span>
            <div class="metric-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;">{{ $completedBatches }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.7);margin-top:8px;position:relative;z-index:1;">{{ __('app.process_done') }}</div>
    </div>

    <div class="metric-card-red" style="padding:1.5rem 1.25rem;" id="card-cancelled">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ __('app.cancelled') }}</span>
            <div class="metric-card-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;">{{ $cancelledBatches }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.7);margin-top:8px;position:relative;z-index:1;">{{ __('app.process_cancelled') }}</div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════
     3. SENSOR CHART (2/3) + CURRENT CONDITIONS (1/3)
═══════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1.25rem;">
    <style>@media(min-width:1024px){.dash-row-chart{grid-template-columns:2fr 1fr!important;}}</style>
    <div class="dash-row-chart" style="display:grid;grid-template-columns:1fr;gap:1rem;">

        {{-- Sensor Chart --}}
        <div class="glass-card" style="padding:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <div>
                    <h3 class="card-header-title" style="font-size:0.88rem;">{{ __('app.realtime_sensor') }}</h3>
                    <p style="font-size:0.7rem;color:#64748b;margin:3px 0 0;">{{ __('app.temp_humidity') }}</p>
                </div>
                <span class="badge badge-green" style="font-size:0.65rem;">
                    <span class="pulse-green" style="width:5px;height:5px;"></span> {{ __('app.live') }}
                </span>
            </div>
            <div id="sensorChart"></div>
        </div>

        {{-- Current Conditions --}}
        <div class="glass-card" style="padding:0;overflow:hidden;">
            <div class="card-header">
                <h3 class="card-header-title" style="font-size:0.82rem;">{{ __('app.current_conditions') }}</h3>
                @if($latestSensor)
                <span style="font-size:0.65rem;color:#64748b;">{{ $latestSensor->recorded_at?->diffForHumans() ?? '—' }}</span>
                @endif
            </div>
            @php
            $conditions = [
                ['label'=>__('app.temp_inside'),      'value'=>$latestSensor?->temperature_inside,  'fmt'=>fn($v)=>number_format($v,1).'°C',   'color'=>'#f97316','bg'=>'#fff7ed','max'=>80,  'warn'=>60,  'icon'=>'M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7z'],
                ['label'=>__('app.temp_outside'),     'value'=>$latestSensor?->temperature_outside, 'fmt'=>fn($v)=>number_format($v,1).'°C',   'color'=>'#d97706','bg'=>'#fffbeb','max'=>50,  'warn'=>38,  'icon'=>'M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7z'],
                ['label'=>__('app.humidity_inside'),  'value'=>$latestSensor?->humidity_inside,      'fmt'=>fn($v)=>number_format($v,1).'%',    'color'=>'#2563eb','bg'=>'#eff6ff','max'=>100, 'warn'=>70,  'icon'=>'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'],
                ['label'=>__('app.solar_irradiance'), 'value'=>$latestSensor?->solar_irradiance,     'fmt'=>fn($v)=>number_format($v,0).' W/m²','color'=>'#ea580c','bg'=>'#fff7ed','max'=>1000,'warn'=>null,'icon'=>'M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2'],
                ['label'=>__('app.wind_speed'),       'value'=>$latestSensor?->wind_speed,            'fmt'=>fn($v)=>number_format($v,1).' m/s', 'color'=>'#059669','bg'=>'#f0fdf4','max'=>20,  'warn'=>10,  'icon'=>'M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2'],
                ['label'=>__('app.grain_moisture'),   'value'=>$latestSensor?->grain_moisture,         'fmt'=>fn($v)=>number_format($v,1).'%',    'color'=>'#7c3aed','bg'=>'#f5f3ff','max'=>30,  'warn'=>14,  'icon'=>'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'],
            ];
            @endphp
            <div style="padding:0.25rem 1rem 0.875rem;">
                @foreach($conditions as $c)
                @php
                    $pct = ($c['value'] && $c['max']>0) ? min(100,($c['value']/$c['max'])*100) : 0;
                    $display = $c['value'] ? ($c['fmt'])($c['value']) : '—';
                    $warn = $c['value'] && isset($c['warn']) && $c['warn'] && $c['value'] > $c['warn'];
                @endphp
                <div style="padding:0.5rem 0;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.3rem;">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <div style="width:26px;height:26px;background:{{ $c['bg'] }};border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="{{ $c['color'] }}" stroke-width="2.5" stroke-linecap="round"><path d="{{ $c['icon'] }}"/></svg>
                            </div>
                            <span style="font-size:0.72rem;font-weight:600;color:#374151;">{{ $c['label'] }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.3rem;">
                            <span style="font-size:0.82rem;font-weight:800;color:{{ $c['color'] }};">{{ $display }}</span>
                            @if($warn)<span style="font-size:0.56rem;font-weight:700;color:#d97706;background:#fef9c3;border-radius:4px;padding:1px 5px;">HIGH</span>
                            @else<span style="font-size:0.56rem;font-weight:700;color:#059669;background:#dcfce7;border-radius:4px;padding:1px 5px;">OK</span>@endif
                        </div>
                    </div>
                    <div style="height:3px;background:#e8edf5;border-radius:99px;overflow:hidden;margin-left:34px;">
                        <div style="width:{{ number_format($pct,1) }}%;height:100%;background:{{ $c['color'] }};border-radius:99px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════
     4. GAUGES — 4 kolom redesign dengan SVG arc
═══════════════════════════════════════════════ --}}
@php
$gaugeData = [
    [
        'label'  => __('app.gauge_grain_moisture'),
        'val'    => $latestSensor?->grain_moisture,
        'unit'   => '%',
        'max'    => 30,
        'good'   => 14,
        'target' => 'Target ≤ 14%',
        'color'  => '#0891b2',
        'bg'     => '#ecfeff',
        'border' => '#a5f3fc',
        'icon'   => 'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z',
        'warn_above' => true,
    ],
    [
        'label'  => __('app.gauge_humidity_in'),
        'val'    => $latestSensor?->humidity_inside,
        'unit'   => '%',
        'max'    => 100,
        'good'   => 70,
        'target' => 'Target < 70%',
        'color'  => '#2563eb',
        'bg'     => '#eff6ff',
        'border' => '#bfdbfe',
        'icon'   => 'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z',
        'warn_above' => true,
    ],
    [
        'label'  => 'Temp Inside',
        'val'    => $latestSensor?->temperature_inside,
        'unit'   => '°C',
        'max'    => 80,
        'good'   => 60,
        'target' => 'Ideal 40–60°C',
        'color'  => '#ea580c',
        'bg'     => '#fff7ed',
        'border' => '#fed7aa',
        'icon'   => 'M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7z',
        'warn_above' => true,
    ],
    [
        'label'  => 'Solar Irradiance',
        'val'    => $latestSensor?->solar_irradiance,
        'unit'   => ' W/m²',
        'max'    => 1000,
        'good'   => 1000,
        'target' => 'Max 1000 W/m²',
        'color'  => '#d97706',
        'bg'     => '#fffbeb',
        'border' => '#fde68a',
        'icon'   => 'M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42',
        'warn_above' => false,
    ],
];
@endphp
<div class="gauge-grid" style="margin-bottom:1.25rem;">
    @foreach($gaugeData as $g)
    @php
        $pct    = ($g['val'] && $g['max']>0) ? min(100, ($g['val']/$g['max'])*100) : 0;
        $isWarn = $g['val'] && $g['warn_above'] && $g['val'] > $g['good'];
        $statusColor = $isWarn ? '#d97706' : '#059669';
        $statusBg    = $isWarn ? '#fef9c3' : '#dcfce7';
        $statusText  = $isWarn ? 'HIGH' : 'NORMAL';
        $displayVal  = $g['val'] ? number_format($g['val'], in_array($g['unit'],['%','°C'])?1:0).$g['unit'] : '—';

        // SVG arc params
        $r = 36; $cx = 50; $cy = 50;
        $startAngle = -220; $sweep = 260;
        $arcCirc = 2 * M_PI * $r;
        $arcLen  = ($pct / 100) * ($sweep / 360) * $arcCirc;
        $trackLen = ($sweep / 360) * $arcCirc;
        $gap = $arcCirc - $trackLen;
    @endphp
    <div style="background:#ffffff;border:1.5px solid {{ $g['border'] }};border-radius:18px;padding:1.25rem 1rem;box-shadow:0 2px 12px rgba(0,0,0,0.05);transition:all 0.25s;position:relative;overflow:hidden;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'"
         onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.05)'">

        {{-- Background accent --}}
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:{{ $g['bg'] }};opacity:0.8;pointer-events:none;"></div>

        {{-- Header: icon + label + status --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.875rem;position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <div style="width:30px;height:30px;border-radius:8px;background:{{ $g['bg'] }};border:1px solid {{ $g['border'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="{{ $g['color'] }}" stroke-width="2.5" stroke-linecap="round"><path d="{{ $g['icon'] }}"/></svg>
                </div>
                <span style="font-size:0.68rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:0.07em;">{{ $g['label'] }}</span>
            </div>
            <span style="font-size:0.56rem;font-weight:800;color:{{ $statusColor }};background:{{ $statusBg }};border-radius:6px;padding:2px 7px;letter-spacing:0.06em;">{{ $statusText }}</span>
        </div>

        {{-- SVG Donut Arc --}}
        <div style="display:flex;align-items:center;gap:0.875rem;position:relative;z-index:1;">
            <div style="flex-shrink:0;position:relative;">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    {{-- Track arc --}}
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}"
                        fill="none"
                        stroke="#e8f0fe"
                        stroke-width="8"
                        stroke-dasharray="{{ number_format($trackLen,2) }} {{ number_format($arcCirc-$trackLen,2) }}"
                        stroke-dashoffset="{{ number_format(-$startAngle/360*$arcCirc,2) }}"
                        stroke-linecap="round"/>
                    {{-- Value arc --}}
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}"
                        fill="none"
                        stroke="{{ $g['color'] }}"
                        stroke-width="8"
                        stroke-dasharray="{{ number_format($arcLen,2) }} {{ number_format($arcCirc-$arcLen,2) }}"
                        stroke-dashoffset="{{ number_format(-$startAngle/360*$arcCirc,2) }}"
                        stroke-linecap="round"
                        style="filter:drop-shadow(0 0 4px {{ $g['color'] }}66);"/>
                    {{-- Center value --}}
                    <text x="{{ $cx }}" y="{{ $cy-3 }}" text-anchor="middle" fill="{{ $g['color'] }}" font-size="14" font-weight="900" font-family="Inter,sans-serif">{{ $g['val'] ? number_format($g['val'],0) : '—' }}</text>
                    <text x="{{ $cx }}" y="{{ $cy+11 }}" text-anchor="middle" fill="#94a3b8" font-size="8" font-weight="600" font-family="Inter,sans-serif">{{ trim($g['unit']) ?: 'W/m²' }}</text>
                    {{-- Pct bottom --}}
                    <text x="{{ $cx }}" y="{{ $cy+26 }}" text-anchor="middle" fill="#cbd5e1" font-size="8" font-family="Inter,sans-serif">{{ number_format($pct,0) }}%</text>
                </svg>
            </div>

            {{-- Right: nilai besar + target --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:1.6rem;font-weight:900;color:{{ $g['color'] }};line-height:1;margin-bottom:0.2rem;">
                    {{ $g['val'] ? number_format($g['val'],in_array($g['unit'],['%','°C'])?1:0) : '—' }}<span style="font-size:0.75rem;font-weight:600;color:{{ $g['color'] }};opacity:0.7;">{{ $g['unit'] }}</span>
                </div>
                <div style="font-size:0.65rem;color:#94a3b8;margin-bottom:0.625rem;">{{ $g['target'] }}</div>
                {{-- Mini progress bar --}}
                <div style="height:5px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="width:{{ number_format($pct,1) }}%;height:100%;background:linear-gradient(90deg,{{ $g['color'] }}88,{{ $g['color'] }});border-radius:99px;transition:width 0.8s ease;"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>


{{-- ═══════════════════════════════════════════════
     5. ACTIVE BATCHES (2/3) + RECENT AI DECISIONS (1/3)
═══════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1.25rem;">
<style>@media(min-width:1024px){.dash-row-batches{grid-template-columns:2fr 1fr!important;}}</style>
<div class="dash-row-batches" style="display:grid;grid-template-columns:1fr;gap:1rem;">

    {{-- Active Batches Table --}}
    <div class="glass-card" style="overflow:hidden;">
        <div class="card-header" style="background:linear-gradient(135deg,#f0fdf4,#fefce8);border-bottom:1px solid #bbf7d0;">
            <div>
                <h3 class="card-header-title" style="font-size:0.88rem;">{{ __('app.active_batches_table') }}</h3>
                <p style="font-size:0.68rem;color:#64748b;margin:2px 0 0;">{{ $activeBatchList->count() }} batch sedang berjalan</p>
            </div>
            <a href="{{ route('web.batches.index') }}" style="font-size:0.75rem;color:#16a34a;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:0.25rem;background:#dcfce7;padding:0.3rem 0.75rem;border-radius:8px;border:1px solid #86efac;">
                {{ __('app.view_all') }}
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
            </a>
        </div>
        @if($activeBatchList->count())
        <div style="padding:0.75rem;">
            @foreach($activeBatchList as $batch)
            @php
                $progress = $batch->initial_moisture > 0
                    ? min(100,max(0,(($batch->initial_moisture-($batch->current_moisture??$batch->initial_moisture))/($batch->initial_moisture-$batch->target_moisture))*100))
                    : 0;
                $moisture = $batch->current_moisture ?? $batch->initial_moisture;
                $progressColor = $progress >= 80 ? '#059669' : ($progress >= 40 ? '#d97706' : '#16a34a');
            @endphp
            <div style="background:#ffffff;border:1.5px solid #e8f5e9;border-radius:14px;padding:1rem;margin-bottom:0.625rem;box-shadow:0 1px 4px rgba(22,163,74,0.06);transition:all 0.2s;"
                 onmouseover="this.style.borderColor='#86efac';this.style.boxShadow='0 4px 16px rgba(22,163,74,0.12)'"
                 onmouseout="this.style.borderColor='#e8f5e9';this.style.boxShadow='0 1px 4px rgba(22,163,74,0.06)'">

                {{-- Row 1: batch code + device + status --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.625rem;flex-wrap:wrap;gap:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.625rem;">
                        {{-- Icon --}}
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#166534,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(22,101,52,0.25);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <a href="{{ route('web.batches.show', $batch) }}" style="font-size:0.88rem;font-weight:800;color:#166534;text-decoration:none;line-height:1.2;display:block;">{{ $batch->batch_code }}</a>
                            <span style="font-size:0.68rem;color:#64748b;">{{ $batch->device?->device_name ?? '—' }}</span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        {{-- Rice type badge --}}
                        <span style="font-size:0.65rem;font-weight:600;color:#92400e;background:#fef9c3;border:1px solid #fde68a;border-radius:6px;padding:2px 8px;">
                            {{ $batch->rice_type }}{{ $batch->rice_variety ? ' · '.$batch->rice_variety : '' }}
                        </span>
                        {{-- Status --}}
                        @if($batch->status==='drying')
                        <span class="badge badge-green" style="display:flex;align-items:center;gap:0.25rem;">
                            <span style="width:5px;height:5px;border-radius:50%;background:#059669;display:inline-block;box-shadow:0 0 0 2px rgba(5,150,105,0.3);"></span>
                            Drying
                        </span>
                        @elseif($batch->status==='paused')
                        <span class="badge badge-yellow">Paused</span>
                        @else
                        <span class="badge badge-gray">{{ $batch->status }}</span>
                        @endif
                    </div>
                </div>

                {{-- Row 2: moisture + progress bar --}}
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    {{-- Moisture --}}
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">
                        <div style="text-align:center;background:#fff7ed;border-radius:8px;padding:0.25rem 0.5rem;border:1px solid #fed7aa;">
                            <div style="font-size:1rem;font-weight:900;color:#d97706;line-height:1;">{{ number_format($moisture,1) }}<span style="font-size:0.65rem;">%</span></div>
                            <div style="font-size:0.55rem;color:#92400e;font-weight:600;">Saat ini</div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
                        <div style="text-align:center;background:#f0fdf4;border-radius:8px;padding:0.25rem 0.5rem;border:1px solid #86efac;">
                            <div style="font-size:1rem;font-weight:900;color:#059669;line-height:1;">{{ number_format($batch->target_moisture,1) }}<span style="font-size:0.65rem;">%</span></div>
                            <div style="font-size:0.55rem;color:#166534;font-weight:600;">Target</div>
                        </div>
                    </div>

                    {{-- Progress bar + persen --}}
                    <div style="flex:1;min-width:120px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem;">
                            <span style="font-size:0.65rem;color:#64748b;font-weight:600;">Progress Pengeringan</span>
                            <span style="font-size:0.75rem;font-weight:800;color:{{ $progressColor }};">{{ number_format($progress,0) }}%</span>
                        </div>
                        <div style="height:8px;background:#e8f5e9;border-radius:99px;overflow:hidden;">
                            <div style="width:{{ number_format($progress,1) }}%;height:100%;background:linear-gradient(90deg,#166534,{{ $progressColor }});border-radius:99px;transition:width 0.6s ease;box-shadow:0 0 6px {{ $progressColor }}44;"></div>
                        </div>
                    </div>

                    {{-- Duration --}}
                    @if($batch->durationMinutes())
                    <div style="flex-shrink:0;text-align:right;">
                        <div style="font-size:0.8rem;font-weight:700;color:#475569;">{{ $batch->durationMinutes() }} min</div>
                        <div style="font-size:0.58rem;color:#94a3b8;">Durasi</div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="padding:2.5rem;text-align:center;">
            <div style="width:56px;height:56px;background:#f0fdf4;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.875rem;border:2px dashed #86efac;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="1.5">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <p style="font-size:0.82rem;color:#64748b;margin:0;">Tidak ada batch aktif</p>
        </div>
        @endif
    </div>

    {{-- Recent AI Decisions --}}
    <div class="glass-card" style="overflow:hidden;">
        <div class="card-header">
            <h3 class="card-header-title">{{ __('app.recent_ai_decisions') }}</h3>
            <a href="{{ route('web.ai.decisions') }}" style="font-size:0.75rem;color:#7c3aed;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:0.25rem;">
                {{ __('app.view_all') }}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
            </a>
        </div>
        <div style="padding:0.25rem 1rem 1rem;max-height:420px;overflow-y:auto;">
            @forelse($recentDecisions as $decision)
            <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid #f1f5f9;">
                <div style="width:34px;height:34px;border-radius:9px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2">
                        <rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>
                    </svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:0.3rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                        <span style="font-size:0.78rem;font-weight:700;color:#0f172a;">{{ str_replace('_',' ',ucwords($decision->decision_type,'_')) }}</span>
                        @if($decision->confidence_score)
                            @php $conf=$decision->confidence_score*100; @endphp
                            <span class="badge {{ $conf>=80?'badge-green':($conf>=50?'badge-yellow':'badge-red') }}" style="font-size:0.58rem;">{{ number_format($conf,0) }}%</span>
                        @endif
                    </div>
                    <p style="font-size:0.72rem;color:#475569;margin:0 0 0.2rem;line-height:1.45;">{{ Str::limit($decision->reasoning,100) }}</p>
                    <div style="display:flex;align-items:center;gap:0.375rem;flex-wrap:wrap;">
                        @if($decision->execution_status==='executed') <span class="badge badge-green" style="font-size:0.58rem;">Executed</span>
                        @elseif($decision->execution_status==='pending')<span class="badge badge-yellow" style="font-size:0.58rem;">Pending</span>
                        @else<span class="badge badge-gray" style="font-size:0.58rem;">{{ $decision->execution_status }}</span>@endif
                        <span style="font-size:0.65rem;color:#94a3b8;">{{ $decision->decided_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:1.5rem 0;text-align:center;color:#94a3b8;font-size:0.8rem;">Belum ada keputusan AI</div>
            @endforelse
        </div>
    </div>

</div>
</div>


{{-- ═══════════════════════════════════════════════
     6. OEE WIDGET — redesign clean
═══════════════════════════════════════════════ --}}
@php
    $oeeColor    = $oeeScore >= 85 ? '#059669' : ($oeeScore >= 60 ? '#d97706' : '#dc2626');
    $oeeBg       = $oeeScore >= 85 ? '#f0fdf4' : ($oeeScore >= 60 ? '#fffbeb' : '#fef2f2');
    $oeeBorder   = $oeeScore >= 85 ? '#86efac' : ($oeeScore >= 60 ? '#fde68a' : '#fecaca');
    $oeeAccent   = 'linear-gradient(135deg,#166534,#16a34a,#22c55e)';
    $oeeLabel    = $oeeScore >= 85 ? 'World Class' : ($oeeScore >= 60 ? 'Average' : 'Needs Improvement');
    $oeeComponents = [
        ['Availability', $oeeAvailability, __('app.oee_availability_desc'), '#2563eb', '#eff6ff', '#bfdbfe'],
        ['Performance',  $oeePerformance,  __('app.oee_performance_desc'),  '#d97706', '#fffbeb', '#fde68a'],
        ['Quality',      $oeeQuality,      __('app.oee_quality_desc'),      '#7c3aed', '#f5f3ff', '#ddd6fe'],
    ];
@endphp

<div class="glass-card" style="margin-bottom:1.25rem;overflow:hidden;">
    {{-- OEE Header bar --}}
    <div style="background:{{ $oeeAccent }};padding:0.875rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:0.6rem;font-weight:800;letter-spacing:0.14em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-bottom:2px;">{{ __('app.oee_title') }}</div>
            <div style="font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.85);">{{ __('app.oee_last30') }} · A × P × Q</div>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);border-radius:10px;padding:0.375rem 0.875rem;border:1px solid rgba(255,255,255,0.2);">
            <span style="width:7px;height:7px;border-radius:50%;background:#fff;display:inline-block;box-shadow:0 0 6px rgba(255,255,255,0.8);"></span>
            <span style="font-size:0.7rem;font-weight:800;color:#fff;letter-spacing:0.05em;">{{ $oeeLabel }}</span>
        </div>
    </div>

    {{-- OEE Body --}}
    <div style="padding:1.5rem;display:grid;grid-template-columns:auto 1fr;gap:2rem;align-items:center;flex-wrap:wrap;">
    <style>@media(max-width:640px){.oee-body{grid-template-columns:1fr!important;}}</style>

        {{-- Score besar --}}
        <div style="text-align:center;flex-shrink:0;">
            {{-- Donut SVG besar --}}
            @php
                $bigR = 52; $bigCirc = 2*M_PI*$bigR;
                $bigDash = ($oeeScore/100)*$bigCirc;
            @endphp
            <div style="position:relative;display:inline-block;">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    {{-- Track --}}
                    <circle cx="70" cy="70" r="{{ $bigR }}" fill="none" stroke="#e8f5e9" stroke-width="12"/>
                    {{-- Progress --}}
                    <circle cx="70" cy="70" r="{{ $bigR }}" fill="none" stroke="{{ $oeeColor }}" stroke-width="12"
                        stroke-dasharray="{{ number_format($bigDash,2) }} {{ number_format($bigCirc,2) }}"
                        stroke-linecap="round"
                        transform="rotate(-90 70 70)"
                        style="filter:drop-shadow(0 0 8px {{ $oeeColor }}66);transition:stroke-dasharray 1s ease;"/>
                    {{-- Center text --}}
                    <text x="70" y="62" text-anchor="middle" fill="{{ $oeeColor }}" font-size="28" font-weight="900" font-family="Inter,sans-serif">{{ $oeeScore }}</text>
                    <text x="70" y="80" text-anchor="middle" fill="#94a3b8" font-size="12" font-weight="600" font-family="Inter,sans-serif">%</text>
                    <text x="70" y="96" text-anchor="middle" fill="#64748b" font-size="9" font-weight="700" font-family="Inter,sans-serif" letter-spacing="1">OEE SCORE</text>
                </svg>
            </div>
            <div style="margin-top:0.5rem;">
                <span style="display:inline-flex;align-items:center;gap:0.375rem;background:{{ $oeeBg }};border:1.5px solid {{ $oeeBorder }};border-radius:20px;padding:4px 14px;">
                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $oeeColor }};display:inline-block;"></span>
                    <span style="font-size:0.7rem;font-weight:800;color:{{ $oeeColor }};">{{ $oeeLabel }}</span>
                </span>
            </div>
        </div>

        {{-- 3 komponen vertikal --}}
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach($oeeComponents as [$name,$val,$desc,$color,$bg,$border])
            @php $cc = $val>=85?'#059669':($val>=60?'#d97706':'#dc2626'); @endphp
            <div style="background:{{ $bg }};border:1.5px solid {{ $border }};border-radius:12px;padding:0.875rem 1rem;display:flex;align-items:center;gap:1rem;">
                {{-- Label + desc --}}
                <div style="min-width:110px;flex-shrink:0;">
                    <div style="font-size:0.8rem;font-weight:800;color:#0f172a;">{{ $name }}</div>
                    <div style="font-size:0.65rem;color:#64748b;margin-top:1px;">{{ $desc }}</div>
                </div>
                {{-- Progress bar --}}
                <div style="flex:1;">
                    <div style="height:10px;background:rgba(0,0,0,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:{{ $val }}%;height:100%;background:linear-gradient(90deg,{{ $color }},{{ $cc }});border-radius:99px;box-shadow:0 0 6px {{ $cc }}44;transition:width 1s ease;"></div>
                    </div>
                </div>
                {{-- Value --}}
                <div style="flex-shrink:0;text-align:right;min-width:48px;">
                    <span style="font-size:1.4rem;font-weight:900;color:{{ $cc }};line-height:1;">{{ $val }}</span>
                    <span style="font-size:0.75rem;color:{{ $cc }};opacity:0.8;">%</span>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════
     7. ACTUATOR STATUS — full width
═══════════════════════════════════════════════ --}}
<div class="glass-card" style="margin-bottom:1.5rem;overflow:hidden;">
    <div class="card-header">
        <h3 class="card-header-title">{{ __('app.actuator_status') }}</h3>
        <span style="font-size:0.68rem;color:#64748b;">{{ __('app.last_24h') }}</span>
    </div>
    <div style="padding:0 1.25rem 1.25rem;">
        @if($actuatorStatus->isEmpty())
        <p style="font-size:0.8rem;color:#64748b;padding:1rem 0;margin:0;">{{ __('app.no_actuator_log') }}</p>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:0.75rem;padding-top:0.875rem;">
            @foreach($actuatorStatus as $log)
            @php
                $isOn  = in_array($log->command,['on','open','start','activate']);
                $color = $isOn ? '#059669' : '#64748b';
                $bg    = $isOn ? 'linear-gradient(135deg,#f0fdf4,#dcfce7)' : 'linear-gradient(135deg,#f8fafc,#f1f5f9)';
                $border= $isOn ? '#86efac' : '#e2e8f0';
                $iconBg= $isOn ? 'linear-gradient(135deg,#166534,#16a34a)' : 'linear-gradient(135deg,#94a3b8,#64748b)';
            @endphp
            <div style="background:{{ $bg }};border:1.5px solid {{ $border }};border-radius:12px;padding:0.875rem;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $iconBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round">
                            <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M6.34 17.66l-1.41 1.41M1 12h2M21 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41"/>
                        </svg>
                    </div>
                    <div style="font-size:0.73rem;font-weight:700;color:#0f172a;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $log->actuator_name ?? ucfirst(str_replace('_',' ',$log->actuator_type)) }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:0.3rem;">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $color }};display:inline-block;{{ $isOn?'box-shadow:0 0 0 3px rgba(5,150,105,0.2);':'' }}"></span>
                        <span style="font-size:0.68rem;font-weight:800;color:{{ $color }};text-transform:uppercase;letter-spacing:0.05em;">{{ strtoupper($log->command) }}</span>
                    </div>
                    <span style="font-size:0.6rem;color:#94a3b8;">{{ $log->executed_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if($recentActuatorLogs->isNotEmpty())
        <div style="margin-top:1.25rem;overflow-x:auto;">
            <table class="table-dark" style="font-size:0.75rem;" id="dt-dashboard-actuator">
                <thead>
                    <tr>
                        <th>{{ __('app.col_actuator') }}</th>
                        <th>{{ __('app.col_command') }}</th>
                        <th>{{ __('app.col_triggered_by') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentActuatorLogs as $log)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $log->actuator_name ?? ucfirst(str_replace('_',' ',$log->actuator_type)) }}</td>
                        <td><span class="badge {{ in_array($log->command,['on','open','start','activate'])?'badge-green':'badge-red' }}">{{ strtoupper($log->command) }}</span></td>
                        <td><span class="badge badge-purple">{{ $log->triggered_by }}</span></td>
                        <td>
                            @if($log->status==='success')  <span class="badge badge-green">{{ __('app.success_label') }}</span>
                            @elseif($log->status==='failed')<span class="badge badge-red">{{ __('app.failed') }}</span>
                            @else                           <span class="badge badge-yellow">{{ $log->status }}</span>
                            @endif
                        </td>
                        <td style="color:#64748b;white-space:nowrap;">{{ $log->executed_at?->format('d M H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Sensor Area Chart ──
    const labels   = @json($chartLabels);
    const tempIn   = @json($chartTempInside);
    const tempOut  = @json($chartTempOutside);
    const humidity = @json($chartHumidInside);

    const chartOptions = {
        ...window.apexDarkConfig,
        chart: { ...window.apexDarkConfig.chart, type: 'area', height: 280 },
        series: [
            { name: 'Temp Inside (°C)',  data: tempIn   },
            { name: 'Temp Outside (°C)', data: tempOut  },
            { name: 'Humidity In (%)',   data: humidity },
        ],
        xaxis: { ...window.apexDarkConfig.xaxis, categories: labels },
        yaxis: [
            { title: { text: '°C', style: { color: '#475569' } }, labels: { style: { colors: '#475569' } } },
            { opposite: true, title: { text: '%', style: { color: '#475569' } }, labels: { style: { colors: '#475569' } } },
        ],
        colors: ['#f97316', '#fbbf24', '#3b82f6'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
    };
    const sensorChart = new ApexCharts(document.querySelector('#sensorChart'), chartOptions);
    sensorChart.render();

    // ── OEE Gauges tidak perlu JS — donut di OEE sudah SVG PHP

    // ── Realtime Echo ──
    if (window.Echo) {
        window.Echo.channel('sensor-updates')
            .listen('SensorUpdated', (e) => {
                const s = e.sensor;
                sensorChart.appendData([
                    { data: [s.temperature_inside] },
                    { data: [s.temperature_outside] },
                    { data: [s.humidity_inside] },
                ]);
            });
        window.Echo.private('App.Models.User.{{ auth()->id() }}')
            .notification(() => {
                const alpine = document.querySelector('[x-data]')?._x_dataStack?.[0];
                if (alpine && 'notifCount' in alpine) alpine.notifCount++;
            });
    }

    // ── DataTables ──
    if ($('#dt-dashboard-actuator').length) $('#dt-dashboard-actuator').DataTable({ paging: false, info: false, language: { search: '{{ __("app.search") }}:' } });

    // ── Polling stat cards 30s ──
    setInterval(() => {
        fetch('/api/dashboard/stats', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                if (data.active_batches !== undefined) {
                    const el = document.getElementById('stat-active-batches');
                    if (el) el.textContent = data.active_batches;
                }
            }).catch(() => {});
    }, 30000);
});
</script>
@endpush

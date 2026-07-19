@extends('layouts.viewer')
@section('title', 'Status Pengeringan')
@section('content')

@php
    $heroClass   = 's-idle';
    $statusIcon  = 'idle';
    $statusText  = 'Tidak Ada Proses';
    $statusSub   = 'Tidak ada pengeringan berlangsung saat ini';

    if ($activeBatch) {
        if ($activeBatch->status === 'drying') {
            $heroClass   = 's-drying';
            $statusIcon  = 'drying';
            $statusText  = 'Sedang Dikeringkan';
            $statusSub   = 'Mesin aktif — gabah sedang diproses';
        } elseif ($activeBatch->status === 'paused') {
            $heroClass   = 's-paused';
            $statusIcon  = 'paused';
            $statusText  = 'Dijeda Otomatis';
            $statusSub   = 'Kemungkinan hujan — sistem akan lanjut otomatis';
        }
    }

    // Device state
    $heaterOn = $latestDecision
        && $latestDecision->decision_type === 'start_heater'
        && $latestDecision->execution_status === 'executed';
    $fanOn    = (bool)($latestDecision?->esp32_command['fan'] ?? false);
    $mixerOn  = $activeBatch && $activeBatch->status === 'drying';
    $online   = $device?->status === 'online';
@endphp

{{-- ═══════════════════════════════════════════════════════════
     STATUS HERO
═══════════════════════════════════════════════════════════ --}}
<div id="poll-hero" class="status-hero {{ $heroClass }}">
    <div class="hero-inner">
        <div id="poll-hero-icon" class="hero-icon-wrap">
            @if($statusIcon === 'drying')
                <svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 2C8.5 6 5 9.5 5 13a7 7 0 0 0 14 0c0-3.5-3.5-7-7-11z"/>
                    <path d="M12 12v5M9.5 14.5l2.5 2.5 2.5-2.5" opacity=".6"/>
                </svg>
            @elseif($statusIcon === 'paused')
                <svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0z"/>
                    <line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/>
                </svg>
            @else
                <svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
                </svg>
            @endif
        </div>

        <div class="hero-text">
            <div id="poll-hero-status" class="hero-status">{{ $statusText }}</div>
            <div id="poll-hero-sub" class="hero-sub">{{ $statusSub }}</div>
        </div>

        <div id="poll-hero-pills" class="hero-pills" style="{{ $activeBatch ? '' : 'display:none' }}">
            @if($activeBatch)
            <span class="hpill">{{ $activeBatch->rice_variety ?? $activeBatch->rice_type ?? 'Gabah' }}</span>
            <span class="hpill mono">{{ $activeBatch->batch_code }}</span>
            @if($activeBatch->petani_name)
                <span class="hpill">{{ $activeBatch->petani_name }}</span>
            @endif
            @if($activeBatch->initial_weight)
                <span class="hpill">{{ $activeBatch->initial_weight }} kg</span>
            @endif
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     TOMBOL AJUKAN PENGERINGAN (tampil saat idle / tidak ada batch aktif)
═══════════════════════════════════════════════════════════ --}}
@if(!$activeBatch)
<div style="padding:0 4px;margin-bottom:4px;">
    @php
        $pendingMyReq = \App\Models\DryingBatch::where('requested_by', auth()->id())
            ->where('request_status', 'pending')->latest()->first();
    @endphp

    @if($pendingMyReq)
    {{-- Ada request pending --}}
    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fcd34d;border-radius:16px;padding:16px 18px;display:flex;align-items:center;gap:14px">
        <div style="font-size:2rem;flex-shrink:0">⏳</div>
        <div style="flex:1">
            <div style="font-weight:700;color:#92400e;font-size:.9rem">Menunggu Persetujuan Operator</div>
            <div style="font-size:.78rem;color:#78350f;margin-top:2px">
                {{ $pendingMyReq->rice_variety }} · {{ $pendingMyReq->initial_weight }} kg ·
                Diajukan {{ $pendingMyReq->requested_at?->diffForHumans() ?? 'baru saja' }}
            </div>
        </div>
        <a href="{{ route('viewer.request') }}"
           style="background:rgba(255,255,255,.6);color:#92400e;border:1px solid #fcd34d;border-radius:10px;padding:7px 14px;font-size:.78rem;font-weight:700;text-decoration:none;flex-shrink:0">
            Lihat
        </a>
    </div>
    @else
    {{-- Tidak ada request — tampilkan tombol ajukan --}}
    <a href="{{ route('viewer.request') }}"
       style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#14532d,#15803d);color:#fff;border-radius:16px;padding:16px 20px;text-decoration:none;box-shadow:0 4px 16px rgba(21,128,61,.35);transition:transform .1s;width:100%"
       onmousedown="this.style.transform='scale(.98)'"
       onmouseup="this.style.transform='scale(1)'">
        <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">🌾</div>
        <div style="flex:1">
            <div style="font-weight:800;font-size:.95rem">Ajukan Pengeringan Gabah</div>
            <div style="font-size:.78rem;color:rgba(255,255,255,.8);margin-top:2px">Isi data gabah → operator setujui → mesin jalan otomatis</div>
        </div>
        <div style="font-size:1.2rem;opacity:.7">→</div>
    </a>
    @endif
</div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     MOISTURE PROGRESS
═══════════════════════════════════════════════════════════ --}}
@if($activeBatch)
<div class="v-card">
    <div class="v-card-header">
        <div class="v-card-title">
            <span class="card-icon card-icon-blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M12 2C8.5 6 5 9.5 5 13a7 7 0 0 0 14 0c0-3.5-3.5-7-7-11z"/>
                </svg>
            </span>
            Kadar Air Gabah
        </div>
        <span class="badge-status {{ ($moistureProgress??0) >= 100 ? 'bs-done' : 'bs-active' }}">
            {{ ($moistureProgress??0) >= 100 ? 'Selesai' : 'Berlangsung' }}
        </span>
    </div>

    <div class="moisture-row">
        <div class="moist-current">
            <span id="poll-moist-val" class="moist-val">{{ number_format($activeBatch->current_moisture ?? $activeBatch->initial_moisture, 1) }}</span>
            <span class="moist-unit">%</span>
            <span class="moist-label">sekarang</span>
        </div>
        <div class="moist-arrow">→</div>
        <div class="moist-stats">
            <div class="moist-stat">
                <span class="mstat-label">Awal</span>
                <span class="mstat-val">{{ $activeBatch->initial_moisture }}%</span>
            </div>
            <div class="moist-stat">
                <span class="mstat-label">Target</span>
                <span class="mstat-val target">≤ {{ $activeBatch->target_moisture }}%</span>
            </div>
            <div class="moist-stat">
                <span class="mstat-label">Turun</span>
                <span id="poll-moist-drop" class="mstat-val drop">{{ number_format($activeBatch->initial_moisture - ($activeBatch->current_moisture ?? $activeBatch->initial_moisture), 1) }}%</span>
            </div>
        </div>
    </div>

    <div class="prog-track">
        <div id="poll-prog-fill" class="prog-fill" style="width:{{ $moistureProgress ?? 0 }}%">
            @if(($moistureProgress ?? 0) > 12)
                <span id="poll-prog-pct" class="prog-pct">{{ $moistureProgress }}%</span>
            @else
                <span id="poll-prog-pct" class="prog-pct" style="display:none">{{ $moistureProgress }}%</span>
            @endif
        </div>
    </div>
    <div class="prog-labels">
        <span>0%</span>
        <span style="color:#6b7280;font-size:.75rem">Progres pengeringan</span>
        <span>100%</span>
    </div>

    @if(($moistureProgress ?? 0) >= 100)
    <div class="done-banner">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Gabah sudah kering! Silakan ambil di lokasi mesin.
    </div>
    @endif
</div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     ESTIMASI WAKTU PENGERINGAN
═══════════════════════════════════════════════════════════ --}}
@if($activeBatch && $dryingEstimation['available'])
<div class="v-card estimation-card {{ $dryingEstimation['estimated_hours'] === null ? 'est-warn' : '' }}">
    <div class="v-card-header">
        <div class="v-card-title">
            <span class="card-icon card-icon-amber">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </span>
            Estimasi Waktu Selesai
        </div>
        @php
            $confMap = ['high'=>['bs-done','Akurasi Tinggi'],'medium'=>['bs-mid','Akurasi Sedang'],'low'=>['bs-warn','Data Sedikit'],'none'=>['bs-off','—']];
            [$confClass, $confLabel] = $confMap[$dryingEstimation['confidence']] ?? ['bs-off','—'];
        @endphp
        <span class="badge-status {{ $confClass }}">{{ $confLabel }}</span>
    </div>

    @if($dryingEstimation['estimated_hours'] !== null && $dryingEstimation['estimated_hours'] > 0)
    <div class="est-grid">
        <div class="est-box">
            <div id="poll-est-hours" class="est-val">
                @if($dryingEstimation['estimated_hours'] < 1)
                    {{ round($dryingEstimation['estimated_hours'] * 60) }}
                    <span class="est-unit">menit</span>
                @else
                    {{ $dryingEstimation['estimated_hours'] }}
                    <span class="est-unit">jam</span>
                @endif
            </div>
            <div class="est-label">Sisa Waktu</div>
        </div>
        <div class="est-divider"></div>
        <div class="est-box">
            <div id="poll-est-finish" class="est-val sm">{{ $dryingEstimation['estimated_finish'] }}</div>
            <div class="est-label">Perkiraan Selesai</div>
        </div>
        <div class="est-divider"></div>
        <div class="est-box">
            <div id="poll-est-rate" class="est-val">{{ $dryingEstimation['rate_per_hour'] ?? '—' }}<span class="est-unit">%/jam</span></div>
            <div class="est-label">Laju Penurunan</div>
        </div>
    </div>
    @endif

    <div id="poll-est-msg" class="est-msg {{ $dryingEstimation['estimated_hours'] === null ? 'est-msg-warn' : '' }}">
        {{ $dryingEstimation['message'] }}
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════
     SENSOR SUHU & KELEMBABAN
═══════════════════════════════════════════════════════════ --}}
@php
    $t      = $sensor?->temperature_inside ?? null;
    $h      = $sensor?->humidity_inside ?? null;
    $tState = $t === null ? 'none' : ($t > 57 ? 'hot' : ($t < 38 ? 'cold' : 'ok'));
    $hState = $h === null ? 'none' : ($h > 75 ? 'high' : ($h > 65 ? 'mid' : 'ok'));

    $tLabels = ['none'=>'Tidak ada data','hot'=>'Terlalu panas','cold'=>'Terlalu dingin','ok'=>'Normal'];
    $hLabels = ['none'=>'Tidak ada data','high'=>'Terlalu lembab','mid'=>'Agak lembab','ok'=>'Normal'];
@endphp

<div class="sensor-grid">
    {{-- Suhu --}}
    <div class="sensor-card sc-t-{{ $tState }}">
        <div class="sc-top">
            <svg class="sc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
            </svg>
            <span class="sc-label">Suhu Dalam</span>
        </div>
        <div id="poll-temp-val" class="sc-value">{{ $t !== null ? number_format($t, 1).'°C' : '—' }}</div>
        <div id="poll-temp-badge" class="sc-badge sc-badge-t-{{ $tState }}">{{ $tLabels[$tState] }}</div>
        @if($sensor?->temperature_outside !== null)
            <div id="poll-temp-out" class="sc-sub">Luar: {{ number_format($sensor->temperature_outside, 1) }}°C</div>
        @else
            <div id="poll-temp-out" class="sc-sub" style="display:none"></div>
        @endif
    </div>

    {{-- Kelembaban --}}
    <div class="sensor-card sc-h-{{ $hState }}">
        <div class="sc-top">
            <svg class="sc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2C8.5 6 5 9.5 5 13a7 7 0 0 0 14 0c0-3.5-3.5-7-7-11z"/>
            </svg>
            <span class="sc-label">Kelembaban Dalam</span>
        </div>
        <div id="poll-rh-val" class="sc-value">{{ $h !== null ? number_format($h, 1).'%' : '—' }}</div>
        <div id="poll-rh-badge" class="sc-badge sc-badge-h-{{ $hState }}">{{ $hLabels[$hState] }}</div>
        @if($sensor?->humidity_outside !== null)
            <div id="poll-rh-out" class="sc-sub">Luar: {{ number_format($sensor->humidity_outside, 1) }}%</div>
        @else
            <div id="poll-rh-out" class="sc-sub" style="display:none"></div>
        @endif
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════
     STATUS PERANGKAT
═══════════════════════════════════════════════════════════ --}}
<div class="v-card">
    <div class="v-card-header">
        <div class="v-card-title">
            <span class="card-icon card-icon-gray">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                </svg>
            </span>
            Status Perangkat
        </div>
        <span class="badge-status {{ $online ? 'bs-done' : 'bs-off' }}">
            {{ $online ? 'Online' : 'Offline' }}
        </span>
    </div>

    <div class="device-grid">
        <div id="poll-device-heater" class="device-item {{ $heaterOn ? 'di-on' : 'di-off' }}">
            <div id="poll-icon-heater" class="di-icon-wrap {{ $heaterOn ? 'diw-on' : 'diw-off' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C8.5 6 5 9.5 5 13a7 7 0 0 0 14 0c0-3.5-3.5-7-7-11z"/>
                    <path d="M12 12v4" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="di-name">Pemanas</div>
            <div id="poll-dot-heater" class="di-dot {{ $heaterOn ? 'dot-on' : 'dot-off' }}"></div>
        </div>

        <div id="poll-device-fan" class="device-item {{ $fanOn ? 'di-on' : 'di-off' }}">
            <div id="poll-icon-fan" class="di-icon-wrap {{ $fanOn ? 'diw-on' : 'diw-off' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 12c-2-2.5-2-6 0-8s5.5-1.5 5.5 1-2 3.5-5.5 7z"/>
                    <path d="M12 12c2.5 2 6 2 8 0s1.5-5.5-1-5.5-3.5 2-7 5.5z"/>
                    <path d="M12 12c2 2.5 2 6 0 8s-5.5 1.5-5.5-1 2-3.5 5.5-7z"/>
                    <path d="M12 12c-2.5-2-6-2-8 0s-1.5 5.5 1 5.5 3.5-2 7-5.5z"/>
                    <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                </svg>
            </div>
            <div class="di-name">Kipas</div>
            <div id="poll-dot-fan" class="di-dot {{ $fanOn ? 'dot-on' : 'dot-off' }}"></div>
        </div>

        <div id="poll-device-mixer" class="device-item {{ $mixerOn ? 'di-on' : 'di-off' }}">
            <div id="poll-icon-mixer" class="di-icon-wrap {{ $mixerOn ? 'diw-on' : 'diw-off' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                </svg>
            </div>
            <div class="di-name">Mixer</div>
            <div id="poll-dot-mixer" class="di-dot {{ $mixerOn ? 'dot-on' : 'dot-off' }}"></div>
        </div>

        <div id="poll-device-sensor" class="device-item {{ $online ? 'di-on' : 'di-warn' }}">
            <div id="poll-icon-sensor" class="di-icon-wrap {{ $online ? 'diw-on' : 'diw-warn' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="di-name">Sensor</div>
            <div id="poll-dot-sensor" class="di-dot {{ $online ? 'dot-on' : 'dot-warn' }}"></div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SARAN AI
═══════════════════════════════════════════════════════════ --}}
@if($latestDecision?->reasoning)
<div class="v-card">
    <div class="v-card-header">
        <div class="v-card-title">
            <span class="card-icon card-icon-purple">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/>
                    <path d="M18 2l2 2-2 2M20 2v6" stroke-linecap="round"/>
                </svg>
            </span>
            Saran Sistem AI
        </div>
        @if(($latestDecision->confidence_score ?? 0) >= 0.8)
            <span class="badge-status bs-done">Keyakinan Tinggi</span>
        @elseif(($latestDecision->confidence_score ?? 0) >= 0.6)
            <span class="badge-status bs-mid">Keyakinan Sedang</span>
        @endif
    </div>

    <div class="ai-meta">{{ $latestDecision->decided_at?->diffForHumans() }}</div>
    <p class="ai-text">{{ $latestDecision->reasoning }}</p>

    @foreach(($latestDecision->output_action['alerts'] ?? []) as $alert)
    <div class="ai-alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        {{ $alert }}
    </div>
    @endforeach
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════
     TIMESTAMP + REFRESH
═══════════════════════════════════════════════════════════ --}}
@if($sensor)
<div class="ts-row">
    <span id="poll-ts">Diperbarui: {{ $sensor->recorded_at?->format('d M Y, H:i') ?? '-' }}</span>
    <span id="poll-live-indicator" class="live-dot" title="Auto-refresh aktif"></span>
    <a href="{{ route('viewer.dashboard') }}" class="refresh-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Perbarui
    </a>
</div>
@endif

@endsection

@push('styles')
<style>
/* ─── STATUS HERO ───────────────────────────────────────── */
.status-hero {
    border-radius: 24px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.15);
}
.status-hero.s-drying { background: linear-gradient(135deg, #14532d 0%, #166534 50%, #15803d 100%); }
.status-hero.s-paused { background: linear-gradient(135deg, #78350f 0%, #b45309 60%, #d97706 100%); }
.status-hero.s-idle   { background: linear-gradient(135deg, #1e293b 0%, #334155 60%, #475569 100%); }

.hero-inner { padding: 32px 28px; position: relative; }
.hero-inner::after {
    content: ''; position: absolute; top: -30px; right: -30px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,.05); border-radius: 50%;
}
.hero-inner::before {
    content: ''; position: absolute; bottom: -20px; left: -20px;
    width: 100px; height: 100px;
    background: rgba(255,255,255,.04); border-radius: 50%;
}

.hero-icon-wrap {
    width: 64px; height: 64px; border-radius: 18px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px; backdrop-filter: blur(4px);
}
.hero-svg { width: 32px; height: 32px; stroke: #fff; }

.hero-status { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1.15; }
.hero-sub    { font-size: .85rem; color: rgba(255,255,255,.72); margin-top: 6px; }

.hero-pills  { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
.hpill {
    background: rgba(255,255,255,.18); color: #fff;
    font-size: .77rem; font-weight: 600; padding: 4px 12px;
    border-radius: 999px; backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,.2);
}
.hpill.mono { font-family: 'Courier New', monospace; letter-spacing: .5px; }

/* ─── CARD BASE ─────────────────────────────────────────── */
.v-card {
    background: #fff;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    margin-bottom: 16px;
    border: 1px solid rgba(0,0,0,.04);
}
.v-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
}
.v-card-title {
    display: flex; align-items: center; gap: 9px;
    font-weight: 700; font-size: .9rem; color: #1f2937;
}
.card-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.card-icon-blue   { background: #dbeafe; color: #1d4ed8; }
.card-icon-amber  { background: #fef3c7; color: #b45309; }
.card-icon-gray   { background: #f3f4f6; color: #374151; }
.card-icon-purple { background: #ede9fe; color: #7c3aed; }
.card-icon svg    { display: block; }

/* ─── BADGES ────────────────────────────────────────────── */
.badge-status {
    font-size: .72rem; font-weight: 700;
    padding: 3px 10px; border-radius: 999px;
}
.bs-done   { background: #dcfce7; color: #166534; }
.bs-active { background: #dbeafe; color: #1e40af; }
.bs-mid    { background: #fef9c3; color: #854d0e; }
.bs-warn   { background: #fee2e2; color: #991b1b; }
.bs-off    { background: #f3f4f6; color: #6b7280; }

/* ─── MOISTURE ──────────────────────────────────────────── */
.moisture-row {
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 16px; flex-wrap: wrap;
}
.moist-current { display: flex; align-items: baseline; gap: 3px; }
.moist-val  { font-size: 3rem; font-weight: 800; color: #15803d; line-height: 1; }
.moist-unit { font-size: 1.3rem; font-weight: 700; color: #15803d; }
.moist-label{ font-size: .75rem; color: #9ca3af; margin-left: 2px; }
.moist-arrow{ font-size: 1.4rem; color: #d1d5db; flex-shrink: 0; }
.moist-stats { display: flex; gap: 14px; flex-wrap: wrap; }
.moist-stat  { display: flex; flex-direction: column; gap: 2px; }
.mstat-label { font-size: .7rem; color: #9ca3af; font-weight: 500; }
.mstat-val   { font-size: .88rem; font-weight: 700; color: #374151; }
.mstat-val.target { color: #15803d; }
.mstat-val.drop   { color: #2563eb; }

.prog-track {
    height: 18px; background: #f3f4f6; border-radius: 9px;
    overflow: hidden; margin-bottom: 6px;
}
.prog-fill {
    height: 100%; border-radius: 9px;
    background: linear-gradient(90deg, #15803d, #22c55e, #86efac);
    transition: width .9s ease;
    display: flex; align-items: center; justify-content: flex-end;
    padding-right: 8px; min-width: 24px;
}
.prog-pct { font-size: .68rem; font-weight: 800; color: #fff; }
.prog-labels {
    display: flex; justify-content: space-between;
    font-size: .72rem; color: #9ca3af;
}
.done-banner {
    display: flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534; font-weight: 700; font-size: .87rem;
    border-radius: 12px; padding: 12px 16px; margin-top: 14px;
}

/* ─── ESTIMASI ──────────────────────────────────────────── */
.estimation-card { border: 1.5px solid #fef3c7; }
.estimation-card.est-warn { border-color: #fee2e2; }

.est-grid {
    display: flex; align-items: center;
    gap: 0; margin-bottom: 14px;
    background: #fffbeb; border-radius: 14px;
    overflow: hidden;
}
.est-box {
    flex: 1; text-align: center; padding: 16px 12px;
}
.est-divider {
    width: 1px; height: 48px; background: #fde68a; flex-shrink: 0;
}
.est-val {
    font-size: 2rem; font-weight: 800; color: #b45309; line-height: 1.1;
}
.est-val.sm { font-size: 1.1rem; font-weight: 700; }
.est-unit { font-size: .75rem; font-weight: 600; color: #92400e; margin-left: 2px; }
.est-label { font-size: .72rem; color: #92400e; margin-top: 4px; font-weight: 500; }
.est-msg {
    font-size: .83rem; color: #374151; padding: 10px 14px;
    background: #f9fafb; border-radius: 10px;
    border-left: 3px solid #f59e0b;
}
.est-msg-warn { background: #fff1f2; border-left-color: #ef4444; color: #991b1b; }

/* ─── SENSOR CARDS ──────────────────────────────────────── */
.sensor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.sensor-card {
    border-radius: 20px; padding: 20px 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
    position: relative; overflow: hidden;
}
.sensor-card::after {
    content: ''; position: absolute;
    bottom: -20px; right: -20px;
    width: 90px; height: 90px;
    background: rgba(255,255,255,.1); border-radius: 50%;
}
.sc-t-ok   { background: linear-gradient(145deg, #166534, #22c55e); }
.sc-t-hot  { background: linear-gradient(145deg, #991b1b, #ef4444); }
.sc-t-cold { background: linear-gradient(145deg, #1e40af, #60a5fa); }
.sc-t-none { background: linear-gradient(145deg, #374151, #6b7280); }
.sc-h-ok   { background: linear-gradient(145deg, #0369a1, #38bdf8); }
.sc-h-high { background: linear-gradient(145deg, #c2410c, #f97316); }
.sc-h-mid  { background: linear-gradient(145deg, #b45309, #fbbf24); }
.sc-h-none { background: linear-gradient(145deg, #374151, #6b7280); }

.sc-top { display: flex; align-items: center; gap: 7px; margin-bottom: 8px; }
.sc-icon { width: 16px; height: 16px; stroke: rgba(255,255,255,.8); flex-shrink: 0; }
.sc-label  { font-size: .73rem; color: rgba(255,255,255,.8); font-weight: 500; }
.sc-value  { font-size: 2.6rem; font-weight: 800; color: #fff; line-height: 1.1; }
.sc-badge  {
    display: inline-block; margin-top: 6px;
    font-size: .68rem; font-weight: 700;
    padding: 3px 9px; border-radius: 999px;
    background: rgba(255,255,255,.2); color: #fff;
}
.sc-sub { font-size: .7rem; color: rgba(255,255,255,.65); margin-top: 5px; }

/* ─── DEVICE GRID ───────────────────────────────────────── */
.device-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.device-item {
    border-radius: 14px; padding: 14px 8px 12px;
    text-align: center; border: 1.5px solid transparent;
    transition: transform .15s;
}
.device-item:hover { transform: translateY(-2px); }
.di-on   { background: #f0fdf4; border-color: #86efac; }
.di-off  { background: #f9fafb; border-color: #e5e7eb; }
.di-warn { background: #fffbeb; border-color: #fcd34d; }

.di-icon-wrap {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 8px;
}
.diw-on   { background: #dcfce7; color: #166534; }
.diw-off  { background: #f3f4f6; color: #9ca3af; }
.diw-warn { background: #fef9c3; color: #b45309; }

.di-name  { font-size: .74rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
.di-dot   { width: 8px; height: 8px; border-radius: 50%; margin: 0 auto; }
.dot-on   { background: #22c55e; box-shadow: 0 0 6px rgba(34,197,94,.6); }
.dot-off  { background: #d1d5db; }
.dot-warn { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,.5); }

/* ─── AI BOX ────────────────────────────────────────────── */
.ai-meta { font-size: .75rem; color: #9ca3af; margin-bottom: 8px; }
.ai-text {
    font-size: .88rem; color: #1f2937; line-height: 1.65;
    margin: 0;
    padding: 14px 16px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-left: 3px solid #22c55e;
    border-radius: 0 12px 12px 0;
}
.ai-alert {
    display: flex; align-items: flex-start; gap: 8px;
    background: linear-gradient(135deg, #fef9c3, #fef3c7);
    border-left: 3px solid #f59e0b;
    border-radius: 0 12px 12px 0;
    padding: 10px 14px; margin-top: 10px;
    font-size: .82rem; color: #78350f;
}

/* ─── TIMESTAMP ROW ─────────────────────────────────────── */
.ts-row {
    display: flex; align-items: center; justify-content: center; gap: 12px;
    color: #9ca3af; font-size: .75rem;
    padding: 4px 0 12px;
}
.refresh-btn {
    display: flex; align-items: center; gap: 5px;
    color: #15803d; text-decoration: none; font-weight: 600;
    padding: 4px 10px; background: #f0fdf4; border-radius: 999px;
    border: 1px solid #bbf7d0; transition: background .15s;
}
.refresh-btn:hover { background: #dcfce7; }

/* ─── RESPONSIVE ────────────────────────────────────────── */
@media (max-width: 480px) {
    .hero-status { font-size: 1.35rem; }
    .moist-val   { font-size: 2.4rem; }
    .sc-value    { font-size: 2.1rem; }
    .device-grid { grid-template-columns: repeat(2, 1fr); }
    .est-grid    { flex-direction: column; }
    .est-divider { width: 100%; height: 1px; }
    .moisture-row{ flex-direction: column; align-items: flex-start; }
}

/* ─── LIVE DOT ──────────────────────────────────────────── */
.live-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34,197,94,.6);
    animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.6); }
    70%  { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}
.live-dot.error { background: #f87171; box-shadow: 0 0 0 0 rgba(248,113,113,.6);
    animation: pulse-err 2s infinite; }
@keyframes pulse-err {
    0%   { box-shadow: 0 0 0 0 rgba(248,113,113,.5); }
    70%  { box-shadow: 0 0 0 6px rgba(248,113,113,0); }
    100% { box-shadow: 0 0 0 0 rgba(248,113,113,0); }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    const POLL_URL  = '{{ route("viewer.dashboard.poll") }}';
    const INTERVAL  = 30000; // 30 detik
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── helpers ──────────────────────────────────────────────────
    function $id(id) { return document.getElementById(id); }

    function setDevice(itemId, iconId, dotId, on, warnOnOff) {
        // warnOnOff: false = off/warn split (sensor), true = on/off only
        const item = $id(itemId);
        const icon = $id(iconId);
        const dot  = $id(dotId);
        if (!item) return;

        if (warnOnOff) {
            // sensor online check
            item.className = 'device-item ' + (on ? 'di-on' : 'di-warn');
            icon.className = 'di-icon-wrap ' + (on ? 'diw-on' : 'diw-warn');
            dot.className  = 'di-dot '       + (on ? 'dot-on' : 'dot-warn');
        } else {
            item.className = 'device-item ' + (on ? 'di-on' : 'di-off');
            icon.className = 'di-icon-wrap ' + (on ? 'diw-on' : 'diw-off');
            dot.className  = 'di-dot '       + (on ? 'dot-on' : 'dot-off');
        }
    }

    function tState(t) {
        if (t === null) return 'none';
        if (t > 57)    return 'hot';
        if (t < 38)    return 'cold';
        return 'ok';
    }
    function hState(h) {
        if (h === null) return 'none';
        if (h > 75)    return 'high';
        if (h > 65)    return 'mid';
        return 'ok';
    }
    const tLabels = { none:'Tidak ada data', hot:'Terlalu panas', cold:'Terlalu dingin', ok:'Normal' };
    const hLabels = { none:'Tidak ada data', high:'Terlalu lembab', mid:'Agak lembab',   ok:'Normal' };

    // ── main update ──────────────────────────────────────────────
    function applyData(d) {
        // — Hero status —
        const hero = $id('poll-hero');
        if (hero) {
            hero.className = 'status-hero s-' + (d.status.state === 'drying' ? 'drying'
                           : d.status.state === 'paused' ? 'paused' : 'idle');
        }

        const svgs = {
            drying: `<svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2C8.5 6 5 9.5 5 13a7 7 0 0 0 14 0c0-3.5-3.5-7-7-11z"/><path d="M12 12v5M9.5 14.5l2.5 2.5 2.5-2.5" opacity=".6"/></svg>`,
            paused: `<svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0z"/><line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/></svg>`,
            idle:   `<svg class="hero-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>`,
        };
        const iconEl = $id('poll-hero-icon');
        if (iconEl) iconEl.innerHTML = svgs[d.status.state] ?? svgs.idle;

        const statusEl = $id('poll-hero-status');
        const subEl    = $id('poll-hero-sub');
        if (statusEl) statusEl.textContent = d.status.text;
        if (subEl)    subEl.textContent    = d.status.sub;

        // — Hero pills —
        const pillsEl = $id('poll-hero-pills');
        if (pillsEl) {
            if (d.status.batch_code) {
                pillsEl.style.display = '';
                let html = '';
                if (d.status.rice_variety) html += `<span class="hpill">${d.status.rice_variety}</span>`;
                html += `<span class="hpill mono">${d.status.batch_code}</span>`;
                if (d.status.petani_name) html += `<span class="hpill">${d.status.petani_name}</span>`;
                if (d.status.weight)      html += `<span class="hpill">${d.status.weight} kg</span>`;
                pillsEl.innerHTML = html;
            } else {
                pillsEl.style.display = 'none';
            }
        }

        // — Moisture —
        if (d.moisture) {
            const mv = $id('poll-moist-val');
            const md = $id('poll-moist-drop');
            const pf = $id('poll-prog-fill');
            const pp = $id('poll-prog-pct');
            if (mv) mv.textContent = d.moisture.current.toFixed(1);
            if (md) md.textContent = d.moisture.dropped.toFixed(1) + '%';
            if (pf) pf.style.width = d.moisture.progress + '%';
            if (pp) {
                pp.textContent = d.moisture.progress + '%';
                pp.style.display = d.moisture.progress > 12 ? '' : 'none';
            }
        }

        // — Sensor —
        if (d.sensor) {
            const ts  = tState(d.sensor.temp_in);
            const hs  = hState(d.sensor.rh_in);

            const tv = $id('poll-temp-val');
            const tb = $id('poll-temp-badge');
            const to = $id('poll-temp-out');
            if (tv) tv.textContent = d.sensor.temp_in !== null ? d.sensor.temp_in.toFixed(1) + '°C' : '—';
            if (tb) { tb.textContent = tLabels[ts]; tb.className = 'sc-badge'; }
            if (to) {
                if (d.sensor.temp_out !== null) {
                    to.textContent = 'Luar: ' + d.sensor.temp_out.toFixed(1) + '°C';
                    to.style.display = '';
                } else { to.style.display = 'none'; }
            }

            const rv = $id('poll-rh-val');
            const rb = $id('poll-rh-badge');
            const ro = $id('poll-rh-out');
            if (rv) rv.textContent = d.sensor.rh_in !== null ? d.sensor.rh_in.toFixed(1) + '%' : '—';
            if (rb) { rb.textContent = hLabels[hs]; rb.className = 'sc-badge'; }
            if (ro) {
                if (d.sensor.rh_out !== null) {
                    ro.textContent = 'Luar: ' + d.sensor.rh_out.toFixed(1) + '%';
                    ro.style.display = '';
                } else { ro.style.display = 'none'; }
            }
        }

        // — Devices —
        const dv = d.devices;
        setDevice('poll-device-heater', 'poll-icon-heater', 'poll-dot-heater', dv.heater, false);
        setDevice('poll-device-fan',    'poll-icon-fan',    'poll-dot-fan',    dv.fan,    false);
        setDevice('poll-device-mixer',  'poll-icon-mixer',  'poll-dot-mixer',  dv.mixer,  false);
        setDevice('poll-device-sensor', 'poll-icon-sensor', 'poll-dot-sensor', dv.online, true);

        // — Estimasi —
        const est = d.estimation;
        const estMsg = $id('poll-est-msg');
        const estHrs = $id('poll-est-hours');
        const estFin = $id('poll-est-finish');
        const estRate= $id('poll-est-rate');
        if (estMsg)  estMsg.textContent  = est.message;
        if (estHrs && est.estimated_hours !== null) {
            if (est.estimated_hours < 1) {
                estHrs.innerHTML = Math.round(est.estimated_hours * 60) + '<span class="est-unit">menit</span>';
            } else {
                estHrs.innerHTML = est.estimated_hours + '<span class="est-unit">jam</span>';
            }
        }
        if (estFin)  estFin.textContent  = est.estimated_finish ?? '—';
        if (estRate) estRate.innerHTML   = (est.rate_per_hour ?? '—') + '<span class="est-unit">%/jam</span>';

        // — Notif badge di topbar —
        const notifBadges = document.querySelectorAll('.notif-dot');
        notifBadges.forEach(b => {
            b.textContent = d.unread_count;
            b.style.display = d.unread_count > 0 ? '' : 'none';
        });

        // — Timestamp —
        const tsEl = $id('poll-ts');
        if (tsEl) tsEl.textContent = 'Diperbarui: ' + d.ts;
    }

    // ── poll ─────────────────────────────────────────────────────
    const dot = $id('poll-live-indicator');

    async function poll() {
        try {
            const res = await fetch(POLL_URL, {
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            applyData(data);
            if (dot) { dot.classList.remove('error'); }
        } catch (e) {
            console.warn('[poll] error:', e);
            if (dot) dot.classList.add('error');
        }
    }

    // Jalankan pertama kali setelah 30 detik, lalu setiap INTERVAL
    // (initial render sudah fresh dari server — tidak perlu langsung poll)
    setInterval(poll, INTERVAL);

    // Juga poll saat tab kembali aktif setelah > 60 detik tidak aktif
    let hiddenAt = null;
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hiddenAt = Date.now();
        } else if (hiddenAt && (Date.now() - hiddenAt) > 60000) {
            poll();
        }
    });
})();
</script>
@endpush

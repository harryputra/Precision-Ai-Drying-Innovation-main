@extends('layouts.app')
@section('title', __('app.weather_data'))
@section('breadcrumb', __('app.nav_monitoring') . ' / ' . __('app.nav_weather'))

@section('content')

{{-- Page header --}}
<div class="page-header-banner" style="position:relative;">
    <div style="position:relative;z-index:1;">
        <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.weather_data') }}</div>
        <h2 style="font-size:1.4rem;font-weight:800;color:#fff;margin:0 0 0.25rem;">{{ __('app.weather_data') }}</h2>
        <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.weather_desc') }}</p>
    </div>
</div>

{{-- Latest Weather --}}
@if($latest)
<div class="glass-card" style="padding:1.5rem;margin-bottom:1.25rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.5rem;">
        <div>
            <div style="font-size:0.7rem;color:#0f172a;text-transform:uppercase;font-weight:700;letter-spacing:0.08em;">{{ __('app.current_condition') }}</div>
            <div style="font-size:0.78rem;color:#0f172a;">{{ $latest->recorded_at?->format('d M Y H:i') }}</div>
        </div>
        @if($latest->location)
        <div style="display:flex;align-items:center;gap:0.375rem;background:#eff6ff;border-radius:20px;padding:0.3rem 0.75rem;border:1px solid #dbeafe;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span style="font-size:0.72rem;font-weight:600;color:#1d4ed8;">{{ $latest->location }}</span>
        </div>
        @endif
    </div>

    <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;margin-bottom:1.25rem;">
        <div>
            <div style="font-size:3.5rem;font-weight:800;line-height:1;background:linear-gradient(135deg,#1d4ed8,#0ea5e9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                {{ $latest->temperature !== null ? number_format($latest->temperature,1).'°C' : '—' }}
            </div>
            <div style="font-size:0.875rem;color:#0f172a;margin-top:0.25rem;text-transform:capitalize;">
                {{ $latest->weather_condition ?? 'Tidak ada data kondisi' }}
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0.75rem;">
        @foreach([
            [__('app.humidity'),       'humidity',         '%',    'linear-gradient(135deg,#1e40af,#2563eb,#60a5fa)', __('app.relative_humidity')],
            [__('app.solar_irradiance'),'solar_irradiance','W/m²', 'linear-gradient(135deg,#c2410c,#ea580c,#fb923c)', __('app.irradiance')],
            [__('app.wind_speed'),     'wind_speed',       'm/s',  'linear-gradient(135deg,#065f46,#059669,#34d399)', __('app.wind_speed_label')],
            [__('app.rainfall'),       'rainfall',         'mm',   'linear-gradient(135deg,#0e7490,#0891b2,#22d3ee)', __('app.precipitation')],
            [__('app.clouds'),         'cloud_cover',      '%',    'linear-gradient(135deg,#5b21b6,#7c3aed,#a78bfa)', __('app.cloud_cover_label')],
            [__('app.uv_index'),       'uv_index',         '',     'linear-gradient(135deg,#92400e,#d97706,#fbbf24)', __('app.uv_index_label')],
        ] as [$label,$field,$unit,$gradient,$sub])
        <div style="background:{{ $gradient }};border-radius:16px;padding:1.1rem 1.25rem;position:relative;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.18);transition:transform 0.2s,box-shadow 0.2s;"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 28px rgba(0,0,0,0.25)'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.18)'">
            {{-- decorative circle --}}
            <div style="position:absolute;top:-24px;right:-24px;width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.1);pointer-events:none;"></div>
            <div style="position:absolute;bottom:-16px;left:-10px;width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.06);pointer-events:none;"></div>

            {{-- label --}}
            <div style="font-size:0.62rem;color:rgba(255,255,255,0.65);text-transform:uppercase;font-weight:700;letter-spacing:0.1em;margin-bottom:0.5rem;position:relative;z-index:1;">{{ $label }}</div>

            {{-- main value --}}
            <div style="position:relative;z-index:1;line-height:1;margin-bottom:0.375rem;">
                <span style="font-size:1.9rem;font-weight:900;color:#fff;letter-spacing:-0.03em;">
                    {{ $latest->$field !== null ? number_format($latest->$field, ($field === 'uv_index' ? 1 : 1)) : '—' }}
                </span>
                @if($unit)
                <span style="font-size:0.85rem;font-weight:600;color:rgba(255,255,255,0.7);margin-left:2px;">{{ $unit }}</span>
                @endif
            </div>

            {{-- sub label --}}
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.5);font-weight:500;position:relative;z-index:1;">{{ $sub }}</div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="glass-card" style="padding:3rem;text-align:center;margin-bottom:1.25rem;">
    <div style="width:64px;height:64px;background:#f1f5f9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
            <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>
        </svg>
    </div>
    <p style="color:#0f172a;font-weight:500;margin:0 0 0.25rem;">{{ __('app.no_weather') }}</p>
    <p style="color:#0f172a;font-size:0.8rem;margin:0;">{{ __('app.no_weather_hint') }}</p>
</div>
@endif

{{-- Chart 24h --}}
@if($history->count())
<div class="glass-card" style="padding:1.25rem;margin-bottom:1.25rem;">
    <h3 style="font-size:0.875rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">{{ __('app.history_24h') }}</h3>
    <div id="weatherChart"></div>
</div>
@endif

{{-- Forecast --}}
@if($forecast->count())
<div class="glass-card" style="margin-bottom:1.25rem;overflow:hidden;">
    <div style="padding:0.875rem 1.25rem;border-bottom:1px solid #f1f5f9;">
        <h3 style="font-size:0.875rem;font-weight:700;color:#1e293b;margin:0;">{{ __('app.weather_forecast') }}</h3>
    </div>
    <div style="display:flex;gap:0;overflow-x:auto;">
        @foreach($forecast as $fc)
        <div style="flex:0 0 150px;padding:1.25rem;text-align:center;border-right:1px solid #f1f5f9;">
            <div style="font-size:0.7rem;color:#0f172a;margin-bottom:0.5rem;">{{ $fc->forecast_for?->format('d M H:i') ?? '—' }}</div>
            <div style="font-size:1.5rem;font-weight:800;color:#1d4ed8;">{{ $fc->temperature !== null ? number_format($fc->temperature,0).'°C' : '—' }}</div>
            <div style="font-size:0.72rem;color:#0f172a;margin-top:4px;text-transform:capitalize;">{{ $fc->weather_condition ?? '—' }}</div>            <div style="font-size:0.72rem;color:#0891b2;margin-top:4px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:3px;">
                @if($fc->humidity !== null)
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                {{ number_format($fc->humidity,0) }}%
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const history = @json($history);
    if (!history.length || !document.querySelector('#weatherChart')) return;
    const labels = history.map(r => r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : '');
    new ApexCharts(document.querySelector('#weatherChart'), {
        chart: { type: 'area', height: 260, background: 'transparent', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: 'Suhu (°C)',    data: history.map(r => r.temperature)      },
            { name: 'Solar (W/m²)', data: history.map(r => r.solar_irradiance) },
            { name: 'Kelembaban (%)', data: history.map(r => r.humidity)       },
        ],
        colors: ['#1d4ed8', '#f97316', '#0891b2'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.2, opacityTo: 0.0 } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        xaxis: { categories: labels, labels: { style: { colors: '#94a3b8', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
        tooltip: { theme: 'light' },
        legend: { labels: { colors: '#64748b' } },
    }).render();
});
</script>
@endpush

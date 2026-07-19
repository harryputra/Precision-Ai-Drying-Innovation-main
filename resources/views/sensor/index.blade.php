@extends('layouts.app')
@section('title', __('app.sensor_data'))
@section('breadcrumb', __('app.nav_monitoring') . ' / ' . __('app.sensor_data'))

@section('content')

{{-- Page header banner --}}
<div class="page-header-banner" style="padding:1.5rem 1.75rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.monitoring') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.sensor_data') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.sensor_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;"><span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">SENSOR READINGS</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span></div>
        </div>
    </div>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.25rem;">
    @php
    $statCards = [
        [__('app.temp_inside'), 'avg_temp',      '°C', 'metric-card-orange', 'M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z'],
        [__('app.humidity'),    'avg_humidity',  '%',  'metric-card-cyan',   'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'],
        [__('app.grain_moisture'),    'avg_moisture',  '%',  'metric-card-purple', 'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'],
        [__('app.readings_count'),  'total',         '',   'metric-card-green',  'M22 12h-4l-3 9L9 3l-3 9H2'],
    ];
    @endphp
    @foreach($statCards as [$label, $key, $unit, $cardClass, $iconPath])
    <div class="{{ $cardClass }}" style="padding:1.25rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
        <div style="position:relative;z-index:1;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(255,255,255,0.75);">{{ $label }}</span>
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="2">
                        <path d="{{ $iconPath }}"/>
                    </svg>
                </div>
            </div>
            <div style="font-size:1.75rem;font-weight:800;color:#fff;line-height:1;">
                {{ $stats[$key] ? number_format($stats[$key], 1).$unit : '—' }}
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filter card --}}
{{-- Chart --}}
@if($chartReadings->count())
<div class="glass-card" style="padding:1.25rem;margin-bottom:1.25rem;">
    <h3 style="font-size:0.875rem;font-weight:600;color:#0f172a;margin:0 0 1rem;">{{ __('app.sensor_trend') }}</h3>
    <div id="sensorTrendChart"></div>
</div>
@endif

{{-- Table --}}
<div class="glass-card" style="overflow:hidden;">

    {{-- Row 1: judul + export --}}
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <div>
            <h3 class="card-header-title">{{ __('app.readings') }}</h3>
            <p style="font-size:0.72rem;color:#64748b;margin:2px 0 0;">{{ $readings->total() }} {{ __('app.total') }}</p>
        </div>
        <a href="{{ route('web.sensor.export', request()->query()) }}"
           style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border-radius:10px;padding:0.4rem 0.9rem;font-size:0.75rem;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(13,148,136,0.3);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('app.export_sensor') }}
        </a>
    </div>

    {{-- Row 2: filter --}}
    <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="label-dark">{{ __('app.device') }}</label>
                <select name="device_id" class="input-dark" style="width:175px;">
                    <option value="">{{ __('app.all_devices') }}</option>
                    @foreach($devices as $d)
                    <option value="{{ $d->id }}" {{ request('device_id') == $d->id ? 'selected' : '' }}>{{ $d->device_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.batches') }}</label>
                <select name="batch_id" class="input-dark" style="width:175px;">
                    <option value="">{{ __('app.batch_list') }}</option>
                    @foreach($batches as $b)
                    <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>{{ $b->batch_code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.last_n_minutes') }}</label>
                <input type="number" name="minutes" value="{{ request('minutes') }}" placeholder="e.g. 60" class="input-dark" style="width:130px;">
            </div>
            <div style="display:flex;gap:0.4rem;align-items:flex-end;">
                <button type="submit" class="btn-primary btn-sm">{{ __('app.filter') }}</button>
                <a href="{{ route('web.sensor.index') }}" class="btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
    @if($readings->isEmpty())
    <div style="padding:3rem;text-align:center;">
        <div style="width:72px;height:72px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">{{ __('app.no_sensor_found') }}</p>
        <p style="font-size:0.82rem;color:#64748b;margin:0;">{{ __('app.no_sensor_found_hint') }}</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table class="table-dark" id="dt-sensor">
            <thead>
                <tr>
                    <th>{{ __('app.time') }}</th>
                    <th>{{ __('app.device') }}</th>
                    <th style="color:#f97316;">🌡 {{ __('app.temp_inside') }}</th>
                    <th style="color:#fbbf24;">🌡 {{ __('app.temp_outside') }}</th>
                    <th style="color:#3b82f6;">💧 {{ __('app.humidity_inside') }}</th>
                    <th style="color:#60a5fa;">💧 {{ __('app.humidity_outside') }}</th>
                    <th style="color:#fbbf24;">☀ {{ __('app.solar_irradiance') }}</th>
                    <th style="color:#a855f7;">🌾 {{ __('app.grain_moisture') }}</th>
                    <th style="color:#10b981;">💨 {{ __('app.wind_speed') }}</th>
                    <th>{{ __('app.valid') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($readings as $r)
                <tr>
                    <td>
                        <div style="font-size:0.75rem;font-weight:600;color:#1e293b;white-space:nowrap;">{{ $r->recorded_at?->format('d M Y') ?? '—' }}</div>
                        <div style="font-size:0.68rem;color:#94a3b8;font-family:monospace;">{{ $r->recorded_at?->format('H:i:s') ?? '' }}</div>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;border-radius:6px;padding:3px 8px;font-size:0.72rem;font-weight:600;color:#374151;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                            {{ $r->device?->device_name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#ea580c;font-size:0.82rem;">
                            {{ $r->temperature_inside !== null ? number_format($r->temperature_inside,1).'°C' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#d97706;font-size:0.82rem;">
                            {{ $r->temperature_outside !== null ? number_format($r->temperature_outside,1).'°C' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#2563eb;font-size:0.82rem;">
                            {{ $r->humidity_inside !== null ? number_format($r->humidity_inside,1).'%' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#3b82f6;font-size:0.82rem;">
                            {{ $r->humidity_outside !== null ? number_format($r->humidity_outside,1).'%' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#b45309;font-size:0.82rem;">
                            {{ $r->solar_irradiance !== null ? number_format($r->solar_irradiance,0).' W/m²' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-block;background:linear-gradient(135deg,#faf5ff,#ede9fe);color:#7c3aed;border:1px solid #ddd6fe;border-radius:8px;padding:2px 8px;font-weight:800;font-size:0.8rem;">
                            {{ $r->grain_moisture !== null ? number_format($r->grain_moisture,1).'%' : '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#059669;font-size:0.82rem;">
                            {{ $r->wind_speed !== null ? number_format($r->wind_speed,1).' m/s' : '—' }}
                        </span>
                    </td>
                    <td>
                        @if($r->is_valid)
                        <span class="badge badge-green">{{ __('app.valid') }}</span>
                        @else
                        <span class="badge badge-red" title="{{ $r->error_message }}">{{ __('app.invalid') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem 1.25rem;border-top:1px solid #f1f5f9;">{{ $readings->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const readings = @json($chartReadings);
    if (!readings.length || !document.querySelector('#sensorTrendChart')) return;
    const labels = readings.map(r => r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : '');
    new ApexCharts(document.querySelector('#sensorTrendChart'), {
        ...window.apexDarkConfig,
        chart: { ...window.apexDarkConfig.chart, type: 'area', height: 260 },
        series: [
            { name: 'Temp Inside (°C)',   data: readings.map(r => r.temperature_inside)  },
            { name: 'Humidity In (%)',    data: readings.map(r => r.humidity_inside)     },
            { name: 'Solar (W/m²)',       data: readings.map(r => r.solar_irradiance)    },
        ],
        xaxis: { ...window.apexDarkConfig.xaxis, categories: labels },
        colors: ['#f97316', '#3b82f6', '#fbbf24'],
    }).render();
});
</script>

<script>
$(document).ready(function () {
    $('#dt-sensor').DataTable({
        paging: false,
        info: false,
        language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_sensor_found") }}' }
    });
});
</script>
@endpush

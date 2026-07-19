@extends('layouts.app')
@section('title', $device->device_name)
@section('breadcrumb', __('app.devices') . ' / '.$device->device_name)

@section('content')
<div x-data="{ tab: 'sensor' }">

    {{-- Header --}}
    @php
        $devStatusCfg = [
            'online'      => ['linear-gradient(135deg,#059669,#10b981)', 'rgba(16,185,129,0.3)', 'badge-green', true],
            'offline'     => ['linear-gradient(135deg,#dc2626,#ef4444)', 'rgba(239,68,68,0.3)',   'badge-red',   false],
            'maintenance' => ['linear-gradient(135deg,#d97706,#f59e0b)', 'rgba(245,158,11,0.3)',  'badge-yellow',false],
        ];
        [$devIconBg, $devIconShadow, $devBadge, $devOnline] = $devStatusCfg[$device->status] ?? ['linear-gradient(135deg,#64748b,#94a3b8)','rgba(100,116,139,0.3)','badge-gray',false];
    @endphp
    <div class="glass-card" style="overflow:hidden;margin-bottom:1.25rem;">
        <div style="height:4px;background:{{ $devIconBg }};animation:shimmer-gold 3s ease-in-out infinite;background-size:200% 100%;"></div>
        <div style="padding:1.25rem 1.5rem;">
            <div style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
                <div style="width:56px;height:56px;border-radius:16px;background:{{ $devIconBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px {{ $devIconShadow }};">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>
                    </svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:4px;">
                        <h2 style="font-size:1.2rem;font-weight:900;color:#0f172a;margin:0;letter-spacing:-0.01em;">{{ $device->device_name }}</h2>
                        @if($devOnline)
                        <span class="badge {{ $devBadge }}"><span class="pulse-green" style="width:5px;height:5px;"></span> {{ __('app.online') }}</span>
                        @else
                        <span class="badge {{ $devBadge }}">{{ ucfirst($device->status) }}</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:#64748b;font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $device->serial_number }}</span>
                        @if($device->location)
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:#64748b;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $device->location }}
                        </span>
                        @endif
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:#64748b;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                            {{ $device->last_seen?->diffForHumans() ?? __('app.never') }}
                        </span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <div style="display:flex;gap:8px;">
                        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                        <a href="{{ route('web.devices.edit', $device) }}" class="btn-secondary btn-sm" style="font-size:0.7rem;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            {{ __('app.edit') }}
                        </a>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">{{ __('app.firmware') }}</div>
                        <div style="font-size:0.95rem;font-weight:800;color:#0f172a;font-family:monospace;">{{ $device->firmware_version ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Batch --}}
    @if($activeBatch)
    <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1.5px solid #fed7aa;border-radius:14px;padding:0.875rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <span class="pulse-green" style="width:8px;height:8px;background:#f97316;flex-shrink:0;"></span>
        <span style="font-size:0.78rem;color:#92400e;font-weight:600;">Active Batch:</span>
        <a href="{{ route('web.batches.show', $activeBatch) }}" style="font-size:0.875rem;font-weight:800;color:#c2410c;text-decoration:none;letter-spacing:0.01em;">{{ $activeBatch->batch_code }}</a>
        <span class="badge badge-orange">{{ $activeBatch->rice_type }}</span>
        <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.7);border:1px solid #fed7aa;border-radius:8px;padding:3px 10px;font-size:0.75rem;font-weight:700;color:#9a3412;margin-left:auto;">
            {{ number_format($activeBatch->current_moisture ?? 0, 1) }}%
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
            {{ number_format($activeBatch->target_moisture, 1) }}%
        </span>
    </div>
    @endif

    {{-- Tabs --}}
    <div style="display:flex;gap:0.375rem;margin-bottom:1.25rem;flex-wrap:wrap;background:#f1f5f9;border-radius:12px;padding:0.3rem;">
        <button @click="tab='sensor'" :class="tab==='sensor' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            {{ __('app.tab_sensor_data') }}
        </button>
        <button @click="tab='actuator'" :class="tab==='actuator' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M6.34 17.66l-1.41 1.41M1 12h2M21 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41"/></svg>
            {{ __('app.tab_actuator_logs') }}
        </button>
        <button @click="tab='oee'" :class="tab==='oee' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
            {{ __('app.tab_oee') }}
        </button>
    </div>

    {{-- Sensor Tab --}}
    <div x-show="tab==='sensor'">
        <div class="glass-card" style="overflow:hidden;margin-bottom:1rem;">
            <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
                <h3 class="card-header-title">{{ __('app.temp_humidity') }}</h3>
                @if($latestSensor)
                <span style="font-size:0.7rem;color:#94a3b8;">{{ $latestSensor->recorded_at?->diffForHumans() }}</span>
                @endif
            </div>
            <div style="padding:1.25rem;">
                <div id="deviceSensorChart"></div>
            </div>
        </div>

        @if($latestSensor)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0.75rem;margin-bottom:1rem;">
            @foreach([
                [__('app.temp_inside'),      'temperature_inside',  '°C',   'linear-gradient(135deg,#7c2d12,#ea580c)',  '#fff', 'rgba(255,255,255,0.2)', '🌡'],
                [__('app.temp_outside'),     'temperature_outside', '°C',   'linear-gradient(135deg,#78350f,#d97706)',  '#fff', 'rgba(255,255,255,0.2)', '🌤'],
                [__('app.humidity_inside'),  'humidity_inside',     '%',    'linear-gradient(135deg,#1e3a8a,#2563eb)',  '#fff', 'rgba(255,255,255,0.2)', '💧'],
                [__('app.humidity_outside'), 'humidity_outside',    '%',    'linear-gradient(135deg,#075985,#0284c7)',  '#fff', 'rgba(255,255,255,0.2)', '🌊'],
                [__('app.solar_irradiance'), 'solar_irradiance',    ' W/m²','linear-gradient(135deg,#713f12,#ca8a04)',  '#fff', 'rgba(255,255,255,0.2)', '☀'],
                [__('app.grain_moisture'),   'grain_moisture',      '%',    'linear-gradient(135deg,#4c1d95,#7c3aed)',  '#fff', 'rgba(255,255,255,0.2)', '🌾'],
            ] as [$label, $field, $unit, $bg, $color, $border, $icon])
            <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:14px;padding:1rem;position:relative;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="position:absolute;top:-16px;right:-16px;width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
                <div style="font-size:1rem;margin-bottom:4px;position:relative;z-index:1;">{{ $icon }}</div>
                <div style="font-size:0.6rem;color:rgba(255,255,255,0.75);text-transform:uppercase;font-weight:700;letter-spacing:0.07em;position:relative;z-index:1;">{{ $label }}</div>
                <div style="font-size:1.6rem;font-weight:900;color:{{ $color }};line-height:1.1;letter-spacing:-0.02em;margin-top:4px;position:relative;z-index:1;">
                    {{ $latestSensor->$field !== null ? number_format($latestSensor->$field, 1) : '—' }}<span style="font-size:0.85rem;font-weight:600;opacity:0.75;">{{ $latestSensor->$field !== null ? $unit : '' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Actuator Tab --}}
    <div x-show="tab==='actuator'">
        <div class="glass-card" style="overflow:hidden;">
            <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
                <h3 class="card-header-title">{{ __('app.tab_actuator_logs') }}</h3>
                <span style="font-size:0.72rem;color:#64748b;">{{ $actuatorLogs->count() }} {{ __('app.total') }}</span>
            </div>
            @if($actuatorLogs->isEmpty())
            <div style="padding:3rem;text-align:center;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.875rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M6.34 17.66l-1.41 1.41M1 12h2M21 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41"/></svg>
                </div>
                <p style="color:#1e293b;font-weight:600;margin:0 0 0.25rem;">{{ __('app.no_actuator_log_msg') }}</p>
                <p style="color:#64748b;font-size:0.78rem;margin:0;">{{ __('app.actuator_run_hint') }}</p>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="table-dark" id="dt-device-actuator">
                    <thead><tr>
                        <th>{{ __('app.col_actuator') }}</th>
                        <th>{{ __('app.col_command') }}</th>
                        <th>{{ __('app.col_triggered_by') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.col_response') }}</th>
                        <th>{{ __('app.col_time') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($actuatorLogs as $log)
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#0f172a;font-size:0.82rem;">{{ $log->actuator_name ?? ucfirst($log->actuator_type) }}</div>
                                <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;">{{ $log->actuator_type }}</div>
                            </td>
                            <td><span class="badge {{ in_array($log->command,['on','open']) ? 'badge-green' : 'badge-red' }}">{{ strtoupper($log->command) }}</span></td>
                            <td><span class="badge badge-purple">{{ $log->triggered_by }}</span></td>
                            <td>
                                @if($log->status === 'success') <span class="badge badge-green">{{ __('app.success_label') }}</span>
                                @elseif($log->status === 'failed') <span class="badge badge-red">{{ __('app.failed') }}</span>
                                @else <span class="badge badge-yellow">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td style="color:#475569;font-size:0.78rem;font-weight:600;">{{ $log->response_time_ms ? $log->response_time_ms.'ms' : '—' }}</td>
                            <td>
                                <div style="font-size:0.75rem;font-weight:600;color:#0f172a;">{{ $log->executed_at?->format('d M Y') ?? '—' }}</div>
                                <div style="font-size:0.67rem;color:#94a3b8;font-family:monospace;">{{ $log->executed_at?->format('H:i') ?? '' }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

{{-- OEE Tab --}}
<div x-show="tab==='oee'">

    @php
        $oeeColor   = $oeeScore >= 85 ? '#10b981' : ($oeeScore >= 60 ? '#f59e0b' : '#ef4444');
        $oeeGrad    = $oeeScore >= 85
            ? 'linear-gradient(135deg,#064e3b 0%,#065f46 40%,#059669 80%,#10b981 100%)'
            : ($oeeScore >= 60
                ? 'linear-gradient(135deg,#78350f 0%,#92400e 40%,#d97706 80%,#f59e0b 100%)'
                : 'linear-gradient(135deg,#7f1d1d 0%,#991b1b 40%,#dc2626 80%,#ef4444 100%)');
        $oeeLabel   = $oeeScore >= 85 ? __('app.world_class') : ($oeeScore >= 60 ? __('app.average') : __('app.needs_improvement'));
        $oeeIcon    = $oeeScore >= 85 ? '🏆' : ($oeeScore >= 60 ? '📊' : '⚠️');
    @endphp

    {{-- Hero OEE Score --}}
    <div style="background:{{ $oeeGrad }};border-radius:20px;padding:1.75rem;margin-bottom:1.25rem;position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        {{-- Decorative circles --}}
        <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,0.08);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-30px;right:60px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,0.05);pointer-events:none;"></div>

        <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.7);margin-bottom:0.375rem;">{{ __('app.oee_title') }}</div>
                <div style="display:flex;align-items:baseline;gap:0.5rem;">
                    <span style="font-size:3.5rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-0.04em;">{{ $oeeScore }}</span>
                    <span style="font-size:1.5rem;font-weight:700;color:rgba(255,255,255,0.7);">%</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;">
                    <span style="font-size:1rem;">{{ $oeeIcon }}</span>
                    <span style="font-size:0.78rem;font-weight:700;color:rgba(255,255,255,0.9);background:rgba(255,255,255,0.15);border-radius:20px;padding:3px 12px;border:1px solid rgba(255,255,255,0.2);">{{ $oeeLabel }}</span>
                </div>
                <p style="font-size:0.72rem;color:rgba(255,255,255,0.6);margin:0.5rem 0 0;">{{ __('app.oee_last30') }} · {{ $device->device_name }}</p>
            </div>

            {{-- Component mini stats --}}
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                @foreach([
                    ['Availability', $oeeAvailability],
                    ['Performance',  $oeePerformance],
                    ['Quality',      $oeeQuality],
                ] as [$name, $val])
                <div style="background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);border-radius:14px;padding:0.875rem 1.1rem;text-align:center;min-width:80px;">
                    <div style="font-size:1.5rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-0.02em;">{{ $val }}<span style="font-size:0.85rem;opacity:0.7;">%</span></div>
                    <div style="font-size:0.62rem;color:rgba(255,255,255,0.75);font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-top:3px;">{{ $name }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Component detail cards --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem;">
        @php
        $oeeComponents = [
            ['Availability', $oeeAvailability, 'Uptime device vs total waktu', 'oeeDevGaugeA',
             'linear-gradient(135deg,#1e3a5f,#1d4ed8,#3b82f6)', 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
            ['Performance',  $oeePerformance,  'Kecepatan aktual vs ideal', 'oeeDevGaugeP',
             'linear-gradient(135deg,#4c1d95,#7c3aed,#a78bfa)', 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['Quality',      $oeeQuality,      'Output valid vs total output', 'oeeDevGaugeQ',
             'linear-gradient(135deg,#064e3b,#059669,#34d399)', 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 0 0 1.946-.806 3.42 3.42 0 0 1 4.438 0 3.42 3.42 0 0 0 1.946.806 3.42 3.42 0 0 1 3.138 3.138 3.42 3.42 0 0 0 .806 1.946 3.42 3.42 0 0 1 0 4.438 3.42 3.42 0 0 0-.806 1.946 3.42 3.42 0 0 1-3.138 3.138 3.42 3.42 0 0 0-1.946.806 3.42 3.42 0 0 1-4.438 0 3.42 3.42 0 0 0-1.946-.806 3.42 3.42 0 0 1-3.138-3.138 3.42 3.42 0 0 0-.806-1.946 3.42 3.42 0 0 1 0-4.438 3.42 3.42 0 0 0 .806-1.946 3.42 3.42 0 0 1 3.138-3.138z'],
        ];
        @endphp
        @foreach($oeeComponents as [$name, $val, $desc, $id, $grad, $iconPath])
        @php
            $valColor = $val >= 85 ? '#10b981' : ($val >= 60 ? '#f59e0b' : '#ef4444');
            $barW     = $val;
            $barColor = $val >= 85 ? 'linear-gradient(90deg,#059669,#10b981)' : ($val >= 60 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
        @endphp
        <div style="background:{{ $grad }};border-radius:18px;padding:1.25rem;position:relative;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.2);">
            <div style="position:absolute;top:-24px;right:-24px;width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.08);pointer-events:none;"></div>
            <div style="position:relative;z-index:1;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $iconPath }}"/></svg>
                    </div>
                    <div id="{{ $id }}"></div>
                </div>
                <div style="font-size:0.7rem;font-weight:700;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:0.08em;">{{ $name }}</div>
                <div style="font-size:2rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-0.03em;margin:4px 0;">{{ $val }}<span style="font-size:1rem;opacity:0.6;">%</span></div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.55);margin-bottom:0.625rem;">{{ $desc }}</div>
                {{-- Mini progress --}}
                <div style="background:rgba(255,255,255,0.15);border-radius:99px;height:5px;overflow:hidden;">
                    <div style="width:{{ $val }}%;height:100%;background:rgba(255,255,255,0.7);border-radius:99px;"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Batch trend chart --}}
    <div class="glass-card" style="overflow:hidden;">
        <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
            <h3 class="card-header-title">{{ __('app.oee_per_batch') }}</h3>
        </div>
        <div style="padding:1.25rem;">
            @if($oeeBatchTrend->isEmpty())
            <p style="font-size:0.8rem;color:#64748b;text-align:center;padding:2rem 0;margin:0;">{{ __('app.no_batch_data') }}</p>
            @else
            <div id="oeeBatchTrendChart"></div>
            @endif
        </div>
    </div>

</div>
</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const readings = @json($chartReadings);
    const labels   = readings.map(r => r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : '');
    const options  = {
        chart: {
            type: 'line',
            height: 260,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'inherit',
        },
        theme: { mode: 'light' },
        series: [
            { name: 'Temp Inside (°C)',  data: readings.map(r => r.temperature_inside)  },
            { name: 'Temp Outside (°C)', data: readings.map(r => r.temperature_outside) },
            { name: 'Humidity In (%)',   data: readings.map(r => r.humidity_inside)     },
        ],
        colors: ['#f97316', '#fbbf24', '#3b82f6'],
        stroke: { curve: 'smooth', width: 2.5 },
        xaxis: {
            categories: labels,
            labels: { style: { colors: '#64748b', fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: '#64748b', fontSize: '11px' } },
        },
        grid: {
            borderColor: '#e2e8f0',
            strokeDashArray: 4,
        },
        legend: {
            labels: { colors: '#334155' },
        },
        tooltip: {
            theme: 'light',
        },
        markers: { size: 0 },
    };
    new ApexCharts(document.querySelector('#deviceSensorChart'), options).render();

    // --- OEE Gauges ---
    const oeeColorFn = v => v >= 85 ? '#10b981' : (v >= 60 ? '#f59e0b' : '#ef4444');
    const oeeA = {{ $oeeAvailability }};
    const oeeP = {{ $oeePerformance }};
    const oeeQ = {{ $oeeQuality }};

    if (document.querySelector('#oeeDevGaugeA')) window.createGaugeChart('#oeeDevGaugeA', oeeA, oeeA+'%', oeeColorFn(oeeA));
    if (document.querySelector('#oeeDevGaugeP')) window.createGaugeChart('#oeeDevGaugeP', oeeP, oeeP+'%', oeeColorFn(oeeP));
    if (document.querySelector('#oeeDevGaugeQ')) window.createGaugeChart('#oeeDevGaugeQ', oeeQ, oeeQ+'%', oeeColorFn(oeeQ));

    // --- OEE Batch Trend ---
    const batchTrend = @json($oeeBatchTrend);
    if (batchTrend.length && document.querySelector('#oeeBatchTrendChart')) {
        new ApexCharts(document.querySelector('#oeeBatchTrendChart'), {
            ...window.apexDarkConfig,
            chart: { ...window.apexDarkConfig.chart, type: 'bar', height: 220 },
            series: [{ name: 'Performance (%)', data: batchTrend.map(b => b.performance) }],
            xaxis: { ...window.apexDarkConfig.xaxis, categories: batchTrend.map(b => b.batch_code) },
            colors: batchTrend.map(b => b.performance >= 85 ? '#10b981' : (b.performance >= 60 ? '#f59e0b' : '#ef4444')),
            plotOptions: { bar: { borderRadius: 5, columnWidth: '60%', distributed: true } },
            legend: { show: false },
            yaxis: { min: 0, max: 100, labels: { style: { colors: '#475569', fontSize: '11px' }, formatter: v => v+'%' } },
            annotations: {
                yaxis: [
                    { y: 85, borderColor: '#10b981', label: { text: 'World Class', style: { color: '#10b981', background: 'transparent', fontSize: '10px' } } },
                    { y: 60, borderColor: '#f59e0b', label: { text: 'Average', style: { color: '#f59e0b', background: 'transparent', fontSize: '10px' } } },
                ],
            },
        }).render();
    }
});
</script>

<script>
$(document).ready(function () {
    if ($('#dt-device-actuator').length) {
        $('#dt-device-actuator').DataTable({
            paging: false,
            info: false,
            language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_data") }}' }
        });
    }
});
</script>
@endpush

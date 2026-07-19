@extends('layouts.app')
@section('title', $dryingBatch->batch_code)
@section('breadcrumb', __('app.batch_title_label') . ' / '.$dryingBatch->batch_code)

@section('content')

<div x-data="{ tab: 'overview' }">

{{-- Header banner --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.batch_detail_header') }}</div>
            <h2 style="font-size:1.4rem;font-weight:800;color:#fff;margin:0 0 0.375rem;">{{ $dryingBatch->batch_code }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">
                {{ $dryingBatch->rice_type }}{{ $dryingBatch->rice_variety ? ' — '.$dryingBatch->rice_variety : '' }}
                · {{ $dryingBatch->device?->device_name ?? '—' }}
            </p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            @php
                $statusConf = [
                    'waiting'   => ['badge-gray',   __('app.waiting')],
                    'drying'    => ['badge-cyan',   __('app.running')],
                    'paused'    => ['badge-yellow', __('app.paused')],
                    'completed' => ['badge-green',  __('app.completed')],
                    'failed'    => ['badge-red',    __('app.failed')],
                ];
                [$sBadge,$sLabel] = $statusConf[$dryingBatch->status] ?? ['badge-gray', ucfirst($dryingBatch->status)];
            @endphp
            <span class="badge {{ $sBadge }}" style="font-size:0.8rem;padding:0.35rem 0.875rem;">{{ $sLabel }}</span>
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            {{-- Manual AI Trigger --}}
            <form method="POST" action="{{ route('web.ai.trigger') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="batch_id" value="{{ $dryingBatch->id }}">
                <input type="hidden" name="device_id" value="{{ $dryingBatch->device_id }}">
                <button type="submit"
                        onclick="return confirm('Jalankan analisis AI untuk batch ini?')"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;padding:0.375rem 0.875rem;font-size:0.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:0.375rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                    Analisis AI
                </button>
            </form>
            <a href="{{ route('web.batches.edit', $dryingBatch) }}" class="btn-primary btn-sm">{{ __('app.edit') }}</a>
            @endif
            <a href="{{ route('web.batches.index') }}" class="btn-secondary btn-sm">{{ __('app.back') }}</a>
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-left:4px solid #16a34a;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.85rem;color:#166534;display:flex;align-items:center;gap:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.85rem;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ session('error') }}
</div>
@endif

{{-- Key metrics --}}
@php
    $range    = $dryingBatch->initial_moisture - $dryingBatch->target_moisture;
    $done     = $range > 0 ? max(0, min(100, (($dryingBatch->initial_moisture - ($dryingBatch->current_moisture ?? $dryingBatch->initial_moisture)) / $range) * 100)) : 0;
    $duration = $dryingBatch->durationMinutes();
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="metric-card-cyan" style="padding:1.25rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-15px;right:-15px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(255,255,255,0.95);margin-bottom:6px;position:relative;z-index:1;">{{ __('app.moisture_initial') }}</div>
        <div style="font-size:1.75rem;font-weight:800;color:#fff;position:relative;z-index:1;">{{ number_format($dryingBatch->initial_moisture,1) }}<span style="font-size:1rem;opacity:0.7;">%</span></div>
    </div>
    <div class="metric-card-orange" style="padding:1.25rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-15px;right:-15px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(255,255,255,0.95);margin-bottom:6px;position:relative;z-index:1;">{{ __('app.moisture_current') }}</div>
        <div style="font-size:1.75rem;font-weight:800;color:#fff;position:relative;z-index:1;">{{ number_format($dryingBatch->current_moisture ?? $dryingBatch->initial_moisture,1) }}<span style="font-size:1rem;opacity:0.7;">%</span></div>
    </div>
    <div class="metric-card-green" style="padding:1.25rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-15px;right:-15px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(255,255,255,0.95);margin-bottom:6px;position:relative;z-index:1;">{{ __('app.moisture_target') }}</div>
        <div style="font-size:1.75rem;font-weight:800;color:#fff;position:relative;z-index:1;">{{ number_format($dryingBatch->target_moisture,1) }}<span style="font-size:1rem;opacity:0.7;">%</span></div>
    </div>
    <div class="metric-card-purple" style="padding:1.25rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-15px;right:-15px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(255,255,255,0.95);margin-bottom:6px;position:relative;z-index:1;">{{ __('app.duration') }}</div>
        <div style="font-size:1.75rem;font-weight:800;color:#fff;position:relative;z-index:1;">
            @if($duration)
                {{ $duration >= 60 ? floor($duration/60).'j '.($duration%60).'m' : $duration.'m' }}
            @else —
            @endif
        </div>
    </div>
</div>

{{-- Progress bar --}}
<div class="glass-card" style="padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);border:1px solid #93c5fd;display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2.5"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>
            </div>
            <h3 style="font-size:0.875rem;font-weight:700;color:#1e293b;margin:0;">{{ __('app.drying_progress') }}</h3>
        </div>
        <span style="font-size:1.5rem;font-weight:900;color:{{ $done >= 100 ? '#059669' : '#1d4ed8' }};letter-spacing:-0.02em;">{{ number_format($done,1) }}<span style="font-size:0.9rem;font-weight:600;opacity:0.7;">%</span></span>
    </div>
    <div class="progress-track" style="height:12px;border-radius:99px;">
        <div class="progress-fill" style="width:{{ number_format($done,1) }}%;background:{{ $done >= 100 ? 'linear-gradient(90deg,#059669,#10b981)' : 'linear-gradient(90deg,#1d4ed8,#0ea5e9)' }};border-radius:99px;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:0.5rem;">
        <span style="font-size:0.72rem;color:#64748b;font-weight:600;">{{ __('app.moisture_initial') }}: <strong style="color:#0f172a;">{{ number_format($dryingBatch->initial_moisture,1) }}%</strong></span>
        <span style="font-size:0.72rem;color:#64748b;font-weight:600;">{{ __('app.moisture_target') }}: <strong style="color:#059669;">{{ number_format($dryingBatch->target_moisture,1) }}%</strong></span>
    </div>
</div>

{{-- Tabs --}}
<div style="display:flex;gap:0.375rem;margin-bottom:1.25rem;flex-wrap:wrap;background:#f1f5f9;border-radius:12px;padding:0.3rem;">
    <button @click="tab='overview'" :class="tab==='overview' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        {{ __('app.tab_overview') }}
    </button>
    <button @click="tab='sensor'" :class="tab==='sensor' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        {{ __('app.tab_sensor_chart') }}
    </button>
    <button @click="tab='decisions'" :class="tab==='decisions' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
        {{ __('app.tab_ai_decisions') }}
        <span style="background:rgba(255,255,255,0.25);border-radius:20px;padding:1px 6px;font-size:0.65rem;font-weight:800;">{{ $aiDecisions->count() }}</span>
    </button>
    <button @click="tab='actuator'" :class="tab==='actuator' ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'" style="border-radius:8px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M6.34 17.66l-1.41 1.41M1 12h2M21 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41"/></svg>
        {{ __('app.tab_actuator') }}
        <span style="background:rgba(255,255,255,0.25);border-radius:20px;padding:1px 6px;font-size:0.65rem;font-weight:800;">{{ $actuatorLogs->count() }}</span>
    </button>
</div>

{{-- Overview Tab --}}
<div x-show="tab==='overview'">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        {{-- Batch info --}}
        <div class="glass-card" style="overflow:hidden;">
            <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
                <h3 class="card-header-title">{{ __('app.batch_info') }}</h3>
            </div>
            <div style="padding:0.5rem 1.25rem;">
            @php $infoRows = [
                [__('app.batch_code'),     $dryingBatch->batch_code,    'monospace'],
                [__('app.rice_type'),      $dryingBatch->rice_type.($dryingBatch->rice_variety ? ' ('.$dryingBatch->rice_variety.')' : ''), ''],
                [__('app.initial_weight'), $dryingBatch->initial_weight ? number_format($dryingBatch->initial_weight,2).' kg' : '—', ''],
                [__('app.final_weight'),   $dryingBatch->current_weight ? number_format($dryingBatch->current_weight,2).' kg' : '—', ''],
                [__('app.drying_method'),  $dryingBatch->drying_method ?? '—', ''],
                [__('app.operator_name'),  $dryingBatch->operator_name ?? '—', ''],
                [__('app.start_time'),     $dryingBatch->start_time?->format('d M Y H:i') ?? '—', ''],
                [__('app.end_time'),       $dryingBatch->end_time?->format('d M Y H:i') ?? ($dryingBatch->isActive() ? __('app.batch_running_label') : '—'), ''],
            ]; @endphp
            @foreach($infoRows as [$lbl,$val,$font])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.55rem 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:0.78rem;color:#64748b;font-weight:500;">{{ $lbl }}</span>
                <span style="font-size:0.82rem;font-weight:700;color:#0f172a;text-align:right;max-width:60%;{{ $font === 'monospace' ? 'font-family:monospace;color:#1d4ed8;' : '' }}">{{ $val }}</span>
            </div>
            @endforeach
            </div>
        </div>

        {{-- Device info --}}
        <div class="glass-card" style="overflow:hidden;">
            <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
                <h3 class="card-header-title">{{ __('app.device_info') }}</h3>
            </div>
            <div style="padding:1rem 1.25rem;">
            @if($dryingBatch->device)
            <div style="display:flex;align-items:center;gap:0.875rem;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #f1f5f9;">
                <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#f97316,#ea580c);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(249,115,22,0.3);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.92rem;font-weight:800;color:#0f172a;">{{ $dryingBatch->device->device_name }}</div>
                    <div style="font-size:0.68rem;color:#94a3b8;font-family:monospace;margin-top:2px;">{{ $dryingBatch->device->serial_number }}</div>
                </div>
                @if($dryingBatch->device->status === 'online')
                    <span class="badge badge-green"><span class="pulse-green" style="width:5px;height:5px;"></span> Online</span>
                @else
                    <span class="badge badge-red">Offline</span>
                @endif
            </div>
            @php $deviceRows = [
                [__('app.location'),   $dryingBatch->device->location ?? '—'],
                [__('app.ip_address'), $dryingBatch->device->ip_address ?? '—'],
                [__('app.firmware'),   $dryingBatch->device->firmware_version ?? '—'],
                [__('app.last_seen'),  $dryingBatch->device->last_seen?->diffForHumans() ?? '—'],
            ]; @endphp
            @foreach($deviceRows as [$lbl,$val])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:0.78rem;color:#64748b;font-weight:500;">{{ $lbl }}</span>
                <span style="font-size:0.82rem;font-weight:700;color:#0f172a;">{{ $val }}</span>
            </div>
            @endforeach
            <div style="margin-top:1rem;">
                <a href="{{ route('web.devices.show', $dryingBatch->device) }}" class="btn-secondary btn-sm">{{ __('app.view_device') }}</a>
            </div>
            @else
            <p style="color:#64748b;font-size:0.82rem;margin:0;">{{ __('app.no_device_connected') }}</p>
            @endif
            </div>
        </div>
    </div>
</div>

{{-- Sensor Chart Tab --}}
<div x-show="tab==='sensor'">
    @if($chartReadings->count())
    <div class="glass-card" style="padding:1.25rem;">
        <h3 style="font-size:0.875rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">{{ __('app.sensor_readings') }} — {{ $chartReadings->count() }} data points</h3>
        <div id="batchSensorChart"></div>
    </div>
    @else
    <div class="glass-card" style="padding:3rem;text-align:center;">
        <div style="width:64px;height:64px;background:#f1f5f9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <p style="color:#0f172a;font-weight:500;margin:0;">{{ __('app.no_sensor_batch') }}</p>
    </div>
    @endif
</div>

{{-- AI Decisions Tab --}}
<div x-show="tab==='decisions'">
    @if($aiDecisions->isEmpty())
    <div class="glass-card" style="padding:3rem;text-align:center;">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.5"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
        </div>
        <p style="color:#1e293b;font-weight:600;margin:0 0 4px;">{{ __('app.no_decisions_batch') }}</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:0.75rem;">
        @foreach($aiDecisions as $dec)
        @php
            $conf = $dec->confidence_score ? $dec->confidence_score * 100 : null;
            $sCls = ['pending'=>'badge-yellow','executed'=>'badge-green','failed'=>'badge-red','skipped'=>'badge-gray','overridden'=>'badge-orange'][$dec->execution_status] ?? 'badge-gray';
        @endphp
        <div class="glass-card" style="overflow:hidden;">
            <div style="height:3px;background:linear-gradient(90deg,#7c3aed,#a855f7,#c084fc);"></div>
            <div style="padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:1rem;">
                <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 8px rgba(124,58,237,0.3);">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:6px;">
                        <span style="font-size:0.85rem;font-weight:800;color:#1e293b;">{{ ucwords(str_replace('_',' ',$dec->decision_type)) }}</span>
                        <span class="badge {{ $sCls }}">{{ ucfirst($dec->execution_status) }}</span>
                        @if($conf)
                        <span class="badge {{ $conf>=80?'badge-green':($conf>=50?'badge-yellow':'badge-red') }}">{{ number_format($conf,0) }}% conf</span>
                        @endif
                        <span style="margin-left:auto;font-size:0.68rem;color:#94a3b8;font-weight:500;">{{ $dec->decided_at?->format('d M Y H:i') }}</span>
                    </div>
                    <p style="font-size:0.8rem;color:#475569;margin:0;line-height:1.6;background:#f8fafc;border-radius:8px;padding:0.5rem 0.75rem;border-left:3px solid #e2e8f0;">{{ Str::limit($dec->reasoning,150) }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Actuator Tab --}}
<div x-show="tab==='actuator'">
    @if($actuatorLogs->isEmpty())
    <div class="glass-card" style="padding:3rem;text-align:center;">
        <div style="width:64px;height:64px;background:#f1f5f9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M6.34 17.66l-1.41 1.41M1 12h2M21 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41"/></svg>
        </div>
        <p style="color:#0f172a;font-weight:500;margin:0;">{{ __('app.no_actuator_batch') }}</p>
    </div>
    @else
    <div class="glass-card" style="overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table-dark" id="dt-batch-actuator">
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
                            <div style="font-weight:600;color:#1e293b;">{{ $log->actuator_name ?? ucfirst($log->actuator_type) }}</div>
                            <div style="font-size:0.7rem;color:#0f172a;">{{ $log->actuator_type }}</div>
                        </td>
                        <td><span class="badge {{ in_array($log->command,['on','open']) ? 'badge-green' : 'badge-red' }}">{{ strtoupper($log->command) }}</span></td>
                        <td><span class="badge badge-purple">{{ $log->triggered_by }}</span></td>
                        <td>
                            @if($log->status==='success') <span class="badge badge-green">{{ __('app.success_label') }}</span>
                            @elseif($log->status==='failed') <span class="badge badge-red">{{ __('app.failed') }}</span>
                            @else <span class="badge badge-yellow">{{ $log->status }}</span>
                            @endif
                        </td>
                        <td style="color:#1e293b;font-size:0.78rem;">{{ $log->response_time_ms ? $log->response_time_ms.'ms' : '—' }}</td>
                        <td style="color:#1e293b;font-size:0.78rem;">{{ $log->executed_at?->format('d M H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const readings = @json($chartReadings);
    if (!readings.length || !document.querySelector('#batchSensorChart')) return;
    const labels = readings.map(r => r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}) : '');
    new ApexCharts(document.querySelector('#batchSensorChart'), {
        ...window.apexDarkConfig,
        chart: { ...window.apexDarkConfig.chart, type: 'area', height: 280 },
        series: [
            { name: 'Temp Inside (°C)',  data: readings.map(r => r.temperature_inside)  },
            { name: 'Humidity In (%)',   data: readings.map(r => r.humidity_inside)     },
            { name: 'Grain Moisture (%)',data: readings.map(r => r.grain_moisture)      },
        ],
        xaxis: { ...window.apexDarkConfig.xaxis, categories: labels },
        colors: ['#f97316', '#3b82f6', '#a855f7'],
    }).render();
});
</script>

<script>
$(document).ready(function () {
    if ($('#dt-batch-actuator').length) {
        $('#dt-batch-actuator').DataTable({
            paging: false,
            info: false,
            language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_data") }}' }
        });
    }
});
</script>
@endpush

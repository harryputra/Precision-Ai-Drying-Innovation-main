@extends('layouts.app')
@section('title', __('app.ai_decision_detail'))
@section('breadcrumb', __('app.nav_ai_system') . ' / ' . __('app.ai_decisions') . ' / ' . __('app.detail'))

@section('content')
@php
    $typeColors = [
        'open_roof'          => ['#0891b2', '#ecfeff', '#cffafe'],
        'close_roof'         => ['#0369a1', '#eff6ff', '#dbeafe'],
        'start_fan'          => ['#059669', '#f0fdf4', '#bbf7d0'],
        'stop_fan'           => ['#dc2626', '#fef2f2', '#fecaca'],
        'start_heater'       => ['#ea580c', '#fff7ed', '#fed7aa'],
        'stop_heater'        => ['#059669', '#f0fdf4', '#bbf7d0'],
        'alert_operator'     => ['#d97706', '#fffbeb', '#fde68a'],
        'adjust_temperature' => ['#7c3aed', '#f5f3ff', '#ddd6fe'],
        'adjust_airflow'     => ['#0891b2', '#ecfeff', '#cffafe'],
        'pause_drying'       => ['#f59e0b', '#fffbeb', '#fde68a'],
        'resume_drying'      => ['#10b981', '#f0fdf4', '#bbf7d0'],
        'other'              => ['#6366f1', '#eef2ff', '#e0e7ff'],
        'default'            => ['#6366f1', '#eef2ff', '#e0e7ff'],
    ];
    $statusConfig = [
        'pending'    => ['badge-yellow', __('app.pending')],
        'executed'   => ['badge-green',  __('app.executed')],
        'failed'     => ['badge-red',    __('app.failed')],
        'skipped'    => ['badge-gray',   'Skipped'],
        'overridden' => ['badge-orange', __('app.override')],
    ];
    [$iconColor, $iconBg, $iconBorder] = $typeColors[$aiDecision->decision_type] ?? $typeColors['default'];
    [$statusClass, $statusLabel] = $statusConfig[$aiDecision->execution_status] ?? ['badge-gray', ucfirst($aiDecision->execution_status)];
    $conf = $aiDecision->confidence_score ? $aiDecision->confidence_score * 100 : null;
    $confClass = $conf ? ($conf >= 80 ? 'badge-green' : ($conf >= 50 ? 'badge-yellow' : 'badge-red')) : 'badge-gray';
@endphp

{{-- Page header --}}
@php
    $typeGradients = [
        'open_roof'          => 'linear-gradient(135deg,#075985,#0284c7,#38bdf8)',
        'close_roof'         => 'linear-gradient(135deg,#1e3a8a,#1d4ed8,#60a5fa)',
        'start_fan'          => 'linear-gradient(135deg,#064e3b,#059669,#34d399)',
        'stop_fan'           => 'linear-gradient(135deg,#7f1d1d,#dc2626,#f87171)',
        'start_heater'       => 'linear-gradient(135deg,#7c2d12,#ea580c,#fb923c)',
        'stop_heater'        => 'linear-gradient(135deg,#064e3b,#059669,#34d399)',
        'alert_operator'     => 'linear-gradient(135deg,#78350f,#d97706,#fbbf24)',
        'adjust_temperature' => 'linear-gradient(135deg,#4c1d95,#7c3aed,#a78bfa)',
        'adjust_airflow'     => 'linear-gradient(135deg,#0c4a6e,#0891b2,#22d3ee)',
        'pause_drying'       => 'linear-gradient(135deg,#78350f,#f59e0b,#fcd34d)',
        'resume_drying'      => 'linear-gradient(135deg,#065f46,#059669,#6ee7b7)',
        'other'              => 'linear-gradient(135deg,#312e81,#4f46e5,#818cf8)',
        'default'            => 'linear-gradient(135deg,#312e81,#4f46e5,#818cf8)',
    ];
    $heroGrad = $typeGradients[$aiDecision->decision_type] ?? $typeGradients['default'];
@endphp
<div style="background:{{ $heroGrad }};border-radius:20px;padding:1.75rem;margin-bottom:1.25rem;position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,0.08);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-20px;right:80px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);pointer-events:none;"></div>
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.7);margin-bottom:0.375rem;">AI System · {{ __('app.ai_decision_detail') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;letter-spacing:-0.02em;">
                {{ ucwords(str_replace('_', ' ', $aiDecision->decision_type)) }}
            </h2>
            <p style="font-size:0.78rem;color:rgba(255,255,255,0.7);margin:0;">
                Decision #{{ $aiDecision->id }} &mdash; {{ $aiDecision->decided_at?->format('d M Y H:i:s') ?? '—' }}
            </p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <span class="badge {{ $statusClass }}" style="font-size:0.78rem;padding:0.35rem 0.875rem;">{{ $statusLabel }}</span>
            @if($conf !== null)
            <span style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:0.35rem 0.875rem;font-size:0.78rem;font-weight:800;">{{ number_format($conf, 1) }}% conf</span>
            @endif
            <a href="{{ route('web.ai.decisions') }}" class="btn-secondary btn-sm">{{ __('app.back') }}</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;align-items:stretch;">

    {{-- Decision Info --}}
    <div class="glass-card" style="overflow:hidden;display:flex;flex-direction:column;">
        <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
            <h3 class="card-header-title">{{ __('app.ai_decision_info') }}</h3>
        </div>
        <div style="padding:0.25rem 1.25rem;flex:1;">
        @php $infoRows = [
            [__('app.type'),            '<span style="background:'.$iconBg.';border:1px solid '.$iconBorder.';color:'.$iconColor.';padding:2px 10px;border-radius:7px;font-size:0.72rem;font-weight:800;">'.ucwords(str_replace('_',' ',$aiDecision->decision_type)).'</span>', true],
            [__('app.status'),          '<span class="badge '.$statusClass.'">'.$statusLabel.'</span>', true],
            [__('app.nav_devices'),     $aiDecision->device ? '<a href="'.route('web.devices.show',$aiDecision->device).'" style="color:#1d4ed8;font-weight:700;text-decoration:none;">'.$aiDecision->device->device_name.'</a>' : '—', true],
            [__('app.batch_code'),      $aiDecision->batch ? '<a href="'.route('web.batches.show',$aiDecision->batch).'" style="color:#1d4ed8;font-weight:700;text-decoration:none;">'.$aiDecision->batch->batch_code.'</a>' : '—', true],
            [__('app.ai_model_label'),  $aiDecision->ai_model ? '<span style="font-family:monospace;font-size:0.72rem;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;padding:2px 8px;border-radius:5px;font-weight:600;">'.$aiDecision->ai_model.'</span>' : '—', true],
            [__('app.decided_at'),      $aiDecision->decided_at?->format('d M Y H:i:s') ?? '—', false],
            [__('app.executed_at_label'), $aiDecision->executed_at?->format('d M Y H:i:s') ?? '—', false],
        ]; @endphp
        @foreach($infoRows as [$lbl, $val, $isHtml])
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid #f1f5f9;">
            <span style="font-size:0.78rem;color:#64748b;font-weight:500;">{{ $lbl }}</span>
            <span style="font-size:0.8rem;font-weight:700;color:#0f172a;text-align:right;">
                @if($isHtml){!! $val !!}@else{{ $val }}@endif
            </span>
        </div>
        @endforeach
        </div>
    </div>

    {{-- Confidence + Override --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Confidence card --}}
        <div class="glass-card" style="overflow:hidden;flex:1;">
            <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
                <h3 class="card-header-title">{{ __('app.confidence') }}</h3>
                @if($conf !== null)
                <span class="badge {{ $confClass }}">{{ $conf >= 80 ? __('app.confidence_high') : ($conf >= 50 ? __('app.confidence_medium') : __('app.confidence_low')) }}</span>
                @endif
            </div>
            <div style="padding:1.5rem 1.25rem;display:flex;flex-direction:column;justify-content:center;">
            @if($conf !== null)
            @php
                $confColor    = $conf >= 80 ? '#059669' : ($conf >= 50 ? '#d97706' : '#dc2626');
                $confColorEnd = $conf >= 80 ? '#34d399' : ($conf >= 50 ? '#fbbf24' : '#f87171');
                $confBarGrad  = $conf >= 80 ? 'linear-gradient(90deg,#059669,#34d399)' : ($conf >= 50 ? 'linear-gradient(90deg,#d97706,#fbbf24)' : 'linear-gradient(90deg,#dc2626,#f87171)');
            @endphp
            {{-- Score display --}}
            <div style="text-align:center;margin-bottom:1.25rem;">
                <div style="display:inline-block;position:relative;">
                    <span style="font-size:4.5rem;font-weight:900;line-height:1;letter-spacing:-0.05em;background:linear-gradient(135deg,{{ $confColor }},{{ $confColorEnd }});-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">{{ number_format($conf,0) }}</span><span style="font-size:2rem;font-weight:700;background:linear-gradient(135deg,{{ $confColor }},{{ $confColorEnd }});-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">%</span>
                </div>
                <div style="font-size:0.72rem;color:#64748b;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin-top:4px;">{{ $conf >= 80 ? __('app.confidence_high') : ($conf >= 50 ? __('app.confidence_medium') : __('app.confidence_low')) }}</div>
            </div>
            {{-- Progress bar --}}
            <div style="height:12px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:8px;">
                <div style="height:100%;width:{{ $conf }}%;background:{{ $confBarGrad }};border-radius:99px;transition:width 0.8s cubic-bezier(.4,0,.2,1);box-shadow:0 2px 8px {{ $confColor }}55;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:0.62rem;color:#94a3b8;font-weight:600;">
                <span>0%</span><span>50%</span><span>100%</span>
            </div>
            @else
            <p style="color:#64748b;font-size:0.82rem;margin:0;">{{ __('app.no_data') }}</p>
            @endif
            </div>
        </div>

        {{-- Override --}}
        @if($aiDecision->override_reason || $aiDecision->overriddenBy)
        <div class="glass-card" style="overflow:hidden;border-left:4px solid #dc2626;">
            <div class="card-header" style="border-bottom:1px solid #fee2e2;background:linear-gradient(135deg,#fef2f2,#fff);">
                <h3 style="font-size:0.82rem;font-weight:800;color:#dc2626;margin:0;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    {{ __('app.override_info') }}
                </h3>
            </div>
            <div style="padding:1rem 1.25rem;">
                @if($aiDecision->overriddenBy)
                <p style="font-size:0.78rem;color:#64748b;margin:0 0 0.5rem;">By: <strong style="color:#0f172a;">{{ $aiDecision->overriddenBy->name }}</strong></p>
                @endif
                @if($aiDecision->override_reason)
                <p style="font-size:0.78rem;color:#b91c1c;margin:0;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:0.6rem 0.875rem;line-height:1.6;">{{ $aiDecision->override_reason }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reasoning --}}
<div class="glass-card" style="overflow:hidden;margin-bottom:1rem;">
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <h3 class="card-header-title">{{ __('app.reasoning') }}</h3>
    </div>
    <div style="padding:1.25rem;">
        <p style="font-size:0.875rem;color:#374151;line-height:1.8;margin:0;white-space:pre-wrap;background:#f8fafc;border-radius:10px;padding:1rem 1.25rem;border-left:4px solid {{ $iconColor }};">{{ $aiDecision->reasoning ?? '—' }}</p>
    </div>
</div>

{{-- Actuator Logs --}}
@if($aiDecision->actuatorLogs->isNotEmpty())
<div class="glass-card" style="padding:1.25rem;">
    <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#0f172a;margin:0 0 0.75rem;">
        {{ __('app.tab_actuator_logs') }}
        <span style="background:#e2e8f0;color:#1e293b;border-radius:20px;padding:1px 8px;font-size:0.7rem;margin-left:0.5rem;">{{ $aiDecision->actuatorLogs->count() }}</span>
    </h3>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #e2e8f0;">
                    <th style="text-align:left;padding:0.5rem 0.625rem;font-size:0.72rem;font-weight:700;color:#0f172a;text-transform:uppercase;">{{ __('app.col_actuator') }}</th>
                    <th style="text-align:left;padding:0.5rem 0.625rem;font-size:0.72rem;font-weight:700;color:#0f172a;text-transform:uppercase;">{{ __('app.col_command') }}</th>
                    <th style="text-align:left;padding:0.5rem 0.625rem;font-size:0.72rem;font-weight:700;color:#0f172a;text-transform:uppercase;">{{ __('app.status') }}</th>
                    <th style="text-align:left;padding:0.5rem 0.625rem;font-size:0.72rem;font-weight:700;color:#0f172a;text-transform:uppercase;">{{ __('app.col_time') }}</th>
                    <th style="text-align:left;padding:0.5rem 0.625rem;font-size:0.72rem;font-weight:700;color:#0f172a;text-transform:uppercase;">{{ __('app.col_response') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aiDecision->actuatorLogs as $log)
                @php
                    $logStatus = match($log->status ?? '') {
                        'success' => ['badge-green', __('app.success_label')],
                        'failed'  => ['badge-red',   __('app.failed')],
                        'pending' => ['badge-yellow', __('app.pending')],
                        default   => ['badge-gray',   ucfirst($log->status ?? '—')],
                    };
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:0.5rem 0.625rem;font-size:0.78rem;color:#1e293b;font-weight:600;">{{ $log->actuator_type ?? '—' }}</td>
                    <td style="padding:0.5rem 0.625rem;font-size:0.72rem;color:#1e293b;font-family:monospace;">{{ $log->command ?? '—' }}</td>
                    <td style="padding:0.5rem 0.625rem;"><span class="badge {{ $logStatus[0] }}">{{ $logStatus[1] }}</span></td>
                    <td style="padding:0.5rem 0.625rem;font-size:0.75rem;color:#0f172a;">{{ $log->executed_at?->format('d M H:i:s') ?? '—' }}</td>
                    <td style="padding:0.5rem 0.625rem;font-size:0.72rem;color:#0f172a;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->response_message ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

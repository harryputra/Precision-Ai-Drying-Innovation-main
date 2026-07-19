@extends('layouts.app')
@section('title', __('app.ai_decisions'))
@section('breadcrumb', __('app.nav_ai_system') . ' / ' . __('app.ai_decisions'))

@section('content')

{{-- Page header banner --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">AI System</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.25rem;letter-spacing:-0.02em;">{{ __('app.ai_decisions_heading') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.85);margin:0;">{{ __('app.ai_decisions_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;">
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">AI SYSTEM</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
            @php
                $totalDecisions   = \App\Models\AiDecision::count();
                $executedToday    = \App\Models\AiDecision::whereDate('decided_at', today())->where('execution_status','executed')->count();
                $pendingDecisions = \App\Models\AiDecision::where('execution_status','pending')->count();
            @endphp
            @foreach([[__('app.total'), $totalDecisions], [__('app.executed_today'), $executedToday], [__('app.pending'), $pendingDecisions]] as [$lbl, $val])
            <div style="background:rgba(255,255,255,0.18);border-radius:12px;padding:0.75rem 1.1rem;min-width:80px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size:1.5rem;font-weight:900;color:#fff;letter-spacing:-0.02em;">{{ $val }}</div>
                <div style="font-size:0.62rem;color:rgba(255,255,255,0.8);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">{{ $lbl }}</div>
            </div>
            @endforeach
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            <form method="POST" action="{{ route('web.ai.trigger') }}" style="margin:0;">
                @csrf
                <button type="submit"
                        onclick="return confirm('Jalankan analisis AI menggunakan data terbaru?')"
                        style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);border-radius:12px;padding:0.75rem 1.1rem;font-size:0.75rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;backdrop-filter:blur(8px);transition:all 0.15s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                    Analisis AI Sekarang
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-left:4px solid #16a34a;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:#166534;display:flex;align-items:center;gap:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    {{ session('error') }}
</div>
@endif

{{-- Filter + Export card --}}
<div class="glass-card" style="overflow:hidden;margin-bottom:1.25rem;">
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <h3 class="card-header-title">{{ __('app.filter') }}</h3>
        <div style="display:flex;gap:0.4rem;align-items:center;">
            <span style="font-size:0.72rem;font-weight:600;color:#64748b;">Export:</span>
            <a href="{{ route('web.ai.decisions.export.excel', request()->query()) }}"
               style="background:linear-gradient(135deg,#166534,#16a34a);color:#fff;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(22,101,52,0.3);">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>Excel
            </a>
            <a href="{{ route('web.ai.decisions.export.csv', request()->query()) }}"
               style="background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(13,148,136,0.3);">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>CSV
            </a>
            <a href="{{ route('web.ai.decisions.export.pdf', request()->query()) }}"
               style="background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(220,38,38,0.3);">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>PDF
            </a>
        </div>
    </div>
    <div style="padding:1rem 1.25rem;">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="label-dark">{{ __('app.device') }}</label>
                <select name="device_id" class="input-dark" style="width:180px;">
                    <option value="">{{ __('app.all_devices') }}</option>
                    @foreach($devices as $d)
                    <option value="{{ $d->id }}" {{ request('device_id') == $d->id ? 'selected' : '' }}>{{ $d->device_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.decision_type') }}</label>
                <select name="decision_type" class="input-dark" style="width:180px;">
                    <option value="">{{ __('app.all_types') }}</option>
                    @foreach(['open_roof','close_roof','start_fan','stop_fan','start_heater','stop_heater','pause_drying','resume_drying','alert_operator','adjust_temperature','adjust_airflow','other'] as $t)
                    <option value="{{ $t }}" {{ request('decision_type') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.status') }}</label>
                <select name="execution_status" class="input-dark" style="width:150px;">
                    <option value="">{{ __('app.all_status') }}</option>
                    @foreach(['pending','executed','failed','skipped','overridden'] as $s)
                    <option value="{{ $s }}" {{ request('execution_status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:0.4rem;align-items:flex-end;">
                <button type="submit" class="btn-primary btn-sm">{{ __('app.filter') }}</button>
                <a href="{{ route('web.ai.decisions') }}" class="btn-secondary btn-sm">{{ __('app.reset') }}</a>
            </div>
        </form>
    </div>
</div>

{{-- Decision Cards --}}
@if($decisions->isEmpty())
<div class="glass-card" style="padding:3rem;text-align:center;">
    <div style="width:64px;height:64px;background:#f1f5f9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
            <rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>
            <line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/>
            <line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/>
        </svg>
    </div>
    <p style="color:#0f172a;font-weight:500;margin:0 0 0.25rem;">{{ __('app.no_ai_decisions') }}</p>
    <p style="color:#0f172a;font-size:0.8rem;margin:0;">{{ __('app.no_batch_found_hint') }}</p>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;margin-bottom:1.5rem;">
    @foreach($decisions as $decision)
    @php
        $typeIcons = [
            'open_roof'          => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
            'close_roof'         => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
            'start_fan'          => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
            'stop_fan'           => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
            'start_heater'       => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
            'stop_heater'        => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
            'alert_operator'     => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z',
            'adjust_temperature' => 'M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z',
            'adjust_airflow'     => 'M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2',
            'default'            => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
        ];
        $typeColors = [
            'open_roof'          => ['#0891b2', '#ecfeff', '#cffafe'],
            'close_roof'         => ['#0369a1', '#eff6ff', '#dbeafe'],
            'start_fan'          => ['#059669', '#f0fdf4', '#bbf7d0'],
            'stop_fan'           => ['#dc2626', '#fef2f2', '#fecaca'],
            'start_heater'       => ['#ea580c', '#fff7ed', '#fed7aa'],
            'stop_heater'        => ['#64748b', '#f8fafc', '#e2e8f0'],
            'alert_operator'     => ['#d97706', '#fffbeb', '#fde68a'],
            'adjust_temperature' => ['#7c3aed', '#f5f3ff', '#ddd6fe'],
            'adjust_airflow'     => ['#0891b2', '#ecfeff', '#cffafe'],
            'pause_drying'       => ['#f59e0b', '#fffbeb', '#fde68a'],
            'resume_drying'      => ['#10b981', '#f0fdf4', '#bbf7d0'],
            'other'              => ['#6366f1', '#eef2ff', '#e0e7ff'],
            'default'            => ['#6366f1', '#eef2ff', '#e0e7ff'],
        ];
        $icon = $typeIcons[$decision->decision_type] ?? $typeIcons['default'];
        [$iconColor, $iconBg, $iconBorder] = $typeColors[$decision->decision_type] ?? $typeColors['default'];
        $conf = $decision->confidence_score ? $decision->confidence_score * 100 : null;
        $statusConfig = [
            'pending'    => ['badge-yellow', __('app.pending')],
            'executed'   => ['badge-green',  __('app.executed')],
            'failed'     => ['badge-red',    __('app.failed')],
            'skipped'    => ['badge-gray',   'Skipped'],
            'overridden' => ['badge-orange', __('app.override')],
        ];
        [$statusClass, $statusLabel] = $statusConfig[$decision->execution_status] ?? ['badge-gray', ucfirst($decision->execution_status)];
        $confClass = $conf ? ($conf >= 80 ? 'badge-green' : ($conf >= 50 ? 'badge-yellow' : 'badge-red')) : 'badge-gray';
    @endphp
    @php
        $typeGradients = [
            'open_roof'          => ['linear-gradient(135deg,#075985,#0284c7)', '#bae6fd'],
            'close_roof'         => ['linear-gradient(135deg,#1e3a8a,#1d4ed8)', '#bfdbfe'],
            'start_fan'          => ['linear-gradient(135deg,#064e3b,#059669)', '#a7f3d0'],
            'stop_fan'           => ['linear-gradient(135deg,#7f1d1d,#dc2626)', '#fca5a5'],
            'start_heater'       => ['linear-gradient(135deg,#7c2d12,#ea580c)', '#fed7aa'],
            'stop_heater'        => ['linear-gradient(135deg,#1e293b,#475569)', '#e2e8f0'],
            'alert_operator'     => ['linear-gradient(135deg,#78350f,#d97706)', '#fde68a'],
            'adjust_temperature' => ['linear-gradient(135deg,#4c1d95,#7c3aed)', '#ddd6fe'],
            'adjust_airflow'     => ['linear-gradient(135deg,#0c4a6e,#0891b2)', '#a5f3fc'],
            'pause_drying'       => ['linear-gradient(135deg,#78350f,#f59e0b)', '#fde68a'],
            'resume_drying'      => ['linear-gradient(135deg,#065f46,#10b981)', '#a7f3d0'],
            'other'              => ['linear-gradient(135deg,#312e81,#6366f1)', '#c7d2fe'],
            'default'            => ['linear-gradient(135deg,#312e81,#6366f1)', '#c7d2fe'],
        ];
        [$iconGrad, $iconBorder] = $typeGradients[$decision->decision_type] ?? $typeGradients['default'];
    @endphp
    <div style="background:#fff;border-radius:18px;border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,0.1)';this.style.borderColor='{{ $iconBorder }}'"
         onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';this.style.borderColor='#e2e8f0'">

        {{-- Accent bar --}}
        <div style="height:3px;background:{{ $iconGrad }};"></div>

        <div style="padding:1.1rem 1.25rem;">
            {{-- Header row --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.875rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:44px;height:44px;border-radius:12px;background:{{ $iconGrad }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:0.875rem;font-weight:800;color:#0f172a;line-height:1.25;letter-spacing:-0.01em;">{{ ucwords(str_replace('_',' ',$decision->decision_type)) }}</div>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:4px;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                            {{ $decision->device?->device_name ?? '—' }}
                        </div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    @if($conf !== null)
                    <span class="badge {{ $confClass }}" style="font-size:0.6rem;">{{ number_format($conf,0) }}% conf</span>
                    @endif
                </div>
            </div>

            {{-- Reasoning box --}}
            <div style="background:#f8fafc;border-radius:10px;border-left:3px solid {{ str_contains($iconGrad,'#059669') || str_contains($iconGrad,'10b981') ? '#10b981' : (str_contains($iconGrad,'dc2626') ? '#ef4444' : '#6366f1') }};padding:0.6rem 0.875rem;margin-bottom:0.875rem;">
                <p style="font-size:0.78rem;color:#374151;margin:0;line-height:1.6;">{{ Str::limit($decision->reasoning, 110) }}</p>
            </div>

            {{-- Meta row --}}
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
                @if($decision->batch)
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fff7ed;border:1px solid #fed7aa;border-radius:7px;padding:2px 8px;font-size:0.68rem;font-weight:700;color:#c2410c;">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    {{ $decision->batch->batch_code }}
                </span>
                @endif
                @if($decision->ai_model)
                <span style="font-size:0.6rem;color:#7c3aed;font-family:monospace;background:#f5f3ff;border:1px solid #ddd6fe;padding:2px 7px;border-radius:5px;font-weight:600;">{{ $decision->ai_model }}</span>
                @endif
            </div>

            {{-- Footer --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.625rem;border-top:1px solid #f1f5f9;">
                <span style="font-size:0.68rem;color:#94a3b8;display:inline-flex;align-items:center;gap:3px;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                    {{ $decision->decided_at?->format('d M Y H:i') ?? '—' }}
                </span>
                <a href="{{ route('web.ai.decisions.show', $decision) }}"
                   style="font-size:0.72rem;font-weight:700;color:#fff;background:{{ $iconGrad }};padding:4px 12px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                    {{ __('app.detail') }}
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
<div class="pagination-wrapper">{{ $decisions->links() }}</div>
@endif
@endsection

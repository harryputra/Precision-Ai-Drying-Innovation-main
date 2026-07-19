@extends('layouts.app')
@section('title', __('app.nav_batches'))
@section('breadcrumb', __('app.nav_monitoring') . ' / ' . __('app.nav_batches'))

@section('content')

{{-- Page header banner --}}
<div class="page-header-banner" style="padding:1.5rem 1.75rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.process_management') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.batch_list') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.batch_list_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;"><span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">DRYING BATCHES</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span></div>
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            <a href="{{ route('web.batches.create') }}" class="btn-primary btn-sm" style="margin-top:0.625rem;">{{ __('app.add_batch') }}</a>
            @endif
        </div>
        {{-- Summary stats --}}
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            @php
                $totalB    = $batches->total();
                $dryingB   = \App\Models\DryingBatch::where('status','drying')->count();
                $completedB= \App\Models\DryingBatch::where('status','completed')->count();
            @endphp
            @foreach([[__('app.total'),$totalB],[__('app.running'),$dryingB],[__('app.completed'),$completedB]] as [$lbl,$val])
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:0.625rem 1rem;min-width:70px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">
                <div style="font-size:1.25rem;font-weight:800;color:#fff;">{{ $val }}</div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.95);font-weight:500;">{{ $lbl }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Status summary cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;">
    @php
        $statusData = [
            [__('app.total_batch'),     null,        'metric-card-dark',   'M4 6h16M4 10h16M4 14h16M4 18h16', __('app.all_batches')],
            [__('app.waiting'),         'waiting',   'metric-card-orange', 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', __('app.not_started')],
            [__('app.running'),         'drying',    'metric-card-cyan',   'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z', __('app.in_drying')],
            [__('app.completed'),       'completed', 'metric-card-green',  'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', __('app.process_done')],
            [__('app.cancelled'),       'failed',    'metric-card-red',    'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', __('app.process_cancelled')],
        ];
    @endphp
    @foreach($statusData as [$label, $status, $cardClass, $iconPath, $desc])
    @php
        $count = $status
            ? \App\Models\DryingBatch::where('status', $status)->count()
            : \App\Models\DryingBatch::count();
    @endphp
    <div class="{{ $cardClass }}" style="padding:1.4rem 1.25rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.875rem;position:relative;z-index:1;">
            <span style="font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.85);letter-spacing:0.01em;">{{ $label }}</span>
            <div class="metric-card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="{{ $iconPath }}"/>
                </svg>
            </div>
        </div>
        <div style="font-size:2.25rem;font-weight:800;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.02em;">{{ $count }}</div>
        <div style="font-size:0.68rem;color:rgba(255,255,255,0.9);margin-top:6px;position:relative;z-index:1;font-weight:500;">{{ $desc }}</div>
    </div>
    @endforeach
</div>

{{-- Table --}}
<div class="glass-card" style="overflow:hidden;">
    {{-- Row 1: Judul + Export --}}
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <div>
            <h3 class="card-header-title">{{ __('app.batch_list') }}</h3>
            <p style="font-size:0.75rem;color:#64748b;margin:2px 0 0;">{{ $batches->total() }} {{ __('app.batch_found') }}</p>
        </div>
        <div style="display:flex;gap:0.4rem;align-items:center;">
            <span style="font-size:0.72rem;font-weight:600;color:#64748b;">Export:</span>
            <a href="{{ route('web.batches.export.excel', request()->query()) }}"
               style="background:linear-gradient(135deg,#166534,#16a34a);color:#fff;border-radius:8px;padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 6px rgba(22,101,52,0.35);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                Excel
            </a>
            <a href="{{ route('web.batches.export.csv', request()->query()) }}"
               style="background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border-radius:8px;padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 6px rgba(13,148,136,0.35);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                CSV
            </a>
            <a href="{{ route('web.batches.export.pdf', request()->query()) }}"
               style="background:linear-gradient(135deg,#991b1b,#dc2626);color:#fff;border-radius:8px;padding:0.375rem 0.75rem;font-size:0.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 6px rgba(220,38,38,0.35);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                PDF
            </a>
        </div>
    </div>

    {{-- Row 2: Search + date --}}
    <div style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="label-dark">{{ __('app.search_batch') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}" class="input-dark" style="width:200px;">
            </div>
            <div>
                <label class="label-dark">{{ __('app.date_from') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-dark" style="width:155px;">
            </div>
            <div>
                <label class="label-dark">{{ __('app.date_to') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-dark" style="width:155px;">
            </div>
            <div style="display:flex;gap:0.4rem;align-items:flex-end;">
                <button type="submit" class="btn-primary btn-sm">{{ __('app.search_btn') }}</button>
                <a href="{{ route('web.batches.index') }}" class="btn-secondary btn-sm">{{ __('app.reset') }}</a>
            </div>
        </form>
    </div>

    {{-- Row 3: Status filter --}}
    <div style="padding:0.875rem 1.25rem;border-bottom:1px solid #e2e8f0;">
        <form method="GET" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
            @if(request('search'))   <input type="hidden" name="search"    value="{{ request('search') }}">    @endif
            @if(request('date_from'))<input type="hidden" name="date_from" value="{{ request('date_from') }}">@endif
            @if(request('date_to'))  <input type="hidden" name="date_to"   value="{{ request('date_to') }}">  @endif
            <span style="font-size:0.78rem;font-weight:600;color:#64748b;margin-right:0.25rem;">{{ __('app.filter') }}:</span>
            @foreach(['all'=>__('app.all'),'drying'=>__('app.drying'),'paused'=>__('app.paused'),'waiting'=>__('app.waiting'),'completed'=>__('app.completed'),'failed'=>__('app.failed')] as $val=>$label)
            <button type="submit" name="status" value="{{ $val }}"
                    class="{{ (request('status') === $val || (!request('status') && $val === 'all')) ? 'btn-primary btn-sm' : 'btn-secondary btn-sm' }}">
                {{ $label }}
            </button>
            @endforeach
        </form>
    </div>

    @if($batches->isEmpty())
    <div style="padding:3rem;text-align:center;">
        <div style="width:72px;height:72px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">{{ __('app.no_batch_found') }}</p>
        <p style="font-size:0.82rem;color:#64748b;margin:0;">{{ __('app.no_batch_found_hint') }}</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table class="table-dark" id="dt-batches">
            <thead>
                <tr>
                    <th>{{ __('app.batch_code') }}</th>
                    <th>{{ __('app.device') }}</th>
                    <th>{{ __('app.rice_type') }}</th>
                    <th>💧 {{ __('app.current_moisture') }}</th>
                    <th>📊 {{ __('app.progress') }}</th>
                    <th>{{ __('app.drying_method') }}</th>
                    <th>👤 {{ __('app.operator_name') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th>⏱ {{ __('app.duration') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $batch)
                @php
                    $statusConfig = [
                        'waiting'   => ['badge-gray',   __('app.waiting')],
                        'drying'    => ['badge-cyan',   __('app.running')],
                        'paused'    => ['badge-yellow', __('app.paused')],
                        'completed' => ['badge-green',  __('app.completed')],
                        'failed'    => ['badge-red',    __('app.failed')],
                    ];
                    [$badgeCls, $statusLabel] = $statusConfig[$batch->status] ?? ['badge-gray', ucfirst($batch->status)];
                    $range = $batch->initial_moisture - $batch->target_moisture;
                    $done  = $range > 0 ? max(0, min(100, (($batch->initial_moisture - ($batch->current_moisture ?? $batch->initial_moisture)) / $range) * 100)) : 0;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('web.batches.show', $batch) }}"
                           style="font-weight:800;font-size:0.82rem;color:#1d4ed8;text-decoration:none;letter-spacing:0.01em;display:block;">
                            {{ $batch->batch_code }}
                        </a>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:3px;font-family:monospace;">{{ $batch->created_at->format('d M Y') }}</div>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;border-radius:7px;padding:3px 9px;font-size:0.72rem;font-weight:600;color:#374151;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                            {{ $batch->device?->device_name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.82rem;font-weight:700;color:#1e293b;">{{ $batch->rice_type }}</div>
                        @if($batch->rice_variety)
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;">{{ $batch->rice_variety }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#fef2f2,#fee2e2);border:1px solid #fca5a5;border-radius:8px;padding:4px 10px;">
                            <span style="color:#dc2626;font-weight:800;font-size:0.82rem;">{{ number_format($batch->current_moisture ?? $batch->initial_moisture,1) }}%</span>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
                            <span style="color:#059669;font-weight:800;font-size:0.82rem;">{{ number_format($batch->target_moisture,1) }}%</span>
                        </div>
                    </td>
                    <td style="min-width:110px;">
                        <div class="progress-track" style="margin-bottom:4px;">
                            <div class="progress-fill" style="width:{{ number_format($done,1) }}%"></div>
                        </div>
                        <span style="font-size:0.67rem;color:#64748b;font-weight:600;">{{ number_format($done,0) }}% {{ __('app.percent_done') }}</span>
                    </td>
                    <td>
                        @if($batch->drying_method)
                        <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:6px;padding:2px 8px;font-size:0.72rem;font-weight:600;">{{ $batch->drying_method }}</span>
                        @else
                        <span style="color:#94a3b8;font-size:0.8rem;">—</span>
                        @endif
                    </td>
                    <td style="font-size:0.8rem;color:#475569;font-weight:500;">{{ $batch->operator_name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span>
                    </td>
                    <td>
                        @if($batch->durationMinutes())
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.78rem;font-weight:700;color:#374151;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                            {{ $batch->durationMinutes() }} {{ __('app.minutes_short') }}
                        </span>
                        @else
                        <span style="color:#94a3b8;font-size:0.8rem;">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('web.batches.show', $batch) }}" class="btn-secondary btn-sm">{{ __('app.detail') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem 1.25rem;border-top:1px solid #f1f5f9;">
        {{ $batches->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#dt-batches').DataTable({
        paging: false,
        info: false,
        language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_batch_found") }}' }
    });
});
</script>
@endpush

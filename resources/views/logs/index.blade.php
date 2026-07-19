@extends('layouts.app')
@section('title', __('app.system_logs'))
@section('breadcrumb', __('app.nav_system') . ' / ' . __('app.nav_logs'))

@section('content')

@php
$levelCfg = [
    'debug'     => ['linear-gradient(135deg,#1e293b,#475569)', '#e2e8f0', 'badge-gray',   '#64748b', 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'],
    'info'      => ['linear-gradient(135deg,#1e3a8a,#1d4ed8)', '#bfdbfe', 'badge-blue',   '#1d4ed8', 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
    'notice'    => ['linear-gradient(135deg,#0c4a6e,#0891b2)', '#a5f3fc', 'badge-blue',   '#0891b2', 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
    'warning'   => ['linear-gradient(135deg,#78350f,#d97706)', '#fde68a', 'badge-yellow', '#d97706', 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'],
    'error'     => ['linear-gradient(135deg,#7f1d1d,#dc2626)', '#fca5a5', 'badge-red',    '#dc2626', 'M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'],
    'critical'  => ['linear-gradient(135deg,#450a0a,#b91c1c)', '#fca5a5', 'badge-red',    '#b91c1c', 'M12 2L2 7l10 5 10-5-10-5z'],
    'alert'     => ['linear-gradient(135deg,#7f1d1d,#dc2626)', '#fca5a5', 'badge-red',    '#dc2626', 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'],
    'emergency' => ['linear-gradient(135deg,#450a0a,#991b1b)', '#fca5a5', 'badge-red',    '#991b1b', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
];

$total    = $logs->total() ?? 0;
$errorCnt = isset($levelCounts) ? (($levelCounts['error'] ?? 0) + ($levelCounts['critical'] ?? 0) + ($levelCounts['alert'] ?? 0) + ($levelCounts['emergency'] ?? 0)) : 0;
$warnCnt  = isset($levelCounts) ? ($levelCounts['warning'] ?? 0) : 0;
@endphp

{{-- Page header --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.nav_system') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.25rem;letter-spacing:-0.02em;">{{ __('app.system_logs') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.85);margin:0;">System activity, errors & audit trail</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;">
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">LOGS</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
            @foreach([[__('app.entries'), $total], ['Errors', $errorCnt], ['Warnings', $warnCnt]] as [$lbl, $val])
            <div style="background:rgba(255,255,255,0.18);border-radius:12px;padding:0.75rem 1.1rem;min-width:70px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size:1.5rem;font-weight:900;color:#fff;letter-spacing:-0.02em;">{{ $val }}</div>
                <div style="font-size:0.62rem;color:rgba(255,255,255,0.8);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">{{ $lbl }}</div>
            </div>
            @endforeach
            <a href="{{ route('web.logs.export', request()->query()) }}"
               style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);border-radius:12px;padding:0.75rem 1.1rem;font-size:0.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:0.5rem;backdrop-filter:blur(8px);transition:all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.3)'"
               onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                {{ __('app.export_logs') }}
            </a>
        </div>
    </div>
</div>

{{-- Filter card --}}
<div class="glass-card" style="overflow:hidden;margin-bottom:1.25rem;">
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <h3 class="card-header-title">{{ __('app.filter') }}</h3>
        <span style="font-size:0.72rem;color:#64748b;font-weight:600;">{{ $total }} {{ __('app.entries') }}</span>
    </div>
    <div style="padding:1rem 1.25rem;">
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="label-dark">{{ __('app.log_level') }}</label>
                <select name="level" class="input-dark" style="width:150px;">
                    <option value="">{{ __('app.all_levels') }}</option>
                    @foreach($levels as $lv)
                    <option value="{{ $lv }}" {{ request('level') === $lv ? 'selected' : '' }}>{{ ucfirst($lv) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.channel') }}</label>
                <select name="channel" class="input-dark" style="width:160px;">
                    <option value="">{{ __('app.all_channels') }}</option>
                    @foreach($channels as $ch)
                    <option value="{{ $ch }}" {{ request('channel') === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:0.4rem;align-items:flex-end;">
                <button type="submit" class="btn-primary btn-sm">{{ __('app.filter') }}</button>
                <a href="{{ route('web.logs.index') }}" class="btn-secondary btn-sm">{{ __('app.reset') }}</a>
            </div>
        </form>
    </div>
</div>

@if($logs->isEmpty())
<div class="glass-card" style="padding:3.5rem;text-align:center;">
    <div style="width:72px;height:72px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
    </div>
    <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.375rem;">{{ __('app.no_log_found') }}</p>
    <p style="font-size:0.82rem;color:#64748b;margin:0;">{{ __('app.no_log_found_hint') }}</p>
</div>
@else

{{-- Log table card --}}
<div style="background:#fff;border-radius:18px;border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:1.5rem;">

    {{-- Table header bar --}}
    <div style="height:3px;background:linear-gradient(90deg,#1e3a8a,#1d4ed8,#7c3aed,#dc2626);"></div>

    <div style="padding:0.875rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:0.625rem;">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#1e3a8a,#1d4ed8);display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(29,78,216,0.3);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
                <div style="font-size:0.875rem;font-weight:800;color:#0f172a;">{{ __('app.system_logs') }}</div>
                <div style="font-size:0.68rem;color:#94a3b8;">{{ __('app.entries') }}: {{ $total }} · Klik baris untuk detail</div>
            </div>
        </div>
        <div style="font-size:0.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
            {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="table-dark" id="dt-logs" style="margin:0;">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>{{ __('app.log_level') }}</th>
                    <th>{{ __('app.channel') }}</th>
                    <th>Event</th>
                    <th>{{ __('app.log_message') }}</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.device') }}</th>
                    <th>IP</th>
                    <th>{{ __('app.time') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    [$lvGrad, $lvBorder, $lvBadge, $lvColor, $lvIcon] = $levelCfg[$log->level] ?? $levelCfg['info'];
                    $detail = [
                        'level'   => $log->level,
                        'grad'    => $lvGrad,
                        'color'   => $lvColor,
                        'border'  => $lvBorder,
                        'icon'    => $lvIcon,
                        'message' => $log->message,
                        'context' => $log->context ? json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
                        'method'  => $log->method ?? null,
                        'url'     => $log->url ?? null,
                        'ua'      => $log->user_agent ?? null,
                    ];
                @endphp
                <tr class="log-row" data-detail='@json($detail)'
                    style="cursor:pointer;border-left:3px solid {{ $lvBorder }};transition:background 0.15s;">
                    <td style="text-align:center;vertical-align:middle;padding:0.625rem;">
                        <span class="dt-expand-icon"
                              style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;background:{{ $lvBorder }};border:1px solid {{ $lvBorder }};transition:all 0.15s;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="{{ $lvColor }}" stroke-width="3" style="transition:transform 0.2s;"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $lvGrad }};color:#fff;border-radius:6px;padding:2px 8px;font-size:0.65rem;font-weight:800;letter-spacing:0.04em;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,0.2);">
                            {{ strtoupper($log->level) }}
                        </span>
                    </td>
                    <td style="font-size:0.72rem;color:#374151;font-family:monospace;font-weight:600;">{{ $log->channel }}</td>
                    <td style="font-size:0.8rem;font-weight:700;color:#0f172a;">{{ $log->event }}</td>
                    <td style="max-width:240px;"><span style="font-size:0.78rem;color:#374151;display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">{{ $log->message }}</span></td>
                    <td>
                        @if($log->user)
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.72rem;color:#374151;">
                            <span style="width:20px;height:20px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#7c3aed);display:inline-flex;align-items:center;justify-content:center;font-size:0.55rem;font-weight:800;color:#fff;flex-shrink:0;">{{ strtoupper(substr($log->user->name,0,1)) }}</span>
                            {{ $log->user->name }}
                        </span>
                        @else
                        <span style="font-size:0.72rem;color:#94a3b8;">—</span>
                        @endif
                    </td>
                    <td style="font-size:0.72rem;color:#374151;">{{ $log->device?->device_name ?? '—' }}</td>
                    <td style="font-size:0.68rem;color:#475569;font-family:monospace;">{{ $log->ip_address ?? '—' }}</td>
                    <td style="font-size:0.68rem;color:#64748b;white-space:nowrap;">
                        <div style="font-weight:600;color:#374151;">{{ $log->created_at->format('d M Y') }}</div>
                        <div style="color:#94a3b8;">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="padding:1rem 1.25rem;border-top:1px solid #f1f5f9;">
        {{ $logs->links() }}
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
#dt-logs tbody tr.log-row:hover {
    background: #f8fafc !important;
}
#dt-logs tbody tr.dt-row-open {
    background: #f8fafc !important;
}
#dt-logs tbody tr.dt-hasChild td {
    border-bottom: none !important;
}
.log-expand-detail {
    background: #f8fafc;
    border-left: none;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {

    function esc(str) {
        return $('<div>').text(str || '').html();
    }

    function formatDetail(d) {
        var accentColor = d.color || '#1d4ed8';
        var accentGrad  = d.grad  || 'linear-gradient(135deg,#1e3a8a,#1d4ed8)';
        var accentBord  = d.border || '#bfdbfe';

        var html = '<div style="padding:1rem 1.5rem 1.25rem;background:#f8fafc;">';

        /* header */
        html += '<div style="display:flex;align-items:center;gap:0.625rem;margin-bottom:0.875rem;">';
        html +=   '<div style="width:28px;height:28px;border-radius:7px;background:' + accentGrad + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.2);">';
        html +=     '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="' + esc(d.icon || '') + '"/></svg>';
        html +=   '</div>';
        html +=   '<span style="font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:' + accentColor + ';">Detail Log — ' + esc((d.level||'').toUpperCase()) + '</span>';
        html += '</div>';

        /* message */
        html += '<div style="background:#fff;border-radius:10px;border-left:3px solid ' + accentColor + ';padding:0.65rem 0.875rem;margin-bottom:0.75rem;">';
        html +=   '<div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:4px;">Message</div>';
        html +=   '<p style="font-size:0.82rem;color:#1e293b;margin:0;line-height:1.6;">' + esc(d.message) + '</p>';
        html += '</div>';

        /* context */
        if (d.context) {
            html += '<div style="margin-bottom:0.75rem;">';
            html +=   '<div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;margin-bottom:6px;">Context</div>';
            html +=   '<pre style="background:#0f172a;border-radius:10px;padding:0.875rem 1rem;font-size:0.7rem;color:#34d399;overflow-x:auto;margin:0;line-height:1.6;font-family:\'Fira Code\',monospace;">' + esc(d.context) + '</pre>';
            html += '</div>';
        }

        /* request info */
        if (d.url) {
            html += '<div style="display:flex;flex-wrap:wrap;gap:0.5rem;">';
            if (d.method) {
                html += '<span style="font-size:0.65rem;font-weight:800;background:' + accentGrad + ';color:#fff;border-radius:5px;padding:2px 8px;">' + esc(d.method) + '</span>';
            }
            html += '<span style="font-size:0.7rem;color:#475569;font-family:monospace;background:#e2e8f0;border-radius:5px;padding:2px 8px;">' + esc(d.url) + '</span>';
            if (d.ua) {
                html += '<span style="font-size:0.65rem;color:#94a3b8;background:#f1f5f9;border-radius:5px;padding:2px 8px;">' + esc(d.ua) + '</span>';
            }
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    var table = $('#dt-logs').DataTable({
        paging:  false,
        info:    false,
        order:   [],
        columnDefs: [{ orderable: false, targets: 0 }],
        language: {
            search:      '{{ __("app.search") }}:',
            zeroRecords: '{{ __("app.no_log_found") }}'
        }
    });

    $('#dt-logs tbody').on('click', 'tr.log-row', function () {
        var tr   = $(this);
        var row  = table.row(tr);
        var svg  = tr.find('.dt-expand-icon svg');
        var wrap = tr.find('.dt-expand-icon');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('dt-row-open');
            svg.css('transform', 'rotate(0deg)');
        } else {
            var d = {};
            try { d = JSON.parse(tr.attr('data-detail') || '{}'); } catch(e) {}
            row.child('<tr class="log-expand-detail"><td colspan="9" style="padding:0;">' + formatDetail(d) + '</td></tr>').show();
            tr.addClass('dt-row-open');
            svg.css('transform', 'rotate(180deg)');
        }
    });
});
</script>
@endpush

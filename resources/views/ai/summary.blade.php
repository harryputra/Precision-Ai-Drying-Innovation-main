﻿@extends('layouts.app')
@section('title', __('app.ai_summary'))
@section('breadcrumb', __('app.nav_ai_system') . ' / ' . __('app.nav_ai_summary'))

@section('content')

{{-- Header banner --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.nav_ai_system') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;letter-spacing:-0.02em;color:#fff;margin:0 0 0.375rem;">{{ __('app.ai_summary_heading') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.ai_summary_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;">
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">AI Summary</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">Â·</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            @foreach([
                [__('app.total'),          $total],
                [__('app.executed'),       $executed],
                [__('app.avg_confidence'), $avgConfidence ? number_format($avgConfidence * 100, 1).'%' : 'â€”'],
            ] as [$lbl, $val])
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:0.625rem 1rem;min-width:80px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">
                <div style="font-size:1.25rem;font-weight:800;color:#fff;">{{ $val }}</div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.95);font-weight:500;">{{ $lbl }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="stat-cards-grid" style="margin-bottom:1.5rem;" id="ai-summary-stat-cards">
    @php
    $statCards = [
        [__('app.total'),    $total,         'metric-card',        'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2-2h2a2 2 0 0 0 2 2', __('app.all_decisions')],
        [__('app.executed'), $executed,      'metric-card-green',  'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',                                                                                                     __('app.executed')],
        [__('app.pending'),  $pending,       'metric-card-orange', 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',                                                                                                        __('app.waiting')],
        [__('app.failed'),   $failed,        'metric-card-red',    'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',                                                                              __('app.failed')],
        [__('app.override'), $overridden,    'metric-card-purple', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',                 __('app.override')],
        [__('app.valid'),    $highConfidence,'metric-card-cyan',   'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 0 0 .95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 0 0-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 0 0-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 0 0-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 0 0 .951-.69l1.519-4.674z', 'â‰¥80% conf'],
    ];
    @endphp
    @foreach($statCards as [$label, $value, $cardClass, $icon, $sub])
    <div class="{{ $cardClass }}" style="padding:1.5rem 1.25rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.875rem;position:relative;z-index:1;">
            <span style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;">{{ $label }}</span>
            <div class="metric-card-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="2"><path d="{{ $icon }}"/></svg>
            </div>
        </div>
        <div style="font-size:2.5rem;font-weight:900;color:#fff;line-height:1;position:relative;z-index:1;letter-spacing:-0.03em;">{{ $value }}</div>
        <div style="font-size:0.65rem;color:rgba(255,255,255,0.65);margin-top:8px;position:relative;z-index:1;font-weight:500;">{{ $sub }}</div>
    </div>
    @endforeach
</div>

{{-- Charts + breakdowns: 2×2 grid --}}
{{-- Baris atas: Jenis Keputusan (full width) --}}
{{-- Baris bawah: Status Eksekusi | Model AI --}}

{{-- Full width: Keyakinan --}}
<div class="glass-card" style="overflow:hidden;margin-bottom:1rem;">
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <h3 class="card-header-title">{{ __('app.confidence') }}</h3>
        @if(!collect($confBuckets)->sum())
        @else
        <span style="font-size:0.65rem;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:6px;padding:2px 8px;font-weight:700;">{{ collect($confBuckets)->sum() }} {{ __('app.ai_decisions') }}</span>
        @endif
    </div>
    <div style="padding:0.75rem 1.25rem 1.25rem;">
        @php
        $confBucketRows = [
            ['0–20%',   $confBuckets['0-20%'],   'linear-gradient(90deg,#dc2626,#f87171)', '#dc2626', '#fef2f2'],
            ['20–40%',  $confBuckets['20-40%'],  'linear-gradient(90deg,#f97316,#fb923c)', '#f97316', '#fff7ed'],
            ['40–60%',  $confBuckets['40-60%'],  'linear-gradient(90deg,#d97706,#fbbf24)', '#d97706', '#fffbeb'],
            ['60–80%',  $confBuckets['60-80%'],  'linear-gradient(90deg,#10b981,#34d399)', '#10b981', '#f0fdf4'],
            ['80–100%', $confBuckets['80-100%'], 'linear-gradient(90deg,#059669,#6ee7b7)', '#059669', '#ecfdf5'],
        ];
        $maxConf   = max(array_values($confBuckets)) ?: 1;
        $totalConf = array_sum($confBuckets) ?: 1;
        @endphp
        @foreach($confBucketRows as [$label, $count, $grad, $clr, $bg])
        @php
            $pct    = $count / $maxConf * 100;
            $pctLbl = number_format($count / $totalConf * 100, 1);
        @endphp
        <div style="margin-bottom:0.875rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></div>
                    <span style="font-size:0.75rem;font-weight:700;color:#0f172a;">{{ $label }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.68rem;color:#94a3b8;">{{ $pctLbl }}%</span>
                    <span style="font-size:0.78rem;font-weight:800;color:{{ $clr }};background:{{ $bg }};border-radius:8px;padding:1px 8px;border:1px solid {{ $clr }}33;">{{ $count }}</span>
                </div>
            </div>
            <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                <div style="width:{{ number_format($pct,1) }}%;height:100%;background:{{ $grad }};border-radius:99px;transition:width 0.5s ease;"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Full width: Jenis Keputusan --}}
<div class="glass-card" style="overflow:hidden;margin-bottom:1rem;">
    <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
        <h3 class="card-header-title">{{ __('app.decision_type') }}</h3>
        @if(!$byType->isEmpty())
        <span style="font-size:0.65rem;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:6px;padding:2px 8px;font-weight:700;">{{ $byType->count() }} types</span>
        @endif
    </div>
    <div style="padding:0.75rem 1.25rem 1.25rem;">
        @if($byType->isEmpty())
        <p style="font-size:0.8rem;color:#94a3b8;padding:0.5rem 0;">{{ __('app.no_data') }}</p>
        @else
        @php $maxType = $byType->max('total'); $totalType = $byType->sum('total'); @endphp
        @foreach($byType as $i => $row)
        @php
            $pct    = $maxType > 0 ? ($row->total / $maxType * 100) : 0;
            $pctLbl = $totalType > 0 ? number_format($row->total / $totalType * 100, 1) : '0';
            $typeGrads = ['linear-gradient(90deg,#7c3aed,#a855f7)','linear-gradient(90deg,#0891b2,#22d3ee)','linear-gradient(90deg,#059669,#34d399)','linear-gradient(90deg,#ea580c,#fb923c)','linear-gradient(90deg,#dc2626,#f87171)','linear-gradient(90deg,#d97706,#fbbf24)'];
            $grad = $typeGrads[$i % count($typeGrads)];
        @endphp
        <div style="margin-bottom:0.875rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <span style="font-size:0.75rem;font-weight:700;color:#0f172a;">{{ ucwords(str_replace('_',' ',$row->decision_type)) }}</span>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.68rem;color:#94a3b8;">{{ $pctLbl }}%</span>
                    <span style="font-size:0.78rem;font-weight:800;color:#7c3aed;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:1px 8px;">{{ $row->total }}</span>
                </div>
            </div>
            <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                <div style="width:{{ number_format($pct,1) }}%;height:100%;background:{{ $grad }};border-radius:99px;transition:width 0.5s ease;"></div>
            </div>
        </div>
        @endforeach
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
    <div class="glass-card" style="overflow:hidden;">
        <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
            <h3 class="card-header-title">{{ __('app.execution_status') }}</h3>
        </div>
        <div style="padding:0.75rem 1.25rem 1.25rem;">
            @php
            $statusStyles = [
                'executed'   => ['linear-gradient(90deg,#059669,#34d399)', '#f0fdf4', '#059669'],
                'pending'    => ['linear-gradient(90deg,#d97706,#fbbf24)', '#fffbeb', '#d97706'],
                'failed'     => ['linear-gradient(90deg,#dc2626,#f87171)', '#fef2f2', '#dc2626'],
                'skipped'    => ['linear-gradient(90deg,#64748b,#94a3b8)', '#f8fafc', '#64748b'],
                'overridden' => ['linear-gradient(90deg,#7c3aed,#a855f7)', '#f5f3ff', '#7c3aed'],
            ];
            $maxStatus = $byStatus->max('total');
            $totalStatus = $byStatus->sum('total');
            @endphp
            @foreach($byStatus as $row)
            @php
                $pct    = $maxStatus > 0 ? ($row->total / $maxStatus * 100) : 0;
                $pctLbl = $totalStatus > 0 ? number_format($row->total / $totalStatus * 100, 1) : '0';
                [$grad, $bg, $clr] = $statusStyles[$row->execution_status] ?? ['linear-gradient(90deg,#94a3b8,#cbd5e1)', '#f8fafc', '#94a3b8'];
                $statusLabels = ['executed'=>__('app.executed'),'pending'=>__('app.pending'),'failed'=>__('app.failed'),'skipped'=>'Skipped','overridden'=>__('app.override')];
                $statusLabel = $statusLabels[$row->execution_status] ?? ucfirst($row->execution_status);
            @endphp
            <div style="margin-bottom:0.875rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></div>
                        <span style="font-size:0.75rem;font-weight:700;color:#0f172a;">{{ $statusLabel }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.68rem;color:#94a3b8;">{{ $pctLbl }}%</span>
                        <span style="font-size:0.78rem;font-weight:800;color:{{ $clr }};background:{{ $bg }};border-radius:8px;padding:1px 8px;border:1px solid {{ $clr }}33;">{{ $row->total }}</span>
                    </div>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="width:{{ number_format($pct,1) }}%;height:100%;background:{{ $grad }};border-radius:99px;transition:width 0.5s ease;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- BAWAH KANAN: Model AI --}}
    <div class="glass-card" style="overflow:hidden;">
        <div class="card-header" style="border-bottom:1px solid #e2e8f0;">
            <h3 class="card-header-title">{{ __('app.ai_model_label') }}</h3>
            @if(!$byModel->isEmpty())
            <span style="font-size:0.65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;padding:2px 8px;font-weight:700;">{{ $byModel->count() }} models</span>
            @endif
        </div>
        <div style="padding:0.75rem 1.25rem 1.25rem;">
            @if($byModel->isEmpty())
            <p style="font-size:0.8rem;color:#94a3b8;padding:0.5rem 0;">{{ __('app.no_data') }}</p>
            @else
            @php $maxModel = $byModel->max('total'); $totalModel = $byModel->sum('total'); @endphp
            @foreach($byModel as $row)
            @php
                $pct    = $maxModel > 0 ? ($row->total / $maxModel * 100) : 0;
                $pctLbl = $totalModel > 0 ? number_format($row->total / $totalModel * 100, 1) : '0';
            @endphp
            <div style="margin-bottom:0.875rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                    <span style="font-size:0.72rem;font-weight:700;font-family:monospace;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:2px 8px;border-radius:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;">{{ $row->ai_model }}</span>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.68rem;color:#94a3b8;">{{ $pctLbl }}%</span>
                        <span style="font-size:0.78rem;font-weight:800;color:#0369a1;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1px 8px;">{{ $row->total }}</span>
                    </div>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="width:{{ number_format($pct,1) }}%;height:100%;background:linear-gradient(90deg,#0369a1,#06b6d4);border-radius:99px;transition:width 0.5s ease;"></div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

</div>

{{-- Trend Harian (full width) --}}
<div class="glass-card" style="padding:1rem;margin-bottom:1.5rem;">
    <div class="card-header" style="padding:0 0 0.75rem;">
        <h3 class="card-header-title">{{ __('app.sensor_trend') }} (14 {{ __('app.date') }})</h3>
    </div>
    <div id="trendChart" style="width:100%;overflow:hidden;"></div>
</div>

{{-- Recent overrides --}}
@if($recentOverrides->isNotEmpty())
<div class="glass-card ai-summary-overrides" style="overflow:hidden;margin-bottom:1.5rem;">
    <div class="card-header">
        <h3 class="card-header-title">{{ __('app.recent_overrides') }}</h3>
        <span style="font-size:0.75rem;color:#64748b;">{{ $overridden }} total</span>
    </div>
    <div class="table-responsive">
        <table class="table-dark" id="dt-ai-summary">
            <thead>
                <tr>
                    <th>{{ __('app.type') }}</th>
                    <th>{{ __('app.confidence') }}</th>
                    <th>{{ __('app.override_by') }}</th>
                    <th>{{ __('app.override') }}</th>
                    <th>{{ __('app.time') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentOverrides as $d)
                <tr>
                    <td>
                        <a href="{{ route('web.ai.decisions.show', $d) }}" style="color:#7c3aed;font-weight:600;text-decoration:none;">
                            {{ ucwords(str_replace('_',' ',$d->decision_type)) }}
                        </a>
                    </td>
                    <td>
                        @if($d->confidence_score)
                        @php $conf = $d->confidence_score * 100; @endphp
                        <span class="badge {{ $conf >= 80 ? 'badge-green' : ($conf >= 50 ? 'badge-yellow' : 'badge-red') }}">
                            {{ number_format($conf, 0) }}%
                        </span>
                        @else â€”
                        @endif
                    </td>
                    <td style="color:#0f172a;">{{ $d->overriddenBy?->name ?? 'â€”' }}</td>
                    <td style="color:#475569;font-size:0.78rem;">{{ Str::limit($d->override_reason, 60) }}</td>
                    <td style="color:#64748b;font-size:0.78rem;white-space:nowrap;">{{ $d->decided_at?->format('d M Y H:i') ?? 'â€”' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
    <a href="{{ route('web.ai.decisions') }}" class="btn-primary btn-sm">{{ __('app.view_all') }}</a>
    <a href="{{ route('web.ai.chat') }}" class="btn-secondary btn-sm">{{ __('app.ai_chat') }}</a>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Daily trend chart
    const trendData  = @json($dailyTrend);
    const trendDates = trendData.map(r => r.date);
    const trendTotal = trendData.map(r => r.total);
    const trendConf  = trendData.map(r => r.avg_conf ? +(parseFloat(r.avg_conf) * 100).toFixed(1) : null);

    new ApexCharts(document.querySelector('#trendChart'), {
        ...window.apexDarkConfig,
        chart: { ...window.apexDarkConfig.chart, type: 'area', height: 220 },
        series: [
            { name: @json(__('app.ai_decisions')), data: trendTotal },
            { name: 'Avg Confidence (%)', data: trendConf },
        ],
        xaxis: { ...window.apexDarkConfig.xaxis, categories: trendDates },
        colors: ['#7c3aed', '#10b981'],
        yaxis: [
            { labels: { style: { colors: '#475569', fontSize: '11px' } } },
            { opposite: true, min: 0, max: 100, labels: { style: { colors: '#475569', fontSize: '11px' }, formatter: v => v ? v+'%' : '' } },
        ],
    }).render();


});
</script>
<style>
/* AI Summary responsive grids */
.ai-summary-charts    { grid-template-columns: 1fr; }
.ai-summary-breakdown { grid-template-columns: 1fr; }

@media (min-width: 640px) {
    .ai-summary-charts { grid-template-columns: 1fr 1fr; }
}
@media (min-width: 768px) {
    .ai-summary-breakdown { grid-template-columns: 1fr 1fr; }
}
@media (min-width: 1024px) {
    .ai-summary-breakdown { grid-template-columns: 1fr 1fr 1fr; }
    #ai-summary-stat-cards { grid-template-columns: repeat(6, 1fr); }
    #conf-bucket-cards     { grid-template-columns: repeat(5, 1fr); }
}

/* Override table in summary on small screens */
@media (max-width: 639px) {
    .ai-summary-overrides .table-responsive { font-size: 0.72rem; }
}
</style>

<script>
$(document).ready(function () {
    if ($('#dt-ai-summary').length) {
        $('#dt-ai-summary').DataTable({
            paging: false,
            info: false,
            language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_data") }}' }
        });
    }
});
</script>
@endpush

@extends('layouts.viewer')
@section('title', 'Riwayat Batch')
@section('content')

{{-- ── PAGE HEADER ──────────────────────────────────────── --}}
<div class="page-header">
    <div class="ph-left">
        <div class="ph-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div>
            <div class="ph-title">Riwayat Pengeringan</div>
            <div class="ph-sub">Semua batch yang pernah diproses</div>
        </div>
    </div>
    <span class="ph-count">{{ $batches->total() }} batch</span>
</div>

{{-- ── BATCH LIST ───────────────────────────────────────── --}}
@forelse($batches as $batch)
@php
    $statusMap = [
        'drying'    => ['label'=>'Sedang Dikeringkan', 'cls'=>'bs-drying',   'border'=>'#22c55e'],
        'paused'    => ['label'=>'Dijeda',              'cls'=>'bs-paused',   'border'=>'#f59e0b'],
        'completed' => ['label'=>'Selesai',             'cls'=>'bs-complete', 'border'=>'#60a5fa'],
        'waiting'   => ['label'=>'Menunggu',            'cls'=>'bs-waiting',  'border'=>'#d1d5db'],
        'failed'    => ['label'=>'Gagal',               'cls'=>'bs-failed',   'border'=>'#f87171'],
    ];
    $s = $statusMap[$batch->status] ?? ['label'=>ucfirst($batch->status),'cls'=>'bs-waiting','border'=>'#d1d5db'];

    $progress = 0;
    if ($batch->initial_moisture > $batch->target_moisture) {
        $reduced  = $batch->initial_moisture - ($batch->current_moisture ?? $batch->initial_moisture);
        $total    = $batch->initial_moisture - $batch->target_moisture;
        $progress = min(100, max(0, round($reduced / $total * 100)));
    }

    $durationHours = $batch->durationMinutes() ? round($batch->durationMinutes() / 60, 1) : null;
@endphp

<div class="batch-card bc-{{ $batch->status }}">
    {{-- HEADER ROW --}}
    <div class="bc-header">
        <div class="bc-left">
            <div class="bc-code">{{ $batch->batch_code }}</div>
            <div class="bc-meta">
                {{ $batch->rice_variety ?? $batch->rice_type ?? 'Gabah' }}
                &middot; {{ $batch->initial_weight }} kg
                @if($batch->petani_name)
                    &middot; {{ $batch->petani_name }}
                @endif
            </div>
        </div>
        <span class="batch-badge {{ $s['cls'] }}">{{ $s['label'] }}</span>
    </div>

    {{-- MOISTURE PROGRESS --}}
    <div class="bc-moisture">
        <div class="bcm-nums">
            <div class="bcm-current">
                <span class="bcm-val">{{ number_format($batch->current_moisture ?? $batch->initial_moisture, 1) }}%</span>
                <span class="bcm-lbl">saat ini</span>
            </div>
            <div class="bcm-range">
                <span class="bcm-init">Awal {{ $batch->initial_moisture }}%</span>
                <span class="bcm-target">Target ≤ {{ $batch->target_moisture }}%</span>
            </div>
        </div>
        <div class="bc-track">
            <div class="bc-fill bc-fill-{{ $batch->status }}" style="width:{{ $progress }}%">
                @if($progress > 10)<span class="bc-pct">{{ $progress }}%</span>@endif
            </div>
        </div>
    </div>

    {{-- STATS ROW --}}
    <div class="bc-stats">
        @if($batch->start_time)
        <div class="bc-stat">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ $batch->start_time->format('d M Y') }}
        </div>
        @endif
        @if($batch->end_time)
        <div class="bc-stat">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            {{ $batch->end_time->format('d M Y') }}
        </div>
        @endif
        @if($durationHours)
        <div class="bc-stat">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ $durationHours }} jam
        </div>
        @endif
        @if($batch->initial_weight)
        <div class="bc-stat">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            {{ $batch->initial_weight }} kg
        </div>
        @endif
    </div>
</div>

@empty
<div class="empty-state">
    <div class="es-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
    </div>
    <div class="es-title">Belum ada riwayat</div>
    <div class="es-sub">Batch pengeringan akan muncul di sini setelah proses dimulai.</div>
</div>
@endforelse

{{-- ── PAGINATION ───────────────────────────────────────── --}}
@if($batches->hasPages())
<div class="pagination-wrap">
    {{ $batches->links() }}
</div>
@endif

@endsection

@push('styles')
<style>
/* ─── PAGE HEADER ───────────────────────────────────────── */
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.ph-left  { display: flex; align-items: center; gap: 12px; }
.ph-icon  {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, #166534, #22c55e);
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}
.ph-title { font-size: 1.05rem; font-weight: 800; color: #1f2937; }
.ph-sub   { font-size: .76rem; color: #9ca3af; margin-top: 2px; }
.ph-count {
    font-size: .78rem; font-weight: 700;
    background: #f0fdf4; color: #166534;
    padding: 5px 12px; border-radius: 999px;
    border: 1px solid #bbf7d0;
}

/* ─── BATCH CARD ────────────────────────────────────────── */
.batch-card {
    background: #fff;
    border-radius: 20px;
    padding: 18px 20px;
    margin-bottom: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border-left: 4px solid #e5e7eb;
    border: 1px solid rgba(0,0,0,.05);
    transition: box-shadow .15s, transform .15s;
}
.batch-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); transform: translateY(-2px); }

.bc-drying    { border-left: 4px solid #22c55e !important; }
.bc-paused    { border-left: 4px solid #f59e0b !important; }
.bc-completed { border-left: 4px solid #60a5fa !important; }
.bc-waiting   { border-left: 4px solid #d1d5db !important; }
.bc-failed    { border-left: 4px solid #f87171 !important; }

.bc-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 14px;
}
.bc-code { font-weight: 800; font-size: .97rem; color: #1f2937; font-family: 'Courier New', monospace; }
.bc-meta { font-size: .77rem; color: #6b7280; margin-top: 3px; }

/* ─── BATCH BADGES ──────────────────────────────────────── */
.batch-badge {
    font-size: .72rem; font-weight: 700;
    padding: 4px 11px; border-radius: 999px; white-space: nowrap;
}
.bs-drying   { background: #dcfce7; color: #166534; }
.bs-paused   { background: #fef9c3; color: #854d0e; }
.bs-complete { background: #dbeafe; color: #1e40af; }
.bs-waiting  { background: #f3f4f6; color: #374151; }
.bs-failed   { background: #fee2e2; color: #991b1b; }

/* ─── MOISTURE SECTION ──────────────────────────────────── */
.bc-moisture { margin-bottom: 12px; }
.bcm-nums {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-bottom: 8px;
}
.bcm-current { display: flex; align-items: baseline; gap: 4px; }
.bcm-val  { font-size: 1.6rem; font-weight: 800; color: #15803d; line-height: 1; }
.bcm-lbl  { font-size: .72rem; color: #9ca3af; }
.bcm-range{ display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.bcm-init  { font-size: .72rem; color: #9ca3af; }
.bcm-target{ font-size: .75rem; font-weight: 700; color: #166534; }

.bc-track {
    height: 10px; background: #f3f4f6; border-radius: 5px; overflow: hidden;
}
.bc-fill {
    height: 100%; border-radius: 5px;
    transition: width .8s ease;
    display: flex; align-items: center; justify-content: flex-end;
    padding-right: 6px;
}
.bc-pct { font-size: .58rem; font-weight: 800; color: #fff; }

.bc-fill-drying    { background: linear-gradient(90deg, #15803d, #22c55e); }
.bc-fill-paused    { background: linear-gradient(90deg, #b45309, #f59e0b); }
.bc-fill-completed { background: linear-gradient(90deg, #1d4ed8, #60a5fa); }
.bc-fill-waiting   { background: #d1d5db; }
.bc-fill-failed    { background: linear-gradient(90deg, #b91c1c, #f87171); }

/* ─── STATS ROW ─────────────────────────────────────────── */
.bc-stats {
    display: flex; flex-wrap: wrap; gap: 10px;
    border-top: 1px solid #f3f4f6; padding-top: 10px;
}
.bc-stat {
    display: flex; align-items: center; gap: 5px;
    font-size: .75rem; color: #6b7280;
}
.bc-stat svg { opacity: .6; flex-shrink: 0; }

/* ─── EMPTY STATE ───────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 60px 20px;
    color: #9ca3af;
}
.es-icon {
    width: 80px; height: 80px; border-radius: 20px;
    background: #f3f4f6; margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center;
    color: #d1d5db;
}
.es-title { font-size: 1rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
.es-sub   { font-size: .83rem; color: #9ca3af; max-width: 260px; margin: 0 auto; }

/* ─── PAGINATION ────────────────────────────────────────── */
.pagination-wrap { margin-top: 8px; }

/* ─── RESPONSIVE ────────────────────────────────────────── */
@media (max-width: 480px) {
    .bcm-val  { font-size: 1.3rem; }
    .ph-title { font-size: .92rem; }
}
</style>
@endpush

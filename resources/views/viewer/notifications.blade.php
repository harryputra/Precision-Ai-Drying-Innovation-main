@extends('layouts.viewer')
@section('title', 'Notifikasi')
@section('content')

{{-- ── PAGE HEADER ──────────────────────────────────────── --}}
<div class="page-header">
    <div class="ph-left">
        <div class="ph-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div>
            <div class="ph-title">Notifikasi</div>
            <div class="ph-sub">Pemberitahuan sistem pengeringan</div>
        </div>
    </div>
    @php $totalUnread = $notifications->where('read_at', null)->count(); @endphp
    @if($totalUnread > 0)
        <span class="ph-badge-unread">{{ $totalUnread }} belum dibaca</span>
    @else
        <span class="ph-badge-read">Semua terbaca</span>
    @endif
</div>

{{-- ── NOTIFICATION LIST ────────────────────────────────── --}}
@forelse($notifications as $notif)
@php
    $typeMap = [
        'alert'   => ['icon-bg'=>'#fee2e2','icon-color'=>'#dc2626','border'=>'#fca5a5','label-bg'=>'#fee2e2','label-color'=>'#991b1b','label'=>'Peringatan'],
        'warning' => ['icon-bg'=>'#fef3c7','icon-color'=>'#d97706','border'=>'#fde68a','label-bg'=>'#fef9c3','label-color'=>'#854d0e','label'=>'Peringatan'],
        'success' => ['icon-bg'=>'#dcfce7','icon-color'=>'#16a34a','border'=>'#86efac','label-bg'=>'#dcfce7','label-color'=>'#166534','label'=>'Berhasil'],
        'info'    => ['icon-bg'=>'#dbeafe','icon-color'=>'#2563eb','border'=>'#93c5fd','label-bg'=>'#dbeafe','label-color'=>'#1e40af','label'=>'Info'],
        'error'   => ['icon-bg'=>'#fee2e2','icon-color'=>'#dc2626','border'=>'#fca5a5','label-bg'=>'#fee2e2','label-color'=>'#991b1b','label'=>'Error'],
    ];
    $t = $typeMap[$notif->type] ?? ['icon-bg'=>'#f3f4f6','icon-color'=>'#6b7280','border'=>'#e5e7eb','label-bg'=>'#f3f4f6','label-color'=>'#374151','label'=>'Info'];
    $isNew = !$notif->read_at;
@endphp

<div class="notif-card {{ $isNew ? 'notif-new' : 'notif-read' }}" style="border-left-color: {{ $t['border'] }}">
    {{-- icon --}}
    <div class="notif-icon" style="background: {{ $t['icon-bg'] }}; color: {{ $t['icon-color'] }}">
        @if($notif->type === 'alert' || $notif->type === 'error')
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        @elseif($notif->type === 'warning')
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        @elseif($notif->type === 'success')
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        @else
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        @endif
    </div>

    {{-- content --}}
    <div class="notif-body">
        <div class="notif-top">
            <div class="notif-meta">
                <span class="notif-type-badge" style="background:{{ $t['label-bg'] }};color:{{ $t['label-color'] }}">
                    {{ $t['label'] }}
                </span>
                @if($isNew)
                    <span class="notif-new-dot"></span>
                @endif
            </div>
            <span class="notif-time">{{ $notif->created_at->diffForHumans() }}</span>
        </div>

        <div class="notif-title">{{ $notif->title }}</div>
        <div class="notif-message">{{ $notif->message }}</div>

        @if($notif->batch_id)
        <div class="notif-ref">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            Batch #{{ $notif->batch_id }}
        </div>
        @endif
    </div>
</div>

@empty
<div class="empty-state">
    <div class="es-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            <line x1="4" y1="4" x2="20" y2="20" stroke-width="1.8"/>
        </svg>
    </div>
    <div class="es-title">Tidak ada notifikasi</div>
    <div class="es-sub">Notifikasi dari sistem pengeringan akan muncul di sini.</div>
</div>
@endforelse

@if($notifications->hasPages())
<div class="pagination-wrap">{{ $notifications->links() }}</div>
@endif

@endsection

@push('styles')
<style>
/* ─── PAGE HEADER ───────────────────────────────────────── */
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
}
.ph-left { display: flex; align-items: center; gap: 12px; }
.ph-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #1e40af, #3b82f6);
    display: flex; align-items: center; justify-content: center; color: #fff;
}
.ph-title { font-size: 1.05rem; font-weight: 800; color: #1f2937; }
.ph-sub   { font-size: .76rem; color: #9ca3af; margin-top: 2px; }

.ph-badge-unread {
    font-size: .75rem; font-weight: 700;
    background: #fee2e2; color: #991b1b;
    padding: 5px 12px; border-radius: 999px;
    border: 1px solid #fca5a5;
}
.ph-badge-read {
    font-size: .75rem; font-weight: 700;
    background: #dcfce7; color: #166534;
    padding: 5px 12px; border-radius: 999px;
    border: 1px solid #86efac;
}

/* ─── NOTIFICATION CARD ─────────────────────────────────── */
.notif-card {
    display: flex; gap: 14px; align-items: flex-start;
    background: #fff;
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 10px;
    border-left: 4px solid #e5e7eb;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    border: 1px solid rgba(0,0,0,.04);
    border-left-width: 4px;
    transition: box-shadow .15s, transform .15s;
}
.notif-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.09); transform: translateY(-1px); }
.notif-new  { background: #fff; }
.notif-read { background: #fafafa; opacity: .85; }

.notif-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    margin-top: 2px;
}

/* ─── NOTIFICATION BODY ─────────────────────────────────── */
.notif-body   { flex: 1; min-width: 0; }
.notif-top    { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 8px; }
.notif-meta   { display: flex; align-items: center; gap: 7px; }

.notif-type-badge {
    font-size: .69rem; font-weight: 700;
    padding: 2px 9px; border-radius: 999px;
}
.notif-new-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #3b82f6; flex-shrink: 0;
    box-shadow: 0 0 5px rgba(59,130,246,.5);
}
.notif-time {
    font-size: .71rem; color: #9ca3af; white-space: nowrap; flex-shrink: 0;
}

.notif-title {
    font-size: .9rem; font-weight: 700; color: #1f2937; margin-bottom: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.notif-message {
    font-size: .82rem; color: #4b5563; line-height: 1.6;
}
.notif-ref {
    display: flex; align-items: center; gap: 5px;
    font-size: .71rem; color: #9ca3af; margin-top: 7px;
}

/* ─── EMPTY STATE ───────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 64px 20px; color: #9ca3af;
}
.es-icon {
    width: 72px; height: 72px; border-radius: 18px;
    background: #f3f4f6; margin: 0 auto 14px;
    display: flex; align-items: center; justify-content: center; color: #d1d5db;
}
.es-title { font-size: .97rem; font-weight: 700; color: #374151; margin-bottom: 5px; }
.es-sub   { font-size: .82rem; color: #9ca3af; max-width: 260px; margin: 0 auto; }

/* ─── PAGINATION ────────────────────────────────────────── */
.pagination-wrap { margin-top: 10px; }

/* ─── RESPONSIVE ────────────────────────────────────────── */
@media (max-width: 480px) {
    .notif-card   { padding: 12px 14px; }
    .notif-title  { font-size: .85rem; }
    .notif-message{ font-size: .78rem; }
    .ph-title     { font-size: .92rem; }
}
</style>
@endpush

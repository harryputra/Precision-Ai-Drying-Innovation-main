@extends('layouts.app')
@section('title', __('app.notifications'))
@section('breadcrumb', __('app.nav_system') . ' / ' . __('app.nav_notifications'))

@section('content')

@php
$configs = [
    'info'    => [
        'grad'   => 'linear-gradient(135deg,#1e3a8a,#1d4ed8)',
        'border' => '#bfdbfe',
        'bg'     => '#eff6ff',
        'color'  => '#1d4ed8',
        'badge'  => 'badge-blue',
        'icon'   => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'label'  => 'Info',
    ],
    'warning' => [
        'grad'   => 'linear-gradient(135deg,#78350f,#d97706)',
        'border' => '#fde68a',
        'bg'     => '#fffbeb',
        'color'  => '#d97706',
        'badge'  => 'badge-yellow',
        'icon'   => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z',
        'label'  => 'Warning',
    ],
    'alert'   => [
        'grad'   => 'linear-gradient(135deg,#7f1d1d,#dc2626)',
        'border' => '#fca5a5',
        'bg'     => '#fef2f2',
        'color'  => '#dc2626',
        'badge'  => 'badge-red',
        'icon'   => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z',
        'label'  => 'Alert',
    ],
    'error'   => [
        'grad'   => 'linear-gradient(135deg,#7f1d1d,#dc2626)',
        'border' => '#fca5a5',
        'bg'     => '#fef2f2',
        'color'  => '#dc2626',
        'badge'  => 'badge-red',
        'icon'   => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'label'  => 'Error',
    ],
    'success' => [
        'grad'   => 'linear-gradient(135deg,#064e3b,#059669)',
        'border' => '#a7f3d0',
        'bg'     => '#f0fdf4',
        'color'  => '#059669',
        'badge'  => 'badge-green',
        'icon'   => 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3',
        'label'  => 'Success',
    ],
];

$totalUnread = $notifications->where('read_at', null)->count();
$totalAll    = $notifications->total();
@endphp

{{-- Page header --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">{{ __('app.nav_system') }}</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.25rem;letter-spacing:-0.02em;">{{ __('app.notifications') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.85);margin:0;">{{ __('app.notifications_found', ['count' => $totalAll]) }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;">
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">NOTIFICATIONS</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
            {{-- Stats --}}
            @foreach([[__('app.all'), $totalAll], [__('app.unread'), $totalUnread]] as [$lbl, $val])
            <div style="background:rgba(255,255,255,0.18);border-radius:12px;padding:0.75rem 1.1rem;min-width:70px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.25);box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size:1.5rem;font-weight:900;color:#fff;letter-spacing:-0.02em;">{{ $val }}</div>
                <div style="font-size:0.62rem;color:rgba(255,255,255,0.8);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">{{ $lbl }}</div>
            </div>
            @endforeach
            {{-- Mark all read --}}
            <form method="POST" action="{{ route('web.notifications.read-all') }}" style="margin:0;">
                @csrf
                <button type="submit"
                        style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);border-radius:12px;padding:0.75rem 1.1rem;font-size:0.75rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;backdrop-filter:blur(8px);transition:all 0.15s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg>
                    {{ __('app.mark_all_read') }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Filter tabs --}}
<div style="display:flex;gap:0.5rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('web.notifications.index') }}"
       style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 1rem;border-radius:9px;font-size:0.78rem;font-weight:700;text-decoration:none;transition:all 0.15s;{{ !request()->hasAny(['unread','type']) ? 'background:linear-gradient(135deg,#1e3a8a,#1d4ed8);color:#fff;box-shadow:0 3px 10px rgba(29,78,216,0.35);' : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;' }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        {{ __('app.all') }}
    </a>
    <a href="{{ route('web.notifications.index', ['unread' => 1]) }}"
       style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 1rem;border-radius:9px;font-size:0.78rem;font-weight:700;text-decoration:none;transition:all 0.15s;{{ request()->boolean('unread') ? 'background:linear-gradient(135deg,#312e81,#6366f1);color:#fff;box-shadow:0 3px 10px rgba(99,102,241,0.35);' : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;' }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ __('app.unread') }}
        @if($totalUnread > 0)
        <span style="background:#ef4444;color:#fff;border-radius:99px;font-size:0.6rem;font-weight:800;padding:0 5px;min-width:16px;text-align:center;line-height:16px;">{{ $totalUnread }}</span>
        @endif
    </a>
    <a href="{{ route('web.notifications.index', ['type' => 'alerts']) }}"
       style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 1rem;border-radius:9px;font-size:0.78rem;font-weight:700;text-decoration:none;transition:all 0.15s;{{ request('type') === 'alerts' ? 'background:linear-gradient(135deg,#7f1d1d,#dc2626);color:#fff;box-shadow:0 3px 10px rgba(220,38,38,0.35);' : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;' }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        {{ __('app.alerts') }}
    </a>
</div>

@if($notifications->isEmpty())
<div class="glass-card" style="padding:3.5rem;text-align:center;">
    <div style="width:72px;height:72px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
    </div>
    <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.375rem;">{{ __('app.no_notifications') }}</p>
    <p style="font-size:0.82rem;color:#64748b;margin:0;">{{ __('app.no_notifications_hint') }}</p>
</div>
@else

{{-- Notifications grouped by date --}}
@php
$grouped = $notifications->groupBy(fn($n) => $n->created_at->format('Y-m-d'));
@endphp

<div style="display:flex;flex-direction:column;gap:1.5rem;margin-bottom:1.5rem;">
    @foreach($grouped as $date => $group)
    @php
        $dateLabel = \Carbon\Carbon::parse($date)->isToday()
            ? (__('app.today') ?: 'Today')
            : (\Carbon\Carbon::parse($date)->isYesterday()
                ? (__('app.yesterday') ?: 'Yesterday')
                : \Carbon\Carbon::parse($date)->format('d M Y'));
        $groupUnread = $group->where('read_at', null)->count();
    @endphp

    {{-- Date divider --}}
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <div style="flex:1;height:1px;background:linear-gradient(to right,#e2e8f0,transparent);"></div>
        <span style="font-size:0.72rem;font-weight:700;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:99px;padding:3px 12px;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ $dateLabel }}
            @if($groupUnread > 0)
            <span style="background:#ef4444;color:#fff;border-radius:99px;font-size:0.55rem;font-weight:800;padding:0 5px;min-width:14px;text-align:center;line-height:14px;">{{ $groupUnread }}</span>
            @endif
        </span>
        <div style="flex:1;height:1px;background:linear-gradient(to left,#e2e8f0,transparent);"></div>
    </div>

    {{-- Cards grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
        @foreach($group as $notif)
        @php
            $cfg      = $configs[$notif->type] ?? $configs['info'];
            $isUnread = !$notif->read_at;
        @endphp

        <div style="background:#fff;border-radius:18px;border:1.5px solid {{ $isUnread ? $cfg['border'] : '#e2e8f0' }};box-shadow:{{ $isUnread ? '0 4px 20px rgba(0,0,0,0.08)' : '0 2px 8px rgba(0,0,0,0.04)' }};overflow:hidden;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;{{ $isUnread ? '' : 'opacity:0.88;' }}"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,0.1)';this.style.opacity='1';this.style.borderColor='{{ $cfg['border'] }}'"
             onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='{{ $isUnread ? '0 4px 20px rgba(0,0,0,0.08)' : '0 2px 8px rgba(0,0,0,0.04)' }}';this.style.opacity='{{ $isUnread ? '1' : '0.88' }}';this.style.borderColor='{{ $isUnread ? $cfg['border'] : '#e2e8f0' }}'">

            {{-- Accent bar --}}
            <div style="height:3px;background:{{ $cfg['grad'] }};{{ $isUnread ? '' : 'opacity:0.5;' }}"></div>

            <div style="padding:1.1rem 1.25rem;">
                {{-- Header --}}
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.875rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div style="position:relative;flex-shrink:0;">
                            <div style="width:44px;height:44px;border-radius:12px;background:{{ $cfg['grad'] }};display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);{{ $isUnread ? '' : 'opacity:0.7;' }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="{{ $cfg['icon'] }}"/>
                                </svg>
                            </div>
                            @if($isUnread)
                            <span style="position:absolute;top:-3px;right:-3px;width:10px;height:10px;background:#ef4444;border-radius:50%;border:2px solid #fff;animation:pulse-dot 1.5s infinite;"></span>
                            @endif
                        </div>
                        <div style="min-width:0;">
                            <div style="font-size:0.875rem;font-weight:{{ $isUnread ? '800' : '600' }};color:{{ $isUnread ? '#0f172a' : '#475569' }};line-height:1.3;letter-spacing:-0.01em;">{{ $notif->title }}</div>
                            <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                @if($notif->device)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                                <span>{{ $notif->device->device_name }}</span>
                                <span>·</span>
                                @endif
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                                {{ $notif->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                        <span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                        @if($notif->category)
                        <span class="badge badge-gray" style="font-size:0.6rem;">{{ str_replace('_',' ',$notif->category) }}</span>
                        @endif
                        @if($isUnread)
                        <span class="badge badge-blue" style="font-size:0.6rem;">{{ __('app.new') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Message box --}}
                <div style="background:#f8fafc;border-radius:10px;border-left:3px solid {{ $cfg['color'] }};padding:0.6rem 0.875rem;margin-bottom:0.875rem;">
                    <p style="font-size:0.78rem;color:#374151;margin:0;line-height:1.6;">{{ $notif->message }}</p>
                </div>

                {{-- Footer --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.625rem;border-top:1px solid #f1f5f9;">
                    <span style="font-size:0.68rem;color:#94a3b8;display:inline-flex;align-items:center;gap:3px;">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                        {{ $notif->created_at->format('H:i') }}
                    </span>
                    @if($isUnread)
                    <form method="POST" action="{{ route('web.notifications.read', $notif) }}" style="margin:0;">
                        @csrf @method('PATCH')
                        <button type="submit"
                                style="font-size:0.72rem;font-weight:700;color:#fff;background:{{ $cfg['grad'] }};padding:4px 12px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,0.15);transition:opacity 0.15s;"
                                onmouseover="this.style.opacity='0.85'"
                                onmouseout="this.style.opacity='1'">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20,6 9,17 4,12"/></svg>
                            {{ __('app.mark_read') }}
                        </button>
                    </form>
                    @else
                    <span style="font-size:0.68rem;color:#94a3b8;display:inline-flex;align-items:center;gap:3px;">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
                        {{ __('app.read') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>

<div class="pagination-wrapper">{{ $notifications->links() }}</div>
@endif

@endsection

@push('styles')
<style>
@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50%       { transform: scale(1.4); opacity: 0.6; }
}
</style>
@endpush

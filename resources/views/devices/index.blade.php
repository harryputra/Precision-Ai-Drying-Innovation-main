@extends('layouts.app')
@section('title', __('app.devices'))
@section('breadcrumb', __('app.nav_monitoring') . ' / ' . __('app.nav_devices'))

@section('content')

{{-- Page header --}}
<div class="page-header-banner" style="padding:1.5rem 1.75rem;margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">Monitoring</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.device_list') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.device_list_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;"><span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">DEVICES</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span><span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span></div>
        </div>
        @php
            $totalDev   = $devices->total();
            $onlineDev  = \App\Models\Device::where('status','online')->count();
            $offlineDev = \App\Models\Device::where('status','offline')->count();
        @endphp
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
            @foreach([[__('app.total'),$totalDev],[__('app.online'),$onlineDev],[__('app.offline'),$offlineDev]] as [$lbl,$val])
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:0.625rem 1rem;min-width:70px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">
                <div style="font-size:1.25rem;font-weight:800;color:#fff;">{{ $val }}</div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.95);font-weight:500;">{{ $lbl }}</div>
            </div>
            @endforeach
            @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
            <a href="{{ route('web.devices.create') }}" class="btn-secondary btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;border-color:rgba(255,255,255,0.3);">
                {{ __('app.add_device') }}
            </a>
            @endif
        </div>
    </div>
</div>

{{-- Status cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;">
    @php $statusCards = [
        [__('app.total_device'),  null,          'metric-card-indigo', 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 0-2-2V9m0 0h18', __('app.all_devices')],
        [__('app.online'),        'online',      'metric-card-green',  'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', __('app.active_connected')],
        [__('app.offline'),       'offline',     'metric-card-red',    'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', __('app.not_connected')],
        [__('app.maintenance'),   'maintenance', 'metric-card-orange', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', __('app.in_maintenance')],
    ]; @endphp
    @foreach($statusCards as [$label,$status,$cardClass,$iconPath,$desc])
    @php
        $count = $status
            ? \App\Models\Device::where('status',$status)->count()
            : \App\Models\Device::count();
    @endphp
    <div class="{{ $cardClass }}" style="padding:1.4rem 1.25rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.875rem;position:relative;z-index:1;">
            <span style="font-size:0.78rem;font-weight:600;color:rgba(255,255,255,0.85);">{{ $label }}</span>
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

{{-- Device Grid --}}
@if($devices->isEmpty())
<div class="glass-card" style="padding:3rem;text-align:center;">
    <div style="width:72px;height:72px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>
        </svg>
    </div>
    <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">{{ __('app.no_device') }}</p>
    <p style="font-size:0.82rem;color:#64748b;margin:0;">{{ __('app.no_device_desc') }}</p>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;">
    @foreach($devices as $device)
    @php
        $statusConfig = [
            'online' => [
                'dot'       => '#10b981',
                'accentBar' => 'linear-gradient(90deg,#059669,#10b981,#34d399)',
                'iconBg'    => 'linear-gradient(135deg,#059669,#10b981)',
                'iconShadow'=> 'rgba(16,185,129,0.4)',
                'cardBorder'=> '#a7f3d0',
                'cardHoverBorder' => '#34d399',
                'badge'     => ['#065f46','#dcfce7','#86efac'],
                'label'     => 'Online',
                'pulse'     => true,
            ],
            'offline' => [
                'dot'       => '#ef4444',
                'accentBar' => 'linear-gradient(90deg,#dc2626,#ef4444,#f87171)',
                'iconBg'    => 'linear-gradient(135deg,#dc2626,#ef4444)',
                'iconShadow'=> 'rgba(239,68,68,0.4)',
                'cardBorder'=> '#fca5a5',
                'cardHoverBorder' => '#f87171',
                'badge'     => ['#7f1d1d','#fee2e2','#fca5a5'],
                'label'     => 'Offline',
                'pulse'     => false,
            ],
            'maintenance' => [
                'dot'       => '#f59e0b',
                'accentBar' => 'linear-gradient(90deg,#d97706,#f59e0b,#fcd34d)',
                'iconBg'    => 'linear-gradient(135deg,#d97706,#f59e0b)',
                'iconShadow'=> 'rgba(245,158,11,0.4)',
                'cardBorder'=> '#fde68a',
                'cardHoverBorder' => '#fcd34d',
                'badge'     => ['#78350f','#fef3c7','#fde68a'],
                'label'     => 'Maintenance',
                'pulse'     => false,
            ],
        ];
        $sc = $statusConfig[$device->status] ?? [
            'dot'=>'#94a3b8','accentBar'=>'linear-gradient(90deg,#64748b,#94a3b8)',
            'iconBg'=>'linear-gradient(135deg,#64748b,#94a3b8)','iconShadow'=>'rgba(100,116,139,0.3)',
            'cardBorder'=>'#e2e8f0','cardHoverBorder'=>'#cbd5e1',
            'badge'=>['#334155','#f1f5f9','#e2e8f0'],'label'=>ucfirst($device->status),'pulse'=>false,
        ];
        $activeBatch = $device->activeBatch();
    @endphp
    <a href="{{ route('web.devices.show', $device) }}" style="display:block;text-decoration:none;" class="device-card-link">
        <div class="device-card" style="--card-border:{{ $sc['cardBorder'] }};--card-hover-border:{{ $sc['cardHoverBorder'] }};border-color:{{ $sc['cardBorder'] }}">

            {{-- Accent bar top --}}
            <div style="height:4px;background:{{ $sc['accentBar'] }};background-size:200% 100%;animation:shimmer-gold 3s ease-in-out infinite;"></div>

            {{-- Card header --}}
            <div style="padding:1.1rem 1.25rem 0.875rem;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.875rem;">
                    <div style="width:46px;height:46px;border-radius:13px;background:{{ $sc['iconBg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px {{ $sc['iconShadow'] }};">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:0.95rem;font-weight:800;color:#0f172a;line-height:1.25;letter-spacing:-0.01em;">{{ $device->device_name }}</div>
                        <div style="font-size:0.68rem;color:#94a3b8;font-family:monospace;margin-top:3px;letter-spacing:0.03em;">{{ $device->serial_number }}</div>
                    </div>
                </div>
                <span style="display:inline-flex;align-items:center;gap:5px;background:{{ $sc['badge'][1] }};color:{{ $sc['badge'][0] }};border:1.5px solid {{ $sc['badge'][2] }};border-radius:20px;padding:4px 11px;font-size:0.68rem;font-weight:800;white-space:nowrap;flex-shrink:0;letter-spacing:0.04em;">
                    @if($sc['pulse'])
                    <span class="pulse-green" style="width:6px;height:6px;background:{{ $sc['dot'] }};"></span>
                    @else
                    <span style="width:6px;height:6px;background:{{ $sc['dot'] }};border-radius:50%;display:inline-block;"></span>
                    @endif
                    {{ $sc['label'] }}
                </span>
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:0 1.25rem;"></div>

            {{-- Body --}}
            <div style="padding:0.875rem 1.25rem;">

                {{-- Info rows --}}
                <div style="display:flex;flex-direction:column;gap:0.4rem;margin-bottom:1rem;">
                    @if($device->location)
                    <div style="display:flex;align-items:center;gap:0.6rem;">
                        <span style="width:24px;height:24px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <span style="font-size:0.78rem;color:#475569;font-weight:500;">{{ $device->location }}</span>
                    </div>
                    @endif
                    @if($device->ip_address)
                    <div style="display:flex;align-items:center;gap:0.6rem;">
                        <span style="width:24px;height:24px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                        </span>
                        <span style="font-size:0.78rem;color:#475569;font-family:monospace;font-weight:500;">{{ $device->ip_address }}</span>
                    </div>
                    @endif
                    <div style="display:flex;align-items:center;gap:0.6rem;">
                        <span style="width:24px;height:24px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                        </span>
                        <span style="font-size:0.78rem;color:#475569;font-weight:500;">{{ $device->last_seen?->diffForHumans() ?? __('app.never') }}</span>
                    </div>
                </div>

                {{-- Stats --}}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-bottom:1rem;">
                    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:12px;padding:0.65rem 0.5rem;text-align:center;border:1px solid #bfdbfe;">
                        <div style="font-size:1.2rem;font-weight:900;color:#1d4ed8;line-height:1;letter-spacing:-0.02em;">{{ $device->drying_batches_count }}</div>
                        <div style="font-size:0.6rem;color:#3b82f6;font-weight:700;margin-top:3px;text-transform:uppercase;letter-spacing:0.04em;">{{ __('app.batches_count') }}</div>
                    </div>
                    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;padding:0.65rem 0.5rem;text-align:center;border:1px solid #bbf7d0;">
                        <div style="font-size:1.2rem;font-weight:900;color:#15803d;line-height:1;letter-spacing:-0.02em;">{{ number_format($device->sensor_readings_count) }}</div>
                        <div style="font-size:0.6rem;color:#16a34a;font-weight:700;margin-top:3px;text-transform:uppercase;letter-spacing:0.04em;">{{ __('app.readings_count') }}</div>
                    </div>
                    <div style="background:linear-gradient(135deg,#fafaf9,#f5f5f4);border-radius:12px;padding:0.65rem 0.5rem;text-align:center;border:1px solid #e7e5e4;">
                        <div style="font-size:0.82rem;font-weight:900;color:#292524;font-family:monospace;line-height:1.2;">{{ $device->firmware_version ?? '—' }}</div>
                        <div style="font-size:0.6rem;color:#78716c;font-weight:700;margin-top:3px;text-transform:uppercase;letter-spacing:0.04em;">{{ __('app.firmware') }}</div>
                    </div>
                </div>

                {{-- Active batch --}}
                @if($activeBatch)
                <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1.5px solid #fed7aa;border-radius:12px;padding:0.6rem 0.875rem;display:flex;align-items:center;gap:0.6rem;margin-bottom:0.875rem;">
                    <span class="pulse-green" style="width:7px;height:7px;background:#f97316;flex-shrink:0;"></span>
                    <span style="font-size:0.75rem;font-weight:800;color:#c2410c;flex:1;letter-spacing:0.01em;">{{ $activeBatch->batch_code }}</span>
                    <span style="font-size:0.68rem;color:#9a3412;font-weight:700;background:rgba(255,255,255,0.8);border-radius:6px;padding:2px 8px;border:1px solid #fed7aa;">{{ number_format($activeBatch->current_moisture ?? 0,1) }}% → {{ number_format($activeBatch->target_moisture,1) }}%</span>
                </div>
                @endif

                {{-- Footer --}}
                <div style="display:flex;justify-content:flex-end;padding-top:0.75rem;border-top:1px solid #f1f5f9;">
                    <span style="font-size:0.74rem;color:#1d4ed8;font-weight:700;display:inline-flex;align-items:center;gap:0.3rem;letter-spacing:0.01em;">
                        {{ __('app.view_detail') }}
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
                    </span>
                </div>

            </div>
        </div>
    </a>
    @endforeach
</div>
@endif

@if($devices->hasPages())
<div style="margin-top:1.5rem;">
    {{ $devices->links() }}
</div>
@endif

@endsection

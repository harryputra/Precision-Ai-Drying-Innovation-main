@extends('layouts.app')
@section('title', __('app.user_management'))
@section('breadcrumb', __('app.nav_admin') . ' / ' . __('app.user_management'))

@section('content')

<div class="page-header-banner" style="padding:1.5rem 1.75rem;margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">Admin</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.user_management') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.user_management_desc') }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <a href="{{ route('admin.users.create') }}"
               style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);border-radius:10px;padding:0.5rem 1rem;font-size:0.82rem;font-weight:700;text-decoration:none;backdrop-filter:blur(4px);transition:all 0.15s;"
               onmouseover="this.style.background='rgba(255,255,255,0.3)'"
               onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                + {{ __('app.create_user') }}
            </a>
            <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:0.625rem 1rem;min-width:70px;text-align:center;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">
                <div style="font-size:1.25rem;font-weight:800;color:#fff;">{{ $users->total() }}</div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.95);font-weight:500;">{{ __('app.total_users') }}</div>
            </div>
        </div>
    </div>
</div>

<div style="padding:0 1.75rem 2rem;">

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;">
        {{ session('success') }}
    </div>
    @endif

    <div class="card" style="border-radius:14px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;" id="dt-users">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:0.875rem 1rem;text-align:left;font-weight:700;color:#374151;">{{ __('app.name') }}</th>
                    <th style="padding:0.875rem 1rem;text-align:left;font-weight:700;color:#374151;">{{ __('app.email') }}</th>
                    <th style="padding:0.875rem 1rem;text-align:center;font-weight:700;color:#374151;">{{ __('app.role') }}</th>
                    <th style="padding:0.875rem 1rem;text-align:center;font-weight:700;color:#374151;">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr style="border-bottom:1px solid #f1f5f9;{{ $loop->even ? 'background:#fafafa;' : '' }}">
                    <td style="padding:0.875rem 1rem;font-weight:600;color:#1e293b;">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                        <span style="font-size:0.65rem;background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:1px 6px;margin-left:4px;font-weight:700;">{{ __('app.you') }}</span>
                        @endif
                    </td>
                    <td style="padding:0.875rem 1rem;color:#64748b;">{{ $user->email }}</td>
                    <td style="padding:0.875rem 1rem;text-align:center;">
                        @php
                            $roleColor = match($user->role) {
                                'admin'    => ['bg'=>'#fef3c7','color'=>'#92400e','label'=> __('app.admin')],
                                'operator' => ['bg'=>'#dbeafe','color'=>'#1e40af','label'=> __('app.operator')],
                                default    => ['bg'=>'#f1f5f9','color'=>'#475569','label'=> __('app.viewer')],
                            };
                        @endphp
                        <span style="background:{{ $roleColor['bg'] }};color:{{ $roleColor['color'] }};border-radius:20px;padding:3px 12px;font-size:0.75rem;font-weight:700;">
                            {{ $roleColor['label'] }}
                        </span>
                    </td>
                    <td style="padding:0.875rem 1rem;text-align:center;">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           style="background:#1d4ed8;color:#fff;border-radius:8px;padding:5px 14px;font-size:0.78rem;font-weight:600;text-decoration:none;">
                            {{ __('app.edit_role') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="padding:2rem;text-align:center;color:#94a3b8;">{{ __('app.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid #f1f5f9;">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#dt-users').DataTable({
        paging: false,
        info: false,
        language: { search: '{{ __("app.search") }}:', zeroRecords: '{{ __("app.no_data") }}' }
    });
});
</script>
@endpush

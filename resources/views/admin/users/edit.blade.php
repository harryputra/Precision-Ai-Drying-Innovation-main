@extends('layouts.app')
@section('title', __('app.edit_role'))
@section('breadcrumb', __('app.nav_admin') . ' / ' . __('app.user_management') . ' / ' . __('app.edit_role'))

@section('content')

<div class="page-header-banner" style="padding:1.5rem 1.75rem;margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;">
        <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">Admin</div>
        <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.edit_role') }}: {{ $user->name }}</h2>
        <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ $user->email }}</p>
    </div>
</div>

<div style="padding:0 1.75rem 2rem;max-width:500px;">

    <div class="card" style="border-radius:14px;padding:1.75rem;">

        <form action="{{ route('admin.users.role', $user) }}" method="POST">
            @csrf
            @method('PATCH')

            <div style="margin-bottom:1.25rem;">
                <label style="display:block;font-weight:700;color:#374151;margin-bottom:0.5rem;font-size:0.875rem;">{{ __('app.role') }}</label>
                <select name="role" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:0.625rem 0.75rem;font-size:0.9rem;color:#1e293b;background:#fff;">
                    @foreach(['admin' => __('app.admin'), 'operator' => __('app.operator'), 'viewer' => __('app.viewer')] as $value => $label)
                    <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')
                <p style="color:#ef4444;font-size:0.8rem;margin-top:0.375rem;">{{ $message }}</p>
                @enderror

                <div style="margin-top:0.75rem;font-size:0.8rem;color:#64748b;line-height:1.6;">
                    {{ __('app.role_desc') }}
                </div>
            </div>

            <div style="display:flex;gap:0.75rem;">
                <button type="submit"
                        style="background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:0.625rem 1.5rem;font-size:0.875rem;font-weight:700;cursor:pointer;">
                    {{ __('app.save_role') }}
                </button>
                <a href="{{ route('admin.users.index') }}"
                   style="background:#f1f5f9;color:#475569;border-radius:8px;padding:0.625rem 1.25rem;font-size:0.875rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">
                    {{ __('app.cancel') }}
                </a>
            </div>

        </form>

    </div>

</div>

@endsection

@extends('layouts.app')
@section('title', __('app.create_user_title'))
@section('breadcrumb', __('app.nav_admin') . ' / ' . __('app.user_management') . ' / ' . __('app.create_user'))

@section('content')

<div class="page-header-banner" style="padding:1.5rem 1.75rem;margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">Admin</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">{{ __('app.create_user_title') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.create_user_desc') }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn-secondary" style="align-self:center;">
            {{ __('app.back') }}
        </a>
    </div>
</div>

<div style="padding:0 1.75rem 2rem;">

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;">
        @foreach($errors->all() as $error)
        <p style="color:#b91c1c;font-size:0.82rem;margin:0 0 2px;">{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="card" style="border-radius:14px;padding:1.75rem;max-width:520px;">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            {{-- Name --}}
            <div style="margin-bottom:1.25rem;">
                <label class="label-dark">{{ __('app.name') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       placeholder="{{ __('app.register_name_ph') }}"
                       class="input-dark" required autofocus>
            </div>

            {{-- Email --}}
            <div style="margin-bottom:1.25rem;">
                <label class="label-dark">{{ __('app.email') }} <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="{{ __('app.register_email_ph') }}"
                       class="input-dark" required>
            </div>

            {{-- Password --}}
            <div style="margin-bottom:1.25rem;">
                <label class="label-dark">{{ __('app.password') }} <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password"
                       placeholder="Min. 8 karakter"
                       class="input-dark" required>
            </div>

            {{-- Password Confirmation --}}
            <div style="margin-bottom:1.25rem;">
                <label class="label-dark">{{ __('app.confirm_password') }} <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password_confirmation"
                       placeholder="Ulangi password"
                       class="input-dark" required>
            </div>

            {{-- Role --}}
            <div style="margin-bottom:1.75rem;">
                <label class="label-dark">{{ __('app.role') }} <span style="color:#ef4444;">*</span></label>
                <select name="role" class="input-dark" required>
                    <option value="viewer"   {{ old('role','viewer') === 'viewer'   ? 'selected' : '' }}>{{ __('app.viewer') }}</option>
                    <option value="operator" {{ old('role') === 'operator' ? 'selected' : '' }}>{{ __('app.operator') }}</option>
                    <option value="admin"    {{ old('role') === 'admin'    ? 'selected' : '' }}>{{ __('app.admin') }}</option>
                </select>
                <p style="font-size:0.72rem;color:#64748b;margin:0.375rem 0 0;">{{ __('app.role_desc') }}</p>
            </div>

            <div style="display:flex;gap:0.75rem;">
                <button type="submit" class="btn-primary">{{ __('app.save_user') }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>

</div>

@endsection

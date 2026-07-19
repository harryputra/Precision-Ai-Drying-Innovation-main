@extends('layouts.app')
@section('title', __('app.profile'))
@section('breadcrumb', __('app.nav_system') . ' / ' . __('app.profile'))

@section('content')

<div class="page-header-banner" style="margin-bottom:1rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;gap:0.75rem;">
        <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:800;color:#fff;flex-shrink:0;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h2 style="font-size:1.1rem;font-weight:800;color:#fff;margin:0;">{{ $user->name }}</h2>
            <p style="font-size:0.75rem;color:rgba(255,255,255,0.75);margin:0;">{{ $user->email }}</p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:1rem;" class="profile-grid">

    {{-- Update Profile --}}
    <div class="glass-card">
        <div class="card-header">
            <h3 class="card-header-title">{{ __('app.update_profile') }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('web.profile.update') }}">
                @csrf @method('PATCH')
                <div style="display:grid;gap:1rem;">
                    <div>
                        <label class="label-dark">{{ __('app.name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="input-dark @error('name') border-red-400 @enderror" required>
                        @error('name') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-dark">{{ __('app.email') }}</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="input-dark @error('email') border-red-400 @enderror" required>
                        @error('email') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-dark">{{ __('app.member_since') }}</label>
                        <input type="text" value="{{ $user->created_at->format('d M Y') }}" class="input-dark" disabled style="background:#f1f5f9;color:#64748b;">
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">{{ __('app.update_profile') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Password --}}
    <div class="glass-card">
        <div class="card-header">
            <h3 class="card-header-title">{{ __('app.update_password') }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('web.profile.password') }}">
                @csrf @method('PATCH')
                <div style="display:grid;gap:1rem;">
                    <div>
                        <label class="label-dark">{{ __('app.current_password') }}</label>
                        <input type="password" name="current_password"
                               class="input-dark @error('current_password') border-red-400 @enderror"
                               placeholder="••••••••" required>
                        @error('current_password') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-dark">{{ __('app.new_password') }}</label>
                        <input type="password" name="password"
                               class="input-dark @error('password') border-red-400 @enderror"
                               placeholder="{{ __('app.password_min_hint') }}" required>
                        @error('password') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-dark">{{ __('app.confirm_password') }}</label>
                        <input type="password" name="password_confirmation"
                               class="input-dark" placeholder="{{ __('app.confirm_password') }}" required>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">{{ __('app.update_password') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<style>
@media (min-width: 768px) {
    .profile-grid { grid-template-columns: 1fr 1fr !important; }
}
</style>
@endpush

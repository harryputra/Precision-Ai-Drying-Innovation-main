@extends('layouts.guest')
@section('title', __('app.register_title'))

@section('content')
<div style="background:#ffffff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden;">

    {{-- Card top gradient header --}}
    <div style="background:linear-gradient(135deg,#052e16,#166534,#15803d);padding:2rem 2rem 3rem;position:relative;overflow:hidden;text-align:center;">
        <div style="position:absolute;top:-30px;left:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
        <div style="position:absolute;top:-10px;right:20px;width:80px;height:80px;border-radius:50%;background:rgba(212,160,23,0.2);"></div>

        {{-- Logo icon --}}
        <div style="position:relative;z-index:1;">
            <div style="width:64px;height:64px;border-radius:16px;overflow:hidden;margin:0 auto 0.75rem;box-shadow:0 4px 16px rgba(0,0,0,0.15);">
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="display:inline-flex;align-items:center;gap:0.375rem;background:rgba(255,255,255,0.15);border-radius:20px;padding:0.25rem 0.75rem;margin-bottom:0.5rem;">
                <div style="width:6px;height:6px;border-radius:50%;background:#34d399;"></div>
                <span style="font-size:0.65rem;font-weight:700;color:rgba(255,255,255,0.9);letter-spacing:0.1em;text-transform:uppercase;">{{ __('app.monitoring_system') }}</span>
            </div>
        </div>
    </div>

    {{-- Form area --}}
    <div style="background:#fff;padding:0 2rem 2rem;margin-top:-1rem;border-radius:20px 20px 0 0;position:relative;z-index:2;">

        <div style="text-align:center;padding-top:1.25rem;margin-bottom:1.5rem;">
            <h1 style="font-size:1.5rem;font-weight:800;color:#1e293b;margin:0 0 0.375rem;">{{ __('app.register_title') }}</h1>
            <p style="color:#0f172a;font-size:0.82rem;margin:0;line-height:1.5;">{{ __('app.register_subtitle') }}</p>
        </div>

        {{-- Errors --}}
        @if($errors->any())
        <div style="margin-bottom:1.25rem;padding:0.75rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:10px;">
            @foreach($errors->all() as $error)
            <p style="color:#b91c1c;font-size:0.8rem;margin:0;">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- Name --}}
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">
                    {{ __('app.name') }}
                </label>
                <div style="position:relative;">
                    <div style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:#0f172a;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="{{ __('app.register_name_ph') }}" required autofocus
                           style="width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;color:#1e293b;padding:0.65rem 0.875rem 0.65rem 2.5rem;font-size:0.875rem;outline:none;transition:all 0.15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#16a34a';this.style.boxShadow='0 0 0 3px rgba(22,163,74,0.1)';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none';this.style.background='#f8fafc'">
                </div>
            </div>

            {{-- Email --}}
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">
                    {{ __('app.email') }}
                </label>
                <div style="position:relative;">
                    <div style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:#0f172a;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="{{ __('app.register_email_ph') }}" required
                           style="width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;color:#1e293b;padding:0.65rem 0.875rem 0.65rem 2.5rem;font-size:0.875rem;outline:none;transition:all 0.15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#16a34a';this.style.boxShadow='0 0 0 3px rgba(22,163,74,0.1)';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none';this.style.background='#f8fafc'">
                </div>
            </div>

            {{-- Password --}}
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">
                    {{ __('app.password') }}
                </label>
                <div style="position:relative;" x-data="{ show: false }">
                    <div style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:#0f172a;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <input :type="show ? 'text' : 'password'" name="password"
                           placeholder="Min. 8 karakter" required
                           style="width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;color:#1e293b;padding:0.65rem 2.75rem 0.65rem 2.5rem;font-size:0.875rem;outline:none;transition:all 0.15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#16a34a';this.style.boxShadow='0 0 0 3px rgba(22,163,74,0.1)';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none';this.style.background='#f8fafc'">
                    <button type="button" @click="show = !show"
                            style="position:absolute;right:0.875rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#0f172a;padding:0;display:flex;">
                        <svg x-show="!show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg x-show="show" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Confirm Password --}}
            <div style="margin-bottom:1.5rem;">
                <label style="display:block;font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">
                    {{ __('app.confirm_password') }}
                </label>
                <div style="position:relative;">
                    <div style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:#0f172a;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <input type="password" name="password_confirmation"
                           placeholder="Ulangi password" required
                           style="width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;color:#1e293b;padding:0.65rem 0.875rem 0.65rem 2.5rem;font-size:0.875rem;outline:none;transition:all 0.15s;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#16a34a';this.style.boxShadow='0 0 0 3px rgba(22,163,74,0.1)';this.style.background='#fff'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none';this.style.background='#f8fafc'">
                </div>
            </div>

            <button type="submit"
                    style="width:100%;background:linear-gradient(135deg,#166534,#16a34a);color:#fff;font-weight:700;font-size:0.9rem;padding:0.8rem;border-radius:12px;border:none;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem;letter-spacing:0.02em;"
                    onmouseover="this.style.opacity='0.92';this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 24px rgba(22,101,52,0.4)'"
                    onmouseout="this.style.opacity='1';this.style.transform='none';this.style.boxShadow='none'">
                {{ __('app.register_button') }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/>
                </svg>
            </button>
        </form>

        <p style="text-align:center;font-size:0.82rem;color:#64748b;margin:1.25rem 0 0;">
            {{ __('app.already_have_account') }}
            <a href="{{ route('login') }}" style="color:#166534;font-weight:600;text-decoration:none;">{{ __('app.login') }}</a>
        </p>
    </div>
</div>
@endsection

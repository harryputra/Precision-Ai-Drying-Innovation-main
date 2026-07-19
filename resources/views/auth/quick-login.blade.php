@extends('layouts.guest')
@section('title', 'Quick Login')

@section('content')
<div style="background:#ffffff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden;">

    <div style="background:linear-gradient(135deg,#052e16,#166534,#15803d);padding:1.5rem 2rem;text-align:center;">
        <h1 style="font-size:1.15rem;font-weight:800;color:#fff;margin:0;">⚡ Quick Login</h1>
        <p style="color:rgba(255,255,255,0.85);font-size:0.78rem;margin:0.375rem 0 0;">
            Masuk cepat untuk pengujian — pilih role di bawah.
        </p>
    </div>

    <div style="padding:1.5rem 2rem 2rem;">
        @forelse($users as $user)
        <form method="POST" action="{{ route('quick-login.attempt', $token) }}" style="margin-bottom:0.75rem;">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <button type="submit"
                    style="width:100%;display:flex;align-items:center;gap:0.75rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:0.7rem 1rem;cursor:pointer;transition:all 0.15s;text-align:left;"
                    onmouseover="this.style.borderColor='#16a34a';this.style.background='#f0fdf4'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                <span style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#166534,#16a34a);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <span style="flex:1;min-width:0;">
                    <span style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;">{{ $user->name }}</span>
                    <span style="display:block;font-size:0.72rem;color:#64748b;">{{ $user->email }}</span>
                </span>
                <span style="font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:0.2rem 0.6rem;border-radius:999px;background:{{ ['admin' => '#fef3c7', 'operator' => '#dbeafe', 'viewer' => '#dcfce7'][$user->role] ?? '#f1f5f9' }};color:{{ ['admin' => '#92400e', 'operator' => '#1e40af', 'viewer' => '#166534'][$user->role] ?? '#475569' }};">
                    {{ $user->role }}
                </span>
            </button>
        </form>
        @empty
        <p style="text-align:center;color:#64748b;font-size:0.85rem;">Tidak ada akun tersedia.</p>
        @endforelse

        <p style="text-align:center;font-size:0.75rem;color:#94a3b8;margin:1rem 0 0;">
            <a href="{{ route('login') }}" style="color:#166534;font-weight:600;text-decoration:none;">← Login biasa</a>
        </p>
    </div>
</div>
@endsection

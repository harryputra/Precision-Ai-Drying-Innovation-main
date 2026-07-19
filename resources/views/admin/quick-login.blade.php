@extends('layouts.app')
@section('title', 'Quick Login')

@section('content')
<div style="max-width:640px;">
    <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0 0 0.25rem;">⚡ Quick Login</h1>
    <p style="font-size:0.82rem;color:#64748b;margin:0 0 1.25rem;">
        Akses masuk cepat untuk pengujian/support. Saat <b>nonaktif</b>, seluruh URL quick-login membalas 404.
    </p>

    @if(session('success'))
    <div style="margin-bottom:1rem;padding:0.7rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;border-radius:10px;color:#166534;font-size:0.82rem;">
        {{ session('success') }}
    </div>
    @endif

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.9rem;">
            <span style="width:10px;height:10px;border-radius:50%;background:{{ $config->isActive() ? '#22c55e' : '#94a3b8' }};"></span>
            <span style="font-weight:700;font-size:0.9rem;color:#1e293b;">
                Status: {{ $config->isActive() ? 'AKTIF' : 'Nonaktif' }}
            </span>
            @if($config->isActive() && $config->expires_at)
            <span style="font-size:0.72rem;color:#b45309;background:#fef3c7;padding:0.15rem 0.6rem;border-radius:999px;">
                kedaluwarsa {{ $config->expires_at->diffForHumans() }}
            </span>
            @endif
        </div>

        @if($config->isActive())
        <div style="margin-bottom:0.9rem;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:#64748b;margin-bottom:0.3rem;">URL Quick-Login (rahasia)</label>
            <code style="display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.55rem 0.75rem;font-size:0.75rem;color:#166534;word-break:break-all;">{{ url('/q/'.$config->token) }}</code>
        </div>
        <p style="font-size:0.75rem;color:#64748b;margin:0 0 0.9rem;">
            Tombol di halaman login: <b>{{ $config->show_button_on_login ? 'Tampil' : 'Disembunyikan' }}</b>
        </p>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            @if(!$config->isActive())
            <form method="POST" action="{{ route('admin.quick-login.update') }}" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                @csrf
                <input type="hidden" name="action" value="enable">
                <input type="number" name="expires_hours" min="1" max="720" placeholder="Expiry (jam, ops.)"
                       style="width:140px;border:1.5px solid #e2e8f0;border-radius:8px;padding:0.5rem 0.7rem;font-size:0.8rem;">
                <button type="submit" style="background:linear-gradient(135deg,#166534,#16a34a);color:#fff;font-weight:700;font-size:0.8rem;padding:0.55rem 1.1rem;border-radius:9px;border:none;cursor:pointer;">
                    Aktifkan
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.quick-login.update') }}">
                @csrf
                <input type="hidden" name="action" value="disable">
                <button type="submit" style="background:#fee2e2;color:#b91c1c;font-weight:700;font-size:0.8rem;padding:0.55rem 1.1rem;border-radius:9px;border:1px solid #fecaca;cursor:pointer;">
                    Nonaktifkan
                </button>
            </form>
            <form method="POST" action="{{ route('admin.quick-login.update') }}">
                @csrf
                <input type="hidden" name="action" value="toggle_button">
                <button type="submit" style="background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:0.8rem;padding:0.55rem 1.1rem;border-radius:9px;border:1px solid #bfdbfe;cursor:pointer;">
                    {{ $config->show_button_on_login ? 'Sembunyikan tombol login' : 'Tampilkan tombol login' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.quick-login.update') }}">
                @csrf
                <input type="hidden" name="action" value="regenerate">
                <button type="submit" style="background:#fef3c7;color:#92400e;font-weight:700;font-size:0.8rem;padding:0.55rem 1.1rem;border-radius:9px;border:1px solid #fde68a;cursor:pointer;">
                    Ganti token
                </button>
            </form>
            @endif
        </div>
    </div>

    <p style="font-size:0.72rem;color:#94a3b8;line-height:1.6;">
        Keamanan: token acak 128-bit divalidasi constant-time; token dihapus saat dinonaktifkan;
        expiry opsional; semua percobaan tercatat di System Logs (channel <code>auth</code>).
        Di produksi biarkan <b>nonaktif</b> kecuali sedang support.
    </p>
</div>
@endsection

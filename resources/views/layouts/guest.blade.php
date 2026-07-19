<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.app_name')) — {{ __('app.login') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- flex kolom + margin:auto pada kartu = tetap center di desktop,
     tapi halaman BISA scroll di HP saat kartu lebih tinggi dari layar --}}
<body style="min-height:100vh;display:flex;flex-direction:column;background:linear-gradient(135deg,#052e16 0%,#166534 35%,#15803d 65%,#1a6b3c 100%);position:relative;overflow-x:hidden;">

    {{-- Background decorative circles (fixed → tidak menambah area scroll) --}}
    <div style="position:fixed;top:-15%;left:-10%;width:500px;height:500px;border-radius:50%;background:rgba(255,255,255,0.04);pointer-events:none;"></div>
    <div style="position:fixed;bottom:-20%;right:-5%;width:600px;height:600px;border-radius:50%;background:rgba(212,160,23,0.1);pointer-events:none;"></div>
    <div style="position:fixed;top:30%;right:10%;width:300px;height:300px;border-radius:50%;background:rgba(74,222,128,0.08);pointer-events:none;"></div>

    {{-- Lang toggle --}}
    <div style="position:absolute;top:1.25rem;right:1.25rem;z-index:20;display:flex;align-items:center;background:rgba(255,255,255,0.15);border-radius:8px;padding:2px;gap:1px;border:1px solid rgba(255,255,255,0.25);">
        <a href="{{ route('locale.switch', 'id') }}"
           style="font-size:0.7rem;font-weight:700;padding:3px 10px;border-radius:6px;text-decoration:none;transition:all 0.15s;
                  {{ app()->getLocale() === 'id' ? 'background:#fff;color:#166534;' : 'color:rgba(255,255,255,0.8);' }}">
            ID
        </a>
        <a href="{{ route('locale.switch', 'en') }}"
           style="font-size:0.7rem;font-weight:700;padding:3px 10px;border-radius:6px;text-decoration:none;transition:all 0.15s;
                  {{ app()->getLocale() === 'en' ? 'background:#fff;color:#166534;' : 'color:rgba(255,255,255,0.8);' }}">
            EN
        </a>
    </div>

    <div style="position:relative;z-index:10;width:100%;max-width:440px;padding:3.5rem 1.25rem 1.5rem;margin:auto;">
        @yield('content')
    </div>

    <div style="position:relative;text-align:center;z-index:10;padding:0 1rem 1.25rem;">
        <p style="color:rgba(255,255,255,0.9);font-size:0.72rem;">{{ __('app.copyright', ['year' => date('Y')]) }}</p>
    </div>
</body>
</html>

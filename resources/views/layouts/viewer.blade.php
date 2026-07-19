<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PADI PRECISION')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    <style>
        :root { --sw: 240px; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter','Segoe UI',sans-serif; background: #f0fdf4; min-height: 100vh; margin: 0; }

        /* ── SIDEBAR ─────────────────────────────────── */
        #viewer-sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sw); height: 100vh;
            background: linear-gradient(170deg, #0f3d20 0%, #14532d 40%, #166534 100%);
            display: flex; flex-direction: column;
            z-index: 300;
            box-shadow: 4px 0 24px rgba(0,0,0,0.18);
            transition: transform .28s cubic-bezier(.4,0,.2,1);
        }

        /* ── MAIN CONTENT ────────────────────────────── */
        #viewer-main {
            transition: margin-left .28s cubic-bezier(.4,0,.2,1);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* ── TOPBAR ──────────────────────────────────── */
        .viewer-topbar {
            position: sticky; top: 0; z-index: 200;
            background: linear-gradient(90deg, #0f3d20, #15803d);
            padding: 11px 20px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,.2);
        }
        .viewer-topbar .tb-left  { display: flex; align-items: center; gap: 12px; }
        .viewer-topbar .tb-brand { color: #fff; font-weight: 800; font-size: .95rem; letter-spacing: .4px; }
        .btn-toggle {
            background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
            color: #fff; border-radius: 8px; padding: 5px 9px;
            cursor: pointer; font-size: 1rem; line-height: 1;
            transition: background .15s;
        }
        .btn-toggle:hover { background: rgba(255,255,255,.22); }

        /* Page title in topbar */
        .topbar-page { color: rgba(255,255,255,.75); font-size: .82rem; font-weight: 500; }

        /* ── OVERLAY ─────────────────────────────────── */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 299;
        }
        .sidebar-overlay.show { display: block; }

        /* ── SIDEBAR INTERNALS ───────────────────────── */
        .sb-brand {
            padding: 20px 16px 14px;
            border-bottom: 1px solid rgba(255,255,255,.09);
            display: flex; align-items: center; justify-content: space-between;
        }
        .sb-brand .logo-row { display: flex; align-items: center; gap: 10px; }
        .sb-brand img { width: 34px; height: 34px; border-radius: 9px; object-fit: cover; border: 2px solid rgba(255,255,255,.25); }
        .sb-brand .logo-ph { width: 34px; height: 34px; border-radius: 9px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .sb-brand .app-name { color: #fff; font-weight: 800; font-size: .9rem; }
        .sb-brand .app-sub  { color: rgba(255,255,255,.45); font-size: .65rem; }
        .btn-close-sb {
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
            color: rgba(255,255,255,.7); border-radius: 7px; padding: 4px 8px;
            cursor: pointer; font-size: .9rem; line-height: 1; transition: all .15s;
        }
        .btn-close-sb:hover { background: rgba(255,255,255,.18); color: #fff; }

        .sb-user {
            padding: 12px 16px; display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .sb-user .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: .85rem; flex-shrink: 0;
        }
        .sb-user .uname { color: #fff; font-size: .82rem; font-weight: 600; }
        .sb-user .urole {
            color: rgba(255,255,255,.45); font-size: .65rem;
            background: rgba(255,255,255,.1); border-radius: 999px;
            padding: 1px 7px; display: inline-block; margin-top: 2px;
        }

        .sb-nav { flex: 1; padding: 12px 10px; display: flex; flex-direction: column; gap: 3px; overflow-y: auto; }
        .sb-nav a {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 13px; border-radius: 11px;
            color: rgba(255,255,255,.7); text-decoration: none;
            font-size: .84rem; font-weight: 500; transition: all .15s; position: relative;
        }
        .sb-nav a:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sb-nav a.active {
            background: rgba(255,255,255,.16); color: #fff; font-weight: 700;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        .sb-nav a.active::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 55%; background: #86efac; border-radius: 0 3px 3px 0;
        }
        .nav-icon { width: 20px; text-align: center; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .notif-dot {
            margin-left: auto; background: #ef4444; color: #fff;
            font-size: .62rem; font-weight: 700; padding: 2px 6px; border-radius: 999px;
        }

        .sb-footer { padding: 12px 10px; border-top: 1px solid rgba(255,255,255,.07); }
        .btn-logout {
            width: 100%; display: flex; align-items: center; gap: 10px;
            padding: 10px 13px; background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12); border-radius: 11px;
            color: rgba(255,255,255,.75); font-size: .82rem; cursor: pointer;
            transition: all .15s; font-family: inherit;
        }
        .btn-logout:hover { background: rgba(239,68,68,.2); border-color: rgba(239,68,68,.4); color: #fca5a5; }

        /* ── PAGE CONTENT ────────────────────────────── */
        .page-body {
            flex: 1;
            padding: 28px 32px;
            max-width: 900px;
            width: 100%;
            margin: 0 auto; /* CENTER konten */
        }

        /* ── CARDS ───────────────────────────────────── */
        .v-card {
            background: #fff; border-radius: 20px; padding: 22px;
            box-shadow: 0 2px 14px rgba(0,0,0,.06);
            margin-bottom: 18px; border: 1px solid rgba(0,0,0,.04);
        }
        .v-card-title {
            font-weight: 700; font-size: .92rem; color: #1f2937;
            margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
        }

        /* Status hero gradient */
        .status-hero {
            border-radius: 24px; padding: 36px 28px; text-align: center;
            margin-bottom: 18px; position: relative; overflow: hidden;
        }
        .status-hero.s-drying { background: linear-gradient(135deg,#14532d 0%,#15803d 55%,#16a34a 100%); }
        .status-hero.s-paused { background: linear-gradient(135deg,#78350f 0%,#b45309 100%); }
        .status-hero.s-idle   { background: linear-gradient(135deg,#1e3a5f 0%,#374151 100%); }
        .status-hero::after { content:'';position:absolute;top:-40px;right:-40px;width:150px;height:150px;background:rgba(255,255,255,.06);border-radius:50%; }
        .status-hero::before{ content:'';position:absolute;bottom:-20px;left:-20px;width:90px;height:90px;background:rgba(255,255,255,.04);border-radius:50%; }
        .s-emoji { font-size:4rem;line-height:1;margin-bottom:10px; }
        .s-text  { font-size:1.65rem;font-weight:800;color:#fff;line-height:1.2; }
        .s-sub   { font-size:.83rem;color:rgba(255,255,255,.75);margin-top:8px; }
        .s-pills { margin-top:12px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap; }

        /* Sensor grid */
        .sensor-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px; }
        .sensor-card { border-radius:20px;padding:22px 14px;text-align:center;position:relative;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.1); }
        .sc-hot  { background:linear-gradient(145deg,#b91c1c,#ef4444); }
        .sc-cold { background:linear-gradient(145deg,#1d4ed8,#60a5fa); }
        .sc-ok   { background:linear-gradient(145deg,#15803d,#34d399); }
        .sc-rh-high { background:linear-gradient(145deg,#c2410c,#f97316); }
        .sc-rh-mid  { background:linear-gradient(145deg,#b45309,#fbbf24); }
        .sc-rh-ok   { background:linear-gradient(145deg,#0369a1,#38bdf8); }
        .sensor-card::after { content:'';position:absolute;bottom:-18px;right:-18px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%; }
        .sc-emoji { font-size:2rem;opacity:.9; }
        .sc-value { font-size:2.8rem;font-weight:800;color:#fff;line-height:1.1;margin:6px 0 3px; }
        .sc-label { font-size:.75rem;color:rgba(255,255,255,.85);font-weight:500; }
        .sc-status{ font-size:.7rem;color:rgba(255,255,255,.75);margin-top:4px; }

        /* Device grid */
        .device-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:10px; }
        .device-box { border-radius:14px;padding:14px 8px;text-align:center;border:1.5px solid transparent; }
        .db-on  { background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-color:#86efac; }
        .db-off { background:#f9fafb;border-color:#e5e7eb; }
        .db-warn{ background:linear-gradient(135deg,#fef9c3,#fde68a);border-color:#fcd34d; }
        .db-emoji{ font-size:1.4rem; }
        .db-label{ font-size:.74rem;font-weight:600;color:#374151;margin:4px 0; }

        /* Progress */
        .prog-wrap { height:22px;background:#f3f4f6;border-radius:11px;overflow:hidden; }
        .prog-bar  { height:100%;border-radius:11px;background:linear-gradient(90deg,#15803d,#22c55e,#86efac);transition:width .8s ease;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;font-size:.73rem;font-weight:700;color:#fff;min-width:30px; }

        /* Pill */
        .pill { display:inline-block;padding:3px 10px;border-radius:999px;font-size:.74rem;font-weight:700; }
        .pill-green  { background:#dcfce7;color:#166534; }
        .pill-yellow { background:#fef9c3;color:#854d0e; }
        .pill-red    { background:#fee2e2;color:#991b1b; }
        .pill-gray   { background:#f3f4f6;color:#374151; }
        .pill-blue   { background:#dbeafe;color:#1e40af; }
        .pill-white  { background:rgba(255,255,255,.2);color:#fff; }

        /* AI box */
        .ai-box { background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-left:4px solid #22c55e;border-radius:0 14px 14px 0;padding:16px; }

        /* Flash */
        .flash-ok  { background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#166534;border-radius:14px;padding:12px 16px;margin-bottom:16px;font-size:.87rem;font-weight:500; }
        .flash-err { background:linear-gradient(135deg,#fee2e2,#fecaca);color:#991b1b;border-radius:14px;padding:12px 16px;margin-bottom:16px;font-size:.87rem;font-weight:500; }

        /* Footer */
        .main-footer { text-align:center;color:#9ca3af;font-size:.75rem;padding:20px 28px;border-top:1px solid #e5e7eb; }

        /* ── RESPONSIVE ──────────────────────────────── */
        @media (min-width: 1024px) {
            #viewer-sidebar { transform: translateX(0) !important; }
        }
        @media (max-width: 1023px) {
            #viewer-sidebar { transform: translateX(-100%); }
            #viewer-sidebar.open { transform: translateX(0); }
            .page-body { padding: 16px; }
        }
        @media (max-width: 480px) {
            .sensor-grid { gap: 10px; }
            .s-text { font-size: 1.3rem; }
            .sc-value { font-size: 2.3rem; }
            .device-grid { grid-template-columns: repeat(2,1fr); }
            .page-body { padding: 12px; }
        }
    </style>
</head>
<body>

{{-- OVERLAY mobile --}}
<div class="sidebar-overlay" id="sb-overlay" onclick="closeSB()"></div>

{{-- SIDEBAR --}}
<aside id="viewer-sidebar">
    <div class="sb-brand">
        <div class="logo-row">
            @if(file_exists(public_path('images/logo.jpeg')))
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo">
            @else
                <div class="logo-ph">🌾</div>
            @endif
            <div>
                <div class="app-name">PADI PRECISION</div>
                <div class="app-sub">Smart Drying System</div>
            </div>
        </div>
        {{-- Tombol tutup sidebar (desktop) --}}
        <button class="btn-close-sb" onclick="toggleSB()" title="Sembunyikan sidebar">✕</button>
    </div>

    <div class="sb-user">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <div>
            <div class="uname">{{ auth()->user()->name }}</div>
            <div class="urole">Petani</div>
        </div>
    </div>

    <nav class="sb-nav">
        <a href="{{ route('viewer.dashboard') }}" class="{{ request()->routeIs('viewer.dashboard') ? 'active' : '' }}" onclick="closeSBmobile()">
            <span class="nav-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            </span>
            Status Pengeringan
        </a>
        <a href="{{ route('viewer.chat') }}" class="{{ request()->routeIs('viewer.chat') ? 'active' : '' }}" onclick="closeSBmobile()">
            <span class="nav-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            Tanya AI
        </a>
        <a href="{{ route('viewer.batches') }}" class="{{ request()->routeIs('viewer.batches') ? 'active' : '' }}" onclick="closeSBmobile()">
            <span class="nav-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </span>
            Riwayat Batch
        </a>
        <a href="{{ route('viewer.notifications') }}" class="{{ request()->routeIs('viewer.notifications') ? 'active' : '' }}" onclick="closeSBmobile()">
            <span class="nav-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </span>
            Notifikasi
            @php $unread = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count(); @endphp
            @if($unread > 0)<span class="notif-dot">{{ $unread }}</span>@endif
        </a>
        <a href="{{ route('viewer.request') }}" class="{{ request()->routeIs('viewer.request*') ? 'active' : '' }}" onclick="closeSBmobile()">
            <span class="nav-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            Ajukan Pengeringan
            @php $pendingReq = \App\Models\DryingBatch::where('requested_by', auth()->id())->where('request_status','pending')->exists(); @endphp
            @if($pendingReq)<span class="notif-dot" style="background:#f59e0b">!</span>@endif
        </a>
    </nav>

    <div class="sb-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Keluar
            </button>
        </form>
    </div>
</aside>

{{-- MAIN --}}
<div id="viewer-main">
    {{-- TOPBAR --}}
    <div class="viewer-topbar">
        <div class="tb-left">
            <button class="btn-toggle" onclick="toggleSB()" title="Toggle sidebar">☰</button>
            <span class="tb-brand">🌾 PADI PRECISION</span>
            <span class="topbar-page d-none d-md-inline">/ @yield('title', 'Dashboard')</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="color:rgba(255,255,255,.75);font-size:.82rem" class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
            <a href="{{ route('viewer.notifications') }}" style="color:#fff;text-decoration:none;font-size:1rem;position:relative">
                🔔 @if(($unread??0)>0)<span style="position:absolute;top:-3px;right:-3px;background:#ef4444;color:#fff;font-size:.58rem;padding:1px 4px;border-radius:999px">{{$unread}}</span>@endif
            </a>
        </div>
    </div>

    {{-- PAGE CONTENT --}}
    <div class="page-body">
        @if(session('success'))<div class="flash-ok">✅ {{ session('success') }}</div>@endif
        @if(session('error'))<div class="flash-err">⚠️ {{ session('error') }}</div>@endif
        @yield('content')
    </div>

    <footer class="main-footer">
        PADI PRECISION — Sistem Pengeringan Gabah Cerdas &copy; {{ date('Y') }}
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const SB    = document.getElementById('viewer-sidebar');
    const MAIN  = document.getElementById('viewer-main');
    const OVR   = document.getElementById('sb-overlay');
    const SW    = 240; // harus sama dengan --sw
    let isOpen  = window.innerWidth >= 1024;

    function applyState() {
        if (window.innerWidth >= 1024) {
            // Desktop: geser main content
            SB.style.transform = isOpen ? 'translateX(0)' : 'translateX(-100%)';
            MAIN.style.marginLeft = isOpen ? SW + 'px' : '0';
            OVR.classList.remove('show');
        } else {
            // Mobile: overlay mode, main tidak geser
            SB.classList.toggle('open', isOpen);
            MAIN.style.marginLeft = '0';
            OVR.classList.toggle('show', isOpen);
        }
    }

    function toggleSB() { isOpen = !isOpen; applyState(); }
    function closeSB()  { isOpen = false; applyState(); }
    function closeSBmobile() { if (window.innerWidth < 1024) closeSB(); }

    window.addEventListener('resize', () => {
        isOpen = window.innerWidth >= 1024;
        applyState();
    });

    applyState();
</script>
@stack('scripts')
</body>
</html>

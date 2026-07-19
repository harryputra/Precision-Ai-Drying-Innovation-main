<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', v => { localStorage.setItem('darkMode', v); document.documentElement.classList.toggle('dark', v); })"
      :class="darkMode ? 'dark' : ''"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Konfigurasi Echo/Reverb runtime (dibaca resources/js/app.js) --}}
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.frontend.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.frontend.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.frontend.scheme') }}">
    <title>@yield('title', 'Dashboard') — Padi PRECISION</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Apply dark mode before render to prevent flash
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        /* === Mobile-first base === */
        body { background: #f0fdf4; color: #1e293b; }

        /* Sidebar: hidden off-screen on mobile, shown on desktop */
        #app-sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; height: 100dvh; width: 220px;
            z-index: 50; display: flex; flex-direction: column;
            background: linear-gradient(180deg, #052e16 0%, #166534 40%, #15803d 100%);
            box-shadow: 4px 0 24px rgba(22,101,52,0.2);
            transition: transform 0.28s cubic-bezier(.4,0,.2,1);
            transform: translateX(-100%);
        }
        /* HP: drawer sedikit lebih lebar agar item nyaman disentuh */
        @media (max-width: 1023.98px) {
            #app-sidebar { width: min(80vw, 256px); }
        }
        /* Tombol tutup drawer — hanya tampil di mobile */
        .sidebar-close { display: none; }
        @media (max-width: 1023.98px) {
            .sidebar-close {
                display: flex; align-items: center; justify-content: center;
                width: 34px; height: 34px; margin-left: auto; flex-shrink: 0;
                background: rgba(255,255,255,0.12); border: none; border-radius: 9px;
                color: rgba(255,255,255,0.85); cursor: pointer;
            }
        }
        /* Desktop: sidebar always visible */
        @media (min-width: 1024px) {
            #app-sidebar { transform: translateX(0); }
            #main-content { margin-left: 220px; }
        }

        #main-content {
            margin-left: 0;
            min-height: 100vh;
            background: #f0fdf4;
            /* room for bottom nav + home indicator (safe area) on mobile */
            padding-bottom: calc(64px + env(safe-area-inset-bottom));
            transition: margin-left 0.28s cubic-bezier(.4,0,.2,1);
        }
        @media (min-width: 1024px) {
            #main-content { padding-bottom: 0; }
        }

        /* === Bottom Nav (mobile only) === */
        #bottom-nav {
            display: flex;
            position: fixed; bottom: 0; left: 0; right: 0;
            height: calc(56px + env(safe-area-inset-bottom));
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 40;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.07);
        }
        #bottom-nav a {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 2px; text-decoration: none;
            color: #64748b; font-size: 0.58rem; font-weight: 600;
            letter-spacing: 0.03em; transition: color 0.15s;
            -webkit-tap-highlight-color: transparent;
        }
        #bottom-nav a.active, #bottom-nav a:active { color: #16a34a; }
        #bottom-nav a svg { width: 20px; height: 20px; }
        @media (min-width: 1024px) {
            #bottom-nav { display: none; }
        }
    </style>
</head>
<body x-data="{
        sidebarOpen: window.innerWidth >= 1024,
        winW: window.innerWidth,
        notifCount: {{ auth()->user()?->unreadNotificationsCount() ?? 0 }}
     }"
     x-init="window.addEventListener('resize', () => {
         // Hanya bereaksi bila LEBAR berubah — di HP, show/hide URL bar
         // memicu resize (tinggi saja) dan tidak boleh menutup drawer
         if (window.innerWidth !== winW) { winW = window.innerWidth; sidebarOpen = winW >= 1024; }
     })"
     :class="{ 'no-scroll': sidebarOpen && winW < 1024 }">

    {{-- ===== SIDEBAR ===== --}}
    <aside id="app-sidebar"
           :style="sidebarOpen ? 'transform:translateX(0)' : 'transform:translateX(-100%)'">

        {{-- Logo --}}
        <div style="padding:1rem 0.875rem;border-bottom:1px solid rgba(255,255,255,0.1);flex-shrink:0;background:linear-gradient(180deg,rgba(5,46,22,0.8),transparent);display:flex;align-items:center;gap:0.5rem;">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:0.625rem;text-decoration:none;min-width:0;">
                <div style="position:relative;flex-shrink:0;">
                    <img src="{{ asset('images/logo.jpeg') }}" alt="Logo" style="width:36px;height:36px;border-radius:10px;object-fit:cover;box-shadow:0 2px 12px rgba(212,160,23,0.4),0 0 0 2px rgba(212,160,23,0.3);">
                    <div style="position:absolute;inset:0;border-radius:10px;box-shadow:inset 0 0 0 1px rgba(255,255,255,0.2);"></div>
                </div>
                <div>
                    <div style="font-size:1.15rem;font-weight:900;color:#ffffff;line-height:1;letter-spacing:0.06em;text-shadow:0 1px 8px rgba(0,0,0,0.3);">PADI</div>
                    <div style="font-size:0.52rem;color:rgba(212,160,23,0.85);font-weight:700;letter-spacing:0.1em;margin-top:2px;text-transform:uppercase;">Precision · AI · Drying</div>
                </div>
            </a>
            {{-- Tutup drawer (mobile) --}}
            <button type="button" class="sidebar-close" @click="sidebarOpen = false" aria-label="Tutup menu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav style="flex:1;overflow-y:auto;padding:0.5rem 0.625rem;">

            <a href="{{ route('dashboard') }}"
               class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                </svg>
                {{ __('app.nav_dashboard') }}
            </a>

            <div class="sidebar-section-label">{{ __('app.nav_monitoring') }}</div>

            <a href="{{ route('web.devices.index') }}"
               class="sidebar-item {{ request()->routeIs('devices.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/>
                </svg>
                {{ __('app.nav_devices') }}
            </a>

            <a href="{{ route('web.batches.index') }}"
               class="sidebar-item {{ request()->routeIs('batches.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                {{ __('app.nav_batches') }}
            </a>

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'operator')
            <a href="{{ route('web.batches.requests') }}"
               class="sidebar-item {{ request()->routeIs('web.batches.requests') ? 'active' : '' }}"
               style="padding-left:2rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                Request Pengeringan
                @php $pendingCount = \App\Models\DryingBatch::where('request_status','pending')->count(); @endphp
                @if($pendingCount > 0)
                <span style="margin-left:auto;background:#f97316;color:#fff;border-radius:999px;padding:1px 7px;font-size:.65rem;font-weight:700;">{{ $pendingCount }}</span>
                @endif
            </a>
            @endif

            <a href="{{ route('web.sensor.index') }}"
               class="sidebar-item {{ request()->routeIs('sensor.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                {{ __('app.nav_sensor') }}
            </a>

            <a href="{{ route('web.weather.index') }}"
               class="sidebar-item {{ request()->routeIs('weather.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>
                </svg>
                {{ __('app.nav_weather') }}
            </a>

            <div class="sidebar-section-label">{{ __('app.nav_ai_system') }}</div>

            <a href="{{ route('web.ai.decisions') }}"
               class="sidebar-item {{ request()->routeIs('ai.decisions') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>
                </svg>
                {{ __('app.nav_ai_decisions') }}
            </a>

            <a href="{{ route('web.ai.chat') }}"
               class="sidebar-item {{ request()->routeIs('ai.chat') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                {{ __('app.nav_ai_chat') }}
            </a>

            <a href="{{ route('web.ai.summary') }}"
               class="sidebar-item {{ request()->routeIs('web.ai.summary') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/>
                </svg>
                {{ __('app.nav_ai_summary') }}
            </a>

            <a href="{{ route('web.knowledge.index') }}"
               class="sidebar-item {{ request()->routeIs('knowledge.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                {{ __('app.nav_knowledge') }}
            </a>

            <div class="sidebar-section-label">{{ __('app.nav_system') }}</div>

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'operator')
            <a href="{{ route('simulator.index') }}"
               class="sidebar-item {{ request()->routeIs('simulator.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                {{ __('app.nav_simulator') }}
            </a>
            @endif

            <a href="{{ route('web.notifications.index') }}"
               class="sidebar-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                {{ __('app.nav_notifications') }}
                <span x-show="notifCount > 0" x-cloak
                      x-text="notifCount > 9 ? '9+' : notifCount"
                      style="margin-left:auto;background:#ef4444;color:#fff;font-size:0.58rem;font-weight:700;padding:1px 5px;border-radius:10px;"></span>
            </a>

            <a href="{{ route('web.logs.index') }}"
               class="sidebar-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                {{ __('app.nav_logs') }}
            </a>

            <a href="{{ route('web.profile.show') }}"
               class="sidebar-item {{ request()->routeIs('web.profile.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                {{ __('app.nav_profile') }}
            </a>

            @if(auth()->user()?->isAdmin())
            <div class="sidebar-section-label">{{ __('app.nav_admin') }}</div>

            <a href="{{ route('admin.users.index') }}"
               class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                {{ __('app.user_management') }}
            </a>

            <a href="{{ route('admin.quick-login.index') }}"
               class="sidebar-item {{ request()->routeIs('admin.quick-login.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                Quick Login
            </a>

            <a href="{{ route('admin.api-settings.index') }}"
               class="sidebar-item {{ request()->routeIs('admin.api-settings.*') ? 'active' : '' }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                </svg>
                API Settings
            </a>
            @endif
        </nav>

        {{-- User --}}
        <div style="padding:0.75rem;border-top:1px solid rgba(255,255,255,0.12);flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:0.625rem;padding:0.375rem 0.625rem;border-radius:9px;background:rgba(255,255,255,0.1);">
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.7rem;font-weight:700;color:#fff;">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.75rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ auth()->user()?->name ?? 'User' }}
                    </div>
                    <div style="font-size:0.6rem;color:rgba(255,255,255,0.75);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ auth()->user()?->email ?? '' }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                    @csrf
                    <button type="submit" title="Logout"
                            style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.7);padding:4px;border-radius:6px;transition:color 0.15s;display:flex;"
                            onmouseover="this.style.color='#fca5a5'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16,17 21,12 16,7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen && winW < 1024"
         x-cloak
         @click="sidebarOpen = false"
         style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:40;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div id="main-content"
         :style="sidebarOpen && winW >= 1024 ? 'margin-left:220px' : 'margin-left:0'">

        {{-- Topbar --}}
        <header class="topbar"
                style="position:sticky;top:0;z-index:30;padding:0.5rem 1rem;display:flex;align-items:center;gap:0.75rem;">

            {{-- Hamburger: mobile = always shown, desktop = toggle --}}
            <button @click="sidebarOpen = !sidebarOpen"
                    style="background:none;border:none;cursor:pointer;color:#0f172a;padding:5px;border-radius:7px;display:flex;align-items:center;flex-shrink:0;transition:all 0.15s;"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            <div style="flex:1;min-width:0;">
                <h1 style="font-size:0.9rem;font-weight:700;color:#1e293b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    @yield('title', 'Dashboard')
                </h1>
                @hasSection('breadcrumb')
                <div style="font-size:0.68rem;color:#64748b;margin-top:1px;">@yield('breadcrumb')</div>
                @endif
            </div>

            {{-- Dark mode toggle --}}
            <button @click="darkMode = !darkMode"
                    title="Toggle dark mode"
                    style="background:none;border:none;cursor:pointer;padding:5px;border-radius:7px;display:flex;align-items:center;flex-shrink:0;transition:all 0.15s;"
                    :style="darkMode ? 'color:#fbbf24' : 'color:#0f172a'"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <svg x-show="!darkMode" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg x-show="darkMode" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </button>

            {{-- Lang toggle --}}
            <div style="display:flex;align-items:center;background:#f0fdf4;border-radius:8px;padding:2px;gap:1px;flex-shrink:0;border:1px solid #bbf7d0;">
                <a href="{{ route('locale.switch', 'id') }}"
                   style="font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:6px;text-decoration:none;transition:all 0.15s;
                          {{ app()->getLocale() === 'id' ? 'background:#166534;color:#fff;box-shadow:0 1px 4px rgba(22,101,52,0.3);' : 'color:#64748b;' }}">
                    ID
                </a>
                <a href="{{ route('locale.switch', 'en') }}"
                   style="font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:6px;text-decoration:none;transition:all 0.15s;
                          {{ app()->getLocale() === 'en' ? 'background:#166534;color:#fff;box-shadow:0 1px 4px rgba(22,101,52,0.3);' : 'color:#64748b;' }}">
                    EN
                </a>
            </div>

            <a href="{{ route('web.notifications.index') }}"
               style="position:relative;color:#0f172a;display:flex;flex-shrink:0;text-decoration:none;padding:5px;border-radius:7px;transition:all 0.15s;"
               onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span x-show="notifCount > 0" x-cloak class="notif-badge"
                      x-text="notifCount > 9 ? '9+' : notifCount"></span>
            </a>

            <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0.625rem;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;flex-shrink:0;">
                <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#166534,#16a34a);display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:#fff;">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </div>
                <span style="font-size:0.75rem;font-weight:600;color:#166534;display:none;" class="topbar-name">{{ auth()->user()?->name ?? 'User' }}</span>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div style="margin:0.75rem 1rem 0;padding:0.625rem 0.875rem;background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #10b981;border-radius:9px;color:#166534;font-size:0.8rem;display:flex;align-items:center;gap:0.5rem;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="margin:0.75rem 1rem 0;padding:0.625rem 0.875rem;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:9px;color:#b91c1c;font-size:0.8rem;display:flex;align-items:center;gap:0.5rem;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        {{-- Page Content --}}
        <main class="main-pad">
            @yield('content')
        </main>
    </div>

    {{-- ===== BOTTOM NAV (mobile only) ===== --}}
    <nav id="bottom-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            {{ __('app.nav_dashboard') }}
        </a>
        <a href="{{ route('web.sensor.index') }}" class="{{ request()->routeIs('sensor.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            {{ __('app.nav_sensor_short') }}
        </a>
        <a href="{{ route('web.ai.chat') }}" class="{{ request()->routeIs('ai.chat') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            {{ __('app.nav_ai_chat') }}
        </a>
        <a href="{{ route('web.batches.index') }}" class="{{ request()->routeIs('batches.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
            {{ __('app.nav_batches_short') }}
        </a>
        <a href="{{ route('web.notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" style="position:relative;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span x-show="notifCount > 0" x-cloak
                  style="position:absolute;top:4px;right:12px;width:14px;height:14px;background:#ef4444;border-radius:50%;font-size:0.5rem;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;"
                  x-text="notifCount > 9 ? '9+' : notifCount"></span>
            {{ __('app.nav_notif_short') }}
        </a>
    </nav>

    {{-- jQuery + DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <style>
        /* DataTables dark theme integration */
        table.dataTable thead th,
        table.dataTable thead td { border-bottom: 1px solid #e2e8f0; background: transparent; }
        table.dataTable.no-footer { border-bottom: none; }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px 10px;
            font-size: 0.8rem; outline: none; margin-left: 6px;
        }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #1d4ed8; }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 3px 6px; font-size: 0.8rem;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { font-size: 0.78rem; color: #475569; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important; font-size: 0.78rem !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #1d4ed8 !important; color: #fff !important; border-color: #1d4ed8 !important;
        }
        .dataTables_wrapper { padding: 0.5rem 1rem 1rem; }
        table.dataTable thead .sorting::after,
        table.dataTable thead .sorting_asc::after,
        table.dataTable thead .sorting_desc::after { opacity: 0.6; }

        /* Mobile: toolbar DataTables menumpuk rapi, tabel scroll sendiri */
        @media (max-width: 767.98px) {
            .dataTables_wrapper { padding: 0.5rem 0.75rem 0.75rem; }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none; text-align: left; margin-bottom: 0.5rem;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: min(100%, 240px); margin-left: 4px;
            }
            .dataTables_wrapper .dataTables_paginate { float: none; text-align: left; }
        }
    </style>

    @stack('scripts')

    {{-- ===== TOAST NOTIFICATIONS ===== --}}
    <div id="toast-container"
         style="position:fixed;bottom:70px;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;max-width:320px;"
         x-data="toastManager()"
         @toast.window="addToast($event.detail)">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 :style="`pointer-events:auto;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);border-left:4px solid ${toast.color};padding:0.75rem 1rem;display:flex;align-items:flex-start;gap:0.625rem;min-width:260px;`"
                 @click="dismiss(toast.id)" style="cursor:pointer;">
                <div :style="`width:8px;height:8px;border-radius:50%;background:${toast.color};flex-shrink:0;margin-top:5px;`"></div>
                <div style="flex:1;min-width:0;">
                    <div x-text="toast.title" style="font-size:0.8rem;font-weight:700;color:#0f172a;margin-bottom:2px;"></div>
                    <div x-text="toast.message" style="font-size:0.73rem;color:#475569;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"></div>
                </div>
                <button @click.stop="dismiss(toast.id)" style="background:none;border:none;padding:0;cursor:pointer;color:#94a3b8;flex-shrink:0;line-height:1;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    @auth
    <script>
    function toastManager() {
        return {
            toasts: [],
            addToast({ title, message, type = 'info' }) {
                const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6', ai: '#7c3aed' };
                const id = Date.now();
                this.toasts.push({ id, title, message, color: colors[type] ?? colors.info, visible: true });
                setTimeout(() => this.dismiss(id), 5000);
            },
            dismiss(id) {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
            },
        };
    }

    // Echo: listen on private notifications channel
    if (window.Echo) {
        window.Echo.private('notifications.{{ auth()->id() }}')
            .listen('.NotificationSent', (e) => {
                const notif = e.notification;
                const typeMap = { alert: 'error', warning: 'warning', info: 'info', success: 'success', ai_decision: 'ai' };
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        title:   notif.title   || 'Notifikasi',
                        message: notif.message || '',
                        type:    typeMap[notif.type] ?? 'info',
                    }
                }));
                // bump badge count
                const badge = document.querySelector('[x-text]');
                if (typeof window.Alpine !== 'undefined') {
                    // update Alpine notifCount via custom event
                    document.body.dispatchEvent(new CustomEvent('notif-received'));
                }
            });
    }
    </script>
    @endauth
</body>
</html>

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuickLoginConfig;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Quick-Login untuk pengujian/demo — aman meski kode ikut ter-deploy ke
 * produksi: saat config disabled SEMUA endpoint di sini balas 404.
 * Token URL acak 128-bit divalidasi constant-time; tiap percobaan diaudit.
 */
class QuickLoginController extends Controller
{
    private function activeConfigOr404(string $token): QuickLoginConfig
    {
        $config = QuickLoginConfig::current();

        if (!$config->isActive() || !$config->matchesToken($token)) {
            SystemLog::write('warning', 'quick_login_denied', 'Percobaan quick-login ditolak', [
                'ip' => request()->ip(),
            ], channel: 'auth');
            abort(404);
        }

        return $config;
    }

    public function show(string $token): View
    {
        $this->activeConfigOr404($token);

        return view('auth.quick-login', [
            'token' => $token,
            'users' => QuickLoginConfig::candidates(),
        ]);
    }

    public function login(Request $request, string $token): RedirectResponse
    {
        $this->activeConfigOr404($token);

        $request->validate(['user_id' => 'required|integer']);

        // Hanya akun kandidat (satu per role) yang boleh — bukan user bebas
        $user = QuickLoginConfig::candidates()->firstWhere('id', (int) $request->user_id);
        abort_unless($user !== null, 404);

        Auth::login($user);
        $request->session()->regenerate();

        SystemLog::write('info', 'quick_login', "Quick-login sebagai {$user->email}", [
            'ip'   => $request->ip(),
            'role' => $user->role,
        ], userId: $user->id, channel: 'auth');

        return $user->role === 'viewer'
            ? redirect()->route('viewer.dashboard')
            : redirect()->route('dashboard');
    }
}

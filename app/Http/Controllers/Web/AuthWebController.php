<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuickLoginConfig;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthWebController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Tombol quick-login hanya bila diaktifkan admin/seed demo
        $quick = QuickLoginConfig::current();
        $showQuick = $quick->isActive() && $quick->show_button_on_login;

        return view('auth.login', [
            'quickToken' => $showQuick ? $quick->token : null,
            'quickUsers' => $showQuick ? QuickLoginConfig::candidates() : collect(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // max:72 — bcrypt memotong diam-diam di 72 byte
        $credentials = $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:72',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Viewer (petani) → dashboard sederhana, bukan dashboard admin/operator
            if (Auth::user()->role === 'viewer') {
                return redirect()->route('viewer.dashboard');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email atau password salah.']);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'max:72', 'confirmed',
                Password::min(8)->mixedCase()->numbers()],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'viewer',
        ]);

        return redirect()
            ->route('login')
            ->with('success', __('app.register_success'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

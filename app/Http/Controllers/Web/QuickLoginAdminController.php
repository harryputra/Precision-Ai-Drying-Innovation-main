<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuickLoginConfig;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan Quick-Login oleh admin — toggle on/off TANPA restart.
 * Di produksi default nonaktif; admin bisa mengaktifkan sementara
 * (dengan expiry) untuk keperluan support, lalu mematikan lagi.
 */
class QuickLoginAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.quick-login', ['config' => QuickLoginConfig::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action'        => 'required|in:enable,disable,toggle_button,regenerate',
            'expires_hours' => 'nullable|integer|min:1|max:720',
        ]);

        $config = QuickLoginConfig::current();

        switch ($data['action']) {
            case 'enable':
                $config->update([
                    'enabled'    => true,
                    'token'      => $config->token ?: bin2hex(random_bytes(16)),
                    'expires_at' => !empty($data['expires_hours'])
                        ? now()->addHours((int) $data['expires_hours'])
                        : null,
                ]);
                break;

            case 'disable':
                // Token di-null-kan → URL lama mati permanen
                $config->update([
                    'enabled'              => false,
                    'token'                => null,
                    'show_button_on_login' => false,
                    'expires_at'           => null,
                ]);
                break;

            case 'toggle_button':
                $config->update(['show_button_on_login' => !$config->show_button_on_login]);
                break;

            case 'regenerate':
                $config->update(['token' => bin2hex(random_bytes(16))]);
                break;
        }

        SystemLog::write('info', 'quick_login_config', "Quick-login: aksi '{$data['action']}' oleh admin", [
            'ip' => $request->ip(),
        ], userId: $request->user()->id, channel: 'auth');

        return back()->with('success', 'Konfigurasi Quick-Login diperbarui.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi Quick-Login — singleton (id=1).
 *
 * Lapisan keamanan:
 *  - enabled=false → semua endpoint /q/* balas 404 (tidak terlihat sama sekali)
 *  - token acak 128-bit (32 hex char) di URL, dibandingkan constant-time
 *  - expires_at opsional → token mati otomatis
 *  - setiap percobaan diaudit ke system_logs
 */
class QuickLoginConfig extends Model
{
    protected $fillable = ['enabled', 'token', 'show_button_on_login', 'expires_at'];

    protected function casts(): array
    {
        return [
            'enabled'              => 'boolean',
            'show_button_on_login' => 'boolean',
            'expires_at'           => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['enabled' => false, 'show_button_on_login' => false]
        );
    }

    public function isActive(): bool
    {
        return $this->enabled
            && $this->token !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** Perbandingan constant-time — anti timing attack. */
    public function matchesToken(string $token): bool
    {
        return $this->token !== null && hash_equals($this->token, $token);
    }

    /**
     * Akun yang boleh dipakai quick-login: satu akun pertama per role.
     * Dipakai halaman /q/{token} dan tombol di halaman login.
     */
    public static function candidates()
    {
        return collect(['admin', 'operator', 'viewer'])
            ->map(fn ($role) => User::where('role', $role)->orderBy('id')->first())
            ->filter()
            ->values();
    }
}

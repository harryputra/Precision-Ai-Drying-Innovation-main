<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Key-value settings yang tersimpan di database.
 * API keys dienkripsi otomatis; model name disimpan plain.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Static helpers ──────────────────────────────────────────────────

    /**
     * Ambil nilai setting, decrypt otomatis jika encrypted.
     * Fallback ke $default jika tidak ada di DB.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::find($key);

            if (!$setting || $setting->value === null || $setting->value === '') {
                return $default;
            }

            if ($setting->is_encrypted) {
                return Crypt::decryptString($setting->value);
            }

            return $setting->value;
        } catch (\Throwable $e) {
            Log::warning("Setting::getValue({$key}) failed", ['error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * Simpan nilai setting, encrypt otomatis jika is_encrypted = true.
     */
    public static function setValue(string $key, ?string $value, bool $encrypted = true, ?int $userId = null): void
    {
        $storedValue = null;

        if ($value !== null && $value !== '') {
            $storedValue = $encrypted ? Crypt::encryptString($value) : $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value'        => $storedValue,
                'is_encrypted' => $encrypted,
                'updated_by'   => $userId,
            ]
        );
    }

    /**
     * Ambil nilai dari DB, fallback ke config jika kosong.
     * Dipakai service untuk resolve API key.
     */
    public static function getOrConfig(string $settingKey, string $configKey, mixed $default = null): mixed
    {
        $dbValue = static::getValue($settingKey);

        if ($dbValue !== null && $dbValue !== '') {
            return $dbValue;
        }

        return config($configKey, $default);
    }

    /**
     * Mask API key untuk tampilan: "sk-xxxx****xxxx"
     */
    public static function getMasked(string $key): ?string
    {
        $value = static::getValue($key);

        if (!$value || strlen($value) < 8) {
            return $value ? str_repeat('•', strlen($value)) : null;
        }

        $show = min(4, (int) floor(strlen($value) * 0.2));
        return substr($value, 0, $show) . str_repeat('•', strlen($value) - $show * 2) . substr($value, -$show);
    }
}

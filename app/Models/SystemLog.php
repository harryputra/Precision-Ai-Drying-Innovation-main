<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemLog extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'level',
        'channel',
        'event',
        'message',
        'context',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'loggable_type',
        'loggable_id',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper static — log cepat
    public static function write(
        string $level,
        string $event,
        string $message,
        array $context = [],
        ?int $deviceId = null,
        ?int $userId = null,
        string $channel = 'app'
    ): self {
        return static::create([
            'level'     => $level,
            'channel'   => $channel,
            'event'     => $event,
            'message'   => $message,
            'context'   => $context,
            'device_id' => $deviceId,
            'user_id'   => $userId,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'batch_id',
        'session_id',
        'role',
        'message',
        'context_data',
        'ai_model',
        'tokens_used',
        'is_helpful',
        'feedback_note',
    ];

    protected function casts(): array
    {
        return [
            'context_data' => 'array',
            'tokens_used'  => 'integer',
            'is_helpful'   => 'boolean',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DryingBatch::class, 'batch_id');
    }

    // Scopes
    public function scopeSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Helpers — ambil semua pesan dalam satu sesi, urut kronologis
    public static function getSessionHistory(string $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return static::session($sessionId)->orderBy('created_at')->get();
    }
}

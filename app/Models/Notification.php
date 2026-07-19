<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'batch_id',
        'type',
        'category',
        'title',
        'message',
        'data',
        'via_app',
        'via_email',
        'via_sms',
        'via_whatsapp',
        'read_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data'         => 'array',
            'via_app'      => 'boolean',
            'via_email'    => 'boolean',
            'via_sms'      => 'boolean',
            'via_whatsapp' => 'boolean',
            'read_at'      => 'datetime',
            'sent_at'      => 'datetime',
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
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAlerts($query)
    {
        return $query->whereIn('type', ['warning', 'alert', 'error']);
    }

    // Helpers
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}

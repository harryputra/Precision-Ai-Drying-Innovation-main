<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActuatorLog extends Model
{
    protected $fillable = [
        'device_id',
        'batch_id',
        'ai_decision_id',
        'actuator_type',
        'actuator_name',
        'command',
        'set_value',
        'actual_value',
        'unit',
        'triggered_by',
        'triggered_by_user',
        'status',
        'error_message',
        'response_time_ms',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'set_value'        => 'decimal:2',
            'actual_value'     => 'decimal:2',
            'response_time_ms' => 'integer',
            'executed_at'      => 'datetime',
        ];
    }

    // Relationships
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DryingBatch::class, 'batch_id');
    }

    public function aiDecision(): BelongsTo
    {
        return $this->belongsTo(AiDecision::class, 'ai_decision_id');
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user');
    }

    // Scopes
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('executed_at', '>=', now()->subHours($hours));
    }
}

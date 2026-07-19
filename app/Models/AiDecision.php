<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiDecision extends Model
{
    protected $fillable = [
        'device_id',
        'batch_id',
        'decision_type',
        'reasoning',
        'input_data',
        'output_action',
        'confidence_score',
        'ai_model',
        'execution_status',
        'override_reason',
        'overridden_by',
        'decided_at',
        'executed_at',
        'command_sent_at',
        'acknowledged_at',
        'esp32_command',
        'ack_status',
    ];

    protected function casts(): array
    {
        return [
            'input_data'       => 'array',
            'output_action'    => 'array',
            'esp32_command'    => 'array',
            'confidence_score' => 'decimal:3',
            'decided_at'       => 'datetime',
            'executed_at'      => 'datetime',
            'command_sent_at'  => 'datetime',
            'acknowledged_at'  => 'datetime',
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

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function actuatorLogs(): HasMany
    {
        return $this->hasMany(ActuatorLog::class, 'ai_decision_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('execution_status', 'pending');
    }

    public function scopeExecuted($query)
    {
        return $query->where('execution_status', 'executed');
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->execution_status === 'pending';
    }

    public function markExecuted(): void
    {
        $this->update([
            'execution_status' => 'executed',
            'executed_at'      => now(),
        ]);
    }

    public function markCommandSent(array $esp32Command): void
    {
        $this->update([
            'command_sent_at' => now(),
            'esp32_command'   => $esp32Command,
            'ack_status'      => 'waiting',
        ]);
    }

    public function markAcknowledged(): void
    {
        $this->update([
            'acknowledged_at'  => now(),
            'ack_status'       => 'acked',
            'execution_status' => 'executed',
            'executed_at'      => now(),
        ]);
    }

    public function scopeWaitingAck($query)
    {
        return $query->where('ack_status', 'waiting');
    }
}

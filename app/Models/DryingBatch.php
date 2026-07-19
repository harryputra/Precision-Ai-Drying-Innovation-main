<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class DryingBatch extends Model
{
    protected $fillable = [
        'device_id',
        'batch_code',
        'rice_type',
        'rice_variety',
        'initial_weight',
        'current_weight',
        'initial_moisture',
        'current_moisture',
        'target_moisture',
        'drying_method',
        'operator_name',
        'petani_name',
        'petani_phone',
        'requested_by',
        'request_status',
        'request_notes',
        'operator_notes',
        'requested_at',
        'start_time',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'initial_weight'   => 'decimal:2',
            'current_weight'   => 'decimal:2',
            'initial_moisture' => 'decimal:2',
            'current_moisture' => 'decimal:2',
            'target_moisture'  => 'decimal:2',
            'start_time'       => 'datetime',
            'end_time'         => 'datetime',
            'requested_at'     => 'datetime',
        ];
    }

    // Relationships
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class, 'batch_id');
    }

    public function aiDecisions(): HasMany
    {
        return $this->hasMany(AiDecision::class, 'batch_id');
    }

    public function actuatorLogs(): HasMany
    {
        return $this->hasMany(ActuatorLog::class, 'batch_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'batch_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['drying', 'paused']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePendingRequest($query)
    {
        return $query->where('request_status', 'pending');
    }

    // Helpers
    public function isActive(): bool
    {
        return in_array($this->status, ['drying', 'paused']);
    }

    public function moistureReduction(): float
    {
        return $this->initial_moisture - ($this->current_moisture ?? $this->initial_moisture);
    }

    public function weightLoss(): float
    {
        if (!$this->current_weight) return 0;
        return $this->initial_weight - $this->current_weight;
    }

    public function durationMinutes(): ?int
    {
        if (!$this->start_time) return null;
        $end = $this->end_time ?? now();
        return (int) $this->start_time->diffInMinutes($end);
    }

    public function latestSensorReading(): ?SensorReading
    {
        return $this->sensorReadings()->latest('recorded_at')->first();
    }

    // ── OEE Helpers ─────────────────────────────────────────────────────────

    /**
     * Availability = batch yang tidak gagal (completed/drying/paused/waiting)
     * dibagi semua batch yang sudah dimulai (start_time not null).
     * Scope: optional device_id, last N days.
     */
    public static function oeeAvailability(?int $deviceId = null, int $days = 30): float
    {
        $base = static::whereNotNull('start_time')
            ->where('start_time', '>=', now()->subDays($days));
        if ($deviceId) $base->where('device_id', $deviceId);

        $total = (clone $base)->count();
        if ($total === 0) return 0;

        $available = (clone $base)->where('status', '!=', 'failed')->count();
        return round($available / $total * 100, 1);
    }

    /**
     * Performance = rata-rata progress moisture reduction per batch completed.
     * (initial_moisture - current_moisture) / (initial_moisture - target_moisture)
     * Capped 0–100%.
     */
    public static function oeePerformance(?int $deviceId = null, int $days = 30): float
    {
        $query = static::whereNotNull('start_time')
            ->whereIn('status', ['completed', 'drying', 'paused'])
            ->where('start_time', '>=', now()->subDays($days))
            ->where('initial_moisture', '>', 0);
        if ($deviceId) $query->where('device_id', $deviceId);

        $batches = $query->get(['initial_moisture', 'current_moisture', 'target_moisture']);
        if ($batches->isEmpty()) return 0;

        $total = $batches->sum(function ($b) {
            $range = $b->initial_moisture - $b->target_moisture;
            if ($range <= 0) return 0;
            $actual = $b->initial_moisture - ($b->current_moisture ?? $b->initial_moisture);
            return min(1, max(0, $actual / $range));
        });

        return round($total / $batches->count() * 100, 1);
    }

    /**
     * Quality = batch completed / (completed + failed).
     */
    public static function oeeQuality(?int $deviceId = null, int $days = 30): float
    {
        $base = static::whereNotNull('start_time')
            ->whereIn('status', ['completed', 'failed'])
            ->where('start_time', '>=', now()->subDays($days));
        if ($deviceId) $base->where('device_id', $deviceId);

        $total = (clone $base)->count();
        if ($total === 0) return 100; // no failures = perfect quality

        $completed = (clone $base)->where('status', 'completed')->count();
        return round($completed / $total * 100, 1);
    }

    /**
     * OEE score = A × P × Q / 10000 (since each is 0–100).
     */
    public static function oeeScore(?int $deviceId = null, int $days = 30): float
    {
        $a = static::oeeAvailability($deviceId, $days);
        $p = static::oeePerformance($deviceId, $days);
        $q = static::oeeQuality($deviceId, $days);
        return round($a * $p * $q / 10000, 1);
    }

    /**
     * OEE per batch (for trend chart) — returns collection of completed batches
     * with their individual performance score.
     */
    public static function oeeBatchTrend(?int $deviceId = null, int $limit = 10): \Illuminate\Support\Collection
    {
        $query = static::whereNotNull('start_time')
            ->whereIn('status', ['completed', 'drying', 'paused'])
            ->orderByDesc('start_time')
            ->limit($limit);
        if ($deviceId) $query->where('device_id', $deviceId);

        return $query->get(['id', 'batch_code', 'start_time', 'initial_moisture', 'current_moisture', 'target_moisture', 'status'])
            ->reverse()
            ->values()
            ->map(function ($b) {
                $range = $b->initial_moisture - $b->target_moisture;
                $perf  = 0;
                if ($range > 0) {
                    $actual = $b->initial_moisture - ($b->current_moisture ?? $b->initial_moisture);
                    $perf   = min(100, max(0, round($actual / $range * 100, 1)));
                }
                return [
                    'batch_code' => $b->batch_code,
                    'date'       => $b->start_time?->format('d M'),
                    'performance'=> $perf,
                    'status'     => $b->status,
                ];
            });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $fillable = [
        'device_id',
        'batch_id',
        'temperature_inside',
        'temperature_outside',
        'humidity_inside',
        'humidity_outside',
        'solar_irradiance',
        'lux',
        'grain_moisture',
        'grain_weight',
        'wind_speed',
        'wind_direction',
        'pid_setpoint',
        'pid_output',
        'ai_active',
        'is_valid',
        'error_message',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature_inside'  => 'decimal:2',
            'temperature_outside' => 'decimal:2',
            'humidity_inside'     => 'decimal:2',
            'humidity_outside'    => 'decimal:2',
            'solar_irradiance'    => 'decimal:2',
            'lux'                 => 'decimal:2',
            'grain_moisture'      => 'decimal:2',
            'grain_weight'        => 'decimal:2',
            'wind_speed'          => 'decimal:2',
            'wind_direction'      => 'integer',
            'pid_setpoint'        => 'decimal:2',
            'pid_output'          => 'decimal:2',
            'ai_active'           => 'boolean',
            'is_valid'            => 'boolean',
            'recorded_at'         => 'datetime',
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

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }
}

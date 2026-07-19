<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'device_name',
        'serial_number',
        'firmware_version',
        'ip_address',
        'location',
        'status',
        'last_seen',
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
        ];
    }

    // Relationships
    public function dryingBatches(): HasMany
    {
        return $this->hasMany(DryingBatch::class);
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function weatherData(): HasMany
    {
        return $this->hasMany(WeatherData::class);
    }

    public function aiDecisions(): HasMany
    {
        return $this->hasMany(AiDecision::class);
    }

    public function actuatorLogs(): HasMany
    {
        return $this->hasMany(ActuatorLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    // Helpers
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function activeBatch(): ?DryingBatch
    {
        return $this->dryingBatches()
            ->whereIn('status', ['drying', 'paused'])
            ->latest()
            ->first();
    }

    public function latestSensorReading(): ?SensorReading
    {
        return $this->sensorReadings()->latest('recorded_at')->first();
    }
}

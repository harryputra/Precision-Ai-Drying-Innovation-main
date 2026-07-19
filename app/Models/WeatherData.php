<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherData extends Model
{
    protected $fillable = [
        'device_id',
        'source',
        'location',
        'latitude',
        'longitude',
        'temperature',
        'humidity',
        'solar_irradiance',
        'wind_speed',
        'wind_direction',
        'rainfall',
        'cloud_cover',
        'uv_index',
        'is_forecast',
        'forecast_for',
        'weather_condition',
        'weather_icon',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'decimal:7',
            'longitude'       => 'decimal:7',
            'temperature'     => 'decimal:2',
            'humidity'        => 'decimal:2',
            'solar_irradiance'=> 'decimal:2',
            'wind_speed'      => 'decimal:2',
            'wind_direction'  => 'integer',
            'rainfall'        => 'decimal:2',
            'cloud_cover'     => 'decimal:2',
            'uv_index'        => 'decimal:2',
            'is_forecast'     => 'boolean',
            'forecast_for'    => 'datetime',
            'recorded_at'     => 'datetime',
        ];
    }

    // Relationships
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // Scopes
    public function scopeActual($query)
    {
        return $query->where('is_forecast', false);
    }

    public function scopeForecast($query)
    {
        return $query->where('is_forecast', true);
    }

    public function scopeRecent($query, int $hours = 1)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }
}

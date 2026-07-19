<?php

namespace App\Events;

use App\Models\SensorReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SensorReading $sensor) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('sensor-updates'),
            new Channel("device.{$this->sensor->device_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SensorUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'sensor' => [
                'id'                  => $this->sensor->id,
                'device_id'           => $this->sensor->device_id,
                'batch_id'            => $this->sensor->batch_id,
                'temperature_inside'  => $this->sensor->temperature_inside,
                'temperature_outside' => $this->sensor->temperature_outside,
                'humidity_inside'     => $this->sensor->humidity_inside,
                'humidity_outside'    => $this->sensor->humidity_outside,
                'solar_irradiance'    => $this->sensor->solar_irradiance,
                'grain_moisture'      => $this->sensor->grain_moisture,
                'grain_weight'        => $this->sensor->grain_weight,
                'wind_speed'          => $this->sensor->wind_speed,
                'wind_direction'      => $this->sensor->wind_direction,
                'is_valid'            => $this->sensor->is_valid,
                'recorded_at'         => $this->sensor->recorded_at?->toISOString(),
            ],
        ];
    }
}

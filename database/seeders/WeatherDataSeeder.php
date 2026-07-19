<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\WeatherData;
use Illuminate\Database\Seeder;

class WeatherDataSeeder extends Seeder
{
    public function run(): void
    {
        WeatherData::truncate();

        $d1 = Device::where('serial_number', 'PADI-BNJ-001')->first();

        // Data cuaca aktual hari ini — Banjaran, Kabupaten Bandung
        $weatherToday = [
            ['hour' => 7,  'temp' => 24.2, 'hum' => 82, 'solar' => 285, 'wind' => 1.2, 'cond' => 'Clouds',   'desc' => 'berawan',              'rain' => 0.0,  'clouds' => 78],
            ['hour' => 8,  'temp' => 25.8, 'hum' => 78, 'solar' => 420, 'wind' => 1.4, 'cond' => 'Clouds',   'desc' => 'berawan sebagian',      'rain' => 0.0,  'clouds' => 60],
            ['hour' => 9,  'temp' => 27.3, 'hum' => 74, 'solar' => 580, 'wind' => 1.6, 'cond' => 'Clear',    'desc' => 'cerah',                 'rain' => 0.0,  'clouds' => 22],
            ['hour' => 10, 'temp' => 29.1, 'hum' => 68, 'solar' => 720, 'wind' => 1.8, 'cond' => 'Clear',    'desc' => 'cerah',                 'rain' => 0.0,  'clouds' => 15],
            ['hour' => 11, 'temp' => 30.8, 'hum' => 63, 'solar' => 840, 'wind' => 2.1, 'cond' => 'Clear',    'desc' => 'cerah',                 'rain' => 0.0,  'clouds' => 10],
            ['hour' => 12, 'temp' => 31.5, 'hum' => 60, 'solar' => 890, 'wind' => 2.3, 'cond' => 'Clear',    'desc' => 'cerah',                 'rain' => 0.0,  'clouds' => 8],
            ['hour' => 13, 'temp' => 31.8, 'hum' => 61, 'solar' => 870, 'wind' => 2.2, 'cond' => 'Clear',    'desc' => 'cerah',                 'rain' => 0.0,  'clouds' => 12],
            ['hour' => 14, 'temp' => 31.2, 'hum' => 63, 'solar' => 760, 'wind' => 2.0, 'cond' => 'Clouds',   'desc' => 'berawan sebagian',      'rain' => 0.0,  'clouds' => 35],
        ];

        foreach ($weatherToday as $w) {
            WeatherData::create([
                'device_id'         => $d1->id,
                'temperature'       => $w['temp'],
                'humidity'          => $w['hum'],
                'solar_irradiance'  => $w['solar'],
                'wind_speed'        => $w['wind'],
                'wind_direction'    => 180,
                'weather_condition' => $w['cond'],
                'rainfall'          => $w['rain'],
                'cloud_cover'       => $w['clouds'],
                'uv_index'          => round($w['solar'] / 120, 1),
                'source'            => 'api',
                'location'          => 'Margahurip, Banjaran',
                'latitude'          => -7.0271,
                'longitude'         => 107.5892,
                'is_forecast'       => false,
                'recorded_at'       => today()->setTime($w['hour'], 0),
            ]);
        }
    }
}

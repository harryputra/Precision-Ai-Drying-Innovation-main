<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenWeatherService
{
    private string $apiKey;
    private float  $lat;
    private float  $lon;
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        $this->apiKey = Setting::getOrConfig('openweather_api_key', 'services.openweather.api_key');
        $this->lat    = config('services.openweather.lat', -7.0271);
        $this->lon    = config('services.openweather.lon', 107.5892);
    }

    /**
     * Data cuaca aktual saat ini.
     * Cache 10 menit — hemat kuota API.
     */
    public function current(): ?array
    {
        return Cache::remember('openweather_current', 600, function () {
            $response = Http::timeout(10)->get("{$this->baseUrl}/weather", [
                'lat'   => $this->lat,
                'lon'   => $this->lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang'  => 'id',
            ]);

            if ($response->failed()) {
                Log::warning('OpenWeather current failed', ['status' => $response->status()]);
                return null;
            }

            $d = $response->json();

            return [
                'temperature'      => $d['main']['temp'] ?? null,
                'humidity'         => $d['main']['humidity'] ?? null,
                'feels_like'       => $d['main']['feels_like'] ?? null,
                'wind_speed'       => $d['wind']['speed'] ?? null,
                'wind_direction'   => $d['wind']['deg'] ?? null,
                'weather_condition'=> $d['weather'][0]['main'] ?? null,
                'description'      => $d['weather'][0]['description'] ?? null,
                'clouds'           => $d['clouds']['all'] ?? null,        // % awan
                'rainfall_1h'      => $d['rain']['1h'] ?? 0,
                'uv_index'         => null,                               // perlu endpoint terpisah
                'visibility'       => $d['visibility'] ?? null,
                'recorded_at'      => now()->toISOString(),
                'source'           => 'openweather_current',
            ];
        });
    }

    /**
     * Forecast 5 hari (interval 3 jam = 40 data points).
     * Cache 30 menit.
     */
    public function forecast(): ?array
    {
        return Cache::remember('openweather_forecast', 1800, function () {
            $response = Http::timeout(10)->get("{$this->baseUrl}/forecast", [
                'lat'   => $this->lat,
                'lon'   => $this->lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang'  => 'id',
                'cnt'   => 16, // 16 x 3 jam = 48 jam ke depan
            ]);

            if ($response->failed()) {
                Log::warning('OpenWeather forecast failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            $list = $data['list'] ?? [];

            return collect($list)->map(function ($item) {
                return [
                    'datetime'         => $item['dt_txt'],
                    'temperature'      => $item['main']['temp'],
                    'humidity'         => $item['main']['humidity'],
                    'wind_speed'       => $item['wind']['speed'],
                    'weather_condition'=> $item['weather'][0]['main'],
                    'description'      => $item['weather'][0]['description'],
                    'clouds'           => $item['clouds']['all'],
                    'rainfall_3h'      => $item['rain']['3h'] ?? 0,
                    'pop'              => $item['pop'] ?? 0, // probability of precipitation (0-1)
                ];
            })->toArray();
        });
    }

    /**
     * Ringkasan forecast untuk AI — fokus 6 jam ke depan.
     * AI butuh info singkat: hujan/tidak, kapan, seberapa parah.
     */
    public function forecastSummaryForAi(): array
    {
        $forecast = $this->forecast();

        if (!$forecast) {
            return [
                'available'        => false,
                'message'          => 'Data forecast tidak tersedia',
                'rain_risk_6h'     => 'unknown',
                'rain_risk_24h'    => 'unknown',
                'next_rain_window' => null,
                'items'            => [],
            ];
        }

        $next6h  = array_slice($forecast, 0, 2);  // 6 jam (2 x 3 jam)
        $next24h = array_slice($forecast, 0, 8);  // 24 jam

        // Hitung risiko hujan
        $maxPop6h  = collect($next6h)->max('pop') ?? 0;
        $maxPop24h = collect($next24h)->max('pop') ?? 0;
        $hasRain6h = collect($next6h)->contains(fn($i) => in_array($i['weather_condition'] ?? '', ['Rain', 'Thunderstorm', 'Drizzle']));

        // Cari jendela hujan pertama
        $nextRainWindow = collect($forecast)->first(
            fn($i) => ($i['pop'] >= 0.5) || in_array($i['weather_condition'], ['Rain', 'Thunderstorm', 'Drizzle'])
        );

        return [
            'available'          => true,
            'rain_risk_6h'       => $maxPop6h >= 0.7 ? 'high' : ($maxPop6h >= 0.4 ? 'medium' : 'low'),
            'rain_risk_24h'      => $maxPop24h >= 0.7 ? 'high' : ($maxPop24h >= 0.4 ? 'medium' : 'low'),
            'max_pop_6h'         => round($maxPop6h * 100) . '%',
            'max_pop_24h'        => round($maxPop24h * 100) . '%',
            'next_rain_window'   => $nextRainWindow ? $nextRainWindow['datetime'] : null,
            'next_6h_conditions' => collect($next6h)->pluck('description')->unique()->values()->toArray(),
            'items'              => $next24h,
        ];
    }

    /**
     * Hapus cache — force refresh data terbaru.
     */
    public function clearCache(): void
    {
        Cache::forget('openweather_current');
        Cache::forget('openweather_forecast');
    }
}

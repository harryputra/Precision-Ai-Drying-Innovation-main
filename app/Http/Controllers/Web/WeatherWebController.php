<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WeatherData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherWebController extends Controller
{
    public function index(): View
    {
        $latest   = WeatherData::actual()->latest('recorded_at')->first();
        $forecast = WeatherData::forecast()->orderBy('forecast_for')->limit(8)->get();
        $history  = WeatherData::actual()->latest('recorded_at')->limit(24)->get()->reverse()->values();

        return view('weather.index', compact('latest', 'forecast', 'history'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = WeatherData::latest('recorded_at');

        if ($request->boolean('forecast_only')) {
            $query->forecast();
        } elseif ($request->boolean('actual_only')) {
            $query->actual();
        }

        $records = $query->limit(5000)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="weather-data-'.now()->format('Y-m-d-His').'.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Time','Source','Location','Type','Temp (°C)','Humidity (%)','Solar (W/m²)',
                'Wind Speed (m/s)','Wind Dir (°)','Rainfall (mm)','Cloud Cover (%)','UV Index',
                'Condition','Forecast For',
            ]);
            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->recorded_at?->format('Y-m-d H:i:s'),
                    $r->source,
                    $r->location,
                    $r->is_forecast ? 'Forecast' : 'Actual',
                    $r->temperature,
                    $r->humidity,
                    $r->solar_irradiance,
                    $r->wind_speed,
                    $r->wind_direction,
                    $r->rainfall,
                    $r->cloud_cover,
                    $r->uv_index,
                    $r->weather_condition,
                    $r->forecast_for?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}

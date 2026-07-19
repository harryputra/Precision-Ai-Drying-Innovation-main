<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeatherData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeatherDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WeatherData::latest('recorded_at');

        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->boolean('forecast_only')) {
            $query->forecast();
        } else {
            $query->actual();
        }

        $data = $query->paginate($request->per_page ?? 24);

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'        => 'nullable|exists:devices,id',
            'source'           => ['nullable', Rule::in(['sensor', 'api', 'manual'])],
            'location'         => 'nullable|string',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'temperature'      => 'nullable|numeric',
            'humidity'         => 'nullable|numeric|between:0,100',
            'solar_irradiance' => 'nullable|numeric|min:0',
            'wind_speed'       => 'nullable|numeric|min:0',
            'wind_direction'   => 'nullable|integer|between:0,359',
            'rainfall'         => 'nullable|numeric|min:0',
            'cloud_cover'      => 'nullable|numeric|between:0,100',
            'uv_index'         => 'nullable|numeric|min:0',
            'is_forecast'      => 'nullable|boolean',
            'forecast_for'     => 'nullable|date',
            'weather_condition'=> 'nullable|string',
            'weather_icon'     => 'nullable|string',
            'recorded_at'      => 'nullable|date',
        ]);

        $data['recorded_at'] ??= now();

        $weather = WeatherData::create($data);

        return response()->json(['status' => true, 'data' => $weather], 201);
    }

    public function show(WeatherData $weatherData): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $weatherData]);
    }

    public function latest(Request $request): JsonResponse
    {
        $query = WeatherData::actual()->latest('recorded_at');

        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        return response()->json(['status' => true, 'data' => $query->first()]);
    }
}

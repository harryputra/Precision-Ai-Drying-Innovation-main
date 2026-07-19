<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\SensorReading;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SensorWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = SensorReading::with('device')->valid()->latest('recorded_at');

        if ($request->filled('device_id')) {
            $query->forDevice($request->device_id);
        }
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        if ($request->filled('minutes')) {
            $query->recent((int) $request->minutes);
        }

        $readings = $query->paginate(10)->withQueryString();

        $stats = [
            'avg_temp'     => SensorReading::valid()->avg('temperature_inside'),
            'avg_humidity' => SensorReading::valid()->avg('humidity_inside'),
            'avg_moisture' => SensorReading::valid()->avg('grain_moisture'),
            'total'        => SensorReading::valid()->count(),
        ];

        $devices = Device::orderBy('device_name')->get(['id', 'device_name']);
        $batches = DryingBatch::orderByDesc('created_at')->limit(20)->get(['id', 'batch_code']);

        $chartReadings = SensorReading::valid()
            ->when($request->filled('device_id'), fn($q) => $q->forDevice($request->device_id))
            ->latest('recorded_at')->limit(30)->get()->reverse()->values();

        return view('sensor.index', compact('readings', 'stats', 'devices', 'batches', 'chartReadings'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = SensorReading::with('device')->valid()->latest('recorded_at');

        if ($request->filled('device_id')) $query->forDevice($request->device_id);
        if ($request->filled('batch_id'))  $query->where('batch_id', $request->batch_id);
        if ($request->filled('minutes'))   $query->recent((int) $request->minutes);

        $readings = $query->limit(5000)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sensor-readings-'.now()->format('Y-m-d-His').'.csv"',
        ];

        return response()->stream(function () use ($readings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time','Device','Temp Inside (°C)','Temp Outside (°C)','Humidity In (%)','Humidity Out (%)','Solar (W/m²)','Grain Moisture (%)','Wind Speed (m/s)','Valid']);
            foreach ($readings as $r) {
                fputcsv($handle, [
                    $r->recorded_at?->format('Y-m-d H:i:s'),
                    $r->device?->device_name,
                    $r->temperature_inside,
                    $r->temperature_outside,
                    $r->humidity_inside,
                    $r->humidity_outside,
                    $r->solar_irradiance,
                    $r->grain_moisture,
                    $r->wind_speed,
                    $r->is_valid ? 'Yes' : 'No',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorUpdated;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorReadingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SensorReading::with('device')
            ->valid()
            ->latest('recorded_at');

        if ($request->has('device_id')) {
            $query->forDevice($request->device_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('minutes')) {
            $query->recent((int) $request->minutes);
        }

        $readings = $query->paginate($request->per_page ?? 50);

        return response()->json(['status' => true, 'data' => $readings]);
    }

    /**
     * Terima data dari IoT device (MQTT bridge / HTTP POST langsung).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'           => 'required|exists:devices,id',
            'batch_id'            => 'nullable|exists:drying_batches,id',
            'temperature_inside'  => 'nullable|numeric',
            'temperature_outside' => 'nullable|numeric',
            'humidity_inside'     => 'nullable|numeric|between:0,100',
            'humidity_outside'    => 'nullable|numeric|between:0,100',
            'solar_irradiance'    => 'nullable|numeric|min:0',
            'lux'                 => 'nullable|numeric|min:0',
            'grain_moisture'      => 'nullable|numeric|between:0,100',
            'grain_weight'        => 'nullable|numeric|min:0',
            'wind_speed'          => 'nullable|numeric|min:0',
            'wind_direction'      => 'nullable|integer|between:0,359',
            'pid_setpoint'        => 'nullable|numeric|between:0,100',
            'pid_output'          => 'nullable|numeric',
            'ai_active'           => 'nullable|boolean',
            'is_valid'            => 'nullable|boolean',
            'error_message'       => 'nullable|string',
            'recorded_at'         => 'nullable|date',
        ]);

        $data['recorded_at'] ??= now();

        $reading = SensorReading::create($data);

        // Cek apakah device sebelumnya offline — log event reconnect
        $device = Device::find($data['device_id']);
        $wasOffline = $device->status === 'offline';

        // Update last_seen + status device
        $device->update([
            'status'    => 'online',
            'last_seen' => now(),
        ]);

        // Log event reconnect setelah offline
        if ($wasOffline) {
            \App\Models\SystemLog::create([
                'level'     => 'info',
                'event'     => 'device.reconnected',
                'message'   => "Device [{$device->device_name}] kembali online — PID setpoint: " . ($data['pid_setpoint'] ?? 'N/A') . '°C',
                'device_id' => $device->id,
                'context'   => [
                    'device_id'    => $device->id,
                    'device_name'  => $device->device_name,
                    'pid_setpoint' => $data['pid_setpoint'] ?? null,
                    'ai_active'    => $data['ai_active'] ?? false,
                ],
            ]);
        }

        // Auto-update current_moisture batch aktif dari grain_moisture sensor
        // AI butuh data kadar air real-time, bukan nilai saat batch dibuat
        if (!empty($data['grain_moisture']) && ($data['is_valid'] ?? true)) {
            $batchQuery = \App\Models\DryingBatch::active()
                ->where('device_id', $data['device_id']);

            // Gunakan batch_id dari payload jika ada, otherwise ambil batch aktif
            if (!empty($data['batch_id'])) {
                $batchQuery->where('id', $data['batch_id']);
            }

            $activeBatch = $batchQuery->latest()->first();

            if ($activeBatch) {
                $activeBatch->update([
                    'current_moisture' => $data['grain_moisture'],
                ]);
            }
        }

        // Broadcast realtime ke dashboard
        broadcast(new SensorUpdated($reading))->toOthers();

        return response()->json(['status' => true, 'data' => $reading], 201);
    }

    public function show(SensorReading $sensorReading): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $sensorReading]);
    }

    /**
     * Data terbaru per device — endpoint untuk dashboard realtime.
     */
    public function latest(Request $request): JsonResponse
    {
        $request->validate(['device_id' => 'required|exists:devices,id']);

        $reading = SensorReading::forDevice($request->device_id)
            ->valid()
            ->latest('recorded_at')
            ->first();

        return response()->json(['status' => true, 'data' => $reading]);
    }

    /**
     * Bulk ingest — device kirim array readings sekaligus.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'readings'              => 'required|array|max:100',
            'readings.*.device_id'  => 'required|exists:devices,id',
            'readings.*.recorded_at'=> 'nullable|date',
        ]);

        $now = now();
        $readings = collect($request->readings)->map(function ($r) use ($now) {
            $r['recorded_at'] ??= $now;
            $r['created_at']   = $now;
            $r['updated_at']   = $now;
            return $r;
        })->toArray();

        SensorReading::insert($readings);

        return response()->json(['status' => true, 'message' => count($readings) . ' readings stored']);
    }
}

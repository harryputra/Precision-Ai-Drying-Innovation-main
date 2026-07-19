<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index(): JsonResponse
    {
        $devices = Device::withCount(['dryingBatches', 'sensorReadings'])
            ->latest()
            ->get();

        return response()->json(['status' => true, 'data' => $devices]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_name'      => 'required|string|max:255',
            'serial_number'    => 'required|string|unique:devices,serial_number',
            'firmware_version' => 'nullable|string',
            'ip_address'       => 'nullable|ip',
            'location'         => 'nullable|string',
            'status'           => ['nullable', Rule::in(['online', 'offline', 'maintenance'])],
        ]);

        $device = Device::create($data);

        return response()->json(['status' => true, 'data' => $device], 201);
    }

    public function show(Device $device): JsonResponse
    {
        $device->load([
            'dryingBatches' => fn($q) => $q->active(),
        ]);

        $device->latest_sensor = $device->latestSensorReading();

        return response()->json(['status' => true, 'data' => $device]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $data = $request->validate([
            'device_name'      => 'sometimes|string|max:255',
            'firmware_version' => 'nullable|string',
            'ip_address'       => 'nullable|ip',
            'location'         => 'nullable|string',
            'status'           => ['nullable', Rule::in(['online', 'offline', 'maintenance'])],
        ]);

        $device->update($data);

        return response()->json(['status' => true, 'data' => $device]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json(['status' => true, 'message' => 'Device deleted']);
    }

    public function heartbeat(Request $request, Device $device): JsonResponse
    {
        $device->update([
            'status'    => 'online',
            'last_seen' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Heartbeat received']);
    }
}

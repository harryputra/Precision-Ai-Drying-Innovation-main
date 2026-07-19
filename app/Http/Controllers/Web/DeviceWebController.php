<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DryingBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceWebController extends Controller
{
    public function index(): View
    {
        $devices = Device::withCount(['dryingBatches', 'sensorReadings'])
            ->latest()
            ->paginate(12);

        return view('devices.index', compact('devices'));
    }

    public function create(): View
    {
        return view('devices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_name'      => 'required|string|max:100',
            'serial_number'    => 'required|string|max:100|unique:devices,serial_number',
            'firmware_version' => 'nullable|string|max:50',
            'ip_address'       => 'nullable|ip',
            'location'         => 'nullable|string|max:255',
            'status'           => 'required|in:online,offline,maintenance',
        ]);

        Device::create($data);

        return redirect()->route('web.devices.index')->with('success', 'Device berhasil ditambahkan.');
    }

    public function show(Device $device): View
    {
        $latestSensor  = $device->latestSensorReading();
        $activeBatch   = $device->activeBatch();
        $actuatorLogs  = $device->actuatorLogs()->latest('executed_at')->limit(20)->get();

        $chartReadings = $device->sensorReadings()
            ->valid()
            ->latest('recorded_at')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        // OEE per device (last 30 days)
        $oeeAvailability = DryingBatch::oeeAvailability($device->id);
        $oeePerformance  = DryingBatch::oeePerformance($device->id);
        $oeeQuality      = DryingBatch::oeeQuality($device->id);
        $oeeScore        = DryingBatch::oeeScore($device->id);
        $oeeBatchTrend   = DryingBatch::oeeBatchTrend($device->id, 10);

        return view('devices.show', compact(
            'device', 'latestSensor', 'activeBatch', 'actuatorLogs', 'chartReadings',
            'oeeAvailability', 'oeePerformance', 'oeeQuality', 'oeeScore', 'oeeBatchTrend'
        ));
    }

    public function edit(Device $device): View
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'device_name'      => 'required|string|max:100',
            'serial_number'    => 'required|string|max:100|unique:devices,serial_number,' . $device->id,
            'firmware_version' => 'nullable|string|max:50',
            'ip_address'       => 'nullable|ip',
            'location'         => 'nullable|string|max:255',
            'status'           => 'required|in:online,offline,maintenance',
        ]);

        $device->update($data);

        return redirect()->route('web.devices.show', $device)->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('web.devices.index')->with('success', 'Device berhasil dihapus.');
    }
}

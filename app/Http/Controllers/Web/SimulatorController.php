<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    /**
     * Tampilkan halaman simulator mesin pengering gabah.
     *
     * Halaman ini menginjeksi IOT_DEVICE_KEY ke frontend agar simulator
     * bisa mengirim HTTP POST ke /api/iot/sensor, persis seperti ESP32.
     * Key hanya bisa diakses oleh admin/operator yang login.
     */
    public function index(): View
    {
        $devices = Device::orderBy('device_name')->get();
        $deviceKey = config('services.webhooks.iot_key', '');

        return view('simulator.index', compact('devices', 'deviceKey'));
    }
}

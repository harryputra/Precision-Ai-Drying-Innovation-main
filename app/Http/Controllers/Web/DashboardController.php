<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActuatorLog;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Polling ringan kartu statistik — dipanggil JS dashboard tiap 30 detik
     * (sebelumnya 404 karena endpoint tidak pernah dibuat).
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'active_batches'    => DryingBatch::active()->count(),
            'waiting_batches'   => DryingBatch::where('status', 'waiting')->count(),
            'drying_batches'    => DryingBatch::where('status', 'drying')->count(),
            'completed_batches' => DryingBatch::where('status', 'completed')->count(),
            'cancelled_batches' => DryingBatch::where('status', 'failed')->count(),
            'online_devices'    => Device::online()->count(),
        ]);
    }

    public function index(): View|RedirectResponse
    {
        // Viewer (petani) tidak boleh akses dashboard teknikal
        if (auth()->user()->role === 'viewer') {
            return redirect()->route('viewer.dashboard');
        }

        $onlineDevices  = Device::online()->count();
        $totalDevices   = Device::count();
        $activeBatches  = DryingBatch::active()->count();
        $todayDecisions = AiDecision::whereDate('decided_at', today())->count();

        $totalBatches     = DryingBatch::count();
        $waitingBatches   = DryingBatch::where('status', 'waiting')->count();
        $dryingBatches    = DryingBatch::where('status', 'drying')->count();
        $completedBatches = DryingBatch::where('status', 'completed')->count();
        $cancelledBatches = DryingBatch::where('status', 'failed')->count();

        $latestSensor = SensorReading::valid()
            ->latest('recorded_at')
            ->first();

        $recentDecisions = AiDecision::with(['device', 'batch'])
            ->latest('decided_at')
            ->limit(5)
            ->get();

        $activeBatchList = DryingBatch::with('device')
            ->active()
            ->latest()
            ->limit(5)
            ->get();

        // Chart data: last 20 sensor readings for first device
        $chartReadings = SensorReading::valid()
            ->latest('recorded_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        $chartLabels       = $chartReadings->pluck('recorded_at')->map(fn($t) => $t?->format('H:i'));
        $chartTempInside   = $chartReadings->pluck('temperature_inside');
        $chartTempOutside  = $chartReadings->pluck('temperature_outside');
        $chartHumidInside  = $chartReadings->pluck('humidity_inside');

        // Actuator status: latest command per actuator_type in last 24h
        $actuatorStatus = ActuatorLog::where('executed_at', '>=', now()->subHours(24))
            ->orderByDesc('executed_at')
            ->get()
            ->unique('actuator_type')
            ->values();

        $recentActuatorLogs = ActuatorLog::with('device')
            ->latest('executed_at')
            ->limit(6)
            ->get();

        // OEE overall (last 30 days)
        $oeeAvailability = DryingBatch::oeeAvailability();
        $oeePerformance  = DryingBatch::oeePerformance();
        $oeeQuality      = DryingBatch::oeeQuality();
        $oeeScore        = DryingBatch::oeeScore();

        return view('dashboard', compact(
            'onlineDevices', 'totalDevices', 'activeBatches', 'todayDecisions',
            'totalBatches', 'waitingBatches', 'dryingBatches', 'completedBatches', 'cancelledBatches',
            'latestSensor', 'recentDecisions', 'activeBatchList',
            'chartLabels', 'chartTempInside', 'chartTempOutside', 'chartHumidInside',
            'actuatorStatus', 'recentActuatorLogs',
            'oeeAvailability', 'oeePerformance', 'oeeQuality', 'oeeScore'
        ));
    }
}

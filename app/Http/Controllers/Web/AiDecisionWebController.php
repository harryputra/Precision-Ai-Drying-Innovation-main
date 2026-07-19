<?php

namespace App\Http\Controllers\Web;

use App\Exports\AiDecisionExport;
use App\Http\Controllers\Controller;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\SensorReading;
use App\Models\WeatherData;
use App\Services\AiService;
use App\Services\OpenWeatherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AiDecisionWebController extends Controller
{
    public function __construct(
        private AiService $ai,
        private OpenWeatherService $weather
    ) {}

    public function index(Request $request): View
    {
        $query = AiDecision::with(['device', 'batch'])->latest('decided_at');

        if ($request->filled('device_id'))        $query->where('device_id', $request->device_id);
        if ($request->filled('decision_type'))    $query->where('decision_type', $request->decision_type);
        if ($request->filled('execution_status')) $query->where('execution_status', $request->execution_status);

        $decisions = $query->paginate(20)->withQueryString();
        $devices   = Device::orderBy('device_name')->get(['id', 'device_name']);

        return view('ai.decisions', compact('decisions', 'devices'));
    }

    public function show(AiDecision $aiDecision): View
    {
        $aiDecision->load(['device', 'batch', 'overriddenBy', 'actuatorLogs']);

        return view('ai.decision-show', compact('aiDecision'));
    }

    /**
     * Manual AI trigger — untuk test tanpa hardware/n8n.
     * Ambil data terakhir dari DB, kirim ke Gemini, simpan keputusan.
     */
    public function triggerDecision(Request $request): RedirectResponse
    {
        $request->validate([
            'batch_id'  => 'nullable|exists:drying_batches,id',
            'device_id' => 'nullable|exists:devices,id',
        ]);

        // Tentukan batch & device
        $batch = $request->filled('batch_id')
            ? DryingBatch::find($request->batch_id)
            : DryingBatch::active()->latest()->first();

        $device = $request->filled('device_id')
            ? Device::find($request->device_id)
            : ($batch?->device ?? Device::orderBy('last_seen', 'desc')->first());

        if (!$batch) {
            return back()->with('error', 'Tidak ada batch aktif. Buat batch dengan status drying/paused terlebih dahulu.');
        }

        if (!$device) {
            return back()->with('error', 'Tidak ada device tersedia.');
        }

        // Ambil data sensor terakhir
        $sensor = SensorReading::valid()
            ->when($device->id, fn($q) => $q->forDevice($device->id))
            ->latest('recorded_at')
            ->first();

        // Cuaca dari OpenWeather
        $weatherCurrent  = $this->weather->current();
        $weatherForecast = $this->weather->forecastSummaryForAi();

        // Bangun context untuk AI
        $context = [
            'sensor'           => $sensor ? [
                'temperature_inside'  => $sensor->temperature_inside,
                'temperature_outside' => $sensor->temperature_outside,
                'humidity_inside'     => $sensor->humidity_inside,
                'humidity_outside'    => $sensor->humidity_outside,
                'solar_irradiance'    => $sensor->solar_irradiance,
                'grain_moisture'      => $sensor->grain_moisture,
                'wind_speed'          => $sensor->wind_speed,
                // PID setpoint aktif — AI hanya ubah setpoint jika perlu, hemat kuota
                'pid_setpoint'        => $sensor->pid_setpoint,
                'pid_output'          => $sensor->pid_output,
                'ai_active'           => $sensor->ai_active ?? false,
            ] : null,
            'weather_current'  => $weatherCurrent,
            'weather_forecast' => $weatherForecast,
            'batch'            => [
                'batch_code'       => $batch->batch_code,
                'rice_type'        => $batch->rice_type,
                'rice_variety'     => $batch->rice_variety,
                'initial_moisture' => $batch->initial_moisture,
                'current_moisture' => $batch->current_moisture ?? $batch->initial_moisture,
                'target_moisture'  => $batch->target_moisture,
                'initial_weight'   => $batch->initial_weight,
                'current_weight'   => $batch->current_weight ?? $batch->initial_weight,
            ],
        ];

        try {
            $result   = $this->ai->analyzeAndDecide($context);
            $decision = $result['decision'];

            // Pastikan decision_type valid
            $validTypes = [
                'open_roof', 'close_roof', 'start_fan', 'stop_fan',
                'start_heater', 'stop_heater', 'pause_drying', 'resume_drying',
                'alert_operator', 'adjust_temperature', 'adjust_airflow', 'other',
            ];
            $decisionType = in_array($decision['decision_type'], $validTypes)
                ? $decision['decision_type']
                : 'other';

            // Gabungkan risk_level dan alerts ke dalam output_action (tidak ada kolom terpisah)
            $outputAction = $decision['output_action'] ?? [];
            $outputAction['risk_level'] = $decision['risk_level'] ?? 'low';
            $outputAction['alerts']     = $decision['alerts'] ?? [];

            $saved = AiDecision::create([
                'device_id'        => $device->id,
                'batch_id'         => $batch->id,
                'decision_type'    => $decisionType,
                'reasoning'        => $decision['reasoning'] ?? 'Manual trigger dari dashboard',
                'input_data'       => $context,
                'output_action'    => $outputAction,
                'confidence_score' => $decision['confidence_score'] ?? null,
                'ai_model'         => $result['model'],
                'decided_at'       => now(),
                'execution_status' => 'pending',
            ]);

            return redirect()
                ->route('web.ai.decisions.show', $saved)
                ->with('success', 'Keputusan AI berhasil dibuat: '.ucwords(str_replace('_', ' ', $decisionType)));

        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat keputusan AI: '.$e->getMessage());
        }
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filename = 'ai-decisions-'.now()->format('Ymd-His').'.xlsx';
        return Excel::download(new AiDecisionExport($request), $filename);
    }

    public function exportCsv(Request $request): BinaryFileResponse
    {
        $filename = 'ai-decisions-'.now()->format('Ymd-His').'.csv';
        return Excel::download(new AiDecisionExport($request), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportPdf(Request $request): Response
    {
        $query = AiDecision::with(['device', 'batch'])->latest('decided_at');
        if ($request->filled('device_id'))        $query->where('device_id', $request->device_id);
        if ($request->filled('decision_type'))    $query->where('decision_type', $request->decision_type);
        if ($request->filled('execution_status')) $query->where('execution_status', $request->execution_status);

        $decisions = $query->limit(500)->get();
        $pdf       = Pdf::loadView('exports.ai-decisions-pdf', compact('decisions'))
                        ->setPaper('a4', 'landscape');

        return $pdf->download('ai-decisions-'.now()->format('Ymd-His').'.pdf');
    }
}

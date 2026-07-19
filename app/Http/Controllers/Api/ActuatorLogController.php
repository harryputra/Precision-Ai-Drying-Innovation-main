<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActuatorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActuatorLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActuatorLog::with(['device', 'batch', 'aiDecision'])
            ->latest('executed_at');

        if ($request->has('device_id')) {
            $query->forDevice($request->device_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('actuator_type')) {
            $query->where('actuator_type', $request->actuator_type);
        }

        $logs = $query->paginate($request->per_page ?? 20);

        return response()->json(['status' => true, 'data' => $logs]);
    }

    /**
     * Log hasil eksekusi aktuator — dari device / n8n.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'         => 'required|exists:devices,id',
            'batch_id'          => 'nullable|exists:drying_batches,id',
            'ai_decision_id'    => 'nullable|exists:ai_decisions,id',
            'actuator_type'     => ['required', Rule::in([
                'roof', 'fan', 'heater', 'ventilation', 'pump', 'conveyor', 'other',
            ])],
            'actuator_name'     => 'nullable|string',
            'command'           => ['required', Rule::in(['on', 'off', 'open', 'close', 'adjust'])],
            'set_value'         => 'nullable|numeric',
            'actual_value'      => 'nullable|numeric',
            'unit'              => 'nullable|string',
            'triggered_by'      => ['nullable', Rule::in(['ai', 'manual', 'schedule', 'safety'])],
            'triggered_by_user' => 'nullable|exists:users,id',
            'status'            => ['nullable', Rule::in(['success', 'failed', 'timeout'])],
            'error_message'     => 'nullable|string',
            'response_time_ms'  => 'nullable|integer',
            'executed_at'       => 'nullable|date',
        ]);

        $data['executed_at'] ??= now();
        $data['triggered_by'] ??= 'ai';
        $data['status'] ??= 'success';

        $log = ActuatorLog::create($data);

        // Mark AI decision as executed kalau ada
        if (!empty($data['ai_decision_id']) && $data['status'] === 'success') {
            \App\Models\AiDecision::find($data['ai_decision_id'])?->markExecuted();
        }

        return response()->json(['status' => true, 'data' => $log], 201);
    }

    public function show(ActuatorLog $actuatorLog): JsonResponse
    {
        $actuatorLog->load(['device', 'batch', 'aiDecision', 'triggeredByUser']);

        return response()->json(['status' => true, 'data' => $actuatorLog]);
    }
}

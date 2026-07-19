<?php

namespace App\Http\Controllers\Api;

use App\Events\AiDecisionMade;
use App\Http\Controllers\Controller;
use App\Models\AiDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiDecisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AiDecision::with(['device', 'batch'])
            ->latest('decided_at');

        if ($request->has('device_id')) {
            $query->forDevice($request->device_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('execution_status')) {
            $query->where('execution_status', $request->execution_status);
        }

        $decisions = $query->paginate($request->per_page ?? 20);

        return response()->json(['status' => true, 'data' => $decisions]);
    }

    /**
     * n8n AI agent POST keputusan ke sini.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'        => 'required|exists:devices,id',
            'batch_id'         => 'nullable|exists:drying_batches,id',
            'decision_type'    => ['required', Rule::in([
                'open_roof', 'close_roof', 'start_fan', 'stop_fan',
                'start_heater', 'stop_heater', 'pause_drying', 'resume_drying',
                'alert_operator', 'adjust_temperature', 'adjust_airflow', 'other',
            ])],
            'reasoning'        => 'required|string',
            'input_data'       => 'nullable|array',
            'output_action'    => 'nullable|array',
            'confidence_score' => 'nullable|numeric|between:0,1',
            'ai_model'         => 'nullable|string',
            'decided_at'       => 'nullable|date',
        ]);

        $data['decided_at']       ??= now();
        $data['execution_status'] = 'pending';

        $decision = AiDecision::create($data);

        broadcast(new AiDecisionMade($decision));

        return response()->json(['status' => true, 'data' => $decision], 201);
    }

    public function show(AiDecision $aiDecision): JsonResponse
    {
        $aiDecision->load(['device', 'batch', 'actuatorLogs']);

        return response()->json(['status' => true, 'data' => $aiDecision]);
    }

    /**
     * Update status eksekusi — dipanggil setelah aktuator jalan.
     */
    public function updateStatus(Request $request, AiDecision $aiDecision): JsonResponse
    {
        $data = $request->validate([
            'execution_status' => ['required', Rule::in(['executed', 'failed', 'skipped', 'overridden'])],
            'override_reason'  => 'nullable|string',
            'overridden_by'    => 'nullable|exists:users,id',
        ]);

        if ($data['execution_status'] === 'executed') {
            $data['executed_at'] = now();
        }

        $aiDecision->update($data);

        return response()->json(['status' => true, 'data' => $aiDecision]);
    }

    public function pending(Request $request): JsonResponse
    {
        $query = AiDecision::with('device')->pending()->latest('decided_at');

        if ($request->has('device_id')) {
            $query->forDevice($request->device_id);
        }

        return response()->json(['status' => true, 'data' => $query->get()]);
    }
}

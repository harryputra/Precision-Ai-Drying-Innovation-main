<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DryingBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DryingBatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DryingBatch::with('device')
            ->latest();

        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->paginate($request->per_page ?? 15);

        return response()->json(['status' => true, 'data' => $batches]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'       => 'required|exists:devices,id',
            'batch_code'      => 'required|string|unique:drying_batches,batch_code',
            'rice_type'       => 'required|string',
            'rice_variety'    => 'nullable|string',
            'initial_weight'  => 'required|numeric|min:0',
            'initial_moisture'=> 'required|numeric|between:0,100',
            'target_moisture' => 'required|numeric|between:0,100',
            'drying_method'   => 'nullable|string',
            'operator_name'   => 'nullable|string',
        ]);

        $data['current_weight']   = $data['initial_weight'];
        $data['current_moisture'] = $data['initial_moisture'];

        $batch = DryingBatch::create($data);

        return response()->json(['status' => true, 'data' => $batch], 201);
    }

    public function show(DryingBatch $dryingBatch): JsonResponse
    {
        $dryingBatch->load([
            'device',
            'aiDecisions' => fn($q) => $q->latest()->limit(10),
            'actuatorLogs' => fn($q) => $q->latest('executed_at')->limit(10),
        ]);

        return response()->json(['status' => true, 'data' => $dryingBatch]);
    }

    public function update(Request $request, DryingBatch $dryingBatch): JsonResponse
    {
        $data = $request->validate([
            'current_weight'  => 'nullable|numeric|min:0',
            'current_moisture'=> 'nullable|numeric|between:0,100',
            'target_moisture' => 'nullable|numeric|between:0,100',
            'operator_name'   => 'nullable|string',
            'status'          => ['nullable', Rule::in(['waiting', 'drying', 'paused', 'completed', 'failed'])],
            'end_time'        => 'nullable|date',
        ]);

        // Auto set start_time saat status → drying
        if (isset($data['status']) && $data['status'] === 'drying' && !$dryingBatch->start_time) {
            $data['start_time'] = now();
        }

        // Auto set end_time saat completed/failed
        if (isset($data['status']) && in_array($data['status'], ['completed', 'failed']) && !$dryingBatch->end_time) {
            $data['end_time'] = now();
        }

        $dryingBatch->update($data);

        return response()->json(['status' => true, 'data' => $dryingBatch]);
    }

    public function destroy(DryingBatch $dryingBatch): JsonResponse
    {
        $dryingBatch->delete();

        return response()->json(['status' => true, 'message' => 'Batch deleted']);
    }

    public function active(): JsonResponse
    {
        $batches = DryingBatch::with('device')
            ->active()
            ->get();

        return response()->json(['status' => true, 'data' => $batches]);
    }
}

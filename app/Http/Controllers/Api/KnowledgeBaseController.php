<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBase::active()->latest();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('tag')) {
            $query->byTag($request->tag);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return response()->json(['status' => true, 'data' => $query->paginate($request->per_page ?? 20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category'        => ['required', Rule::in([
                'drying_rules', 'rice_varieties', 'weather_patterns',
                'equipment_specs', 'troubleshooting', 'best_practices', 'other',
            ])],
            'title'           => 'required|string',
            'content'         => 'required|string',
            'tags'            => 'nullable|array',
            'metadata'        => 'nullable|array',
            'is_active'       => 'nullable|boolean',
            'use_for_ai'      => 'nullable|boolean',
            'priority_weight' => 'nullable|numeric|min:0',
        ]);

        $data['created_by'] = $request->user()?->id;

        $kb = KnowledgeBase::create($data);

        return response()->json(['status' => true, 'data' => $kb], 201);
    }

    public function show(KnowledgeBase $knowledgeBase): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $knowledgeBase]);
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $data = $request->validate([
            'category'        => ['nullable', Rule::in([
                'drying_rules', 'rice_varieties', 'weather_patterns',
                'equipment_specs', 'troubleshooting', 'best_practices', 'other',
            ])],
            'title'           => 'nullable|string',
            'content'         => 'nullable|string',
            'tags'            => 'nullable|array',
            'metadata'        => 'nullable|array',
            'is_active'       => 'nullable|boolean',
            'use_for_ai'      => 'nullable|boolean',
            'priority_weight' => 'nullable|numeric|min:0',
        ]);

        $data['updated_by'] = $request->user()?->id;
        $data['version']    = $knowledgeBase->version + 1;

        $knowledgeBase->update($data);

        return response()->json(['status' => true, 'data' => $knowledgeBase]);
    }

    public function destroy(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->delete();

        return response()->json(['status' => true, 'message' => 'Knowledge base entry deleted']);
    }

    /**
     * Endpoint khusus n8n — ambil semua KB aktif untuk konteks AI.
     */
    public function forAi(Request $request): JsonResponse
    {
        $kb = KnowledgeBase::forAi()
            ->orderByDesc('priority_weight')
            ->when($request->has('category'), fn($q) => $q->byCategory($request->category))
            ->get(['id', 'category', 'title', 'content', 'tags', 'priority_weight']);

        return response()->json(['status' => true, 'data' => $kb]);
    }
}

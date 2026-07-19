<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = KnowledgeBase::latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%"));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $knowledgeBases = $query->paginate(20)->withQueryString();

        $categories = [
            'drying_rules', 'rice_varieties', 'weather_patterns',
            'equipment_specs', 'troubleshooting', 'best_practices', 'other',
        ];

        return view('knowledge.index', compact('knowledgeBases', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|string',
            'content'         => 'required|string',
            'tags'            => 'nullable|string',
            'priority_weight' => 'nullable|numeric|min:0|max:10',
            'use_for_ai'      => 'nullable',
            'is_active'       => 'nullable',
        ]);

        KnowledgeBase::create([
            'title'           => $data['title'],
            'category'        => $data['category'],
            'content'         => $data['content'],
            'tags'            => $this->parseTags($data['tags'] ?? null),
            'priority_weight' => $data['priority_weight'] ?? 1.0,
            'use_for_ai'      => $request->boolean('use_for_ai'),
            'is_active'       => $request->boolean('is_active'),
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
            'version'         => 1,
        ]);

        return redirect()->route('web.knowledge.index')
            ->with('success', 'Knowledge entry berhasil ditambahkan.');
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|string',
            'content'         => 'required|string',
            'tags'            => 'nullable|string',
            'priority_weight' => 'nullable|numeric|min:0|max:10',
            'use_for_ai'      => 'nullable',
            'is_active'       => 'nullable',
        ]);

        $knowledgeBase->update([
            'title'           => $data['title'],
            'category'        => $data['category'],
            'content'         => $data['content'],
            'tags'            => $this->parseTags($data['tags'] ?? null),
            'priority_weight' => $data['priority_weight'] ?? $knowledgeBase->priority_weight,
            'use_for_ai'      => $request->boolean('use_for_ai'),
            'is_active'       => $request->boolean('is_active'),
            'updated_by'      => auth()->id(),
            'version'         => $knowledgeBase->version + 1,
        ]);

        return redirect()->route('web.knowledge.index')
            ->with('success', 'Knowledge entry berhasil diupdate.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $knowledgeBase->delete();

        return redirect()->route('web.knowledge.index')
            ->with('success', 'Knowledge entry berhasil dihapus.');
    }

    private function parseTags(?string $raw): ?array
    {
        if (!$raw) return null;
        $tags = array_values(array_filter(array_map('trim', explode(',', $raw))));
        return empty($tags) ? null : $tags;
    }
}

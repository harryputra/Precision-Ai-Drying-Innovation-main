<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AiConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // List sesi unik milik user
        $sessions = AiConversation::forUser($request->user()->id)
            ->select('session_id')
            ->selectRaw('MIN(created_at) as started_at')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as message_count')
            ->groupBy('session_id')
            ->orderByDesc('last_message_at')
            ->paginate($request->per_page ?? 20);

        return response()->json(['status' => true, 'data' => $sessions]);
    }

    public function session(Request $request, string $sessionId): JsonResponse
    {
        $messages = AiConversation::session($sessionId)
            ->orderBy('created_at')
            ->get();

        return response()->json(['status' => true, 'data' => $messages]);
    }

    /**
     * Simpan satu pesan — dipanggil n8n setelah tiap turn.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'device_id'    => 'nullable|exists:devices,id',
            'batch_id'     => 'nullable|exists:drying_batches,id',
            'session_id'   => 'nullable|string',
            'role'         => ['required', Rule::in(['user', 'assistant', 'system'])],
            'message'      => 'required|string',
            'context_data' => 'nullable|array',
            'ai_model'     => 'nullable|string',
            'tokens_used'  => 'nullable|integer',
        ]);

        $data['session_id'] ??= (string) Str::uuid();
        $data['user_id']    ??= $request->user()?->id;

        $conversation = AiConversation::create($data);

        return response()->json(['status' => true, 'data' => $conversation], 201);
    }

    /**
     * Feedback dari user setelah menerima jawaban AI.
     */
    public function feedback(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $data = $request->validate([
            'is_helpful'    => 'required|boolean',
            'feedback_note' => 'nullable|string',
        ]);

        $aiConversation->update($data);

        return response()->json(['status' => true, 'data' => $aiConversation]);
    }

    public function newSession(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => ['session_id' => (string) Str::uuid()]]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiChatWebController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();

        $sessions = AiConversation::forUser($userId)
            ->select('session_id')
            ->selectRaw('MIN(created_at) as started_at')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as message_count')
            ->groupBy('session_id')
            ->orderByDesc('last_message_at')
            ->limit(20)
            ->get();

        $currentSessionId = $request->query('session_id', $sessions->first()?->session_id);

        $messages = $currentSessionId
            ? AiConversation::session($currentSessionId)->orderBy('created_at')->get()
            : collect();

        $devices = Device::online()->orderBy('device_name')->get(['id', 'device_name']);
        $batches = DryingBatch::active()->with('device')->get(['id', 'batch_code', 'device_id']);

        return view('ai.chat', compact('sessions', 'messages', 'currentSessionId', 'devices', 'batches'));
    }

    public function send(Request $request, AiService $ai): JsonResponse
    {
        $data = $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|string',
            'device_id'  => 'nullable|exists:devices,id',
            'batch_id'   => 'nullable|exists:drying_batches,id',
        ]);

        $sessionId = $data['session_id'] ?: (string) Str::uuid();
        $userId    = auth()->id();

        // Simpan pesan user
        AiConversation::create([
            'user_id'    => $userId,
            'device_id'  => $data['device_id'] ?? null,
            'batch_id'   => $data['batch_id'] ?? null,
            'session_id' => $sessionId,
            'role'       => 'user',
            'message'    => $data['message'],
        ]);

        try {
            $result = $ai->chat(
                userMessage: $data['message'],
                sessionId: $sessionId,
                deviceId: $data['device_id'] ?? null,
                batchId: $data['batch_id'] ?? null,
            );

            // Simpan balasan AI
            AiConversation::create([
                'user_id'     => $userId,
                'device_id'   => $data['device_id'] ?? null,
                'batch_id'    => $data['batch_id'] ?? null,
                'session_id'  => $sessionId,
                'role'        => 'assistant',
                'message'     => $result['message'],
                'ai_model'    => $result['model'],
                'tokens_used' => $result['tokens_used'],
            ]);

            return response()->json([
                'status'     => true,
                'session_id' => $sessionId,
                'reply'      => $result['message'],
                'model'      => $result['model'],
                'tokens'     => $result['tokens_used'],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghubungi AI: ' . $e->getMessage(),
            ], 500);
        }
    }
}

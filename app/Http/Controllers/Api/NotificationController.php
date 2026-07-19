<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::forUser($request->user()->id)
            ->latest();

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $notifications = $query->paginate($request->per_page ?? 20);

        return response()->json(['status' => true, 'data' => $notifications]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'device_id'    => 'nullable|exists:devices,id',
            'batch_id'     => 'nullable|exists:drying_batches,id',
            'type'         => ['required', Rule::in(['info', 'warning', 'alert', 'success', 'error'])],
            'category'     => ['nullable', Rule::in([
                'moisture_alert', 'temperature_alert', 'weather_alert',
                'device_offline', 'batch_complete', 'batch_failed',
                'ai_decision', 'system', 'other',
            ])],
            'title'        => 'required|string',
            'message'      => 'required|string',
            'data'         => 'nullable|array',
            'via_app'      => 'nullable|boolean',
            'via_email'    => 'nullable|boolean',
            'via_sms'      => 'nullable|boolean',
            'via_whatsapp' => 'nullable|boolean',
        ]);

        $data['sent_at'] = now();
        $notification = Notification::create($data);

        return response()->json(['status' => true, 'data' => $notification], 201);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $notification->markAsRead();

        return response()->json(['status' => true, 'data' => $notification]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['status' => true, 'message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser($request->user()->id)->unread()->count();

        return response()->json(['status' => true, 'data' => ['count' => $count]]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(['status' => true, 'message' => 'Notification deleted']);
    }
}

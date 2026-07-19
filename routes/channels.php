<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// AI Chat private channel — only the session owner
Broadcast::channel('ai-chat.{sessionId}', function ($user, $sessionId) {
    return \App\Models\AiConversation::where('session_id', $sessionId)
        ->where('user_id', $user->id)
        ->exists();
});

// Notifications private channel
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

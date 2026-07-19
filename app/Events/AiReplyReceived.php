<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiReplyReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $message,
        public ?string $aiModel = null,
        public ?int $tokensUsed = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ai-chat.{$this->sessionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AiReplyReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'  => $this->sessionId,
            'message'     => $this->message,
            'ai_model'    => $this->aiModel,
            'tokens_used' => $this->tokensUsed,
        ];
    }
}

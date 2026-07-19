<?php

namespace App\Events;

use App\Models\AiDecision;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiDecisionMade implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AiDecision $decision) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('ai-decisions'),
            new Channel("device.{$this->decision->device_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AiDecisionMade';
    }

    public function broadcastWith(): array
    {
        return [
            'decision' => [
                'id'               => $this->decision->id,
                'device_id'        => $this->decision->device_id,
                'batch_id'         => $this->decision->batch_id,
                'decision_type'    => $this->decision->decision_type,
                'reasoning'        => $this->decision->reasoning,
                'confidence_score' => $this->decision->confidence_score,
                'ai_model'         => $this->decision->ai_model,
                'execution_status' => $this->decision->execution_status,
                'decided_at'       => $this->decision->decided_at?->toISOString(),
            ],
        ];
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TypingStarted implements ShouldBroadcastNow
{
    public function __construct(
        public int $conversationId,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("presence.conversation.".$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return "typing.started";
    }

    public function broadcastWith(): array
    {
        return ["conversation_id" => $this->conversationId, "user_id" => $this->userId];
    }
}
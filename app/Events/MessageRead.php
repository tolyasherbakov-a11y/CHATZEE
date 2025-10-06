<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets; // ← добавили
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageRead implements ShouldBroadcastNow
{
    use InteractsWithSockets; // ← добавили

    public function __construct(
        public int $conversationId,
        public int $userId,
        public ?int $lastReadMessageId
    ) {
        // Не отправлять событие инициатору, только другим участникам
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.' . $this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id'      => $this->conversationId,
            'user_id'              => $this->userId,
            'last_read_message_id' => $this->lastReadMessageId,
        ];
    }
}

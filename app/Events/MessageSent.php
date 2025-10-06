<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("conversation.".$this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return "message.sent";
    }

    public function broadcastWith(): array
    {
        return [
            "id" => $this->message->id,
            "conversation_id" => $this->message->conversation_id,
            "user_id" => $this->message->user_id,
            "body" => $this->message->body,
            "attachments" => $this->message->relationLoaded('attachments')
            ? $this->message->attachments->map->toArray()->all()
            : [],

            "created_at" => optional($this->message->created_at)->toISOString(),
        ];
    }
}
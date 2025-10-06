<?php
namespace App\Http\Controllers\Api\V1;
use App\Events\MessageRead;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class ConversationReadController extends Controller
{
    /**
     * Mark conversation read up to given message_id (inclusive).
     * If message_id is omitted, marks up to the latest message.
     */
    public function upTo(Request $request, Conversation $conversation)
    {
        Gate::authorize("view", $conversation);
        $data = $request->validate([
            "message_id" => ["nullable","integer"],
        ]);
        $messageId = $data["message_id"] ?? $conversation->messages()->max("id");
        if ($messageId) {
            // убедимся, что сообщение принадлежит этому диалогу
            $exists = Message::whereKey($messageId)->where("conversation_id", $conversation->id)->exists();
            if (! $exists) {
                return response()->json(["message" => "Message does not belong to conversation"], 422);
            }
        }
        ConversationRead::query()->updateOrCreate(
            ["conversation_id" => $conversation->id, "user_id" => $request->user()->id],
            ["last_read_message_id" => $messageId, "read_at" => now()]
        );
        event(new MessageRead($conversation->id, $request->user()->id, $messageId));
        return response()->json([
            "conversation_id" => $conversation->id,
            "last_read_message_id" => $messageId,
        ]);
    }
}
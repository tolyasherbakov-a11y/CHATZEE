<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation)
    {
        Gate::authorize('view', $conversation);

        $messages = $conversation->messages()->with(['user:id,name','attachments'])->latest('id')->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        Gate::authorize('sendMessage', $conversation);

        $data = $request->validate([
            'body' => ['required','string','max:5000'],
        ]);

        $msg = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        // обновим updated_at у диалога для сортировки
        $conversation->touch();

        event(new MessageSent($msg));

        return response()->json([
            'id' => $msg->id,
            'body' => $msg->body,
            'conversation_id' => $msg->conversation_id,
            'user_id' => $msg->user_id,
        ], 201);

        $payload = [
            'id'              => (int) $message->id,
            'conversation_id' => (int) $message->conversation_id,
            'user_id'         => (int) $message->user_id,
            'body'            => (string) $message->body,
            'created_at'      => $message->created_at?->toISOString(),
        ];
        
        event(new MessageCreated((int) $message->conversation_id, $payload));
    }
}
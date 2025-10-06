<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $convs = Conversation::query()
            ->whereHas('users', fn ($q) => $q->whereKey($me->id))
            ->with(['users:id,name,email'])
            ->latest('updated_at')
            ->paginate(15);

        // Добавляем счётчик непрочитанных на лету
        $convs->getCollection()->transform(function ($c) use ($me) {
            $last = ConversationRead::where('conversation_id', $c->id)
                ->where('user_id', $me->id)
                ->value('last_read_message_id') ?? 0;

            $c->unread_count = $c->messages()->where('id', '>', $last)->count();
            return $c;
        });

        return response()->json($convs);
    }

    public function startDirect(Request $request)
    {
        // Без exists и без different — самопроверку делаем вручную ниже
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $me = $request->user();
        if ((int) $data['user_id'] === (int) $me->id) {
            return response()->json(['message' => 'Cannot start a direct conversation with yourself.'], 422);
        }

        // Проверим существование пользователя тут — вернёт 404, если id неверный
        $other = User::findOrFail((int) $data['user_id']);

        // Создаём/находим 1–1 диалог
        $conv = Conversation::oneToOne($me, $other);

        return response()->json([
            'id'       => $conv->id,
            'is_group' => $conv->is_group,
        ], 201);
    }
}

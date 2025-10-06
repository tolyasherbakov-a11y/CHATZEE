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
            ->whereHas('users', fn ($q) => $q->whereKey($me->getKey()))
            ->with(['users:id,name,email'])
            ->latest('updated_at')
            ->paginate(15);

        // Добавляем счётчик непрочитанных на лету
        $convs->getCollection()->transform(function ($c) use ($me) {
            $last = ConversationRead::where('conversation_id', $c->id)
                ->where('user_id', $me->getKey())
                ->value('last_read_message_id') ?? 0;

            $c->unread_count = $c->messages()->where('id', '>', $last)->count();
            return $c;
        });

        return response()->json($convs);
    }

    public function startDirect(Request $request)
    {
        // Никаких numeric/int правил — id может быть строковым (UUID/ULID)
        $data = $request->validate([
            'user_id' => ['required'],
        ]);

        $meId     = (string) $request->user()->getKey();
        $otherId  = (string) $data['user_id'];

        // Запретить диалог с самим собой — сравниваем как строки
        if ($otherId === $meId) {
            return response()->json(['message' => 'Cannot start a direct conversation with yourself.'], 422);
        }

        // Находим другого пользователя с учётом типа PK
        $other = User::query()->whereKey($otherId)->firstOrFail();

        // Найти/создать 1–1 диалог
        $conv = Conversation::oneToOne($request->user(), $other);

        return response()->json([
            'id'       => $conv->id,
            'is_group' => $conv->is_group,
        ], 201);
    }
}

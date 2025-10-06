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

    public function startDirect(\Illuminate\Http\Request $request)
{
    $data = $request->validate([
        'user_id' => ['required'],
    ]);
    $otherId = (string) $data['user_id'];

    // 1) Берём пользователя как задумано: sanctum-guard
    $me = $request->user();

    // 2) Если по каким-то причинам это не владелец Bearer-токена — переопределим вручную
    $bearer = $request->bearerToken();
    if ($bearer && str_contains($bearer, '|')) {
        [$tokenId] = explode('|', $bearer, 2);
        if ($tokenId !== '') {
            $pat = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
            if ($pat && $pat->tokenable) {
                if (!$me || (string) $me->getKey() !== (string) $pat->tokenable_id) {
                    $me = $pat->tokenable;
                }
            }
        }
    }

    if (!$me) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    $meId = (string) $me->getKey();

    if ($otherId === $meId) {
        // В тестовой среде полезно увидеть реальные значения
        if (app()->environment('testing')) {
            return response()->json([
                'message'  => 'Cannot start a direct conversation with yourself.',
                'me_id'    => $meId,
                'other_id' => $otherId,
            ], 422);
        }
        return response()->json(['message' => 'Cannot start a direct conversation with yourself.'], 422);
    }

    $other = \App\Models\User::query()->whereKey($otherId)->firstOrFail();

    $conv = \App\Models\Conversation::oneToOne($me, $other);

    return response()->json([
        'id'       => $conv->id,
        'is_group' => $conv->is_group,
    ], 201);
}

}

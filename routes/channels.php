<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel("App.Models.User.{id}", function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Участники видны только участникам, presence выдаёт базовый профиль
Broadcast::channel("presence.conversation.{conversationId}", function ($user, $conversationId) {
    $conv = Conversation::find($conversationId);
    if (! $conv) return false;

    if ($conv->users()->whereKey($user->id)->exists()) {
        return [
            "id" => $user->id,
            "name" => $user->name,
            "avatar" => method_exists($user, "getAttribute") ? $user->getAttribute("avatar_url") : null,
        ];
    }
    return false;
});

// Приватный поток сообщений (мы уже его используем в M4)
Broadcast::channel("conversation.{conversationId}", function ($user, $conversationId) {
    $conv = Conversation::find($conversationId);
    if (! $conv) return false;
    return $conv && $conv->users()->whereKey($user->id)->exists();
});
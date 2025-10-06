<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

/**
 * Приватный канал диалога.
 * Разрешаем доступ только участникам conversation.{conversationId}
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conv = Conversation::query()->find($conversationId);
    if (! $conv) {
        return false;
    }
    // Проверяем, что текущий пользователь состоит в диалоге
    return $conv->users()->whereKey($user->getKey())->exists();
});

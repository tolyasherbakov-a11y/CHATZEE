<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Видеть/доступ к диалогу разрешено только участнику.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->whereKey($user->getKey())->exists();
    }
}

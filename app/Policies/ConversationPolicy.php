<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->whereKey($user->id)->exists();
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->whereKey($user->id)->exists();
    }
}
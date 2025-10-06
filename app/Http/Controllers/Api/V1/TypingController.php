<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TypingStarted;
use App\Events\TypingStopped;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TypingController extends Controller
{
    public function start(Request $request, Conversation $conversation)
    {
        Gate::authorize("view", $conversation);

        event(new TypingStarted($conversation->id, $request->user()->id));
        return response()->noContent(); // 204
    }

    public function stop(Request $request, Conversation $conversation)
    {
        Gate::authorize("view", $conversation);

        event(new TypingStopped($conversation->id, $request->user()->id));
        return response()->noContent(); // 204
    }
}
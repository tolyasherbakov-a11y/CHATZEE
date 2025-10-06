<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Авторизация к /broadcasting/auth по токену Sanctum (Bearer ...)
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Регистрируем каналы из routes/channels.php
        require base_path('routes/channels.php');
    }
}

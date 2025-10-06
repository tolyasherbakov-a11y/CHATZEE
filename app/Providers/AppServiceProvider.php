<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // Автоматически создать файл SQLite для тестов, если он не существует
    if (app()->environment('testing') && config('database.default') === 'sqlite') {
        $dbPath = config('database.connections.sqlite.database');

        // На случай, если в конфиге вернулся относительный путь (не должен после шага 1)
        if (!str_starts_with($dbPath, DIRECTORY_SEPARATOR)) {
            $dbPath = database_path($dbPath);
        }

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!file_exists($dbPath)) {
            // создаём пустой файл — SQLite сам наполнит его при миграциях
            @touch($dbPath);
        }
    }
}

}

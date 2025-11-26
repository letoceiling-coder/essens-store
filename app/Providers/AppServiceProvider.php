<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\ExtendedTelegraph;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем ExtendedTelegraph как singleton
        $this->app->singleton(ExtendedTelegraph::class, function ($app) {
            return new ExtendedTelegraph();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Используем кастомную модель PersonalAccessToken
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        
        // Настраиваем редирект для неаутентифицированных пользователей
        // Для API маршрутов не делаем редирект - обработчик исключений вернет JSON
        \Illuminate\Auth\Middleware\Authenticate::redirectUsing(function (\Illuminate\Http\Request $request) {
            // Для API маршрутов возвращаем null, чтобы обработчик исключений вернул JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            // Для веб-маршрутов тоже не делаем редирект, так как у нас SPA
            return null;
        });
    }
}

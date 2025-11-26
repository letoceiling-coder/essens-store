<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        
        // Добавляем middleware для обработки API аутентификации
        $middleware->append(\App\Http\Middleware\HandleApiAuthentication::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Для API маршрутов возвращаем JSON вместо редиректа на login
        // Это должно сработать ДО того, как Laravel попытается сделать редирект
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            // Всегда возвращаем JSON для API маршрутов
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            
            // Для запросов с заголовком Accept: application/json тоже возвращаем JSON
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            
            // Для веб-запросов возвращаем null, чтобы Laravel использовал стандартную обработку
            return null;
        });
    })->create();

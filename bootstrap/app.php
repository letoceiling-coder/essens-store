<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // ВАЖНО: API роуты загружаются первыми, чтобы они обрабатывались раньше web роутов
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        
        // ВАЖНО: ForceJsonForApi должен быть первым, чтобы обрабатывать все API запросы
        $middleware->prepend(\App\Http\Middleware\ForceJsonForApi::class);
        
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
        
        // Для всех исключений в API маршрутах возвращаем JSON
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Логируем исключения для Telegram webhook
            if ($request->is('api/telegram/webhook/*')) {
                \Illuminate\Support\Facades\Log::error('Exception in Telegram webhook', [
                    'path' => $request->path(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Internal Server Error',
                    'error_type' => get_class($e),
                ], $statusCode);
            }
            
            return null;
        });
    })->create();

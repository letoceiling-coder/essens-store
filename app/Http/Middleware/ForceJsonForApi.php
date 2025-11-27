<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class ForceJsonForApi
{
    /**
     * Принудительно устанавливаем JSON для всех API запросов
     * И перехватываем API запросы, которые попали в web роуты
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Если это API запрос
        if ($request->is('api/*')) {
            // Принудительно устанавливаем Accept: application/json
            $request->headers->set('Accept', 'application/json');
            
            // Проверяем, зарегистрирован ли этот роут в API
            $route = $request->route();
            
            // Если роут не найден или это web роут, возвращаем 404 JSON
            if (!$route || !$route->getPrefix() || $route->getPrefix() !== 'api') {
                // Проверяем, есть ли такой роут в API
                $apiRoute = Route::getRoutes()->match($request);
                
                // Если роут не найден или это не API роут, возвращаем 404
                if (!$apiRoute || !$apiRoute->getPrefix() || $apiRoute->getPrefix() !== 'api') {
                    return response()->json([
                        'success' => false,
                        'message' => 'API route not found',
                        'error' => 'Route conflict: API request handled by web router. Please clear route cache on server.',
                        'path' => $request->path(),
                    ], 404);
                }
            }
        }
        
        $response = $next($request);
        
        // Если это API запрос и ответ не JSON, возвращаем ошибку
        if ($request->is('api/*')) {
            // Проверяем, что ответ JSON
            $contentType = $response->headers->get('Content-Type');
            if (!$contentType || strpos($contentType, 'application/json') === false) {
                // Если это не JSON, возвращаем ошибку
                return response()->json([
                    'success' => false,
                    'message' => 'API endpoint должен возвращать JSON',
                    'error' => 'Route conflict: API route handled by web router. Please clear route cache on server.',
                    'path' => $request->path(),
                    'content_type' => $contentType,
                ], 500);
            }
        }
        
        return $response;
    }
}


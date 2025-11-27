<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ForceJsonForApi
{
    /**
     * Принудительно устанавливаем JSON для всех API запросов
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Если это API запрос
        if ($request->is('api/*')) {
            // Принудительно устанавливаем Accept: application/json
            $request->headers->set('Accept', 'application/json');
        }
        
        // Пропускаем запрос дальше
        $response = $next($request);
        
        // Если это API запрос и ответ не JSON (и это не редирект), логируем предупреждение
        if ($request->is('api/*') && $response->getStatusCode() !== 302) {
            $contentType = $response->headers->get('Content-Type');
            
            // Проверяем, что ответ JSON (но не блокируем, если это ошибка 404 от самого Laravel)
            if (!$contentType || strpos($contentType, 'application/json') === false) {
                // Если это не JSON и не HTML (редирект), логируем
                if (strpos($contentType, 'text/html') === false) {
                    Log::warning('ForceJsonForApi: API response is not JSON', [
                        'path' => $request->path(),
                        'content_type' => $contentType,
                        'status' => $response->getStatusCode(),
                    ]);
                }
            }
        }
        
        return $response;
    }
}

<?php

use Illuminate\Support\Facades\Route;

// Единая точка входа для SPA
// Все маршруты обрабатываются Vue Router на клиенте
// ИСКЛЮЧАЕМ API роуты из этого catch-all
// Важно: этот роут должен быть последним, чтобы не перехватывать API роуты
Route::get('/{any}', function () {
    $request = request();
    
    // СТРОГАЯ проверка: если запрос к API, возвращаем 404 JSON
    // Проверяем путь, заголовки и метод
    if ($request->is('api/*') || 
        $request->expectsJson() || 
        $request->wantsJson() ||
        $request->header('Accept') === 'application/json' ||
        strpos($request->path(), 'api/') === 0) {
        
        return response()->json([
            'success' => false,
            'message' => 'API route not found',
            'error' => 'This API endpoint should be handled by routes/api.php. Please check server route cache.',
            'path' => $request->path(),
        ], 404);
    }
    
    return view('app');
})->where('any', '^(?!api).*');

<?php

use Illuminate\Support\Facades\Route;

// Единая точка входа для SPA
// Все маршруты обрабатываются Vue Router на клиенте
// ИСКЛЮЧАЕМ API роуты из этого catch-all
// Важно: этот роут должен быть последним, чтобы не перехватывать API роуты
// Используем where для исключения api/* из catch-all
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*');

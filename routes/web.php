<?php

use Illuminate\Support\Facades\Route;

// Deploy endpoint (для обратной совместимости)
Route::post('/deploy', [\App\Http\Controllers\DeployController::class, 'deploy']);

// Единая точка входа для SPA
// Все маршруты обрабатываются Vue Router на клиенте
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

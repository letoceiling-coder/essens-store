<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeployController extends Controller
{
    /**
     * Обработка запроса на деплой
     */
    public function deploy(Request $request)
    {
        $secret = env('DEPLOY_SECRET');
        $clientSecret = $request->header('Deploy-Secret');

        if ($secret !== $clientSecret) {
            return response()->json(['error' => 'Invalid secret'], 403);
        }

        $output = [];
        $status = 0;

        // Проверка версии composer и npm
        $composerVersion = exec('composer --version', $output, $status);
        if ($status !== 0 || !$composerVersion) {
            exec('apt-get install composer -y', $output, $status);
        }

        $npmVersion = exec('npm --version', $output, $status);
        if ($status !== 0 || !$npmVersion) {
            exec('apt-get install npm -y', $output, $status);
        }

        // Обновление файлов из git
        exec('git pull origin master', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'Git pull failed', 'output' => $output], 500);
        }

        // Установка зависимостей и сборка проекта
        exec('composer install --no-interaction --prefer-dist --optimize-autoloader', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'Composer install failed', 'output' => $output], 500);
        }

        exec('npm install', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'NPM install failed', 'output' => $output], 500);
        }

        // Сборка проекта
        exec('npm run build', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'NPM build failed', 'output' => $output], 500);
        }

        // Выполнение миграций
        exec('php artisan migrate --force', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'Migrations failed', 'output' => $output], 500);
        }

        // Очистка кеша
        exec('php artisan optimize:clear', $output, $status);
        if ($status !== 0) {
            return response()->json(['error' => 'Cache clearing failed', 'output' => $output], 500);
        }

        return response()->json(['status' => 'Deployment successful', 'output' => $output]);
    }
}

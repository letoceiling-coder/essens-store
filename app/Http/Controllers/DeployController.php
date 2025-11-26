<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeployController extends Controller
{
    /**
     * Обработка запроса на развертывание
     */
    public function deploy(Request $request)
    {
        // Проверка секретного ключа
        $secret = $request->input('secret');
        $expectedSecret = config('app.deploy_secret', env('DEPLOY_SECRET'));
        
        if ($expectedSecret && $secret !== $expectedSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный секретный ключ',
            ], 403);
        }

        $branch = $request->input('branch', 'master');
        $timestamp = $request->input('timestamp');

        Log::info('Deploy request received', [
            'branch' => $branch,
            'timestamp' => $timestamp,
            'ip' => $request->ip(),
        ]);

        try {
            // Проверяем, что мы в git репозитории
            if (!is_dir(base_path('.git'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Git репозиторий не найден',
                ], 500);
            }

            // Получаем текущую ветку
            $currentBranchProcess = new Process(['git', 'branch', '--show-current']);
            $currentBranchProcess->setWorkingDirectory(base_path());
            $currentBranchProcess->run();
            $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;

            // Если указанная ветка отличается от текущей, переключаемся
            if ($currentBranch !== $branch) {
                $checkoutProcess = new Process(['git', 'checkout', $branch]);
                $checkoutProcess->setWorkingDirectory(base_path());
                $checkoutProcess->setTimeout(60);
                $checkoutProcess->run();
                
                if (!$checkoutProcess->isSuccessful()) {
                    Log::error('Failed to checkout branch', [
                        'branch' => $branch,
                        'error' => $checkoutProcess->getErrorOutput(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Не удалось переключиться на ветку: ' . $branch,
                        'error' => $checkoutProcess->getErrorOutput(),
                    ], 500);
                }
            }

            // Получаем последние изменения из git
            $fetchProcess = new Process(['git', 'fetch', 'origin']);
            $fetchProcess->setWorkingDirectory(base_path());
            $fetchProcess->setTimeout(60);
            $fetchProcess->run();

            if (!$fetchProcess->isSuccessful()) {
                Log::warning('Failed to fetch from origin', [
                    'error' => $fetchProcess->getErrorOutput(),
                ]);
            }

            // Делаем pull
            $pullProcess = new Process(['git', 'pull', 'origin', $branch]);
            $pullProcess->setWorkingDirectory(base_path());
            $pullProcess->setTimeout(300);
            $pullProcess->run();

            if (!$pullProcess->isSuccessful()) {
                Log::error('Failed to pull from origin', [
                    'branch' => $branch,
                    'error' => $pullProcess->getErrorOutput(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить изменения из git',
                    'error' => $pullProcess->getErrorOutput(),
                ], 500);
            }

            $pullOutput = $pullProcess->getOutput();
            Log::info('Git pull completed', [
                'branch' => $branch,
                'output' => $pullOutput,
            ]);

            // Проверяем, были ли изменения
            $hasChanges = strpos($pullOutput, 'Already up to date') === false;

            // Выполняем обновление зависимостей и кеша
            $commands = [];
            
            if ($hasChanges) {
                // Обновляем зависимости composer
                $composerProcess = new Process(['composer', 'install', '--no-dev', '--optimize-autoloader']);
                $composerProcess->setWorkingDirectory(base_path());
                $composerProcess->setTimeout(600);
                $composerProcess->run();
                
                if (!$composerProcess->isSuccessful()) {
                    Log::warning('Composer install failed', [
                        'error' => $composerProcess->getErrorOutput(),
                    ]);
                }

                // Очищаем кеш
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                // Кешируем конфигурацию
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');

                // Выполняем миграции
                try {
                    Artisan::call('migrate', ['--force' => true]);
                } catch (\Exception $e) {
                    Log::warning('Migration failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Развертывание выполнено успешно',
                'status' => 'completed',
                'branch' => $branch,
                'has_changes' => $hasChanges,
                'pull_output' => $pullOutput,
            ]);

        } catch (\Exception $e) {
            Log::error('Deploy failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при развертывании: ' . $e->getMessage(),
            ], 500);
        }
    }
}


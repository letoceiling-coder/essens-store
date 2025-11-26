<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeployController extends Controller
{
    /**
     * Получить путь к исполняемому файлу PHP
     */
    protected function getPhpExecutable(): string
    {
        // Проверяем переменную окружения
        $phpPath = env('PHP_EXECUTABLE');
        if ($phpPath) {
            return $phpPath;
        }

        // Пробуем найти php8.2
        $php82 = $this->findPhpExecutable('php8.2');
        if ($php82) {
            return $php82;
        }

        // Пробуем найти php8.1
        $php81 = $this->findPhpExecutable('php8.1');
        if ($php81) {
            return $php81;
        }

        // Пробуем найти php8.0
        $php80 = $this->findPhpExecutable('php8.0');
        if ($php80) {
            return $php80;
        }

        // По умолчанию используем php
        return 'php';
    }

    /**
     * Найти Git репозиторий
     */
    protected function findGitRepository(): ?string
    {
        // Проверяем переменную окружения
        $gitPath = env('GIT_REPO_PATH');
        if ($gitPath && is_dir($gitPath . '/.git')) {
            return $gitPath;
        }

        // Проверяем в base_path (текущая директория Laravel)
        $basePath = base_path();
        if (is_dir($basePath . '/.git')) {
            return $basePath;
        }

        // Проверяем в родительской директории
        $parentPath = dirname($basePath);
        if (is_dir($parentPath . '/.git')) {
            return $parentPath;
        }

        // Проверяем в ~/essens
        $homePath = getenv('HOME') ?: getenv('USERPROFILE');
        if ($homePath) {
            $essensPath = $homePath . '/essens';
            if (is_dir($essensPath . '/.git')) {
                return $essensPath;
            }
        }

        return null;
    }

    /**
     * Получить базовые аргументы Git с исправлением ownership
     */
    protected function getGitBaseArgs(string $gitRepoPath): array
    {
        // Используем опцию -c для установки safe.directory для каждой команды
        // Это обходит проблему с dubious ownership
        return [
            'git',
            '-c', 'safe.directory=' . $gitRepoPath
        ];
    }

    /**
     * Собрать фронтенд (Vite)
     */
    protected function buildFrontend(string $projectPath): void
    {
        // Проверяем, есть ли package.json
        $packageJsonPath = $projectPath . '/package.json';
        if (!file_exists($packageJsonPath)) {
            Log::info('package.json not found, skipping frontend build');
            return;
        }

        // Проверяем, есть ли Node.js и npm
        $nodeCheck = new Process(['which', 'node']);
        $nodeCheck->run();
        if (!$nodeCheck->isSuccessful()) {
            Log::warning('Node.js not found, skipping frontend build');
            return;
        }

        $npmCheck = new Process(['which', 'npm']);
        $npmCheck->run();
        if (!$npmCheck->isSuccessful()) {
            Log::warning('npm not found, skipping frontend build');
            return;
        }

        try {
            // Устанавливаем зависимости (если нужно)
            // Проверяем, существует ли node_modules
            if (!is_dir($projectPath . '/node_modules')) {
                Log::info('Installing npm dependencies...');
                $npmInstallProcess = new Process(['npm', 'install', '--production']);
                $npmInstallProcess->setWorkingDirectory($projectPath);
                $npmInstallProcess->setTimeout(600);
                $npmInstallProcess->run();
                
                if (!$npmInstallProcess->isSuccessful()) {
                    Log::warning('npm install failed', [
                        'error' => $npmInstallProcess->getErrorOutput(),
                    ]);
                } else {
                    Log::info('npm install completed');
                }
            }

            // Собираем фронтенд
            Log::info('Building frontend assets...');
            $buildProcess = new Process(['npm', 'run', 'build']);
            $buildProcess->setWorkingDirectory($projectPath);
            $buildProcess->setTimeout(600);
            $buildProcess->run();
            
            if (!$buildProcess->isSuccessful()) {
                Log::warning('Frontend build failed', [
                    'error' => $buildProcess->getErrorOutput(),
                    'output' => $buildProcess->getOutput(),
                ]);
            } else {
                Log::info('Frontend build completed');
            }
        } catch (\Exception $e) {
            Log::warning('Frontend build error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Найти исполняемый файл PHP
     */
    protected function findPhpExecutable(string $name): ?string
    {
        $paths = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/opt/php/bin/' . $name,
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Пробуем через which
        $process = new Process(['which', $name]);
        $process->run();
        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            if (!empty($output)) {
                return $output;
            }
        }

        return null;
    }

    /**
     * Обработка запроса на развертывание
     */
    public function deploy(Request $request)
    {
        // Убеждаемся, что всегда возвращаем JSON
        $request->headers->set('Accept', 'application/json');
        
        try {
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
            // Определяем путь к Git репозиторию
            $gitRepoPath = $this->findGitRepository();
            
            if (!$gitRepoPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Git репозиторий не найден',
                ], 500);
            }

            // Получаем базовые аргументы Git с исправлением ownership
            $gitBaseArgs = $this->getGitBaseArgs($gitRepoPath);

            // Получаем текущую ветку
            $currentBranchProcess = new Process(array_merge($gitBaseArgs, ['branch', '--show-current']));
            $currentBranchProcess->setWorkingDirectory($gitRepoPath);
            $currentBranchProcess->run();
            $currentBranch = trim($currentBranchProcess->getOutput()) ?: $branch;

            // Если указанная ветка отличается от текущей, переключаемся
            if ($currentBranch !== $branch) {
                $checkoutProcess = new Process(array_merge($gitBaseArgs, ['checkout', $branch]));
                $checkoutProcess->setWorkingDirectory($gitRepoPath);
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
            $fetchProcess = new Process(array_merge($gitBaseArgs, ['fetch', 'origin']));
            $fetchProcess->setWorkingDirectory($gitRepoPath);
            $fetchProcess->setTimeout(60);
            $fetchProcess->run();

            if (!$fetchProcess->isSuccessful()) {
                Log::warning('Failed to fetch from origin', [
                    'error' => $fetchProcess->getErrorOutput(),
                ]);
            }

            // Делаем pull
            $pullProcess = new Process(array_merge($gitBaseArgs, ['pull', 'origin', $branch]));
            $pullProcess->setWorkingDirectory($gitRepoPath);
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

            // Получаем путь к PHP
            $phpExecutable = $this->getPhpExecutable();
            Log::info('Using PHP executable', ['php' => $phpExecutable]);

            // Выполняем обновление зависимостей и кеша
            $commands = [];
            
            if ($hasChanges) {
                // Обновляем зависимости composer
                // Используем правильный PHP для composer
                $composerProcess = new Process([$phpExecutable, base_path('composer.phar'), 'install', '--no-dev', '--optimize-autoloader']);
                $composerProcess->setWorkingDirectory(base_path());
                $composerProcess->setTimeout(600);
                
                // Если composer.phar не найден, пробуем composer из PATH
                if (!file_exists(base_path('composer.phar'))) {
                    $composerProcess = new Process(['composer', 'install', '--no-dev', '--optimize-autoloader']);
                    $composerProcess->setWorkingDirectory(base_path());
                    $composerProcess->setTimeout(600);
                }
                
                $composerProcess->run();
                
                if (!$composerProcess->isSuccessful()) {
                    Log::warning('Composer install failed', [
                        'error' => $composerProcess->getErrorOutput(),
                        'output' => $composerProcess->getOutput(),
                    ]);
                } else {
                    Log::info('Composer install completed');
                }

                // Очищаем кеш через Artisan (использует текущий PHP)
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                // Кешируем конфигурацию
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');

                // Собираем фронтенд (Vite)
                // Используем base_path() для Laravel проекта, а не gitRepoPath
                $this->buildFrontend(base_path());

                // Выполняем миграции
                try {
                    Artisan::call('migrate', ['--force' => true]);
                    Log::info('Migrations completed');
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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при развертывании: ' . $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Deploy failed (Throwable)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Критическая ошибка при развертывании: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }
}


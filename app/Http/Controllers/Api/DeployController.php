<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    /**
     * Обработка запроса на деплой
     */
    public function deploy(Request $request)
    {
        try {
            $secret = env('DEPLOY_SECRET');
            
            // Пробуем получить заголовок разными способами (Laravel может нормализовать заголовки)
            $clientSecret = $request->header('Deploy-Secret') 
                ?? $request->header('deploy-secret')
                ?? $request->header('DEPLOY-SECRET');

            // Убираем пробелы и переносы строк (на случай, если они попали в .env)
            $secret = trim($secret);
            $clientSecret = trim($clientSecret ?? '');

            // Логируем для отладки (без полного секрета)
            Log::info('Deploy: Secret check', [
                'server_secret_set' => !empty($secret),
                'server_secret_length' => $secret ? strlen($secret) : 0,
                'server_secret_preview' => $secret ? substr($secret, 0, 4) . '...' : 'empty',
                'client_secret_provided' => !empty($clientSecret),
                'client_secret_length' => $clientSecret ? strlen($clientSecret) : 0,
                'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 4) . '...' : 'empty',
                'secrets_match' => $secret === $clientSecret,
                'secrets_identical' => $secret === $clientSecret,
            ]);

            if (empty($secret)) {
                Log::error('Deploy: DEPLOY_SECRET not set on server');
                return response()->json([
                    'error' => 'DEPLOY_SECRET not configured on server',
                    'hint' => 'Добавьте DEPLOY_SECRET в .env файл на сервере и выполните: php artisan config:clear'
                ], 500);
            }

            if ($secret !== $clientSecret) {
                Log::warning('Deploy: Invalid secret', [
                    'client_secret_provided' => $clientSecret ? 'yes' : 'no',
                    'client_secret_length' => $clientSecret ? strlen($clientSecret) : 0,
                    'server_secret_length' => strlen($secret),
                    'server_secret_preview' => substr($secret, 0, 4) . '...',
                    'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 4) . '...' : 'empty',
                ]);
                return response()->json([
                    'error' => 'Invalid secret',
                    'hint' => 'Проверьте, что DEPLOY_SECRET в .env на сервере совпадает с DEPLOY_SECRET в .env на локальной машине. После изменения .env выполните: php artisan config:clear',
                    'debug' => [
                        'server_secret_length' => strlen($secret),
                        'client_secret_length' => $clientSecret ? strlen($clientSecret) : 0,
                        'server_secret_preview' => substr($secret, 0, 4) . '...',
                        'client_secret_preview' => $clientSecret ? substr($clientSecret, 0, 4) . '...' : 'empty',
                    ]
                ], 403);
            }

            $allOutput = [];
            $output = [];
            $status = 0;

            // Проверка версии composer и npm (пропускаем установку, так как это может требовать sudo)
            $composerVersion = exec('composer --version 2>&1', $output, $status);
            $allOutput['composer_check'] = ['version' => $composerVersion, 'status' => $status, 'output' => $output];
            if ($status !== 0 || !$composerVersion) {
                Log::warning('Deploy: Composer not found or error', ['output' => $output]);
            }

            $output = [];
            $npmVersion = exec('npm --version 2>&1', $output, $status);
            $allOutput['npm_check'] = ['version' => $npmVersion, 'status' => $status, 'output' => $output];
            if ($status !== 0 || !$npmVersion) {
                Log::warning('Deploy: NPM not found or error', ['output' => $output]);
            }

            // Проверка наличия git репозитория
            $gitDir = base_path() . '/.git';
            if (!is_dir($gitDir)) {
                Log::error('Deploy: Git repository not found', ['path' => base_path(), 'git_dir' => $gitDir]);
                return response()->json([
                    'success' => false,
                    'message' => 'Git репозиторий не найден',
                    'error' => 'Git репозиторий не найден. Необходимо клонировать репозиторий на сервер или инициализировать git в директории проекта.',
                    'path' => base_path(),
                    'git_dir' => $gitDir,
                    'hint' => 'Выполните на сервере: git clone <repository-url> . или git init && git remote add origin <repository-url>',
                ], 500);
            }

            // Функция для безопасного выполнения git команд
            $projectPath = base_path();
            $gitSafePath = escapeshellarg($projectPath);
            
            // Используем переменную окружения GIT_SAFE_DIRECTORY (работает без sudo)
            // И устанавливаем safe.directory через флаг -c в каждой команде
            
            // Проверка, что это git репозиторий
            // Используем переменную окружения GIT_SAFE_DIRECTORY для обхода проблемы с правами
            // GIT_CONFIG_NOSYSTEM игнорирует системные настройки, которые могут требовать прав
            $env = [
                'GIT_SAFE_DIRECTORY' => $projectPath,
                'GIT_CONFIG_NOSYSTEM' => '1',
                'HOME' => $_SERVER['HOME'] ?? '/tmp',
            ];
            $envString = '';
            foreach ($env as $key => $value) {
                $envString .= $key . '=' . escapeshellarg($value) . ' ';
            }
            
            // Используем переменную окружения GIT_SAFE_DIRECTORY для обхода проблемы с правами
            // Это работает без необходимости изменения git config
            // Также используем GIT_CONFIG_NOSYSTEM=1 чтобы игнорировать системные настройки
            $gitEnv = 'GIT_SAFE_DIRECTORY=' . escapeshellarg($projectPath) . ' GIT_CONFIG_NOSYSTEM=1 ';
            
            // Проверка, что это git репозиторий
            // Используем переменную окружения напрямую в команде git
            $output = [];
            // Пробуем сначала с переменной окружения
            exec($gitEnv . 'cd ' . $projectPath . ' && git rev-parse --git-dir 2>&1', $output, $status);
            
            // Если не работает, пробуем с флагом -c safe.directory в дополнение к переменной окружения
            if ($status !== 0) {
                $output = [];
                exec($gitEnv . 'cd ' . $projectPath . ' && git -c safe.directory=' . $gitSafePath . ' rev-parse --git-dir 2>&1', $output, $status);
            }
            
            // Если все еще не работает, пробуем прочитать локальный config и использовать его
            if ($status !== 0) {
                $outputStr = implode(' ', $output);
                if (strpos($outputStr, 'dubious ownership') !== false) {
                    // Проверяем, есть ли локальный config
                    $configFile = $projectPath . '/.git/config';
                    if (file_exists($configFile)) {
                        $configContent = file_get_contents($configFile);
                        if (strpos($configContent, 'safe.directory') !== false) {
                            Log::info('Deploy: Local git config contains safe.directory, but still failing', [
                                'path' => $projectPath,
                            ]);
                        }
                    }
                    
                    // Пытаемся добавить в локальный config (не требует прав суперпользователя)
                    exec($gitEnv . 'cd ' . $projectPath . ' && git config --local safe.directory ' . $gitSafePath . ' 2>&1', $localOutput, $localStatus);
                    if ($localStatus === 0) {
                        Log::info('Deploy: Added directory to local git config', [
                            'path' => $projectPath, 
                            'output' => $localOutput,
                        ]);
                        // Повторяем проверку
                        $output = [];
                        exec($gitEnv . 'cd ' . $projectPath . ' && git rev-parse --git-dir 2>&1', $output, $status);
                    } else {
                        Log::warning('Deploy: Could not add to local git config', [
                            'path' => $projectPath, 
                            'output' => $localOutput,
                            'status' => $localStatus
                        ]);
                    }
                }
            }
            
            // Если проверка не прошла, все равно пробуем выполнить git pull
            // Возможно, проверка не работает, но git pull может работать с переменной окружения
            if ($status !== 0) {
                Log::warning('Deploy: Git repository check failed, but continuing with git pull', [
                    'output' => $output, 
                    'status' => $status,
                    'path' => $projectPath
                ]);
                // Не возвращаем ошибку, продолжаем выполнение
            }

            // Проверка наличия удаленного репозитория
            $output = [];
            $gitEnv = 'GIT_SAFE_DIRECTORY=' . escapeshellarg($projectPath) . ' GIT_CONFIG_NOSYSTEM=1 ';
            exec($gitEnv . 'cd ' . $projectPath . ' && git remote -v 2>&1', $output, $status);
            $allOutput['git_remote_check'] = ['status' => $status, 'output' => $output];
            if ($status !== 0 || empty($output)) {
                Log::warning('Deploy: No remote repository configured', ['output' => $output]);
            }

            // Обновление файлов из git
            // Используем переменную окружения GIT_SAFE_DIRECTORY (работает без изменения конфигурации)
            $output = [];
            // Пробуем несколько вариантов для обхода проблемы с правами
            exec($gitEnv . 'cd ' . $projectPath . ' && git pull origin master 2>&1', $output, $status);
            
            // Если не работает, пробуем с флагом -c safe.directory
            if ($status !== 0) {
                $output = [];
                exec($gitEnv . 'cd ' . $projectPath . ' && git -c safe.directory=' . $gitSafePath . ' pull origin master 2>&1', $output, $status);
            }
            $allOutput['git_pull'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                // Если git pull не работает, пробуем использовать скрипт deploy.sh
                $deployScript = $projectPath . '/deploy.sh';
                if (file_exists($deployScript)) {
                    Log::info('Deploy: Git pull failed, trying deploy.sh script', ['script' => $deployScript]);
                    
                    // Определяем владельца репозитория
                    $repoOwner = @fileowner($projectPath);
                    $ownerInfo = $repoOwner ? @posix_getpwuid($repoOwner) : null;
                    $ownerUser = $ownerInfo['name'] ?? 'dsc23ytp';
                    
                    // Выполняем скрипт от имени владельца репозитория
                    $output = [];
                    // Используем bash с переменными окружения для выполнения скрипта
                    $scriptEnv = 'GIT_SAFE_DIRECTORY=' . escapeshellarg($projectPath) . ' GIT_CONFIG_NOSYSTEM=1 ';
                    exec($scriptEnv . 'cd ' . $projectPath . ' && bash ' . escapeshellarg($deployScript) . ' 2>&1', $output, $status);
                    
                    if ($status === 0) {
                        Log::info('Deploy: deploy.sh executed successfully');
                        // Скрипт уже выполнил все операции, возвращаем успех
                        return response()->json([
                            'status' => 'Deployment successful (via deploy.sh)',
                            'method' => 'deploy.sh',
                            'all_output' => $allOutput
                        ]);
                    } else {
                        Log::error('Deploy: deploy.sh failed', ['output' => $output, 'status' => $status]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Git pull failed and deploy.sh also failed',
                            'error' => 'Git pull failed',
                            'output' => $output,
                            'status' => $status,
                            'all_output' => $allOutput,
                            'hint' => 'Проверьте права на выполнение скрипта deploy.sh: chmod +x deploy.sh'
                        ], 500);
                    }
                } else {
                    Log::error('Deploy: Git pull failed', ['output' => $output, 'status' => $status]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Git pull failed',
                        'error' => 'Git pull failed',
                        'output' => $output,
                        'status' => $status,
                        'all_output' => $allOutput,
                        'hint' => 'Выполните на сервере: cd ' . $projectPath . ' && git config --local safe.directory ' . $projectPath . ' или создайте скрипт deploy.sh'
                    ], 500);
                }
            }

            // Установка зависимостей и сборка проекта
            $output = [];
            exec('cd ' . base_path() . ' && composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1', $output, $status);
            $allOutput['composer_install'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: Composer install failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'error' => 'Composer install failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
            }

            $output = [];
            exec('cd ' . base_path() . ' && npm install 2>&1', $output, $status);
            $allOutput['npm_install'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: NPM install failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'error' => 'NPM install failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
            }

            // Сборка проекта
            $output = [];
            exec('cd ' . base_path() . ' && npm run build 2>&1', $output, $status);
            $allOutput['npm_build'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: NPM build failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'error' => 'NPM build failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
            }

            // Выполнение миграций
            $output = [];
            exec('cd ' . base_path() . ' && php artisan migrate --force 2>&1', $output, $status);
            $allOutput['migrations'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: Migrations failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'error' => 'Migrations failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
            }

            // Очистка кеша
            $output = [];
            exec('cd ' . base_path() . ' && php artisan optimize:clear 2>&1', $output, $status);
            $allOutput['cache_clear'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: Cache clearing failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'error' => 'Cache clearing failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
            }

            // Получаем информацию о текущем коммите
            $output = [];
            exec($envString . 'cd ' . $projectPath . ' && git -c safe.directory=' . $gitSafePath . ' log -1 --pretty=format:"%H|%s|%an|%ad" --date=iso 2>&1', $output, $status);
            $commitInfo = [];
            if ($status === 0 && !empty($output)) {
                $commitData = explode('|', $output[0]);
                $commitInfo = [
                    'hash' => $commitData[0] ?? null,
                    'message' => $commitData[1] ?? null,
                    'author' => $commitData[2] ?? null,
                    'date' => $commitData[3] ?? null,
                ];
            }
            
            // Получаем короткий хеш коммита
            $output = [];
            exec($envString . 'cd ' . $projectPath . ' && git -c safe.directory=' . $gitSafePath . ' rev-parse --short HEAD 2>&1', $output, $status);
            $shortHash = $status === 0 && !empty($output) ? $output[0] : null;
            
            // Получаем дату последнего обновления файлов
            $lastModified = filemtime(base_path() . '/.git/HEAD');
            
            Log::info('Deploy: Deployment successful', [
                'commit' => $shortHash,
                'commit_info' => $commitInfo,
            ]);
            
            return response()->json([
                'status' => 'Deployment successful',
                'commit' => $shortHash,
                'commit_info' => $commitInfo,
                'deployed_at' => date('Y-m-d H:i:s'),
                'last_modified' => date('Y-m-d H:i:s', $lastModified),
                'all_output' => $allOutput
            ]);
        } catch (\Exception $e) {
            Log::error('Deploy: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить информацию о текущей версии на сервере
     */
    public function version(Request $request)
    {
        try {
            $projectPath = base_path();
            $info = [];

            // Проверяем наличие git
            $gitDir = $projectPath . '/.git';
            if (is_dir($gitDir)) {
                // Получаем текущий коммит
                $output = [];
                exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' rev-parse HEAD 2>&1', $output, $status);
                $info['commit_hash'] = $status === 0 && !empty($output) ? $output[0] : null;

                // Короткий хеш
                $output = [];
                exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' rev-parse --short HEAD 2>&1', $output, $status);
                $info['commit_short'] = $status === 0 && !empty($output) ? $output[0] : null;

                // Информация о коммите
                $output = [];
                exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' log -1 --pretty=format:"%H|%s|%an|%ad" --date=iso 2>&1', $output, $status);
                if ($status === 0 && !empty($output)) {
                    $commitData = explode('|', $output[0]);
                    $info['commit_info'] = [
                        'hash' => $commitData[0] ?? null,
                        'message' => $commitData[1] ?? null,
                        'author' => $commitData[2] ?? null,
                        'date' => $commitData[3] ?? null,
                    ];
                }

                // Ветка
                $output = [];
                exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' branch --show-current 2>&1', $output, $status);
                $info['branch'] = $status === 0 && !empty($output) ? $output[0] : null;

                // Статус (есть ли незакоммиченные изменения)
                $output = [];
                exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' status --porcelain 2>&1', $output, $status);
                $info['has_uncommitted_changes'] = !empty($output);
            } else {
                $info['git_available'] = false;
            }

            // Информация о приложении
            $info['app_name'] = config('app.name');
            $info['app_env'] = config('app.env');
            $info['app_debug'] = config('app.debug');
            $info['laravel_version'] = app()->version();
            $info['php_version'] = PHP_VERSION;
            $info['server_time'] = date('Y-m-d H:i:s');
            $info['timezone'] = config('app.timezone');

            return response()->json([
                'success' => true,
                'version' => $info,
            ]);
        } catch (\Exception $e) {
            Log::error('Deploy: Version check failed', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

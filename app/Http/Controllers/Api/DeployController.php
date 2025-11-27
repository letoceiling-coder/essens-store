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
            $clientSecret = $request->header('Deploy-Secret');

            if ($secret !== $clientSecret) {
                Log::warning('Deploy: Invalid secret', [
                    'client_secret' => $clientSecret ? 'provided' : 'missing',
                ]);
                return response()->json(['error' => 'Invalid secret'], 403);
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

            // Проверка, что это git репозиторий
            $output = [];
            exec('cd ' . base_path() . ' && git rev-parse --git-dir 2>&1', $output, $status);
            if ($status !== 0) {
                // Проверяем, не является ли это проблемой с правами доступа
                $outputStr = implode(' ', $output);
                if (strpos($outputStr, 'dubious ownership') !== false) {
                    // Пытаемся добавить директорию в safe.directory
                    $projectPath = base_path();
                    exec('git config --global --add safe.directory ' . escapeshellarg($projectPath) . ' 2>&1', $safeOutput, $safeStatus);
                    Log::info('Deploy: Added directory to safe.directory', ['path' => $projectPath, 'output' => $safeOutput]);
                    
                    // Повторяем проверку
                    $output = [];
                    exec('cd ' . base_path() . ' && git rev-parse --git-dir 2>&1', $output, $status);
                }
                
                if ($status !== 0) {
                    Log::error('Deploy: Not a git repository', ['output' => $output, 'status' => $status]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Git репозиторий не найден или не инициализирован',
                        'error' => 'Git репозиторий не найден или не инициализирован',
                        'output' => $output,
                        'status' => $status,
                    ], 500);
                }
            }

            // Проверка наличия удаленного репозитория
            $output = [];
            exec('cd ' . base_path() . ' && git remote -v 2>&1', $output, $status);
            $allOutput['git_remote_check'] = ['status' => $status, 'output' => $output];
            if ($status !== 0 || empty($output)) {
                Log::warning('Deploy: No remote repository configured', ['output' => $output]);
            }

            // Обновление файлов из git
            $output = [];
            $projectPath = base_path();
            exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' pull origin master 2>&1', $output, $status);
            $allOutput['git_pull'] = ['status' => $status, 'output' => $output];
            if ($status !== 0) {
                Log::error('Deploy: Git pull failed', ['output' => $output, 'status' => $status]);
                return response()->json([
                    'success' => false,
                    'message' => 'Git pull failed',
                    'error' => 'Git pull failed',
                    'output' => $output,
                    'status' => $status,
                    'all_output' => $allOutput
                ], 500);
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
            exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' log -1 --pretty=format:"%H|%s|%an|%ad" --date=iso 2>&1', $output, $status);
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
            exec('cd ' . $projectPath . ' && git -c safe.directory=' . escapeshellarg($projectPath) . ' rev-parse --short HEAD 2>&1', $output, $status);
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

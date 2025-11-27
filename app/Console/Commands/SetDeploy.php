<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сборка проекта и отправка изменений на Git';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gitUrl = env('URL_GIT');
        $deploySecret = env('DEPLOY_SECRET');
        
        // Выполнение сборки npm
        $this->info('Запуск сборки npm...');
        exec('npm run build', $output, $status);
        if ($status !== 0) {
            $this->error('Ошибка сборки npm');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }
        $this->info('Сборка npm выполнена успешно');
        
        // Отправка изменений в git
        $this->info('Отправка изменений в Git...');
        $gitRemote = $gitUrl ?: 'origin master';
        
        // Проверяем, есть ли изменения для коммита
        exec('git status --porcelain', $statusOutput, $statusCode);
        $hasChanges = !empty($statusOutput);
        
        if ($hasChanges) {
            // Добавляем все изменения
            $this->line('Обнаружены изменения, добавляем в индекс...');
            exec('git add .', $output, $status);
            if ($status !== 0) {
                $this->error('Ошибка при добавлении файлов в git');
                $this->error(implode("\n", $output));
                return Command::FAILURE;
            }
            
            // Создаем коммит
            $this->line('Создание коммита...');
            exec('git commit -m "Deploy update"', $output, $status);
            if ($status !== 0) {
                $this->error('Ошибка при создании коммита');
                $this->error(implode("\n", $output));
                return Command::FAILURE;
            }
            $this->info('Коммит создан успешно');
        } else {
            $this->info('Нет изменений для коммита');
        }
        
        // Отправляем изменения на сервер (даже если не было новых коммитов, могут быть неотправленные)
        $this->line('Отправка изменений на удаленный репозиторий...');
        exec('git push ' . $gitRemote, $output, $status);
        if ($status !== 0) {
            $this->error('Ошибка отправки на git');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }
        $this->info('Отправка на Git выполнена успешно');
        
        // Отправка запроса на сервер для обновления проекта
        $serverUrl = env('SERVER_URL');
        if (!$serverUrl) {
            $this->warn('SERVER_URL не указан в .env, пропускаем обновление на сервере');
            return Command::SUCCESS;
        }
        
        if (!$deploySecret) {
            $this->warn('DEPLOY_SECRET не указан в .env, пропускаем обновление на сервере');
            return Command::SUCCESS;
        }
        
        $this->info('Отправка запроса на сервер для обновления проекта...');
        try {
            // Формируем URL правильно
            $deployUrl = rtrim($serverUrl, '/');
            // Если в URL уже есть /api/deploy, не добавляем его снова
            if (strpos($deployUrl, '/api/deploy') === false) {
                $deployUrl .= '/api/deploy';
            }
            
            $this->line('URL: ' . $deployUrl);
            $this->line('Используется секретный ключ: ' . (strlen($deploySecret) > 0 ? substr($deploySecret, 0, 4) . '...' : 'не указан'));
            
            $response = Http::withoutVerifying() // Отключаем проверку SSL сертификата
                ->timeout(300) // 5 минут таймаут для деплоя
                ->withHeaders([
                    'Deploy-Secret' => $deploySecret,
                    'Accept' => 'application/json',
                ])
                ->post($deployUrl);
            
            if ($response->successful()) {
                $this->info('Обновление на сервере выполнено успешно');
                $responseData = $response->json();
                
                if (isset($responseData['status'])) {
                    $this->line('Статус: ' . $responseData['status']);
                }
                
                // Выводим информацию о коммите
                if (isset($responseData['commit'])) {
                    $this->newLine();
                    $this->info('Информация о версии на сервере:');
                    $this->line('  Коммит: ' . $responseData['commit']);
                    
                    if (isset($responseData['commit_info'])) {
                        $commitInfo = $responseData['commit_info'];
                        if (isset($commitInfo['message'])) {
                            $this->line('  Сообщение: ' . $commitInfo['message']);
                        }
                        if (isset($commitInfo['author'])) {
                            $this->line('  Автор: ' . $commitInfo['author']);
                        }
                        if (isset($commitInfo['date'])) {
                            $this->line('  Дата: ' . $commitInfo['date']);
                        }
                    }
                    
                    if (isset($responseData['deployed_at'])) {
                        $this->line('  Время деплоя: ' . $responseData['deployed_at']);
                    }
                }
            } else {
                $this->error('Ошибка обновления на сервере');
                $this->error('HTTP Status: ' . $response->status());
                
                // Пытаемся получить JSON ответ
                $errorData = $response->json();
                if ($errorData) {
                    if (isset($errorData['error'])) {
                        $this->error('Ошибка: ' . $errorData['error']);
                    }
                    if (isset($errorData['message'])) {
                        $this->error('Сообщение: ' . $errorData['message']);
                    }
                    if (isset($errorData['output'])) {
                        $this->error('Вывод: ' . implode("\n", (array)$errorData['output']));
                    }
                    // Выводим весь ответ для отладки
                    $this->line('Полный ответ сервера:');
                    $this->line(json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    // Если не JSON, выводим тело ответа как есть
                    $body = $response->body();
                    $this->error('Тело ответа: ' . $body);
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Исключение при отправке запроса на сервер: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        $this->info('Сборка, отправка на Git и обновление на сервере выполнены успешно');
        return Command::SUCCESS;
    }
}

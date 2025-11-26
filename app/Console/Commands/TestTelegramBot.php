<?php

namespace App\Console\Commands;

use App\Services\ExtendedTelegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;

class TestTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test 
                            {--bot-id= : ID бота для тестирования}
                            {--chat-id= : ID чата для отправки тестового сообщения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование соединения с Telegram ботом';

    protected ExtendedTelegraph $telegraph;

    public function __construct(ExtendedTelegraph $telegraph)
    {
        parent::__construct();
        $this->telegraph = $telegraph;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Тестирование Telegram бота...');
        $this->newLine();

        // Выбор бота
        $botId = $this->option('bot-id');
        if (!$botId) {
            $bots = TelegraphBot::all();
            if ($bots->isEmpty()) {
                $this->error('❌ Боты не найдены. Создайте бота через админ-панель.');
                return Command::FAILURE;
            }

            $this->info('Доступные боты:');
            $botChoices = [];
            foreach ($bots as $bot) {
                $botChoices[$bot->id] = "{$bot->name} (ID: {$bot->id})";
                $this->line("  [{$bot->id}] {$bot->name}");
            }

            $botId = $this->ask('Выберите ID бота для тестирования');
            if (!isset($botChoices[$botId])) {
                $this->error('❌ Неверный ID бота');
                return Command::FAILURE;
            }
        }

        $bot = TelegraphBot::find($botId);
        if (!$bot) {
            $this->error("❌ Бот с ID {$botId} не найден");
            return Command::FAILURE;
        }

        $this->info("✅ Бот выбран: {$bot->name}");
        $this->newLine();

        // Тест 1: Получение информации о боте
        $this->info('📋 Тест 1: Получение информации о боте (getMe)...');
        try {
            $response = $this->telegraph->bot($bot)->getMe()->send();
            
            if ($response->successful()) {
                $botInfo = $response->json('result');
                $this->info('✅ Соединение успешно!');
                $this->line("   Имя бота: @{$botInfo['username']}");
                $this->line("   ID бота: {$botInfo['id']}");
                $this->line("   Имя: {$botInfo['first_name']}");
                if (isset($botInfo['can_join_groups'])) {
                    $this->line("   Может присоединяться к группам: " . ($botInfo['can_join_groups'] ? 'Да' : 'Нет'));
                }
                if (isset($botInfo['can_read_all_group_messages'])) {
                    $this->line("   Может читать все сообщения в группах: " . ($botInfo['can_read_all_group_messages'] ? 'Да' : 'Нет'));
                }
            } else {
                $this->error('❌ Ошибка при получении информации о боте');
                $this->error("   Описание: " . $response->json('description', 'Неизвестная ошибка'));
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Исключение при получении информации о боте');
            $this->error("   Ошибка: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->newLine();

        // Тест 2: Получение информации о webhook
        $this->info('🔗 Тест 2: Проверка webhook...');
        try {
            $response = $this->telegraph->bot($bot)->getWebhookInfo()->send();
            
            if ($response->successful()) {
                $webhookInfo = $response->json('result');
                if (!empty($webhookInfo['url'])) {
                    $this->info('✅ Webhook установлен');
                    $this->line("   URL: {$webhookInfo['url']}");
                    if (isset($webhookInfo['pending_update_count'])) {
                        $this->line("   Ожидающих обновлений: {$webhookInfo['pending_update_count']}");
                    }
                    if (isset($webhookInfo['last_error_date'])) {
                        $this->warn("   ⚠️  Последняя ошибка: " . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
                        if (isset($webhookInfo['last_error_message'])) {
                            $this->warn("   Сообщение: {$webhookInfo['last_error_message']}");
                        }
                    }
                } else {
                    $this->warn('⚠️  Webhook не установлен');
                }
            } else {
                $this->warn('⚠️  Не удалось получить информацию о webhook');
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Ошибка при проверке webhook: {$e->getMessage()}");
        }

        $this->newLine();

        // Тест 3: Получение обновлений
        $this->info('📥 Тест 3: Получение обновлений (getUpdates)...');
        try {
            $response = $this->telegraph->bot($bot)->getUpdates(limit: 1)->send();
            
            if ($response->successful()) {
                $updates = $response->json('result', []);
                $this->info('✅ Успешно получены обновления');
                $this->line("   Количество обновлений: " . count($updates));
                if (count($updates) > 0) {
                    $this->line("   Последнее обновление ID: {$updates[0]['update_id']}");
                }
            } else {
                $this->warn('⚠️  Не удалось получить обновления');
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Ошибка при получении обновлений: {$e->getMessage()}");
        }

        $this->newLine();

        // Тест 4: Отправка тестового сообщения (если указан chat_id)
        $chatId = $this->option('chat-id');
        if ($chatId) {
            $this->info("💬 Тест 4: Отправка тестового сообщения в чат {$chatId}...");
            try {
                $message = "✅ Тестовое сообщение от бота {$bot->name}\n" .
                          "Время: " . now()->format('Y-m-d H:i:s') . "\n" .
                          "Сервер: " . config('app.name');
                
                $response = $this->telegraph
                    ->bot($bot)
                    ->chat($chatId)
                    ->html($message)
                    ->send();
                
                if ($response->successful()) {
                    $this->info('✅ Тестовое сообщение успешно отправлено!');
                    $messageData = $response->json('result');
                    if (isset($messageData['message_id'])) {
                        $this->line("   ID сообщения: {$messageData['message_id']}");
                    }
                } else {
                    $this->error('❌ Ошибка при отправке сообщения');
                    $this->error("   Описание: " . $response->json('description', 'Неизвестная ошибка'));
                }
            } catch (\Exception $e) {
                $this->error('❌ Исключение при отправке сообщения');
                $this->error("   Ошибка: {$e->getMessage()}");
            }
        } else {
            $this->info('💡 Совет: Используйте --chat-id=CHAT_ID для отправки тестового сообщения');
            $this->line('   Пример: php artisan telegram:test --bot-id=1 --chat-id=123456789');
        }

        $this->newLine();
        $this->info('🎉 Тестирование завершено!');

        return Command::SUCCESS;
    }
}


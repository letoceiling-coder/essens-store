<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExtendedTelegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected ExtendedTelegraph $telegraph;

    public function __construct(ExtendedTelegraph $telegraph)
    {
        $this->telegraph = $telegraph;
    }

    /**
     * Обработка webhook от Telegram
     */
    public function handle(Request $request, string $token)
    {
        // Находим бота по токену
        $bot = TelegraphBot::where('token', $token)->first();

        if (!$bot) {
            Log::warning('Telegram webhook: Bot not found', ['token' => substr($token, 0, 10) . '...']);
            return response()->json(['ok' => false, 'error' => 'Bot not found'], 404);
        }

        $update = $request->all();
        $updateId = $update['update_id'] ?? null;

        // Логируем полученное обновление
        Log::info('Telegram webhook received', [
            'bot_id' => $bot->id,
            'update_id' => $updateId,
        ]);

        // Обрабатываем сообщение
        if (isset($update['message'])) {
            $this->handleMessage($bot, $update['message']);
        }

        // Обрабатываем callback query (нажатия на кнопки)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($bot, $update['callback_query']);
        }

        // Всегда возвращаем успешный ответ
        return response()->json(['ok' => true]);
    }

    /**
     * Обработка сообщения
     */
    protected function handleMessage(TelegraphBot $bot, array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;

        if (!$chatId || !$text) {
            return;
        }

        Log::info('Telegram message received', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        // Обработка команды /test_server
        // Команда может быть: /test_server или /test_server@botname или /test_server параметры
        $isTestServerCommand = false;
        if ($text === '/test_server') {
            $isTestServerCommand = true;
        } elseif (preg_match('/^\/test_server(@\w+)?(\s|$)/', $text)) {
            $isTestServerCommand = true;
        }

        if ($isTestServerCommand) {
            Log::info('Processing /test_server command', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'text' => $text,
            ]);

            try {
                $response = $this->telegraph
                    ->bot($bot)
                    ->chat($chatId)
                    ->html('✅ Связь настроена')
                    ->send();

                if ($response->successful()) {
                    Log::info('Test server command response sent successfully', [
                        'bot_id' => $bot->id,
                        'chat_id' => $chatId,
                        'response' => $response->json(),
                    ]);
                } else {
                    Log::error('Failed to send test server response', [
                        'bot_id' => $bot->id,
                        'chat_id' => $chatId,
                        'status' => $response->status(),
                        'error' => $response->json('description'),
                        'full_response' => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception while sending test server response', [
                    'bot_id' => $bot->id,
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } else {
            // Логируем другие сообщения для отладки
            Log::debug('Message received but not /test_server', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }
    }

    /**
     * Обработка callback query (нажатия на кнопки)
     */
    protected function handleCallbackQuery(TelegraphBot $bot, array $callbackQuery): void
    {
        $queryId = $callbackQuery['id'] ?? null;
        $data = $callbackQuery['data'] ?? null;
        $message = $callbackQuery['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;

        if (!$queryId || !$data) {
            return;
        }

        Log::info('Telegram callback query received', [
            'bot_id' => $bot->id,
            'query_id' => $queryId,
            'data' => $data,
        ]);

        // Отвечаем на callback query
        try {
            $this->telegraph
                ->bot($bot)
                ->answerCallbackQuery(
                    callbackQueryId: $queryId,
                    text: 'Обработано',
                    showAlert: false
                )
                ->send();
        } catch (\Exception $e) {
            Log::error('Exception while answering callback query', [
                'bot_id' => $bot->id,
                'query_id' => $queryId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


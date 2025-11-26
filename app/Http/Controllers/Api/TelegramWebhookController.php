<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
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

        // Логируем полученное обновление
        Log::info('Telegram webhook received', [
            'bot_id' => $bot->id,
            'update_id' => $request->input('update_id'),
        ]);

        // Здесь можно добавить обработку обновлений
        // Например, через очередь или напрямую
        
        // Пока просто возвращаем успешный ответ
        return response()->json(['ok' => true]);
    }
}


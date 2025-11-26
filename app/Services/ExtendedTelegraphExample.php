<?php

namespace App\Services;

use App\Services\ExtendedTelegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Carbon;

/**
 * Примеры использования ExtendedTelegraph
 * 
 * Этот файл содержит примеры использования всех методов ExtendedTelegraph
 */
class ExtendedTelegraphExample
{
    /**
     * Пример: Отправка сообщения
     */
    public function sendMessage()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Используем методы из базового Telegraph через делегирование
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->message('Привет, мир!')
            ->send();
        
        return $response;
    }

    /**
     * Пример: Пересылка сообщения
     */
    public function forwardMessage()
    {
        $telegraph = new ExtendedTelegraph();
        
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('DESTINATION_CHAT_ID')
            ->forwardMessage(
                fromChatId: 'SOURCE_CHAT_ID',
                messageId: 123,
                disableNotification: false,
                protectContent: false
            )
            ->send();
        
        return $response;
    }

    /**
     * Пример: Копирование сообщения
     */
    public function copyMessage()
    {
        $telegraph = new ExtendedTelegraph();
        
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('DESTINATION_CHAT_ID')
            ->copyMessage(
                fromChatId: 'SOURCE_CHAT_ID',
                messageId: 123,
                caption: 'Скопированное сообщение',
                parseMode: 'HTML'
            )
            ->send();
        
        return $response;
    }

    /**
     * Пример: Редактирование местоположения в реальном времени
     */
    public function editMessageLiveLocation()
    {
        $telegraph = new ExtendedTelegraph();
        
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->editMessageLiveLocation(
                messageId: 123,
                latitude: 55.7558,
                longitude: 37.6173,
                horizontalAccuracy: 10.5,
                heading: 90
            )
            ->send();
        
        return $response;
    }

    /**
     * Пример: Получение информации о файле
     */
    public function getFile()
    {
        $telegraph = new ExtendedTelegraph();
        
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->getFile('FILE_ID')
            ->send();
        
        // Получить URL для скачивания
        if ($response->telegraph()->successful()) {
            $filePath = $response->json('result.file_path');
            $downloadUrl = $telegraph->getFileDownloadUrl($filePath);
        }
        
        return $response;
    }

    /**
     * Пример: Работа с чатами
     */
    public function chatManagement()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Получить информацию о чате
        $chatInfo = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->chatInfo()
            ->send();
        
        // Получить администраторов чата
        $admins = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->getChatAdministrators()
            ->send();
        
        // Получить количество участников
        $memberCount = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->getChatMemberCount()
            ->send();
        
        // Забанить пользователя
        $banResult = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->banChatMember(
                userId: 'USER_ID',
                untilDate: Carbon::now()->addDays(7),
                revokeMessages: true
            )
            ->send();
        
        return [
            'chat_info' => $chatInfo,
            'admins' => $admins,
            'member_count' => $memberCount,
            'ban_result' => $banResult,
        ];
    }

    /**
     * Пример: Работа с опросами
     */
    public function pollManagement()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Создать опрос (используя базовый Telegraph)
        $poll = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->poll('Какой ваш любимый язык?')
            ->option('PHP')
            ->option('JavaScript')
            ->option('Python')
            ->send();
        
        // Остановить опрос
        $stopPoll = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->stopPoll(messageId: $poll->json('result.message_id'))
            ->send();
        
        return $stopPoll;
    }

    /**
     * Пример: Работа с ботом
     */
    public function botManagement()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Получить информацию о боте
        $botInfo = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->getMe()
            ->send();
        
        // Установить команды бота
        $setCommands = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->setMyCommands([
                ['command' => 'start', 'description' => 'Начать работу с ботом'],
                ['command' => 'help', 'description' => 'Помощь'],
            ])
            ->send();
        
        // Установить описание бота
        $setDescription = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->setMyDescription('Это мой бот')
            ->send();
        
        return [
            'bot_info' => $botInfo,
            'commands' => $setCommands,
            'description' => $setDescription,
        ];
    }

    /**
     * Пример: Работа с webhook
     */
    public function webhookManagement()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Установить webhook
        $setWebhook = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->setWebhook(
                url: 'https://example.com/webhook',
                secretToken: 'secret_token_123',
                maxConnections: 40,
                allowedUpdates: ['message', 'callback_query']
            )
            ->send();
        
        // Получить информацию о webhook
        $webhookInfo = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->getWebhookInfo()
            ->send();
        
        // Удалить webhook
        $deleteWebhook = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->deleteWebhook(dropPendingUpdates: true)
            ->send();
        
        return [
            'set_webhook' => $setWebhook,
            'webhook_info' => $webhookInfo,
            'delete_webhook' => $deleteWebhook,
        ];
    }

    /**
     * Пример: Работа с форумами
     */
    public function forumManagement()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Создать топик форума
        $createTopic = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->createForumTopic('Новый топик')
            ->send();
        
        // Редактировать общий топик
        $editGeneral = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->editGeneralForumTopic('Новое имя общего топика')
            ->send();
        
        // Закрыть общий топик
        $closeGeneral = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->closeGeneralForumTopic()
            ->send();
        
        return [
            'create_topic' => $createTopic,
            'edit_general' => $editGeneral,
            'close_general' => $closeGeneral,
        ];
    }

    /**
     * Пример: Использование с моделями TelegraphBot и TelegraphChat
     */
    public function useWithModels()
    {
        // Получить бота из базы данных
        $bot = TelegraphBot::first();
        $chat = TelegraphChat::first();
        
        $telegraph = new ExtendedTelegraph();
        
        $response = $telegraph
            ->bot($bot)
            ->chat($chat)
            ->message('Сообщение через модели')
            ->send();
        
        return $response;
    }

    /**
     * Пример: Комбинирование методов
     */
    public function combinedUsage()
    {
        $telegraph = new ExtendedTelegraph();
        
        // Отправить сообщение с клавиатурой
        $response = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('CHAT_ID')
            ->message('Выберите действие:')
            ->keyboard(function ($keyboard) {
                $keyboard->button('Кнопка 1')->action('action1');
                $keyboard->button('Кнопка 2')->action('action2');
            })
            ->send();
        
        // Затем переслать это сообщение в другой чат
        $forwarded = $telegraph
            ->bot('YOUR_BOT_TOKEN')
            ->chat('ANOTHER_CHAT_ID')
            ->forwardMessage(
                fromChatId: 'CHAT_ID',
                messageId: $response->json('result.message_id')
            )
            ->send();
        
        return $forwarded;
    }
}


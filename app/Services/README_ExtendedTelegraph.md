# ExtendedTelegraph - Расширенный класс для работы с Telegram Bot API

## Описание

`ExtendedTelegraph` - это класс-обертка над пакетом `defstudio/telegraph`, который добавляет все недостающие методы из официальной документации Telegram Bot API.

## Установка

Класс уже создан и готов к использованию. Не требуется дополнительная установка.

## Использование

### Базовое использование

```php
use App\Services\ExtendedTelegraph;

$telegraph = new ExtendedTelegraph();

$response = $telegraph
    ->bot('YOUR_BOT_TOKEN')
    ->chat('CHAT_ID')
    ->message('Привет, мир!')
    ->send();
```

### Использование с моделями

```php
use App\Services\ExtendedTelegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

$bot = TelegraphBot::first();
$chat = TelegraphChat::first();

$telegraph = new ExtendedTelegraph();
$response = $telegraph
    ->bot($bot)
    ->chat($chat)
    ->message('Сообщение')
    ->send();
```

### Использование через фасад

```php
use App\Facades\ExtendedTelegraphFacade as ExtendedTelegraph;

$response = ExtendedTelegraph::bot('YOUR_BOT_TOKEN')
    ->chat('CHAT_ID')
    ->message('Привет!')
    ->send();
```

## Доступные методы

### Методы для работы с сообщениями

#### `forwardMessage()`
Переслать сообщение из другого чата

```php
$telegraph->forwardMessage(
    fromChatId: 'SOURCE_CHAT_ID',
    messageId: 123,
    disableNotification: false,
    protectContent: false
)->send();
```

#### `copyMessage()`
Скопировать сообщение в другой чат

```php
$telegraph->copyMessage(
    fromChatId: 'SOURCE_CHAT_ID',
    messageId: 123,
    caption: 'Скопированное сообщение',
    parseMode: 'HTML'
)->send();
```

#### `editMessageLiveLocation()`
Редактировать местоположение в реальном времени

```php
$telegraph->editMessageLiveLocation(
    messageId: 123,
    latitude: 55.7558,
    longitude: 37.6173,
    horizontalAccuracy: 10.5
)->send();
```

#### `stopMessageLiveLocation()`
Остановить обновление местоположения

```php
$telegraph->stopMessageLiveLocation(messageId: 123)->send();
```

#### `deleteMessages()`
Удалить несколько сообщений одновременно

```php
$telegraph->deleteMessages([123, 124, 125])->send();
```

### Методы для работы с чатами

#### `getChatAdministrators()`
Получить список администраторов чата

```php
$telegraph->getChatAdministrators()->send();
```

#### `getChatMemberCount()`
Получить количество участников чата

```php
$telegraph->getChatMemberCount()->send();
```

#### `getChatMember()`
Получить информацию об участнике чата

```php
$telegraph->getChatMember(userId: 'USER_ID')->send();
```

#### `setChatStickerSet()`
Установить набор стикеров для чата

```php
$telegraph->setChatStickerSet('sticker_set_name')->send();
```

#### `deleteChatStickerSet()`
Удалить набор стикеров из чата

```php
$telegraph->deleteChatStickerSet()->send();
```

#### `banChatMember()`
Забанить пользователя в чате

```php
$telegraph->banChatMember(
    userId: 'USER_ID',
    untilDate: Carbon::now()->addDays(7),
    revokeMessages: true
)->send();
```

#### `unbanChatMember()`
Разбанить пользователя в чате

```php
$telegraph->unbanChatMember(userId: 'USER_ID')->send();
```

#### `promoteChatMember()`
Повысить пользователя до администратора

```php
$telegraph->promoteChatMember(
    userId: 'USER_ID',
    canManageChat: true,
    canDeleteMessages: true
)->send();
```

#### `restrictChatMember()`
Ограничить права пользователя

```php
$telegraph->restrictChatMember(
    userId: 'USER_ID',
    permissions: [
        'can_send_messages' => true,
        'can_send_media_messages' => false,
    ],
    untilDate: Carbon::now()->addDays(1)
)->send();
```

### Методы для работы с файлами

#### `getFile()`
Получить информацию о файле

```php
$response = $telegraph->getFile('FILE_ID')->send();
$filePath = $response->json('result.file_path');
```

#### `getFileDownloadUrl()`
Получить URL для скачивания файла

```php
$downloadUrl = $telegraph->getFileDownloadUrl($filePath);
```

### Методы для работы с опросами

#### `stopPoll()`
Остановить опрос

```php
$telegraph->stopPoll(messageId: 123)->send();
```

### Методы для работы с ботом

#### `getMe()`
Получить информацию о боте

```php
$telegraph->getMe()->send();
```

#### `getUpdates()`
Получить обновления бота

```php
$telegraph->getUpdates(
    offset: 0,
    limit: 100,
    timeout: 30
)->send();
```

#### `setMyCommands()`
Установить команды бота

```php
$telegraph->setMyCommands([
    ['command' => 'start', 'description' => 'Начать'],
    ['command' => 'help', 'description' => 'Помощь'],
])->send();
```

#### `getMyCommands()`
Получить команды бота

```php
$telegraph->getMyCommands()->send();
```

#### `deleteMyCommands()`
Удалить команды бота

```php
$telegraph->deleteMyCommands()->send();
```

#### `setMyDescription()`
Установить описание бота

```php
$telegraph->setMyDescription('Описание бота')->send();
```

#### `getMyDescription()`
Получить описание бота

```php
$telegraph->getMyDescription()->send();
```

#### `setMyShortDescription()`
Установить краткое описание бота

```php
$telegraph->setMyShortDescription('Краткое описание')->send();
```

#### `getMyShortDescription()`
Получить краткое описание бота

```php
$telegraph->getMyShortDescription()->send();
```

#### `setMyName()`
Установить имя бота

```php
$telegraph->setMyName('Имя бота')->send();
```

#### `getMyName()`
Получить имя бота

```php
$telegraph->getMyName()->send();
```

#### `setMyPhoto()`
Установить фото бота

```php
$telegraph->setMyPhoto('/path/to/photo.jpg')->send();
```

#### `deleteMyPhoto()`
Удалить фото бота

```php
$telegraph->deleteMyPhoto()->send();
```

#### `setMyMenuButton()`
Установить меню бота

```php
$telegraph->setMyMenuButton([
    'type' => 'commands',
])->send();
```

#### `getMyMenuButton()`
Получить меню бота

```php
$telegraph->getMyMenuButton()->send();
```

### Методы для работы с webhook

#### `setWebhook()`
Установить webhook

```php
$telegraph->setWebhook(
    url: 'https://example.com/webhook',
    secretToken: 'secret_token',
    maxConnections: 40
)->send();
```

#### `deleteWebhook()`
Удалить webhook

```php
$telegraph->deleteWebhook(dropPendingUpdates: true)->send();
```

#### `getWebhookInfo()`
Получить информацию о webhook

```php
$telegraph->getWebhookInfo()->send();
```

### Методы для работы с форумами

#### `editGeneralForumTopic()`
Редактировать общий топик форума

```php
$telegraph->editGeneralForumTopic('Новое имя')->send();
```

#### `closeGeneralForumTopic()`
Закрыть общий топик форума

```php
$telegraph->closeGeneralForumTopic()->send();
```

#### `reopenGeneralForumTopic()`
Открыть общий топик форума

```php
$telegraph->reopenGeneralForumTopic()->send();
```

#### `hideGeneralForumTopic()`
Скрыть общий топик форума

```php
$telegraph->hideGeneralForumTopic()->send();
```

#### `unhideGeneralForumTopic()`
Показать общий топик форума

```php
$telegraph->unhideGeneralForumTopic()->send();
```

### Методы для работы с видеозвонками

#### `createVideoChatInviteLink()`
Создать приглашение для видеозвонка

```php
$telegraph->createVideoChatInviteLink(
    name: 'Видеозвонок',
    expiresDate: Carbon::now()->addDays(1),
    participantLimit: 10
)->send();
```

#### `editVideoChatInviteLink()`
Редактировать приглашение для видеозвонка

```php
$telegraph->editVideoChatInviteLink(
    inviteLink: 'INVITE_LINK',
    name: 'Новое имя'
)->send();
```

#### `revokeVideoChatInviteLink()`
Отозвать приглашение для видеозвонка

```php
$telegraph->revokeVideoChatInviteLink('INVITE_LINK')->send();
```

### Методы для работы с платежами

#### `createInvoiceLink()`
Создать ссылку на инвойс

```php
$telegraph->createInvoiceLink(
    title: 'Товар',
    description: 'Описание товара',
    payload: 'unique_payload',
    providerToken: 'PROVIDER_TOKEN',
    currency: 'RUB',
    prices: [
        ['label' => 'Товар', 'amount' => 10000]
    ]
)->send();
```

#### `answerCallbackQuery()`
Ответить на callback query

```php
$telegraph->answerCallbackQuery(
    callbackQueryId: 'CALLBACK_QUERY_ID',
    text: 'Ответ',
    showAlert: false
)->send();
```

## Делегирование методов

Все методы из базового класса `Telegraph` доступны через делегирование:

```php
$telegraph
    ->bot('YOUR_BOT_TOKEN')
    ->chat('CHAT_ID')
    ->message('Сообщение')
    ->keyboard(function ($keyboard) {
        $keyboard->button('Кнопка')->action('action');
    })
    ->send();
```

## Обработка ответов

Все методы возвращают `TelegraphResponse`, который можно использовать для проверки результата:

```php
$response = $telegraph
    ->bot('YOUR_BOT_TOKEN')
    ->chat('CHAT_ID')
    ->message('Сообщение')
    ->send();

if ($response->successful()) {
    $messageId = $response->json('result.message_id');
    // Обработка успешного ответа
} else {
    $error = $response->json('description');
    // Обработка ошибки
}
```

## Отправка в очередь

Можно отправить запрос в очередь:

```php
$telegraph
    ->bot('YOUR_BOT_TOKEN')
    ->chat('CHAT_ID')
    ->message('Сообщение')
    ->dispatch('telegram');
```

## Дополнительная информация

- Официальная документация Telegram Bot API: https://core.telegram.org/bots/api
- Документация defstudio/telegraph: https://docs.defstudio.it/telegraph
- Примеры использования: см. `app/Services/ExtendedTelegraphExample.php`


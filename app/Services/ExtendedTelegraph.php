<?php

namespace App\Services;

use DefStudio\Telegraph\Telegraph;
use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Carbon;

/**
 * Расширенный класс для работы с Telegram Bot API
 * Добавляет недостающие методы из официальной документации Telegram Bot API
 * 
 * @see https://core.telegram.org/bots/api
 */
class ExtendedTelegraph
{
    protected Telegraph $telegraph;

    public function __construct(?Telegraph $telegraph = null)
    {
        $this->telegraph = $telegraph ?? new Telegraph();
    }

    /**
     * Получить базовый экземпляр Telegraph
     */
    public function getTelegraph(): Telegraph
    {
        return $this->telegraph;
    }

    /**
     * Установить бота
     */
    public function bot(TelegraphBot|string $bot): self
    {
        $this->telegraph = $this->telegraph->bot($bot);
        return $this;
    }

    /**
     * Установить чат
     */
    public function chat(TelegraphChat|string $chat): self
    {
        $this->telegraph = $this->telegraph->chat($chat);
        return $this;
    }

    /**
     * Отправить запрос
     */
    public function send(): TelegraphResponse
    {
        return $this->telegraph->send();
    }

    /**
     * Отправить запрос в очередь
     */
    public function dispatch(?string $queue = null)
    {
        return $this->telegraph->dispatch($queue);
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С СООБЩЕНИЯМИ
    // ============================================

    /**
     * Переслать сообщение из другого чата
     * 
     * @param int|string $fromChatId ID чата, откуда пересылать
     * @param int $messageId ID сообщения для пересылки
     * @param bool $disableNotification Отключить уведомление
     * @param bool $protectContent Защитить содержимое от пересылки и сохранения
     * @param int|null $messageThreadId ID топика в форуме
     * 
     * @see https://core.telegram.org/bots/api#forwardmessage
     */
    public function forwardMessage(
        int|string $fromChatId,
        int $messageId,
        bool $disableNotification = false,
        bool $protectContent = false,
        ?int $messageThreadId = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_FORWARD_MESSAGE)
            ->withData('from_chat_id', $fromChatId)
            ->withData('message_id', $messageId)
            ->withData('disable_notification', $disableNotification)
            ->withData('protect_content', $protectContent);

        if ($messageThreadId !== null) {
            $this->telegraph = $this->telegraph->withData('message_thread_id', $messageThreadId);
        }

        return $this;
    }

    /**
     * Копировать сообщение в другой чат
     * 
     * @param int|string $fromChatId ID чата, откуда копировать
     * @param int $messageId ID сообщения для копирования
     * @param string|null $caption Подпись к сообщению
     * @param string|null $parseMode Режим парсинга (html, markdown, MarkdownV2)
     * @param bool $disableNotification Отключить уведомление
     * @param bool $protectContent Защитить содержимое
     * @param int|null $replyToMessageId ID сообщения для ответа
     * @param bool $allowSendingWithoutReply Отправить без ответа, если сообщение не найдено
     * 
     * @see https://core.telegram.org/bots/api#copymessage
     */
    public function copyMessage(
        int|string $fromChatId,
        int $messageId,
        ?string $caption = null,
        ?string $parseMode = null,
        bool $disableNotification = false,
        bool $protectContent = false,
        ?int $replyToMessageId = null,
        bool $allowSendingWithoutReply = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_COPY_MESSAGE)
            ->withData('from_chat_id', $fromChatId)
            ->withData('message_id', $messageId)
            ->withData('disable_notification', $disableNotification)
            ->withData('protect_content', $protectContent)
            ->withData('allow_sending_without_reply', $allowSendingWithoutReply);

        if ($caption !== null) {
            $this->telegraph = $this->telegraph->withData('caption', $caption);
        }

        if ($parseMode !== null) {
            $this->telegraph = $this->telegraph->withData('parse_mode', $parseMode);
        }

        if ($replyToMessageId !== null) {
            $this->telegraph = $this->telegraph->withData('reply_to_message_id', $replyToMessageId);
        }

        return $this;
    }

    /**
     * Редактировать местоположение в реальном времени
     * 
     * @param int $messageId ID сообщения для редактирования
     * @param float $latitude Широта
     * @param float $longitude Долгота
     * @param float|null $horizontalAccuracy Точность в метрах
     * @param int|null $heading Направление в градусах
     * @param int|null $proximityAlertRadius Радиус оповещения о приближении
     * @param array|null $replyMarkup Inline клавиатура
     * 
     * @see https://core.telegram.org/bots/api#editmessagelivelocation
     */
    public function editMessageLiveLocation(
        int $messageId,
        float $latitude,
        float $longitude,
        ?float $horizontalAccuracy = null,
        ?int $heading = null,
        ?int $proximityAlertRadius = null,
        ?array $replyMarkup = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('editMessageLiveLocation')
            ->withData('message_id', $messageId)
            ->withData('latitude', $latitude)
            ->withData('longitude', $longitude);

        if ($horizontalAccuracy !== null) {
            $this->telegraph = $this->telegraph->withData('horizontal_accuracy', $horizontalAccuracy);
        }

        if ($heading !== null) {
            $this->telegraph = $this->telegraph->withData('heading', $heading);
        }

        if ($proximityAlertRadius !== null) {
            $this->telegraph = $this->telegraph->withData('proximity_alert_radius', $proximityAlertRadius);
        }

        if ($replyMarkup !== null) {
            $this->telegraph = $this->telegraph->withData('reply_markup', $replyMarkup);
        }

        return $this;
    }

    /**
     * Остановить обновление местоположения в реальном времени
     * 
     * @param int $messageId ID сообщения
     * @param array|null $replyMarkup Inline клавиатура
     * 
     * @see https://core.telegram.org/bots/api#stopmessagelivelocation
     */
    public function stopMessageLiveLocation(
        int $messageId,
        ?array $replyMarkup = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('stopMessageLiveLocation')
            ->withData('message_id', $messageId);

        if ($replyMarkup !== null) {
            $this->telegraph = $this->telegraph->withData('reply_markup', $replyMarkup);
        }

        return $this;
    }

    /**
     * Редактировать только клавиатуру сообщения
     * 
     * @param int $messageId ID сообщения
     * @param array|null $replyMarkup Новая клавиатура
     * 
     * @see https://core.telegram.org/bots/api#editmessagereplymarkup
     */
    public function editMessageReplyMarkup(
        int $messageId,
        ?array $replyMarkup = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_REPLACE_KEYBOARD)
            ->withData('message_id', $messageId);

        if ($replyMarkup !== null) {
            $this->telegraph = $this->telegraph->withData('reply_markup', $replyMarkup);
        }

        return $this;
    }

    /**
     * Удалить несколько сообщений одновременно
     * 
     * @param array $messageIds Массив ID сообщений для удаления
     * 
     * @see https://core.telegram.org/bots/api#deletemessages
     */
    public function deleteMessages(array $messageIds): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_DELETE_MESSAGES)
            ->withData('message_ids', $messageIds);

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ЧАТАМИ
    // ============================================

    /**
     * Установить набор стикеров для чата
     * 
     * @param string $stickerSetName Имя набора стикеров
     * 
     * @see https://core.telegram.org/bots/api#setchatstickerset
     */
    public function setChatStickerSet(string $stickerSetName): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setChatStickerSet')
            ->withData('sticker_set_name', $stickerSetName);

        return $this;
    }

    /**
     * Удалить набор стикеров из чата
     * 
     * @see https://core.telegram.org/bots/api#deletechatstickerset
     */
    public function deleteChatStickerSet(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('deleteChatStickerSet');

        return $this;
    }

    /**
     * Получить список администраторов чата
     * 
     * @see https://core.telegram.org/bots/api#getchatadministrators
     */
    public function getChatAdministrators(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_CHAT_ADMINISTRATORS);

        return $this;
    }

    /**
     * Получить количество участников чата
     * 
     * @see https://core.telegram.org/bots/api#getchatmembercount
     */
    public function getChatMemberCount(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_CHAT_MEMBER_COUNT);

        return $this;
    }

    /**
     * Получить информацию об участнике чата
     * 
     * @param string|int $userId ID пользователя
     * 
     * @see https://core.telegram.org/bots/api#getchatmember
     */
    public function getChatMember(string|int $userId): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_CHAT_MEMBER)
            ->withData('user_id', $userId);

        return $this;
    }

    /**
     * Установить права администратора для пользователя
     * 
     * @param string|int $userId ID пользователя
     * @param bool $isAnonymous Анонимный администратор
     * @param bool $canManageChat Может управлять чатом
     * @param bool $canDeleteMessages Может удалять сообщения
     * @param bool $canManageVideoChats Может управлять видеозвонками
     * @param bool $canRestrictMembers Может ограничивать участников
     * @param bool $canPromoteMembers Может повышать участников
     * @param bool $canChangeInfo Может изменять информацию
     * @param bool $canInviteUsers Может приглашать пользователей
     * @param bool $canPostMessages Может публиковать сообщения (только для каналов)
     * @param bool $canEditMessages Может редактировать сообщения (только для каналов)
     * @param bool $canPinMessages Может закреплять сообщения
     * @param bool $canPostStories Может публиковать истории (только для каналов)
     * @param bool $canEditStories Может редактировать истории (только для каналов)
     * @param bool $canDeleteStories Может удалять истории (только для каналов)
     * @param bool $canManageTopics Может управлять топиками (только для форумов)
     * 
     * @see https://core.telegram.org/bots/api#promotechatmember
     */
    public function promoteChatMember(
        string|int $userId,
        bool $isAnonymous = false,
        bool $canManageChat = false,
        bool $canDeleteMessages = false,
        bool $canManageVideoChats = false,
        bool $canRestrictMembers = false,
        bool $canPromoteMembers = false,
        bool $canChangeInfo = false,
        bool $canInviteUsers = false,
        bool $canPostMessages = false,
        bool $canEditMessages = false,
        bool $canPinMessages = false,
        bool $canPostStories = false,
        bool $canEditStories = false,
        bool $canDeleteStories = false,
        bool $canManageTopics = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_PROMOTE_CHAT_MEMBER)
            ->withData('user_id', $userId)
            ->withData('is_anonymous', $isAnonymous)
            ->withData('can_manage_chat', $canManageChat)
            ->withData('can_delete_messages', $canDeleteMessages)
            ->withData('can_manage_video_chats', $canManageVideoChats)
            ->withData('can_restrict_members', $canRestrictMembers)
            ->withData('can_promote_members', $canPromoteMembers)
            ->withData('can_change_info', $canChangeInfo)
            ->withData('can_invite_users', $canInviteUsers)
            ->withData('can_post_messages', $canPostMessages)
            ->withData('can_edit_messages', $canEditMessages)
            ->withData('can_pin_messages', $canPinMessages)
            ->withData('can_post_stories', $canPostStories)
            ->withData('can_edit_stories', $canEditStories)
            ->withData('can_delete_stories', $canDeleteStories)
            ->withData('can_manage_topics', $canManageTopics);

        return $this;
    }

    /**
     * Установить права пользователя в чате
     * 
     * @param string|int $userId ID пользователя
     * @param array $permissions Массив прав
     * @param Carbon|null $untilDate До какой даты действуют ограничения
     * @param bool $useIndependentChatPermissions Использовать независимые права для каналов
     * 
     * @see https://core.telegram.org/bots/api#restrictchatmember
     */
    public function restrictChatMember(
        string|int $userId,
        array $permissions,
        ?Carbon $untilDate = null,
        bool $useIndependentChatPermissions = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_RESTRICT_CHAT_MEMBER)
            ->withData('user_id', $userId)
            ->withData('permissions', $permissions)
            ->withData('use_independent_chat_permissions', $useIndependentChatPermissions);

        if ($untilDate !== null) {
            $this->telegraph = $this->telegraph->withData('until_date', $untilDate->timestamp);
        }

        return $this;
    }

    /**
     * Забанить пользователя в чате
     * 
     * @param string|int $userId ID пользователя
     * @param Carbon|null $untilDate До какой даты забанен
     * @param bool $revokeMessages Удалить все сообщения пользователя
     * 
     * @see https://core.telegram.org/bots/api#banchatmember
     */
    public function banChatMember(
        string|int $userId,
        ?Carbon $untilDate = null,
        bool $revokeMessages = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_BAN_CHAT_MEMBER)
            ->withData('user_id', $userId)
            ->withData('revoke_messages', $revokeMessages);

        if ($untilDate !== null) {
            $this->telegraph = $this->telegraph->withData('until_date', $untilDate->timestamp);
        }

        return $this;
    }

    /**
     * Разбанить пользователя в чате
     * 
     * @param string|int $userId ID пользователя
     * @param bool $onlyIfBanned Разбанить только если забанен
     * 
     * @see https://core.telegram.org/bots/api#unbanchatmember
     */
    public function unbanChatMember(
        string|int $userId,
        bool $onlyIfBanned = true
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_UNBAN_CHAT_MEMBER)
            ->withData('user_id', $userId)
            ->withData('only_if_banned', $onlyIfBanned);

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ФАЙЛАМИ
    // ============================================

    /**
     * Получить информацию о файле
     * 
     * @param string $fileId ID файла
     * 
     * @see https://core.telegram.org/bots/api#getfile
     */
    public function getFile(string $fileId): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_FILE)
            ->withData('file_id', $fileId);

        return $this;
    }

    /**
     * Скачать файл по file_path
     * 
     * @param string $filePath Путь к файлу от Telegram
     * @return string URL для скачивания файла
     */
    public function getFileDownloadUrl(string $filePath): string
    {
        // Используем публичный метод getFilesUrl из Telegraph
        $baseUrl = $this->telegraph->getFilesUrl();
        return rtrim($baseUrl, '/') . '/' . ltrim($filePath, '/');
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ОПРОСАМИ
    // ============================================

    /**
     * Остановить опрос
     * 
     * @param int $messageId ID сообщения с опросом
     * @param array|null $replyMarkup Inline клавиатура
     * 
     * @see https://core.telegram.org/bots/api#stoppoll
     */
    public function stopPoll(
        int $messageId,
        ?array $replyMarkup = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('stopPoll')
            ->withData('message_id', $messageId);

        if ($replyMarkup !== null) {
            $this->telegraph = $this->telegraph->withData('reply_markup', $replyMarkup);
        }

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ИНЛАЙН-ЗАПРОСАМИ
    // ============================================

    /**
     * Ответить на инлайн-запрос
     * 
     * @param string $inlineQueryId ID инлайн-запроса
     * @param array $results Массив результатов
     * @param int|null $cacheTime Время кеширования в секундах
     * @param bool $isPersonal Персональные результаты
     * @param string|null $nextOffset Смещение для следующего запроса
     * @param string|null $switchPmText Текст для кнопки переключения в PM
     * @param string|null $switchPmParameter Параметр для кнопки переключения в PM
     * 
     * @see https://core.telegram.org/bots/api#answerinlinequery
     */
    public function answerInlineQuery(
        string $inlineQueryId,
        array $results,
        ?int $cacheTime = null,
        bool $isPersonal = false,
        ?string $nextOffset = null,
        ?string $switchPmText = null,
        ?string $switchPmParameter = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_ANSWER_INLINE_QUERY)
            ->withData('inline_query_id', $inlineQueryId)
            ->withData('results', $results)
            ->withData('is_personal', $isPersonal);

        if ($cacheTime !== null) {
            $this->telegraph = $this->telegraph->withData('cache_time', $cacheTime);
        }

        if ($nextOffset !== null) {
            $this->telegraph = $this->telegraph->withData('next_offset', $nextOffset);
        }

        if ($switchPmText !== null) {
            $this->telegraph = $this->telegraph->withData('switch_pm_text', $switchPmText);
        }

        if ($switchPmParameter !== null) {
            $this->telegraph = $this->telegraph->withData('switch_pm_parameter', $switchPmParameter);
        }

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С БОТОМ
    // ============================================

    /**
     * Получить информацию о боте
     * 
     * @see https://core.telegram.org/bots/api#getme
     */
    public function getMe(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_BOT_INFO);

        return $this;
    }

    /**
     * Получить обновления бота
     * 
     * @param int|null $offset Смещение
     * @param int|null $limit Лимит обновлений
     * @param int|null $timeout Таймаут в секундах
     * @param array|null $allowedUpdates Разрешенные типы обновлений
     * 
     * @see https://core.telegram.org/bots/api#getupdates
     */
    public function getUpdates(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        ?array $allowedUpdates = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_BOT_UPDATES);

        if ($offset !== null) {
            $this->telegraph = $this->telegraph->withData('offset', $offset);
        }

        if ($limit !== null) {
            $this->telegraph = $this->telegraph->withData('limit', $limit);
        }

        if ($timeout !== null) {
            $this->telegraph = $this->telegraph->withData('timeout', $timeout);
        }

        if ($allowedUpdates !== null) {
            $this->telegraph = $this->telegraph->withData('allowed_updates', $allowedUpdates);
        }

        return $this;
    }

    /**
     * Установить команды бота
     * 
     * @param array $commands Массив команд
     * @param array|null $scope Область действия команд
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#setmycommands
     */
    public function setMyCommands(
        array $commands,
        ?array $scope = null,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_REGISTER_BOT_COMMANDS)
            ->withData('commands', $commands);

        if ($scope !== null) {
            $this->telegraph = $this->telegraph->withData('scope', $scope);
        }

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Получить команды бота
     * 
     * @param array|null $scope Область действия команд
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#getmycommands
     */
    public function getMyCommands(
        ?array $scope = null,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_REGISTERED_BOT_COMMANDS);

        if ($scope !== null) {
            $this->telegraph = $this->telegraph->withData('scope', $scope);
        }

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Удалить команды бота
     * 
     * @param array|null $scope Область действия команд
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#deletemycommands
     */
    public function deleteMyCommands(
        ?array $scope = null,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_UNREGISTER_BOT_COMMANDS);

        if ($scope !== null) {
            $this->telegraph = $this->telegraph->withData('scope', $scope);
        }

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Установить описание бота
     * 
     * @param string $description Описание
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#setmydescription
     */
    public function setMyDescription(
        string $description,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setMyDescription')
            ->withData('description', $description);

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Получить описание бота
     * 
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#getmydescription
     */
    public function getMyDescription(?string $languageCode = null): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('getMyDescription');

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Установить краткое описание бота
     * 
     * @param string $shortDescription Краткое описание
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#setmyshortdescription
     */
    public function setMyShortDescription(
        string $shortDescription,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setMyShortDescription')
            ->withData('short_description', $shortDescription);

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Получить краткое описание бота
     * 
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#getmyshortdescription
     */
    public function getMyShortDescription(?string $languageCode = null): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('getMyShortDescription');

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Установить имя бота
     * 
     * @param string $name Имя бота
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#setmyname
     */
    public function setMyName(
        string $name,
        ?string $languageCode = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setMyName')
            ->withData('name', $name);

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Получить имя бота
     * 
     * @param string|null $languageCode Код языка
     * 
     * @see https://core.telegram.org/bots/api#getmyname
     */
    public function getMyName(?string $languageCode = null): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('getMyName');

        if ($languageCode !== null) {
            $this->telegraph = $this->telegraph->withData('language_code', $languageCode);
        }

        return $this;
    }

    /**
     * Установить фото бота
     * 
     * @param string $photoPath Путь к фото
     * 
     * @see https://core.telegram.org/bots/api#setmyphoto
     */
    public function setMyPhoto(string $photoPath): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setMyPhoto');

        // Добавляем файл через метод photo из Telegraph
        $this->telegraph = $this->telegraph->photo($photoPath);

        return $this;
    }

    /**
     * Удалить фото бота
     * 
     * @see https://core.telegram.org/bots/api#deletemyphoto
     */
    public function deleteMyPhoto(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('deleteMyPhoto');

        return $this;
    }

    /**
     * Установить меню бота
     * 
     * @param array $menuButton Кнопка меню
     * 
     * @see https://core.telegram.org/bots/api#setmymenubutton
     */
    public function setMyMenuButton(
        array $menuButton
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('setMyMenuButton')
            ->withData('menu_button', $menuButton);

        return $this;
    }

    /**
     * Получить меню бота
     * 
     * @see https://core.telegram.org/bots/api#getmymenubutton
     */
    public function getMyMenuButton(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('getMyMenuButton');

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПРАВАМИ ДОСТУПА
    // ============================================

    /**
     * Установить права по умолчанию для всех участников
     * 
     * @param array $permissions Массив прав
     * @param bool $useIndependentChatPermissions Использовать независимые права для каналов
     * 
     * @see https://core.telegram.org/bots/api#setchatpermissions
     */
    public function setChatPermissions(
        array $permissions,
        bool $useIndependentChatPermissions = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_SET_CHAT_PERMISSIONS)
            ->withData('permissions', $permissions)
            ->withData('use_independent_chat_permissions', $useIndependentChatPermissions);

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПРОФИЛЯМИ ПОЛЬЗОВАТЕЛЕЙ
    // ============================================

    /**
     * Получить фото профиля пользователя
     * 
     * @param string|int $userId ID пользователя
     * @param int|null $offset Смещение
     * @param int|null $limit Лимит
     * 
     * @see https://core.telegram.org/bots/api#getuserprofilephotos
     */
    public function getUserProfilePhotos(
        string|int $userId,
        ?int $offset = null,
        ?int $limit = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_USER_PROFILE_PHOTOS)
            ->withData('user_id', $userId);

        if ($offset !== null) {
            $this->telegraph = $this->telegraph->withData('offset', $offset);
        }

        if ($limit !== null) {
            $this->telegraph = $this->telegraph->withData('limit', $limit);
        }

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С WEBHOOK
    // ============================================

    /**
     * Установить webhook
     * 
     * @param string|null $url URL webhook
     * @param string|null $certificate Путь к сертификату
     * @param string|null $ipAddress IP адрес
     * @param int|null $maxConnections Максимальное количество соединений
     * @param array|null $allowedUpdates Разрешенные типы обновлений
     * @param bool $dropPendingUpdates Удалить ожидающие обновления
     * @param string|null $secretToken Секретный токен
     * 
     * @see https://core.telegram.org/bots/api#setwebhook
     */
    public function setWebhook(
        ?string $url = null,
        ?string $certificate = null,
        ?string $ipAddress = null,
        ?int $maxConnections = null,
        ?array $allowedUpdates = null,
        bool $dropPendingUpdates = false,
        ?string $secretToken = null
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_SET_WEBHOOK)
            ->withData('drop_pending_updates', $dropPendingUpdates);

        if ($url !== null) {
            $this->telegraph = $this->telegraph->withData('url', $url);
        }

        if ($certificate !== null) {
            $this->telegraph = $this->telegraph->withData('certificate', $certificate);
        }

        if ($ipAddress !== null) {
            $this->telegraph = $this->telegraph->withData('ip_address', $ipAddress);
        }

        if ($maxConnections !== null) {
            $this->telegraph = $this->telegraph->withData('max_connections', $maxConnections);
        }

        if ($allowedUpdates !== null) {
            $this->telegraph = $this->telegraph->withData('allowed_updates', $allowedUpdates);
        }

        if ($secretToken !== null) {
            $this->telegraph = $this->telegraph->withData('secret_token', $secretToken);
        }

        return $this;
    }

    /**
     * Удалить webhook
     * 
     * @param bool $dropPendingUpdates Удалить ожидающие обновления
     * 
     * @see https://core.telegram.org/bots/api#deletewebhook
     */
    public function deleteWebhook(
        bool $dropPendingUpdates = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_UNSET_WEBHOOK)
            ->withData('drop_pending_updates', $dropPendingUpdates);

        return $this;
    }

    /**
     * Получить информацию о webhook
     * 
     * @see https://core.telegram.org/bots/api#getwebhookinfo
     */
    public function getWebhookInfo(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_GET_WEBHOOK_DEBUG_INFO);

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ВИДЕОЧАТАМИ
    // ============================================

    /**
     * Создать приглашение для видеозвонка
     * 
     * @param string|null $name Имя звонка
     * @param Carbon|null $expiresDate Дата истечения
     * @param int|null $participantLimit Лимит участников
     * @param bool $createsJoinRequest Требовать подтверждение для присоединения
     * 
     * @see https://core.telegram.org/bots/api#createchatinvitelink
     */
    public function createVideoChatInviteLink(
        ?string $name = null,
        ?Carbon $expiresDate = null,
        ?int $participantLimit = null,
        bool $createsJoinRequest = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('createVideoChatInviteLink')
            ->withData('creates_join_request', $createsJoinRequest);

        if ($name !== null) {
            $this->telegraph = $this->telegraph->withData('name', $name);
        }

        if ($expiresDate !== null) {
            $this->telegraph = $this->telegraph->withData('expires_date', $expiresDate->timestamp);
        }

        if ($participantLimit !== null) {
            $this->telegraph = $this->telegraph->withData('participant_limit', $participantLimit);
        }

        return $this;
    }

    /**
     * Редактировать приглашение для видеозвонка
     * 
     * @param string $inviteLink Ссылка-приглашение
     * @param string|null $name Имя звонка
     * @param Carbon|null $expiresDate Дата истечения
     * @param int|null $participantLimit Лимит участников
     * @param bool $createsJoinRequest Требовать подтверждение для присоединения
     * 
     * @see https://core.telegram.org/bots/api#editchatinvitelink
     */
    public function editVideoChatInviteLink(
        string $inviteLink,
        ?string $name = null,
        ?Carbon $expiresDate = null,
        ?int $participantLimit = null,
        bool $createsJoinRequest = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint('editVideoChatInviteLink')
            ->withData('invite_link', $inviteLink)
            ->withData('creates_join_request', $createsJoinRequest);

        if ($name !== null) {
            $this->telegraph = $this->telegraph->withData('name', $name);
        }

        if ($expiresDate !== null) {
            $this->telegraph = $this->telegraph->withData('expires_date', $expiresDate->timestamp);
        }

        if ($participantLimit !== null) {
            $this->telegraph = $this->telegraph->withData('participant_limit', $participantLimit);
        }

        return $this;
    }

    /**
     * Отозвать приглашение для видеозвонка
     * 
     * @param string $inviteLink Ссылка-приглашение
     * 
     * @see https://core.telegram.org/bots/api#revokechatinvitelink
     */
    public function revokeVideoChatInviteLink(string $inviteLink): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('revokeVideoChatInviteLink')
            ->withData('invite_link', $inviteLink);

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ИСТОРИЯМИ
    // ============================================

    /**
     * Удалить историю чата
     * 
     * @param int|null $messageId ID сообщения до которого удалить (включительно)
     * 
     * @see https://core.telegram.org/bots/api#deletechatmessages
     */
    public function deleteChatMessages(?int $messageId = null): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('deleteChatMessages');

        if ($messageId !== null) {
            $this->telegraph = $this->telegraph->withData('message_id', $messageId);
        }

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ТОПИКАМИ ФОРУМА
    // ============================================

    /**
     * Редактировать общее имя топиков форума
     * 
     * @param string $name Новое имя
     * 
     * @see https://core.telegram.org/bots/api#editgeneralforumtopic
     */
    public function editGeneralForumTopic(string $name): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('editGeneralForumTopic')
            ->withData('name', $name);

        return $this;
    }

    /**
     * Закрыть общий топик форума
     * 
     * @see https://core.telegram.org/bots/api#closegeneralforumtopic
     */
    public function closeGeneralForumTopic(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('closeGeneralForumTopic');

        return $this;
    }

    /**
     * Открыть общий топик форума
     * 
     * @see https://core.telegram.org/bots/api#reopengeneralforumtopic
     */
    public function reopenGeneralForumTopic(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('reopenGeneralForumTopic');

        return $this;
    }

    /**
     * Скрыть общий топик форума
     * 
     * @see https://core.telegram.org/bots/api#hidegeneralforumtopic
     */
    public function hideGeneralForumTopic(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('hideGeneralForumTopic');

        return $this;
    }

    /**
     * Показать общий топик форума
     * 
     * @see https://core.telegram.org/bots/api#unhidegeneralforumtopic
     */
    public function unhideGeneralForumTopic(): self
    {
        $this->telegraph = $this->telegraph
            ->withEndpoint('unhideGeneralForumTopic');

        return $this;
    }

    /**
     * Отправить ответ на callback query
     * 
     * @param string $callbackQueryId ID callback query
     * @param string|null $text Текст ответа
     * @param bool $showAlert Показать как alert
     * @param string|null $url URL для открытия
     * @param int $cacheTime Время кеширования
     * 
     * @see https://core.telegram.org/bots/api#answercallbackquery
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false,
        ?string $url = null,
        int $cacheTime = 0
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_ANSWER_WEBHOOK)
            ->withData('callback_query_id', $callbackQueryId)
            ->withData('show_alert', $showAlert)
            ->withData('cache_time', $cacheTime);

        if ($text !== null) {
            $this->telegraph = $this->telegraph->withData('text', $text);
        }

        if ($url !== null) {
            $this->telegraph = $this->telegraph->withData('url', $url);
        }

        return $this;
    }

    // ============================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПЛАТЕЖАМИ
    // ============================================

    /**
     * Создать ссылку на инвойс
     * 
     * @param string $title Название товара
     * @param string $description Описание
     * @param string $payload Уникальный payload
     * @param string $providerToken Токен провайдера платежей
     * @param string $currency Валюта (например, USD, EUR, RUB)
     * @param array $prices Массив цен
     * @param int|null $maxTipAmount Максимальная сумма чаевых
     * @param array|null $suggestedTipAmounts Предложенные суммы чаевых
     * @param string|null $providerData Данные провайдера
     * @param string|null $photoUrl URL фото товара
     * @param int|null $photoSize Размер фото
     * @param int|null $photoWidth Ширина фото
     * @param int|null $photoHeight Высота фото
     * @param bool $needName Требовать имя
     * @param bool $needPhoneNumber Требовать номер телефона
     * @param bool $needEmail Требовать email
     * @param bool $needShippingAddress Требовать адрес доставки
     * @param bool $sendPhoneNumberToProvider Отправить номер телефона провайдеру
     * @param bool $sendEmailToProvider Отправить email провайдеру
     * @param bool $isFlexible Гибкая цена
     * 
     * @see https://core.telegram.org/bots/api#createinvoicelink
     */
    public function createInvoiceLink(
        string $title,
        string $description,
        string $payload,
        string $providerToken,
        string $currency,
        array $prices,
        ?int $maxTipAmount = null,
        ?array $suggestedTipAmounts = null,
        ?string $providerData = null,
        ?string $photoUrl = null,
        ?int $photoSize = null,
        ?int $photoWidth = null,
        ?int $photoHeight = null,
        bool $needName = false,
        bool $needPhoneNumber = false,
        bool $needEmail = false,
        bool $needShippingAddress = false,
        bool $sendPhoneNumberToProvider = false,
        bool $sendEmailToProvider = false,
        bool $isFlexible = false
    ): self {
        $this->telegraph = $this->telegraph
            ->withEndpoint(Telegraph::ENDPOINT_CREATE_INVOICE_LINK)
            ->withData('title', $title)
            ->withData('description', $description)
            ->withData('payload', $payload)
            ->withData('provider_token', $providerToken)
            ->withData('currency', $currency)
            ->withData('prices', $prices)
            ->withData('need_name', $needName)
            ->withData('need_phone_number', $needPhoneNumber)
            ->withData('need_email', $needEmail)
            ->withData('need_shipping_address', $needShippingAddress)
            ->withData('send_phone_number_to_provider', $sendPhoneNumberToProvider)
            ->withData('send_email_to_provider', $sendEmailToProvider)
            ->withData('is_flexible', $isFlexible);

        if ($maxTipAmount !== null) {
            $this->telegraph = $this->telegraph->withData('max_tip_amount', $maxTipAmount);
        }

        if ($suggestedTipAmounts !== null) {
            $this->telegraph = $this->telegraph->withData('suggested_tip_amounts', $suggestedTipAmounts);
        }

        if ($providerData !== null) {
            $this->telegraph = $this->telegraph->withData('provider_data', $providerData);
        }

        if ($photoUrl !== null) {
            $this->telegraph = $this->telegraph->withData('photo_url', $photoUrl);
        }

        if ($photoSize !== null) {
            $this->telegraph = $this->telegraph->withData('photo_size', $photoSize);
        }

        if ($photoWidth !== null) {
            $this->telegraph = $this->telegraph->withData('photo_width', $photoWidth);
        }

        if ($photoHeight !== null) {
            $this->telegraph = $this->telegraph->withData('photo_height', $photoHeight);
        }

        return $this;
    }

    // ============================================
    // МАГИЧЕСКИЕ МЕТОДЫ ДЛЯ ДЕЛЕГИРОВАНИЯ
    // ============================================

    /**
     * Делегировать вызовы методов к базовому Telegraph
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->telegraph, $method)) {
            $result = call_user_func_array([$this->telegraph, $method], $arguments);
            
            // Если метод возвращает Telegraph, оборачиваем его обратно в ExtendedTelegraph
            if ($result instanceof Telegraph) {
                $this->telegraph = $result;
                return $this;
            }
            
            return $result;
        }

        throw new \BadMethodCallException("Method {$method} does not exist in " . static::class);
    }
}


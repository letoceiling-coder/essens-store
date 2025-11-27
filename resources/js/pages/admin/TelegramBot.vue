<template>
    <div class="telegram-bot-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Telegram Bot</h1>
                <p class="text-muted-foreground mt-1">Управление настройками Telegram бота</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать бота</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка ботов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Bots List -->
        <div v-if="!loading && bots.length > 0" class="space-y-4">
            <div
                v-for="bot in bots"
                :key="bot.id"
                class="bg-card rounded-lg border border-border p-6"
            >
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-foreground">{{ bot.name }}</h3>
                        <p class="text-sm text-muted-foreground mt-1">ID: {{ bot.id }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="selectBot(bot)"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                        >
                            Настроить
                        </button>
                        <button
                            @click="deleteBot(bot)"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                        >
                            Удалить
                        </button>
                    </div>
                </div>

                <div v-if="selectedBot && selectedBot.id === bot.id" class="mt-6">
                    <!-- Tabs Navigation -->
                    <div class="border-b border-border mb-4">
                        <div class="flex space-x-1">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'px-4 py-2 font-medium transition-colors border-b-2',
                                    activeTab === tab.id
                                        ? 'text-accent border-accent'
                                        : 'text-muted-foreground hover:text-foreground border-transparent'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                        <!-- General Tab -->
                        <div v-if="activeTab === 'general'" class="space-y-4 mt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium mb-1 block">Имя бота</label>
                                    <input
                                        v-model="botForm.name"
                                        type="text"
                                        placeholder="Например: My Telegram Bot"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Внутреннее имя бота для идентификации в системе
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium mb-1 block">Токен бота</label>
                                    <input
                                        v-model="botForm.token"
                                        type="password"
                                        placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Токен бота от @BotFather. Формат: число:строка
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="updateBot(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Сохранить
                                </button>
                                <button
                                    @click="getBotInfo(bot.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                >
                                    Получить информацию от Telegram
                                </button>
                            </div>
                            <div v-if="botInfo" class="mt-4 p-4 bg-muted/30 rounded-lg">
                                <h4 class="font-semibold mb-2">Информация от Telegram:</h4>
                                <pre class="text-sm overflow-auto">{{ JSON.stringify(botInfo, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Commands Tab -->
                        <div v-if="activeTab === 'commands'" class="space-y-4 mt-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Команды бота</label>
                                <p class="text-xs text-muted-foreground mb-2">
                                    Команды отображаются в меню бота. Команда должна начинаться с / (например: /start, /help)
                                </p>
                                <div
                                    v-for="(command, index) in commandsForm"
                                    :key="index"
                                    class="flex gap-2 mb-2"
                                >
                                    <div class="flex-1">
                                        <input
                                            v-model="command.command"
                                            type="text"
                                            placeholder="/start"
                                            class="w-full h-10 px-3 border border-border rounded bg-background"
                                        />
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Команда (без /, например: start, help, settings)
                                        </p>
                                    </div>
                                    <div class="flex-1">
                                        <input
                                            v-model="command.description"
                                            type="text"
                                            placeholder="Начать работу с ботом"
                                            class="w-full h-10 px-3 border border-border rounded bg-background"
                                        />
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Описание команды (до 256 символов)
                                        </p>
                                    </div>
                                    <button
                                        @click="removeCommand(index)"
                                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors h-10"
                                    >
                                        Удалить
                                    </button>
                                </div>
                                <button
                                    @click="addCommand"
                                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                                >
                                    + Добавить команду
                                </button>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="setCommands(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить команды
                                </button>
                                <button
                                    @click="getCommands(bot.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                >
                                    Получить команды
                                </button>
                                <button
                                    @click="deleteCommands(bot.id)"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                >
                                    Удалить все команды
                                </button>
                            </div>
                        </div>

                        <!-- Description Tab -->
                        <div v-if="activeTab === 'description'" class="space-y-4 mt-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Описание бота</label>
                                <textarea
                                    v-model="descriptionForm.description"
                                    rows="4"
                                    maxlength="512"
                                    class="w-full px-3 py-2 border border-border rounded bg-background"
                                    placeholder="Например: Умный бот для управления вашим магазином. Помогает отслеживать заказы, управлять товарами и отвечать на вопросы клиентов."
                                ></textarea>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ descriptionForm.description?.length || 0 }} / 512 символов. Отображается в профиле бота
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Краткое описание</label>
                                <textarea
                                    v-model="descriptionForm.short_description"
                                    rows="2"
                                    maxlength="120"
                                    class="w-full px-3 py-2 border border-border rounded bg-background"
                                    placeholder="Например: Бот для управления магазином"
                                ></textarea>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ descriptionForm.short_description?.length || 0 }} / 120 символов. Отображается в результатах поиска
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Имя бота</label>
                                <input
                                    v-model="descriptionForm.name"
                                    type="text"
                                    maxlength="64"
                                    placeholder="Например: My Store Bot"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ descriptionForm.name?.length || 0 }} / 64 символов. Отображается в профиле бота
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="setDescription(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить описание
                                </button>
                                <button
                                    @click="setShortDescription(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить краткое описание
                                </button>
                                <button
                                    @click="setName(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить имя
                                </button>
                                <button
                                    @click="getDescription(bot.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                >
                                    Получить настройки
                                </button>
                            </div>
                        </div>

                        <!-- Photo Tab -->
                        <div v-if="activeTab === 'photo'" class="space-y-4 mt-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Фото бота</label>
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg"
                                    @change="handlePhotoChange"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Максимальный размер: 10MB. Форматы: JPG, PNG. Рекомендуемый размер: 640x640 пикселей
                                </p>
                                <p class="text-xs text-blue-500 mt-1">
                                    💡 Совет: Используйте квадратное изображение для лучшего отображения
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="setPhoto(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить фото
                                </button>
                                <button
                                    @click="deletePhoto(bot.id)"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                >
                                    Удалить фото
                                </button>
                            </div>
                        </div>

                        <!-- Menu Tab -->
                        <div v-if="activeTab === 'menu'" class="space-y-4 mt-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Тип меню</label>
                                <select
                                    v-model="menuForm.type"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                >
                                    <option value="default">По умолчанию (стандартное меню Telegram)</option>
                                    <option value="commands">Команды (показывать список команд)</option>
                                    <option value="web_app">Web App (кнопка для веб-приложения)</option>
                                </select>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Тип кнопки меню, отображаемой в интерфейсе бота
                                </p>
                            </div>
                            <div v-if="menuForm.type === 'web_app'">
                                <label class="text-sm font-medium mb-1 block">Текст кнопки</label>
                                <input
                                    v-model="menuForm.text"
                                    type="text"
                                    placeholder="Например: Открыть магазин"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Текст, который будет отображаться на кнопке меню (до 64 символов)
                                </p>
                            </div>
                            <div v-if="menuForm.type === 'web_app'">
                                <label class="text-sm font-medium mb-1 block">URL Web App</label>
                                <input
                                    v-model="menuForm.web_app.url"
                                    type="url"
                                    placeholder="https://example.com/app"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    URL веб-приложения, которое откроется при нажатии на кнопку. Должен использовать HTTPS
                                </p>
                                <p class="text-xs text-blue-500 mt-1">
                                    💡 Совет: URL должен быть доступен по HTTPS и соответствовать требованиям Telegram Web Apps
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="setMenuButton(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить меню
                                </button>
                                <button
                                    @click="getMenuButton(bot.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                >
                                    Получить меню
                                </button>
                            </div>
                        </div>

                        <!-- Webhook Tab -->
                        <div v-if="activeTab === 'webhook'" class="space-y-4 mt-4">
                            <div>
                                <label class="text-sm font-medium mb-1 block">Домен (например: essens-store.ru)</label>
                                <input
                                    v-model="webhookForm.domain"
                                    type="text"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                    placeholder="essens-store.ru"
                                    @input="updateWebhookUrl"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    URL будет автоматически сформирован: {{ webhookForm.url || 'https://ваш-домен.ru/api/telegram/webhook/{token}' }}
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium mb-1 block">Максимум соединений</label>
                                    <input
                                        v-model.number="webhookForm.max_connections"
                                        type="number"
                                        min="1"
                                        max="100"
                                        placeholder="40"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Максимальное количество одновременных HTTPS соединений (1-100). По умолчанию: 40
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium mb-1 block">IP адрес (опционально)</label>
                                    <input
                                        v-model="webhookForm.ip_address"
                                        type="text"
                                        placeholder="192.168.1.1"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Разрешенный IP-адрес для отправки обновлений. Оставьте пустым, чтобы разрешить все IP
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Секретный токен</label>
                                <input
                                    v-model="webhookForm.secret_token"
                                    type="text"
                                    placeholder="my_secret_token_12345"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Секретный токен для проверки подлинности webhook. Будет отправлен в заголовке X-Telegram-Bot-Api-Secret-Token
                                </p>
                                <p class="text-xs text-blue-500 mt-1">
                                    💡 Совет: Используйте случайную строку для безопасности. Telegram будет отправлять этот токен в каждом запросе
                                </p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="webhookForm.drop_pending_updates"
                                        type="checkbox"
                                        class="w-4 h-4"
                                    />
                                    <span class="text-sm">Удалить ожидающие обновления</span>
                                </label>
                                <p class="text-xs text-muted-foreground mt-1 ml-6">
                                    Если включено, все ожидающие обновления будут удалены перед установкой webhook. Полезно при первой настройке
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Разрешенные типы обновлений</label>
                                <p class="text-xs text-muted-foreground mb-2">
                                    Выберите типы обновлений, которые бот будет получать. Если ничего не выбрано, будут получаться все типы
                                </p>
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        v-for="updateType in updateTypes"
                                        :key="updateType"
                                        class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-muted/30"
                                        :title="getUpdateTypeDescription(updateType)"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="updateType"
                                            v-model="webhookForm.allowed_updates"
                                            class="w-4 h-4"
                                        />
                                        <span class="text-sm">{{ updateType }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="setWebhook(bot.id)"
                                    class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    Установить Webhook
                                </button>
                                <button
                                    @click="deleteWebhook(bot.id)"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                >
                                    Удалить Webhook
                                </button>
                                <button
                                    @click="getWebhookInfo(bot.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                >
                                    Получить информацию
                                </button>
                            </div>
                            <div v-if="webhookInfo" class="mt-4 p-4 bg-muted/30 rounded-lg">
                                <h4 class="font-semibold mb-2">Информация о Webhook:</h4>
                                <pre class="text-sm overflow-auto">{{ JSON.stringify(webhookInfo, null, 2) }}                                </pre>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && bots.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Боты не найдены. Создайте первого бота.</p>
        </div>

        <!-- Create Bot Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">Создать бота</h3>
                <form @submit.prevent="createBot" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Имя бота</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="Например: My Telegram Bot"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Внутреннее имя бота для идентификации в системе
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Токен бота</label>
                        <input
                            v-model="createForm.token"
                            type="text"
                            required
                            placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Получите токен у @BotFather в Telegram. Формат: число:строка
                        </p>
                        <p class="text-xs text-blue-500 mt-1">
                            💡 Как получить: Откройте @BotFather → /newbot → следуйте инструкциям
                        </p>
                    </div>
                    <div class="flex gap-2 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 h-10 px-4 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50"
                        >
                            {{ saving ? 'Создание...' : 'Создать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TelegramBot',
    data() {
        return {
            bots: [],
            loading: false,
            error: null,
            saving: false,
            showCreateModal: false,
            selectedBot: null,
            botInfo: null,
            webhookInfo: null,
            activeTab: 'general',
            tabs: [
                { id: 'general', label: 'Общее' },
                { id: 'commands', label: 'Команды' },
                { id: 'description', label: 'Описание' },
                { id: 'photo', label: 'Фото' },
                { id: 'menu', label: 'Меню' },
                { id: 'webhook', label: 'Webhook' },
            ],
            createForm: {
                name: '',
                token: '',
            },
            botForm: {
                name: '',
                token: '',
            },
            commandsForm: [
                { command: '', description: '' },
            ],
            descriptionForm: {
                description: '',
                short_description: '',
                name: '',
            },
            photoFile: null,
            menuForm: {
                type: 'default',
                text: '',
                web_app: {
                    url: '',
                },
            },
            webhookForm: {
                domain: '',
                url: '',
                max_connections: 40,
                ip_address: '',
                secret_token: '',
                drop_pending_updates: false,
                allowed_updates: [],
            },
            updateTypes: [
                'message',
                'edited_message',
                'channel_post',
                'edited_channel_post',
                'inline_query',
                'chosen_inline_result',
                'callback_query',
                'shipping_query',
                'pre_checkout_query',
                'poll',
                'poll_answer',
                'my_chat_member',
                'chat_member',
                'chat_join_request',
            ],
        };
    },
    mounted() {
        this.loadBots();
        // Устанавливаем домен по умолчанию из текущего URL
        if (!this.webhookForm.domain) {
            const hostname = window.location.hostname;
            if (hostname && hostname !== 'localhost') {
                this.webhookForm.domain = hostname;
                this.updateWebhookUrl();
            }
        }
    },
    methods: {
        async loadBots() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get('/api/admin/telegram-bot/bots');
                this.bots = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при загрузке ботов';
            } finally {
                this.loading = false;
            }
        },
        async createBot() {
            this.saving = true;
            try {
                const response = await axios.post('/api/admin/telegram-bot/bots', this.createForm);
                this.bots.push(response.data.data);
                this.showCreateModal = false;
                this.createForm = { name: '', token: '' };
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при создании бота';
            } finally {
                this.saving = false;
            }
        },
        async updateBot(id) {
            this.saving = true;
            try {
                await axios.put(`/api/admin/telegram-bot/bots/${id}`, this.botForm);
                await this.loadBots();
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при обновлении бота';
            } finally {
                this.saving = false;
            }
        },
        async deleteBot(bot) {
            if (!confirm(`Удалить бота "${bot.name}"?`)) return;
            try {
                await axios.delete(`/api/admin/telegram-bot/bots/${bot.id}`);
                await this.loadBots();
                if (this.selectedBot?.id === bot.id) {
                    this.selectedBot = null;
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при удалении бота';
            }
        },
        async selectBot(bot) {
            this.selectedBot = bot;
            this.activeTab = 'general';
            
            // Загружаем полную информацию о боте, чтобы получить токен
            try {
                const response = await axios.get(`/api/admin/telegram-bot/bots/${bot.id}`);
                const fullBot = response.data.data;
                this.selectedBot = fullBot; // Обновляем с полной информацией
                this.botForm = {
                    name: fullBot.name,
                    token: fullBot.token,
                };
            } catch (error) {
                // Если не удалось загрузить, используем данные из списка
                this.botForm = {
                    name: bot.name,
                    token: bot.token || '',
                };
            }
            
            // Обновляем URL webhook при выборе бота
            this.updateWebhookUrl();
        },
        async getBotInfo(id) {
            try {
                const response = await axios.get(`/api/admin/telegram-bot/bots/${id}/info`);
                this.botInfo = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при получении информации';
            }
        },
        addCommand() {
            this.commandsForm.push({ command: '', description: '' });
        },
        removeCommand(index) {
            this.commandsForm.splice(index, 1);
        },
        async setCommands(id) {
            try {
                await axios.post(`/api/admin/telegram-bot/bots/${id}/commands`, {
                    commands: this.commandsForm.filter(c => c.command && c.description),
                });
                alert('Команды успешно установлены');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке команд';
            }
        },
        async getCommands(id) {
            try {
                const response = await axios.get(`/api/admin/telegram-bot/bots/${id}/commands`);
                this.commandsForm = response.data.data || [{ command: '', description: '' }];
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при получении команд';
            }
        },
        async deleteCommands(id) {
            if (!confirm('Удалить все команды бота?')) return;
            try {
                await axios.delete(`/api/admin/telegram-bot/bots/${id}/commands`);
                this.commandsForm = [{ command: '', description: '' }];
                alert('Команды успешно удалены');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при удалении команд';
            }
        },
        async setDescription(id) {
            try {
                await axios.post(`/api/admin/telegram-bot/bots/${id}/description`, {
                    description: this.descriptionForm.description,
                });
                alert('Описание успешно установлено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке описания';
            }
        },
        async setShortDescription(id) {
            try {
                await axios.post(`/api/admin/telegram-bot/bots/${id}/short-description`, {
                    short_description: this.descriptionForm.short_description,
                });
                alert('Краткое описание успешно установлено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке краткого описания';
            }
        },
        async setName(id) {
            try {
                await axios.post(`/api/admin/telegram-bot/bots/${id}/name`, {
                    name: this.descriptionForm.name,
                });
                alert('Имя успешно установлено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке имени';
            }
        },
        async getDescription(id) {
            try {
                const [descRes, shortDescRes, nameRes] = await Promise.all([
                    axios.get(`/api/admin/telegram-bot/bots/${id}/description`),
                    axios.get(`/api/admin/telegram-bot/bots/${id}/short-description`),
                    axios.get(`/api/admin/telegram-bot/bots/${id}/name`),
                ]);
                this.descriptionForm.description = descRes.data.data?.description || '';
                this.descriptionForm.short_description = shortDescRes.data.data?.short_description || '';
                this.descriptionForm.name = nameRes.data.data?.name || '';
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при получении настроек';
            }
        },
        handlePhotoChange(event) {
            this.photoFile = event.target.files[0];
        },
        async setPhoto(id) {
            if (!this.photoFile) {
                alert('Выберите файл');
                return;
            }
            try {
                const formData = new FormData();
                formData.append('photo', this.photoFile);
                await axios.post(`/api/admin/telegram-bot/bots/${id}/photo`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                alert('Фото успешно установлено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке фото';
            }
        },
        async deletePhoto(id) {
            if (!confirm('Удалить фото бота?')) return;
            try {
                await axios.delete(`/api/admin/telegram-bot/bots/${id}/photo`);
                alert('Фото успешно удалено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при удалении фото';
            }
        },
        async setMenuButton(id) {
            try {
                const menuButton = {
                    type: this.menuForm.type,
                };
                if (this.menuForm.type === 'web_app') {
                    menuButton.text = this.menuForm.text;
                    menuButton.web_app = { url: this.menuForm.web_app.url };
                }
                await axios.post(`/api/admin/telegram-bot/bots/${id}/menu-button`, {
                    menu_button: menuButton,
                });
                alert('Меню успешно установлено');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке меню';
            }
        },
        async getMenuButton(id) {
            try {
                const response = await axios.get(`/api/admin/telegram-bot/bots/${id}/menu-button`);
                const menu = response.data.data?.menu_button || {};
                this.menuForm.type = menu.type || 'default';
                if (menu.text) this.menuForm.text = menu.text;
                if (menu.web_app?.url) this.menuForm.web_app.url = menu.web_app.url;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при получении меню';
            }
        },
        updateWebhookUrl() {
            // Получаем токен из selectedBot или из списка ботов
            let token = this.selectedBot?.token;
            if (!token && this.selectedBot?.id) {
                const bot = this.bots.find(b => b.id === this.selectedBot.id);
                if (bot) {
                    token = bot.token;
                }
            }
            
            if (this.webhookForm.domain && token) {
                // Убираем протокол и слеши, если есть
                let domain = this.webhookForm.domain.replace(/^https?:\/\//, '').replace(/\/$/, '');
                // Формируем полный URL
                this.webhookForm.url = `https://${domain}/api/telegram/webhook/${token}`;
            } else if (this.webhookForm.domain) {
                // Если токена еще нет, показываем шаблон
                let domain = this.webhookForm.domain.replace(/^https?:\/\//, '').replace(/\/$/, '');
                this.webhookForm.url = `https://${domain}/api/telegram/webhook/{token}`;
            } else {
                this.webhookForm.url = '';
            }
        },
        async setWebhook(id) {
            // Получаем токен бота, если он не доступен
            let token = this.selectedBot?.token;
            if (!token) {
                const bot = this.bots.find(b => b.id === id);
                if (bot) {
                    token = bot.token;
                }
            }
            
            // Проверяем, что домен указан
            if (!this.webhookForm.domain) {
                this.error = 'Пожалуйста, укажите домен';
                return;
            }
            
            // Проверяем, что токен доступен
            if (!token) {
                this.error = 'Токен бота не найден. Пожалуйста, обновите информацию о боте.';
                return;
            }
            
            // Обновляем URL перед отправкой
            this.updateWebhookUrl();
            
            // Проверяем, что URL сформирован правильно (без {token})
            if (!this.webhookForm.url || this.webhookForm.url.includes('{token}')) {
                this.error = 'Не удалось сформировать URL. Проверьте, что домен указан правильно и токен бота доступен.';
                return;
            }
            
            try {
                // Используем JSON вместо FormData для правильной передачи boolean значений
                const payload = {
                    url: this.webhookForm.url,
                    max_connections: this.webhookForm.max_connections,
                    drop_pending_updates: this.webhookForm.drop_pending_updates,
                };
                
                if (this.webhookForm.ip_address) {
                    payload.ip_address = this.webhookForm.ip_address;
                }
                if (this.webhookForm.secret_token) {
                    payload.secret_token = this.webhookForm.secret_token;
                }
                if (this.webhookForm.allowed_updates.length > 0) {
                    payload.allowed_updates = this.webhookForm.allowed_updates;
                }
                
                await axios.post(`/api/admin/telegram-bot/bots/${id}/webhook`, payload, {
                    headers: { 'Content-Type': 'application/json' },
                });
                alert('Webhook успешно установлен');
                // Обновляем информацию о webhook
                await this.getWebhookInfo(id);
            } catch (error) {
                this.error = error.response?.data?.message || error.response?.data?.errors?.url?.[0] || 'Ошибка при установке webhook';
            }
        },
        async deleteWebhook(id) {
            if (!confirm('Удалить webhook?')) return;
            try {
                await axios.delete(`/api/admin/telegram-bot/bots/${id}/webhook`, {
                    params: { drop_pending_updates: this.webhookForm.drop_pending_updates ? 'true' : 'false' },
                });
                alert('Webhook успешно удален');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при удалении webhook';
            }
        },
        async getWebhookInfo(id) {
            try {
                const response = await axios.get(`/api/admin/telegram-bot/bots/${id}/webhook`);
                this.webhookInfo = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при получении информации о webhook';
            }
        },
        getUpdateTypeDescription(type) {
            const descriptions = {
                'message': 'Обычные текстовые сообщения и медиа',
                'edited_message': 'Отредактированные сообщения',
                'channel_post': 'Сообщения в каналах',
                'edited_channel_post': 'Отредактированные сообщения в каналах',
                'inline_query': 'Inline-запросы (поиск)',
                'chosen_inline_result': 'Выбранный результат inline-запроса',
                'callback_query': 'Нажатия на кнопки (callback)',
                'shipping_query': 'Запросы доставки для платежей',
                'pre_checkout_query': 'Запросы перед оплатой',
                'poll': 'Обновления опросов',
                'poll_answer': 'Ответы на опросы',
                'my_chat_member': 'Изменения статуса бота в чате',
                'chat_member': 'Изменения статуса участников',
                'chat_join_request': 'Запросы на присоединение к чату',
            };
            return descriptions[type] || 'Тип обновления';
        },
    },
};
</script>


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
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-medium mb-1 block">Токен</label>
                                    <input
                                        v-model="botForm.token"
                                        type="password"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
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
                                <div
                                    v-for="(command, index) in commandsForm"
                                    :key="index"
                                    class="flex gap-2 mb-2"
                                >
                                    <input
                                        v-model="command.command"
                                        type="text"
                                        placeholder="Команда (например: start)"
                                        class="flex-1 h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <input
                                        v-model="command.description"
                                        type="text"
                                        placeholder="Описание"
                                        class="flex-1 h-10 px-3 border border-border rounded bg-background"
                                    />
                                    <button
                                        @click="removeCommand(index)"
                                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
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
                                    placeholder="Полное описание бота (до 512 символов)"
                                ></textarea>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ descriptionForm.description?.length || 0 }} / 512
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Краткое описание</label>
                                <textarea
                                    v-model="descriptionForm.short_description"
                                    rows="2"
                                    maxlength="120"
                                    class="w-full px-3 py-2 border border-border rounded bg-background"
                                    placeholder="Краткое описание бота (до 120 символов)"
                                ></textarea>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ descriptionForm.short_description?.length || 0 }} / 120
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Имя бота</label>
                                <input
                                    v-model="descriptionForm.name"
                                    type="text"
                                    maxlength="64"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                    placeholder="Имя бота (до 64 символов)"
                                />
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
                                    accept="image/*"
                                    @change="handlePhotoChange"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Максимальный размер: 10MB. Форматы: JPG, PNG
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
                                    <option value="default">По умолчанию</option>
                                    <option value="commands">Команды</option>
                                    <option value="web_app">Web App</option>
                                </select>
                            </div>
                            <div v-if="menuForm.type === 'web_app'">
                                <label class="text-sm font-medium mb-1 block">Текст кнопки</label>
                                <input
                                    v-model="menuForm.text"
                                    type="text"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
                            </div>
                            <div v-if="menuForm.type === 'web_app'">
                                <label class="text-sm font-medium mb-1 block">URL Web App</label>
                                <input
                                    v-model="menuForm.web_app.url"
                                    type="url"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                />
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
                                <label class="text-sm font-medium mb-1 block">URL Webhook</label>
                                <input
                                    v-model="webhookForm.url"
                                    type="url"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                    placeholder="https://example.com/webhook"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium mb-1 block">Максимум соединений</label>
                                    <input
                                        v-model.number="webhookForm.max_connections"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm font-medium mb-1 block">IP адрес (опционально)</label>
                                    <input
                                        v-model="webhookForm.ip_address"
                                        type="text"
                                        class="w-full h-10 px-3 border border-border rounded bg-background"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Секретный токен</label>
                                <input
                                    v-model="webhookForm.secret_token"
                                    type="text"
                                    class="w-full h-10 px-3 border border-border rounded bg-background"
                                    placeholder="Секретный токен для проверки webhook"
                                />
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
                            </div>
                            <div>
                                <label class="text-sm font-medium mb-1 block">Разрешенные типы обновлений</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        v-for="updateType in updateTypes"
                                        :key="updateType"
                                        class="flex items-center gap-2 cursor-pointer"
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
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Токен бота</label>
                        <input
                            v-model="createForm.token"
                            type="text"
                            required
                            placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Получите токен у @BotFather в Telegram
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
        selectBot(bot) {
            this.selectedBot = bot;
            this.activeTab = 'general';
            this.botForm = {
                name: bot.name,
                token: bot.token,
            };
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
        async setWebhook(id) {
            try {
                const formData = new FormData();
                formData.append('url', this.webhookForm.url);
                formData.append('max_connections', this.webhookForm.max_connections);
                if (this.webhookForm.ip_address) {
                    formData.append('ip_address', this.webhookForm.ip_address);
                }
                if (this.webhookForm.secret_token) {
                    formData.append('secret_token', this.webhookForm.secret_token);
                }
                formData.append('drop_pending_updates', this.webhookForm.drop_pending_updates);
                if (this.webhookForm.allowed_updates.length > 0) {
                    this.webhookForm.allowed_updates.forEach(update => {
                        formData.append('allowed_updates[]', update);
                    });
                }
                await axios.post(`/api/admin/telegram-bot/bots/${id}/webhook`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                alert('Webhook успешно установлен');
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка при установке webhook';
            }
        },
        async deleteWebhook(id) {
            if (!confirm('Удалить webhook?')) return;
            try {
                await axios.delete(`/api/admin/telegram-bot/bots/${id}/webhook`, {
                    params: { drop_pending_updates: this.webhookForm.drop_pending_updates },
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
    },
};
</script>


<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExtendedTelegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TelegramBotController extends Controller
{
    protected ExtendedTelegraph $telegraph;

    public function __construct(ExtendedTelegraph $telegraph)
    {
        $this->telegraph = $telegraph;
    }

    /**
     * Получить список всех ботов
     */
    public function index()
    {
        $bots = TelegraphBot::with('chats')->get();

        return response()->json([
            'success' => true,
            'data' => $bots,
        ]);
    }

    /**
     * Получить информацию о боте
     */
    public function show($id)
    {
        $bot = TelegraphBot::with('chats')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bot,
        ]);
    }

    /**
     * Создать нового бота
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot = TelegraphBot::create([
            'name' => $request->name,
            'token' => $request->token,
        ]);

        return response()->json([
            'success' => true,
            'data' => $bot,
            'message' => 'Бот успешно создан',
        ], 201);
    }

    /**
     * Обновить бота
     */
    public function update(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'token' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot->update($request->only(['name', 'token']));

        return response()->json([
            'success' => true,
            'data' => $bot,
            'message' => 'Бот успешно обновлен',
        ]);
    }

    /**
     * Удалить бота
     */
    public function destroy($id)
    {
        $bot = TelegraphBot::findOrFail($id);
        $bot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Бот успешно удален',
        ]);
    }

    /**
     * Получить информацию о боте от Telegram
     */
    public function getBotInfo($id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMe()
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении информации о боте'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить обновления бота
     */
    public function getUpdates(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'offset' => 'sometimes|integer|min:0',
            'limit' => 'sometimes|integer|min:1|max:100',
            'timeout' => 'sometimes|integer|min:0|max:50',
            'allowed_updates' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getUpdates(
                    offset: $request->input('offset'),
                    limit: $request->input('limit', 100),
                    timeout: $request->input('timeout'),
                    allowedUpdates: $request->input('allowed_updates')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result', []),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении обновлений'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить команды бота
     */
    public function setCommands(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'commands' => 'required|array',
            'commands.*.command' => 'required|string|max:32',
            'commands.*.description' => 'required|string|max:256',
            'scope' => 'sometimes|array',
            'language_code' => 'sometimes|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->setMyCommands(
                    commands: $request->commands,
                    scope: $request->input('scope'),
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Команды успешно установлены',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке команд'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить команды бота
     */
    public function getCommands(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMyCommands(
                    scope: $request->input('scope'),
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result', []),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении команд'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить команды бота
     */
    public function deleteCommands(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->deleteMyCommands(
                    scope: $request->input('scope'),
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Команды успешно удалены',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при удалении команд'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить описание бота
     */
    public function setDescription(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:512',
            'language_code' => 'sometimes|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->setMyDescription(
                    description: $request->description,
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Описание успешно установлено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке описания'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить описание бота
     */
    public function getDescription(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMyDescription(
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении описания'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить краткое описание бота
     */
    public function setShortDescription(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'short_description' => 'required|string|max:120',
            'language_code' => 'sometimes|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->setMyShortDescription(
                    shortDescription: $request->short_description,
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Краткое описание успешно установлено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке краткого описания'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить краткое описание бота
     */
    public function getShortDescription(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMyShortDescription(
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении краткого описания'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить имя бота
     */
    public function setName(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:64',
            'language_code' => 'sometimes|string|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->setMyName(
                    name: $request->name,
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Имя успешно установлено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке имени'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить имя бота
     */
    public function getName(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMyName(
                    languageCode: $request->input('language_code')
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении имени'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить фото бота
     */
    public function setPhoto(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'photo' => 'required|file|image|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $photoPath = $request->file('photo')->getRealPath();
            $response = $this->telegraph
                ->bot($bot)
                ->setMyPhoto($photoPath)
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Фото успешно установлено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке фото'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить фото бота
     */
    public function deletePhoto($id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->deleteMyPhoto()
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Фото успешно удалено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при удалении фото'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить меню бота
     */
    public function setMenuButton(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'menu_button' => 'required|array',
            'menu_button.type' => 'required|string|in:commands,web_app,default',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->setMyMenuButton(
                    menuButton: $request->menu_button
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Меню успешно установлено',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке меню'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить меню бота
     */
    public function getMenuButton($id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getMyMenuButton()
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении меню'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Установить webhook
     */
    public function setWebhook(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        // Преобразуем drop_pending_updates в boolean, если это строка
        $data = $request->all();
        if (isset($data['drop_pending_updates'])) {
            $value = $data['drop_pending_updates'];
            // Если это уже boolean, оставляем как есть
            if (is_bool($value)) {
                $data['drop_pending_updates'] = $value;
            } 
            // Если это строка "true" или "false", преобразуем
            elseif (is_string($value)) {
                $value = strtolower(trim($value));
                $data['drop_pending_updates'] = in_array($value, ['true', '1', 'yes', 'on'], true);
            }
            // Если это число, преобразуем в boolean
            elseif (is_numeric($value)) {
                $data['drop_pending_updates'] = (bool) $value;
            }
            // Во всех остальных случаях - false
            else {
                $data['drop_pending_updates'] = false;
            }
        }

        $validator = Validator::make($data, [
            'url' => 'required|url',
            'certificate' => 'sometimes|file|mimes:pem',
            'ip_address' => 'sometimes|ip',
            'max_connections' => 'sometimes|integer|min:1|max:100',
            'allowed_updates' => 'sometimes|array',
            'drop_pending_updates' => 'sometimes|boolean',
            'secret_token' => 'sometimes|string|max:256',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $certificatePath = null;
            if (isset($data['certificate']) && $request->hasFile('certificate')) {
                $certificatePath = $request->file('certificate')->getRealPath();
            }

            $response = $this->telegraph
                ->bot($bot)
                ->setWebhook(
                    url: $data['url'],
                    certificate: $certificatePath,
                    ipAddress: $data['ip_address'] ?? null,
                    maxConnections: $data['max_connections'] ?? null,
                    allowedUpdates: $data['allowed_updates'] ?? null,
                    dropPendingUpdates: $data['drop_pending_updates'] ?? false,
                    secretToken: $data['secret_token'] ?? null
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook успешно установлен',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при установке webhook'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить webhook
     */
    public function deleteWebhook(Request $request, $id)
    {
        $bot = TelegraphBot::findOrFail($id);

        // Преобразуем drop_pending_updates в boolean, если это строка
        $dropPendingUpdates = $request->input('drop_pending_updates', false);
        if (is_string($dropPendingUpdates)) {
            $dropPendingUpdates = filter_var(
                $dropPendingUpdates,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if ($dropPendingUpdates === null) {
                $dropPendingUpdates = false;
            }
        }

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->deleteWebhook(
                    dropPendingUpdates: $dropPendingUpdates
                )
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook успешно удален',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при удалении webhook'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить информацию о webhook
     */
    public function getWebhookInfo($id)
    {
        $bot = TelegraphBot::findOrFail($id);

        try {
            $response = $this->telegraph
                ->bot($bot)
                ->getWebhookInfo()
                ->send();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('result'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response->json('description', 'Ошибка при получении информации о webhook'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}


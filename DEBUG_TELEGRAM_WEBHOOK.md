# Отладка Telegram Webhook

## Проблема: команда /test_server не отвечает

## Проверка на сервере:

### 1. Убедитесь, что код обновился

```bash
cd ~/essens/public_html
git pull origin master

# Скопируйте обновленный контроллер
cp ~/essens/app/Http/Controllers/Api/TelegramWebhookController.php app/Http/Controllers/Api/TelegramWebhookController.php

# Обновите автозагрузчик
php8.2 $(which composer) dump-autoload

# Очистите кеш
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache
```

### 2. Проверьте логи

```bash
# Просмотрите последние логи
tail -n 100 storage/logs/laravel.log | grep -i "telegram\|webhook\|test_server"

# Или все логи webhook
tail -n 200 storage/logs/laravel.log | grep -A 5 -B 5 "Telegram webhook"
```

### 3. Проверьте, что webhook установлен правильно

```bash
# Используйте команду тестирования
php8.2 artisan telegram:test --bot-id=1
```

### 4. Проверьте, что роут доступен

```bash
# Проверьте роуты
php8.2 artisan route:list | grep telegram
```

Должен быть роут:
- `POST api/telegram/webhook/{token}`

### 5. Проверьте вручную отправку webhook

```bash
# Получите токен бота из базы данных
php8.2 artisan tinker
```

В tinker:
```php
$bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);
echo $bot->token;
exit
```

Затем проверьте webhook вручную:
```bash
curl -X POST https://essens-store.ru/api/telegram/webhook/YOUR_TOKEN \
  -H "Content-Type: application/json" \
  -d '{
    "update_id": 123,
    "message": {
      "message_id": 1,
      "from": {"id": 123456789, "is_bot": false, "first_name": "Test"},
      "chat": {"id": 123456789, "type": "private"},
      "date": 1234567890,
      "text": "/test_server"
    }
  }'
```

### 6. Проверьте права доступа

```bash
# Убедитесь, что Laravel может писать логи
chmod -R 775 storage
chown -R dsc23ytp:dsc23ytp storage
```

## Что должно быть в логах:

При отправке команды `/test_server` должны появиться записи:
1. `Telegram webhook received` - получено обновление
2. `Telegram message received` - получено сообщение
3. `Processing /test_server command` - обработка команды
4. `Test server command response sent successfully` - ответ отправлен

Если какой-то из этих логов отсутствует, значит проблема на соответствующем этапе.


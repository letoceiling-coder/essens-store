# Исправление проблемы с Deploy Endpoint

## Что было исправлено

1. ✅ Улучшена обработка ошибок в `DeployController` - теперь всегда возвращает JSON
2. ✅ Добавлена глобальная обработка исключений для API роутов в `bootstrap/app.php`
3. ✅ Добавлено детальное логирование ошибок

## Что нужно сделать на сервере

### 1. Обновите код из Git

```bash
cd ~/essens
git pull origin master
```

### 2. Очистите и закешируйте конфигурацию

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache
```

### 3. Проверьте логи (если endpoint все еще не работает)

```bash
tail -n 100 storage/logs/laravel.log
```

### 4. Протестируйте endpoint снова

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"secret":"123123123","branch":"master"}' \
  -v
```

Флаг `-v` покажет полный HTTP ответ, включая заголовки.

### 5. Если все еще возвращается HTML

Проверьте конфигурацию веб-сервера (nginx/apache). Убедитесь, что:
- Запросы к `/api/*` правильно проксируются в Laravel
- Не настроен редирект на другую страницу
- Не блокируется POST запросы

### 6. Альтернативный тест через PHP CLI

Создайте файл `test-deploy.php` в корне проекта:

```php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/deploy', 'POST', [
    'secret' => '123123123',
    'branch' => 'master'
], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'CONTENT_TYPE' => 'application/json',
]);

$response = $kernel->handle($request);
echo $response->getContent() . "\n";
$kernel->terminate($request, $response);
```

Запустите:
```bash
php8.2 test-deploy.php
```

Если это работает, значит проблема в конфигурации веб-сервера.

## Ожидаемый ответ

Должен вернуться JSON:
```json
{
  "success": true,
  "message": "Развертывание выполнено успешно",
  "status": "completed",
  "branch": "master",
  "has_changes": false,
  "pull_output": "..."
}
```

Или при ошибке:
```json
{
  "success": false,
  "message": "Описание ошибки",
  "error_type": "Exception"
}
```


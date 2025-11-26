# Диагностика проблемы с Deploy Endpoint

## Проблема
Endpoint возвращает HTML вместо JSON.

## Возможные причины

### 1. Проверьте логи Laravel на сервере

```bash
cd ~/essens
tail -n 50 storage/logs/laravel.log
```

Ищите ошибки, связанные с DeployController или маршрутизацией.

### 2. Проверьте, что роут действительно зарегистрирован

```bash
php8.2 artisan route:list | grep deploy
```

Должны увидеть:
- `POST api/deploy`
- `POST deploy`

### 3. Проверьте, что контроллер загружается

```bash
php8.2 artisan tinker
```

В tinker выполните:
```php
class_exists(\App\Http\Controllers\DeployController::class);
// Должно вернуть true
```

### 4. Проверьте конфигурацию веб-сервера

Убедитесь, что запросы к `/api/deploy` правильно проксируются в Laravel.

### 5. Попробуйте прямой запрос через PHP

Создайте тестовый файл `test-deploy.php` в корне проекта:

```php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/deploy', 'POST', [
    'secret' => '123123123',
    'branch' => 'master'
]);

$response = $kernel->handle($request);
echo $response->getContent();
$kernel->terminate($request, $response);
```

Запустите:
```bash
php8.2 test-deploy.php
```

### 6. Проверьте формат запроса

Убедитесь, что отправляете правильный Content-Type:

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"secret":"123123123","branch":"master"}' \
  -v
```

Флаг `-v` покажет полный ответ сервера.

### 7. Проверьте, что .env настроен правильно

```bash
cd ~/essens
grep DEPLOY_SECRET .env
```

Должно быть:
```
DEPLOY_SECRET=123123123
```

### 8. Очистите кеш роутов

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache
```

### 9. Проверьте права доступа

```bash
chmod -R 775 storage bootstrap/cache
chown -R dsc23ytp:dsc23ytp storage bootstrap/cache
```

## Быстрое решение

Если ничего не помогает, попробуйте добавить явную обработку ошибок в контроллер.


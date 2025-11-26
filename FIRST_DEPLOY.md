# Первое развертывание на сервер

## Проблема

Endpoint `/api/deploy` еще не существует на сервере, потому что код с контроллером `DeployController` еще не загружен на сервер.

## Решение: Первое развертывание вручную

### Вариант 1: Через SSH (рекомендуется)

1. Подключитесь к серверу по SSH:
```bash
ssh user@essens-store.ru
```

2. Перейдите в директорию проекта:
```bash
cd /path/to/your/project
```

3. Если Git репозиторий еще не настроен:
```bash
git init
git remote add origin https://github.com/letoceiling-coder/essens-store.git
git fetch origin
git checkout master
git pull origin master
```

4. Если Git уже настроен, просто обновите код:
```bash
git fetch origin
git pull origin master
```

5. Установите зависимости:
```bash
php8.2 composer.phar install --no-dev --optimize-autoloader
# или если composer в PATH:
composer install --no-dev --optimize-autoloader
```

6. Настройте `.env`:
```bash
nano .env
# Добавьте:
DEPLOY_SECRET=123123123
PHP_EXECUTABLE=php8.2
```

7. Очистите и закешируйте конфигурацию:
```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

8. Выполните миграции (если нужно):
```bash
php8.2 artisan migrate --force
```

9. Проверьте, что роут работает:
```bash
php8.2 artisan route:list | grep deploy
```

Должны увидеть:
```
POST      api/deploy ................... deploy › DeployController@deploy
POST      deploy ...................... deploy › DeployController@deploy
```

### Вариант 2: Через FTP/SFTP

1. Загрузите все файлы проекта на сервер через FTP/SFTP
2. Выполните шаги 5-9 из Варианта 1 через SSH

### Вариант 3: Использование скрипта настройки

Если у вас есть доступ к серверу, выполните:

```bash
cd /path/to/your/project
bash server-setup.sh
```

Скрипт автоматически выполнит все необходимые шаги.

## После первого развертывания

После того, как код загружен на сервер и endpoint `/api/deploy` работает, вы сможете использовать автоматическое развертывание:

```bash
php artisan set-deploy --force --no-ssl-verify --secret=123123123
```

## Проверка работы endpoint

После загрузки кода проверьте, что endpoint работает:

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret":"123123123","branch":"master"}'
```

Должен вернуться JSON ответ:
```json
{
  "success": true,
  "message": "Развертывание выполнено успешно",
  "status": "completed",
  "branch": "master",
  "has_changes": false
}
```

## Важные замечания

1. **Безопасность**: Используйте более сложный `DEPLOY_SECRET`, чем `123123123`
2. **Права доступа**: Убедитесь, что веб-сервер имеет права на выполнение git команд
3. **PHP версия**: Убедитесь, что `php8.2` доступен в PATH или укажите полный путь в `PHP_EXECUTABLE`


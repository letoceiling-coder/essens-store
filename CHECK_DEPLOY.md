# Проверка работы Deploy на сервере

## Текущая ситуация

Git репозиторий настроен правильно в `~/essens`. Есть неотслеживаемые файлы - это нормально, если они не должны быть в репозитории.

## Проверка работы Deploy

### 1. Проверьте, что код с DeployController загружен:

```bash
# Проверьте наличие контроллера
ls -la app/Http/Controllers/DeployController.php

# Проверьте наличие роутов
cat routes/api.php | grep -i deploy
cat routes/web.php | grep -i deploy
```

### 2. Проверьте роуты через artisan:

```bash
php8.2 artisan route:list | grep deploy
```

Должны увидеть:
```
POST   api/deploy  ................... deploy › DeployController@deploy
POST   deploy  ...................... deploy › DeployController@deploy
```

### 3. Если роутов нет, очистите кеш:

```bash
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan cache:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
```

### 4. Проверьте .env:

```bash
# Проверьте DEPLOY_SECRET
grep DEPLOY_SECRET .env

# Проверьте PHP_EXECUTABLE
grep PHP_EXECUTABLE .env
```

### 5. Протестируйте endpoint:

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret":"123123123","branch":"master"}'
```

Должен вернуться JSON ответ с `"success": true`.

## Если роутов нет

Если команда `php8.2 artisan route:list | grep deploy` ничего не показывает:

1. Убедитесь, что код загружен:
```bash
git pull origin master
```

2. Проверьте, что файлы на месте:
```bash
ls -la app/Http/Controllers/DeployController.php
ls -la routes/api.php
ls -la routes/web.php
```

3. Очистите кеш и пересоздайте:
```bash
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan view:clear
php8.2 artisan cache:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

4. Проверьте снова:
```bash
php8.2 artisan route:list | grep deploy
```

## Неотслеживаемые файлы

Файлы в списке `git status` (12/, 26112025/, 270625/, C/, all/, new_folder/, public_html/) - это нормально, если они:
- Не должны быть в репозитории
- Уже добавлены в .gitignore

Если `public_html/` - это ваша публичная директория, она должна быть в .gitignore или быть символической ссылкой на `public/`.


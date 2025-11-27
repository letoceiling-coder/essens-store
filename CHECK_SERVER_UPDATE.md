# Проверка обновления на сервере

## Проблема

Ошибка валидации все еще возникает, возможно код не обновился на сервере.

## Что нужно проверить на сервере:

```bash
# 1. Перейдите в рабочую директорию
cd ~/essens/public_html

# 2. Обновите код из Git
git pull origin master

# 3. Скопируйте обновленные файлы контроллера
cp ~/essens/app/Http/Controllers/Api/Admin/TelegramBotController.php app/Http/Controllers/Api/Admin/TelegramBotController.php

# 4. Проверьте, что изменения есть в файле
grep -A 10 "Преобразуем drop_pending_updates" app/Http/Controllers/Api/Admin/TelegramBotController.php

# 5. Обновите автозагрузчик
php8.2 $(which composer) dump-autoload

# 6. Очистите кеш
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache

# 7. Проверьте версию файла (должна быть строка с улучшенной обработкой)
head -n 750 app/Http/Controllers/Api/Admin/TelegramBotController.php | tail -n 30
```

## Если файл не обновился:

```bash
# Проверьте, есть ли изменения в Git репозитории
cd ~/essens
git log --oneline -5
git show HEAD:app/Http/Controllers/Api/Admin/TelegramBotController.php | grep -A 10 "Преобразуем drop_pending_updates"
```

## Альтернативное решение - использовать JSON вместо FormData

Можно изменить отправку данных на фронтенде, чтобы использовать JSON вместо FormData, тогда boolean значения будут передаваться правильно.


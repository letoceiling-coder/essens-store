#!/bin/bash
# Скрипт для автоматического обновления проекта на сервере Beget
# Этот скрипт должен выполняться от имени пользователя, который владеет репозиторием

cd /home/d/dsc23ytp/essens/public_html || exit 1

# Используем переменную окружения для обхода проблемы с правами
export GIT_SAFE_DIRECTORY=/home/d/dsc23ytp/essens/public_html
export GIT_CONFIG_NOSYSTEM=1

# Убеждаемся, что локальный git config настроен
git config --local safe.directory /home/d/dsc23ytp/essens/public_html 2>/dev/null

# Обновление из git
git pull origin master || exit 1

# Установка зависимостей
if command -v composer &> /dev/null; then
    composer install --no-interaction --prefer-dist --optimize-autoloader || exit 1
fi

if command -v npm &> /dev/null; then
    npm install || exit 1
    npm run build || exit 1
fi

# Выполнение миграций
php artisan migrate --force || exit 1

# Очистка кеша
php artisan optimize:clear || exit 1

echo "Deployment successful"


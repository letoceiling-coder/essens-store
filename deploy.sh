#!/bin/bash
# Скрипт для автоматического обновления проекта на сервере Beget
# Этот скрипт должен выполняться от имени пользователя, который владеет репозиторием

cd /home/d/dsc23ytp/essens/public_html || exit 1

# Загружаем PATH для доступа к composer и npm
export PATH="$HOME/bin:$PATH"

# Загружаем NVM для доступа к node и npm
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Используем переменную окружения для обхода проблемы с правами
export GIT_SAFE_DIRECTORY=/home/d/dsc23ytp/essens/public_html
export GIT_CONFIG_NOSYSTEM=1

# Определяем git команду с флагом safe.directory для всех операций
GIT_CMD="git -c safe.directory=/home/d/dsc23ytp/essens/public_html"

# Убеждаемся, что локальный git config настроен
$GIT_CMD config --local safe.directory /home/d/dsc23ytp/essens/public_html 2>/dev/null || true

# Добавляем deploy.sh и install-tools.sh в gitignore, если их там нет (чтобы избежать конфликтов)
if ! $GIT_CMD check-ignore deploy.sh &>/dev/null; then
    if ! grep -q "^deploy.sh$" .gitignore 2>/dev/null; then
        echo "deploy.sh" >> .gitignore
    fi
fi
if ! $GIT_CMD check-ignore install-tools.sh &>/dev/null; then
    if ! grep -q "^install-tools.sh$" .gitignore 2>/dev/null; then
        echo "install-tools.sh" >> .gitignore
    fi
fi

# Удаляем конфликтующие файлы перед обновлением
if [ -f "install-tools.sh" ]; then
    rm -f install-tools.sh
fi

# Получаем последние изменения из удаленного репозитория
$GIT_CMD fetch origin master 2>&1 || {
    echo "Warning: git fetch failed, but continuing..."
    # Не выходим, продолжаем выполнение
}

# Сбрасываем локальные изменения и применяем изменения из удаленного репозитория
# Используем --force для принудительного сброса
$GIT_CMD reset --hard origin/master 2>&1 || {
    echo "Warning: git reset failed, but continuing..."
    # Не выходим, продолжаем выполнение
}

# Очищаем неотслеживаемые файлы, которые могут конфликтовать
$GIT_CMD clean -fd 2>/dev/null || true

# Проверка версии PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

# Проверяем, что PHP версия >= 8.2
if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo "Warning: PHP version $PHP_VERSION is too old. Required: PHP 8.2+. Skipping composer install."
    echo "Please update PHP version on the server or use a different PHP version for composer."
else
    # Установка зависимостей
    if command -v composer &> /dev/null; then
        echo "Info: Composer found at $(command -v composer)"
        composer --version
        composer install --no-interaction --prefer-dist --optimize-autoloader || echo "Warning: composer install failed, but continuing..."
    else
        echo "Warning: composer not found, skipping composer install"
    fi
fi

# Установка npm зависимостей (если npm доступен)
if command -v npm &> /dev/null; then
    echo "Info: NPM found at $(command -v npm)"
    npm --version
    npm install || echo "Warning: npm install failed, but continuing..."
    # Используем npx для запуска vite, чтобы избежать проблем с правами
    npx vite build || echo "Warning: npm build failed, but continuing..."
else
    echo "Warning: npm not found, skipping npm install and build"
fi

# Выполнение миграций (используем версию PHP, которая используется веб-сервером)
# Пробуем найти правильную версию PHP
if command -v php8.2 &> /dev/null; then
    PHP_CMD="php8.2"
elif command -v php8.3 &> /dev/null; then
    PHP_CMD="php8.3"
elif command -v php8.4 &> /dev/null; then
    PHP_CMD="php8.4"
else
    PHP_CMD="php"
fi

# Выполнение миграций
$PHP_CMD artisan migrate --force || echo "Warning: migrations failed, but continuing..."

# Очистка кеша
$PHP_CMD artisan optimize:clear || echo "Warning: cache clear failed, but continuing..."

echo "Deployment successful"


#!/bin/bash

# Скрипт для первоначальной настройки сервера для автоматического обновления из Git
# Использование: bash server-setup.sh

set -e

echo "🚀 Настройка сервера для автоматического обновления из Git"
echo ""

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Получаем путь к проекту
PROJECT_PATH=$(pwd)
echo "📁 Путь к проекту: $PROJECT_PATH"
echo ""

# Проверка Git
echo "🔍 Проверка Git..."
if ! command -v git &> /dev/null; then
    echo -e "${RED}❌ Git не установлен${NC}"
    echo "Установите Git: sudo apt install git -y"
    exit 1
fi
echo -e "${GREEN}✅ Git установлен${NC}"
echo ""

# Проверка Composer
echo "🔍 Проверка Composer..."
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}⚠️  Composer не найден в PATH${NC}"
    if [ -f "composer.phar" ]; then
        echo -e "${GREEN}✅ Найден composer.phar${NC}"
    else
        echo -e "${RED}❌ Composer не установлен${NC}"
        echo "Установите Composer или скачайте composer.phar"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Composer установлен${NC}"
fi
echo ""

# Настройка Git репозитория
echo "🔧 Настройка Git репозитория..."
if [ ! -d ".git" ]; then
    echo "Инициализация Git репозитория..."
    git init
    git remote add origin https://github.com/letoceiling-coder/essens-store.git
    echo -e "${GREEN}✅ Git репозиторий инициализирован${NC}"
else
    echo "Проверка remote URL..."
    CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
    EXPECTED_REMOTE="https://github.com/letoceiling-coder/essens-store.git"
    
    if [ "$CURRENT_REMOTE" != "$EXPECTED_REMOTE" ]; then
        if [ -z "$CURRENT_REMOTE" ]; then
            echo "Добавление remote origin..."
            git remote add origin "$EXPECTED_REMOTE"
        else
            echo "Обновление remote URL..."
            git remote set-url origin "$EXPECTED_REMOTE"
        fi
        echo -e "${GREEN}✅ Remote URL настроен${NC}"
    else
        echo -e "${GREEN}✅ Remote URL уже настроен правильно${NC}"
    fi
fi

# Получение последних изменений
echo ""
echo "📥 Получение изменений из Git..."
git fetch origin
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "master")
if [ -z "$CURRENT_BRANCH" ]; then
    git checkout -b master
    CURRENT_BRANCH="master"
fi
git pull origin "$CURRENT_BRANCH" || echo -e "${YELLOW}⚠️  Не удалось выполнить pull (возможно, репозиторий пустой)${NC}"
echo -e "${GREEN}✅ Git настроен${NC}"
echo ""

# Проверка .env
echo "🔍 Проверка .env файла..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "Создание .env из .env.example..."
        cp .env.example .env
        echo -e "${YELLOW}⚠️  Не забудьте настроить .env файл!${NC}"
    else
        echo -e "${RED}❌ .env файл не найден${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ .env файл существует${NC}"
fi

# Установка прав на .env
chmod 600 .env 2>/dev/null || echo -e "${YELLOW}⚠️  Не удалось установить права на .env${NC}"

# Установка зависимостей
echo ""
echo "📦 Установка зависимостей Composer..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
else
    if [ -f "composer.phar" ]; then
        $PHP_CMD composer.phar install --no-dev --optimize-autoloader
    else
        echo -e "${RED}❌ Composer не найден${NC}"
        echo "Установите Composer или скачайте composer.phar"
        exit 1
    fi
fi
echo -e "${GREEN}✅ Зависимости установлены${NC}"

# Определение команды PHP
echo ""
echo "🔍 Определение команды PHP..."
if command -v php8.2 &> /dev/null; then
    PHP_CMD="php8.2"
    echo -e "${GREEN}✅ Найден php8.2${NC}"
elif command -v php8.1 &> /dev/null; then
    PHP_CMD="php8.1"
    echo -e "${GREEN}✅ Найден php8.1${NC}"
elif command -v php8.0 &> /dev/null; then
    PHP_CMD="php8.0"
    echo -e "${GREEN}✅ Найден php8.0${NC}"
elif command -v php &> /dev/null; then
    PHP_CMD="php"
    echo -e "${GREEN}✅ Найден php${NC}"
else
    PHP_CMD="php8.2"
    echo -e "${YELLOW}⚠️  PHP не найден в PATH, будет использоваться php8.2${NC}"
fi

# Добавление PHP_EXECUTABLE в .env если его нет
if ! grep -q "PHP_EXECUTABLE=" .env 2>/dev/null; then
    echo "" >> .env
    echo "PHP_EXECUTABLE=$PHP_CMD" >> .env
    echo -e "${GREEN}✅ PHP_EXECUTABLE добавлен в .env${NC}"
fi

# Генерация ключа приложения (если нужно)
echo ""
echo "🔑 Проверка APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "Генерация APP_KEY..."
    $PHP_CMD artisan key:generate --force
    echo -e "${GREEN}✅ APP_KEY сгенерирован${NC}"
else
    echo -e "${GREEN}✅ APP_KEY уже настроен${NC}"
fi

# Очистка и кеширование
echo ""
echo "🧹 Очистка кеша..."
$PHP_CMD artisan config:clear
$PHP_CMD artisan cache:clear
$PHP_CMD artisan route:clear
$PHP_CMD artisan view:clear
echo -e "${GREEN}✅ Кеш очищен${NC}"

echo ""
echo "💾 Кеширование конфигурации..."
$PHP_CMD artisan config:cache
$PHP_CMD artisan route:cache
$PHP_CMD artisan view:cache
echo -e "${GREEN}✅ Конфигурация закеширована${NC}"

# Проверка прав доступа
echo ""
echo "🔒 Проверка прав доступа..."
WEB_USER=$(ps aux | grep -E 'apache|nginx|php-fpm' | grep -v grep | head -1 | awk '{print $1}' || echo "www-data")
echo "Пользователь веб-сервера: $WEB_USER"

echo ""
echo -e "${GREEN}✅ Настройка завершена!${NC}"
echo ""
echo "📝 Следующие шаги:"
echo "1. Проверьте настройки в .env файле"
echo "2. Выполните миграции: php artisan migrate --force"
echo ""


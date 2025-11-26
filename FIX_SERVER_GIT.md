# Исправление настройки Git на сервере

## Проблема

Вы инициализировали Git в `public_html`, но это неправильная директория. 
`public_html` - это публичная директория (аналог `public/` в Laravel), а Git должен быть в корне проекта.

## Решение

### Шаг 1: Перейдите в правильную директорию

```bash
# Выйдите из public_html
cd ~/essens

# Проверьте структуру
ls -la
```

Должны увидеть:
- `app/`
- `config/`
- `routes/`
- `composer.json`
- `artisan`
- `public_html/` или `public/`

### Шаг 2: Удалите неправильный Git репозиторий из public_html (опционально)

```bash
# Если хотите удалить Git из public_html
rm -rf ~/essens/public_html/.git
```

### Шаг 3: Инициализируйте Git в правильной директории

```bash
# Убедитесь, что вы в ~/essens
cd ~/essens

# Инициализируйте Git
git init

# Исправьте проблему с правами доступа (если нужно)
git config --global --add safe.directory /home/d/dsc23ytp/essens

# Добавьте remote
git remote add origin https://github.com/letoceiling-coder/essens-store.git

# Получите код
git fetch origin
git checkout -b master origin/master
```

### Шаг 4: Если возникла ошибка с правами доступа

```bash
# Добавьте текущую директорию в безопасные
git config --global --add safe.directory /home/d/dsc23ytp/essens

# Или добавьте все поддиректории пользователя
git config --global --add safe.directory '*'
```

### Шаг 5: Настройте .env

```bash
# Если .env нет
cp .env.example .env

# Откройте для редактирования
nano .env

# Добавьте:
DEPLOY_SECRET=123123123
PHP_EXECUTABLE=php8.2
```

### Шаг 6: Установите зависимости и настройте Laravel

```bash
# Установка зависимостей
php8.2 composer.phar install --no-dev --optimize-autoloader

# Генерация ключа (если нужно)
php8.2 artisan key:generate

# Очистка и кеширование
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan cache:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
```

### Шаг 7: Проверьте роуты

```bash
php8.2 artisan route:list | grep deploy
```

## Быстрая команда для исправления

Выполните все команды последовательно:

```bash
# 1. Перейдите в корневую директорию
cd ~/essens

# 2. Удалите Git из public_html (если создали там)
rm -rf ~/essens/public_html/.git

# 3. Инициализируйте Git в правильной директории
git init
git config --global --add safe.directory /home/d/dsc23ytp/essens
git remote add origin https://github.com/letoceiling-coder/essens-store.git
git fetch origin
git checkout -b master origin/master

# 4. Настройте .env
echo "DEPLOY_SECRET=123123123" >> .env
echo "PHP_EXECUTABLE=php8.2" >> .env

# 5. Установите зависимости
php8.2 composer.phar install --no-dev --optimize-autoloader

# 6. Настройте Laravel
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
```

## Проверка

После выполнения команд проверьте:

```bash
# Проверьте Git
git status

# Проверьте remote
git remote -v

# Проверьте роуты
php8.2 artisan route:list | grep deploy
```


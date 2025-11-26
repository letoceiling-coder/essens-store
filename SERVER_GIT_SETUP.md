# Настройка Git на сервере

## Проблема

Вы находитесь в директории `public_html`, но Git репозиторий должен быть в корневой директории проекта Laravel.

## Решение

### Шаг 1: Определите правильную директорию проекта

Обычно структура хостинга выглядит так:
```
~/essens/
  ├── public_html/     <- это public/ в Laravel (текущая директория)
  ├── app/
  ├── config/
  ├── routes/
  ├── vendor/
  └── .env
```

Нужно перейти в родительскую директорию:
```bash
cd ~/essens
# или
cd ..
```

### Шаг 2: Проверьте структуру

Убедитесь, что вы в правильной директории:
```bash
ls -la
```

Должны увидеть:
- `app/`
- `config/`
- `routes/`
- `composer.json`
- `artisan`
- `public_html/` или `public/`

### Шаг 3: Инициализируйте Git репозиторий

```bash
# Если репозитория нет
git init

# Добавьте remote
git remote add origin https://github.com/letoceiling-coder/essens-store.git

# Или если remote уже есть, проверьте его
git remote -v

# Если remote неправильный, обновите:
git remote set-url origin https://github.com/letoceiling-coder/essens-store.git
```

### Шаг 4: Получите код из репозитория

```bash
# Получите информацию о ветках
git fetch origin

# Переключитесь на master и получите код
git checkout -b master origin/master

# Или если ветка уже существует:
git checkout master
git pull origin master
```

### Шаг 5: Настройте .env

```bash
# Если .env нет, создайте из примера
cp .env.example .env

# Откройте для редактирования
nano .env

# Добавьте или обновите:
DEPLOY_SECRET=123123123
PHP_EXECUTABLE=php8.2
```

### Шаг 6: Установите зависимости

```bash
php8.2 composer.phar install --no-dev --optimize-autoloader
# или
composer install --no-dev --optimize-autoloader
```

### Шаг 7: Настройте Laravel

```bash
# Генерация ключа (если нужно)
php8.2 artisan key:generate

# Очистка кеша
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan cache:clear
php8.2 artisan view:clear

# Кеширование
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

### Шаг 8: Проверьте роуты

```bash
php8.2 artisan route:list | grep deploy
```

Должны увидеть:
```
POST   api/deploy  ................... deploy › DeployController@deploy
POST   deploy  ...................... deploy › DeployController@deploy
```

## Если структура другая

Если у вас другая структура (например, Laravel установлен в `public_html`), то:

```bash
# Останьтесь в public_html
cd ~/essens/public_html

# Инициализируйте Git там
git init
git remote add origin https://github.com/letoceiling-coder/essens-store.git
git fetch origin
git checkout -b master origin/master
```

## Проверка правильной директории

Чтобы убедиться, что вы в правильной директории, проверьте наличие файла `artisan`:

```bash
ls -la artisan
```

Если файл найден - вы в корне проекта Laravel.

## Быстрая команда для проверки

```bash
# Проверьте, где находится artisan
find ~ -name "artisan" -type f 2>/dev/null | head -1

# Перейдите в эту директорию
cd $(dirname $(find ~ -name "artisan" -type f 2>/dev/null | head -1))
```


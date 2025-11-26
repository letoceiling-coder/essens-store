# Исправление проблемы с public_html

## Проблема

Веб-сервер работает из `~/essens/public_html`, а файл контроллера находится в `~/essens/app/Http/Controllers/DeployController.php`.

В стек-трейсе видно:
- `/home/d/dsc23ytp/essens/public_html/vendor/...`
- `/home/d/dsc23ytp/essens/public_html/app/...`

Это означает, что Laravel запускается из `public_html`, а не из корня проекта.

## Решение

### Вариант 1: Скопировать файл контроллера в public_html

```bash
# Проверьте структуру
ls -la ~/essens/public_html/app/Http/Controllers/

# Если директория существует, скопируйте файл
cp ~/essens/app/Http/Controllers/DeployController.php ~/essens/public_html/app/Http/Controllers/DeployController.php

# Обновите автозагрузчик в public_html
cd ~/essens/public_html
php8.2 $(which composer) dump-autoload
```

### Вариант 2: Создать симлинк (если поддерживается)

```bash
ln -s ~/essens/app/Http/Controllers/DeployController.php ~/essens/public_html/app/Http/Controllers/DeployController.php
```

### Вариант 3: Правильная настройка веб-сервера (рекомендуется)

Веб-сервер должен указывать на `~/essens/public` (или `~/essens/public_html`, если это синоним), но Laravel должен работать из `~/essens`.

Проверьте конфигурацию nginx/apache:
- DocumentRoot должен быть `~/essens/public` или `~/essens/public_html`
- Но Laravel должен искать файлы в `~/essens`

### Вариант 4: Синхронизировать весь проект

Если `public_html` - это рабочая директория, нужно синхронизировать весь проект:

```bash
# Синхронизируйте app директорию
rsync -av ~/essens/app/ ~/essens/public_html/app/

# Или используйте git pull в public_html
cd ~/essens/public_html
git pull origin master
php8.2 $(which composer) dump-autoload
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache
```

## Проверка

После копирования/синхронизации проверьте:

```bash
ls -la ~/essens/public_html/app/Http/Controllers/DeployController.php
```

Файл должен существовать.


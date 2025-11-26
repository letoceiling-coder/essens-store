# Проверка структуры public_html

## Проверьте структуру:

```bash
# Проверьте, что есть в public_html
ls -la ~/essens/public_html/

# Проверьте, есть ли app директория
ls -la ~/essens/public_html/app/

# Если app нет, проверьте, что есть
ls -la ~/essens/public_html/ | head -20
```

## Если app директории нет в public_html:

Это означает, что `public_html` - это только `public` директория Laravel, а не весь проект.

В этом случае нужно:
1. Либо настроить веб-сервер правильно (указывать на `~/essens/public`)
2. Либо создать симлинк или скопировать нужные файлы

## Решение - скопировать app директорию:

```bash
# Проверьте, есть ли app в корне
ls -la ~/essens/app/Http/Controllers/DeployController.php

# Создайте app директорию в public_html, если её нет
mkdir -p ~/essens/public_html/app/Http/Controllers

# Скопируйте файл
cp ~/essens/app/Http/Controllers/DeployController.php ~/essens/public_html/app/Http/Controllers/DeployController.php

# Или скопируйте всю app директорию (если её нет)
# rsync -av ~/essens/app/ ~/essens/public_html/app/

# Обновите автозагрузчик
cd ~/essens/public_html
php8.2 $(which composer) dump-autoload
php8.2 artisan route:clear
php8.2 artisan route:cache
```


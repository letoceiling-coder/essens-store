# Исправление проблемы с командой telegram:test

## Проблема

Команда `telegram:test` не найдена на сервере.

## Решение

### 1. Обновите код и скопируйте файл команды

```bash
cd ~/essens/public_html

# Обновите код
git pull origin master

# Скопируйте файл команды
cp ~/essens/app/Console/Commands/TestTelegramBot.php app/Console/Commands/TestTelegramBot.php

# Обновите автозагрузчик
php8.2 $(which composer) dump-autoload

# Очистите кеш
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
```

### 2. Проверьте, что файл существует

```bash
ls -la app/Console/Commands/TestTelegramBot.php
```

### 3. Проверьте команду

```bash
php8.2 artisan list | grep telegram
```

Должна появиться команда `telegram:test`.

### 4. Запустите команду

```bash
php8.2 artisan telegram:test --bot-id=1
```

## Альтернатива: проверка webhook напрямую

Если команда все еще не работает, можно проверить webhook напрямую через логи:

```bash
# Отправьте команду /test_server боту, затем проверьте логи
tail -n 100 storage/logs/laravel.log | grep -i "telegram\|test_server"
```


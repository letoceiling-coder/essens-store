# Исправление проблемы с Composer на сервере

## Проблема

`composer.phar` не найден. Нужно использовать правильную команду composer.

## Решения

### Вариант 1: Использовать composer из PATH

```bash
composer install --no-dev --optimize-autoloader
```

### Вариант 2: Найти composer.phar

```bash
# Найдите composer.phar
find ~ -name "composer.phar" 2>/dev/null

# Или в корне проекта
ls -la composer.phar

# Если найден, используйте полный путь
php8.2 /path/to/composer.phar install --no-dev --optimize-autoloader
```

### Вариант 3: Скачать composer.phar

```bash
cd ~/essens
php8.2 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php8.2 composer-setup.php
php8.2 -r "unlink('composer-setup.php');"

# Теперь используйте
php8.2 composer.phar install --no-dev --optimize-autoloader
```

### Вариант 4: Установить composer глобально

```bash
# Скачать и установить composer глобально
curl -sS https://getcomposer.org/installer | php8.2
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Теперь можно использовать просто
composer install --no-dev --optimize-autoloader
```

## После установки зависимостей

```bash
# Очистите и закешируйте конфигурацию
php8.2 artisan config:clear
php8.2 artisan route:clear
php8.2 artisan cache:clear
php8.2 artisan config:cache
php8.2 artisan route:cache

# Проверьте роуты
php8.2 artisan route:list | grep deploy
```


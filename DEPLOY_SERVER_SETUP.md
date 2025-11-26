# Инструкция по настройке сервера для автоматического обновления из Git

## Шаг 1: Установка Git (если не установлен)

```bash
# Для Ubuntu/Debian
sudo apt update
sudo apt install git -y

# Для CentOS/RHEL
sudo yum install git -y
```

## Шаг 2: Настройка Git репозитория на сервере

1. Перейдите в директорию проекта:
```bash
cd /path/to/your/project
```

2. Проверьте, есть ли уже Git репозиторий:
```bash
git status
```

3. Если репозитория нет, инициализируйте его:
```bash
git init
git remote add origin https://github.com/letoceiling-coder/essens-store.git
```

4. Если репозиторий уже есть, проверьте remote:
```bash
git remote -v
```

5. Если remote неправильный, обновите его:
```bash
git remote set-url origin https://github.com/letoceiling-coder/essens-store.git
```

6. Получите код из репозитория:
```bash
git fetch origin
git checkout master
git pull origin master
```

## Шаг 3: Настройка переменных окружения

1. Откройте файл `.env`:
```bash
nano .env
# или
vim .env
```

2. Добавьте или обновите переменные:
```env
DEPLOY_SECRET=your_very_secret_key_here_make_it_long_and_random
PHP_EXECUTABLE=php8.2
```

**Важно:** 
- Используйте длинный случайный ключ для `DEPLOY_SECRET`!
- `PHP_EXECUTABLE` указывает путь к исполняемому файлу PHP (по умолчанию будет искать php8.2 автоматически)

3. Сохраните файл и очистите кеш конфигурации:
```bash
php8.2 artisan config:clear
php8.2 artisan config:cache
```

## Шаг 4: Настройка прав доступа

Убедитесь, что веб-сервер имеет права на выполнение git команд:

```bash
# Проверьте пользователя веб-сервера (обычно www-data или nginx)
ps aux | grep -E 'apache|nginx|php-fpm'

# Дайте права на выполнение git (если нужно)
sudo chown -R www-data:www-data /path/to/your/project
sudo chmod -R 755 /path/to/your/project

# Для git операций может потребоваться
sudo chmod +x /usr/bin/git
```

## Шаг 5: Настройка SSH ключей для GitHub (если используется SSH)

Если вы используете SSH для доступа к GitHub:

1. Создайте SSH ключ (если его нет):
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
```

2. Добавьте публичный ключ в GitHub:
```bash
cat ~/.ssh/id_ed25519.pub
# Скопируйте вывод и добавьте в GitHub Settings > SSH and GPG keys
```

3. Проверьте подключение:
```bash
ssh -T git@github.com
```

## Шаг 6: Настройка HTTPS доступа к GitHub (альтернатива SSH)

Если используете HTTPS, может потребоваться настроить credentials:

```bash
# Настройка git для использования токена
git config --global credential.helper store

# При первом pull/push введите:
# Username: ваш_username_github
# Password: ваш_personal_access_token (не пароль!)
```

Для создания Personal Access Token:
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token
3. Выберите права: `repo` (полный доступ к репозиториям)
4. Скопируйте токен и используйте его как пароль

## Шаг 7: Проверка работы

1. Проверьте, что Git работает:
```bash
cd /path/to/your/project
git status
git pull origin master
```

2. Проверьте, что endpoint доступен:
```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret":"your_deploy_secret","branch":"master"}'
```

Должен вернуться JSON ответ с `"success": true`.

**Примечание:** Если на сервере используется `php8.2` вместо `php`, добавьте в `.env`:
```env
PHP_EXECUTABLE=php8.2
```

Контроллер автоматически определит правильную команду PHP, но можно явно указать через переменную окружения.

## Шаг 8: Настройка автоматического обновления (опционально)

Можно настроить cron для периодической проверки обновлений:

```bash
# Откройте crontab
crontab -e

# Добавьте строку для проверки обновлений каждые 5 минут
*/5 * * * * cd /path/to/your/project && git fetch origin && git pull origin master >> /var/log/git-pull.log 2>&1

# Или с выполнением команд после pull:
*/5 * * * * cd /path/to/your/project && git fetch origin && git pull origin master && php8.2 artisan config:cache >> /var/log/git-pull.log 2>&1
```

## Шаг 9: Настройка веб-сервера (Nginx/Apache)

Убедитесь, что веб-сервер правильно настроен для Laravel:

### Nginx:
```nginx
server {
    listen 80;
    server_name essens-store.ru;
    root /path/to/your/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache:
Убедитесь, что `.htaccess` в `public/` директории настроен правильно.

## Шаг 10: Первоначальная настройка проекта

После клонирования репозитория выполните:

```bash
# Установка зависимостей
composer install --no-dev --optimize-autoloader
# Или если composer не в PATH:
php8.2 composer.phar install --no-dev --optimize-autoloader

# Копирование .env (если нужно)
cp .env.example .env

# Генерация ключа приложения
php8.2 artisan key:generate

# Запуск миграций
php8.2 artisan migrate --force

# Очистка и кеширование
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:clear
php8.2 artisan view:clear

php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
```

## Проверка безопасности

1. Убедитесь, что `.env` не доступен извне:
```bash
# Проверьте права на .env
ls -la .env
# Должно быть: -rw------- (600)

# Если нет, исправьте:
chmod 600 .env
```

2. Проверьте, что `DEPLOY_SECRET` достаточно сложный:
```bash
# Длина должна быть минимум 32 символа
grep DEPLOY_SECRET .env
```

## Тестирование

После настройки протестируйте с локальной машины:

```bash
php artisan set-deploy --force --no-ssl-verify --secret=your_deploy_secret
```

**Важно:** На сервере контроллер автоматически определит правильную команду PHP (php8.2, php8.1, php8.0 или php). 
Если нужно явно указать, добавьте в `.env` на сервере:
```env
PHP_EXECUTABLE=php8.2
```

Если все настроено правильно, вы должны увидеть:
- ✅ Изменения отправлены в Git
- ✅ Запрос на обновление отправлен на сервер
- ✅ Развертывание выполнено успешно

## Устранение проблем

### Ошибка: "Git репозиторий не найден"
```bash
cd /path/to/your/project
git init
git remote add origin https://github.com/letoceiling-coder/essens-store.git
```

### Ошибка: "Permission denied"
```bash
sudo chown -R www-data:www-data /path/to/your/project
sudo chmod -R 755 /path/to/your/project
```

### Ошибка: "Неверный секретный ключ"
Проверьте, что `DEPLOY_SECRET` в `.env` на сервере совпадает с тем, что вы используете в команде.

### Ошибка: "Composer not found"
```bash
# Установите composer глобально или используйте локальный
php composer.phar install
```

## Дополнительные рекомендации

1. **Резервное копирование**: Настройте автоматическое резервное копирование базы данных перед обновлением
2. **Логирование**: Проверяйте логи Laravel после каждого обновления
3. **Тестирование**: Сначала тестируйте на staging сервере
4. **Мониторинг**: Настройте мониторинг доступности сайта после обновлений


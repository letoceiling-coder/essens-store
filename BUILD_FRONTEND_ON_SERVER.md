# Сборка фронтенда на сервере

## Проблема

Ошибка: `Vite manifest not found at: /home/d/dsc23ytp/essens/public_html/public/build/manifest.json`

Это означает, что фронтенд не собран на сервере.

## Решение

### Вариант 1: Автоматическая сборка (рекомендуется)

Теперь сборка фронтенда автоматически выполняется при развертывании. Просто выполните:

```bash
php artisan set-deploy --force --no-ssl-verify --secret=123123123
```

Контроллер автоматически:
1. Проверит наличие Node.js и npm
2. Установит зависимости (если нужно)
3. Соберет фронтенд командой `npm run build`

### Вариант 2: Ручная сборка на сервере

Если нужно собрать вручную:

```bash
cd ~/essens/public_html

# Проверьте, установлен ли Node.js и npm
node --version
npm --version

# Если не установлены, установите Node.js
# Для Ubuntu/Debian:
# curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
# sudo apt-get install -y nodejs

# Установите зависимости
npm install --production

# Соберите фронтенд
npm run build
```

### Вариант 3: Сборка на локальной машине и отправка

Если на сервере нет Node.js, можно собрать на локальной машине:

```bash
# На локальной машине
npm run build

# Отправьте собранные файлы в Git
git add public/build
git commit -m "Build frontend assets"
git push origin master

# Затем разверните на сервере
php artisan set-deploy --force --no-ssl-verify --secret=123123123
```

## Проверка

После сборки проверьте, что файл существует:

```bash
ls -la ~/essens/public_html/public/build/manifest.json
```

Файл должен существовать.

## Примечание

Если на сервере нет Node.js, добавьте `public/build` в `.gitignore` и собирайте фронтенд на локальной машине перед развертыванием.


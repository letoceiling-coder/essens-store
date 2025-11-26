# Исправление ошибки "DeployController does not exist"

## Проблема

Ошибка: `Target class [App\\Http\\Controllers\\DeployController] does not exist.`

## Причины

1. Вы находитесь в неправильной директории (`~/essens/public_html` вместо `~/essens`)
2. Автозагрузчик Composer не обновлен после добавления нового контроллера

## Решение

### 1. Перейдите в корневую директорию проекта

```bash
cd ~/essens
```

**ВАЖНО:** Не `~/essens/public_html`, а именно `~/essens`!

### 2. Обновите автозагрузчик Composer

```bash
php8.2 composer.phar dump-autoload
```

Или если composer установлен глобально:

```bash
php8.2 $(which composer) dump-autoload
```

### 3. Проверьте, что файл существует

```bash
ls -la app/Http/Controllers/DeployController.php
```

Должен показать файл.

### 4. Очистите кеш

```bash
php8.2 artisan route:clear
php8.2 artisan config:clear
php8.2 artisan cache:clear
php8.2 artisan route:cache
php8.2 artisan config:cache
```

### 5. Протестируйте снова

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"secret":"123123123","branch":"master"}'
```

## Проверка структуры проекта

Убедитесь, что структура правильная:

```
~/essens/
├── app/
│   └── Http/
│       └── Controllers/
│           └── DeployController.php  ← должен быть здесь
├── public/
│   └── index.php  ← точка входа
├── routes/
│   └── api.php
├── composer.json
└── ...
```

Если `public_html` - это синоним `public`, то Laravel должен работать из `~/essens`, а не из `~/essens/public_html`.


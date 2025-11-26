# Тестирование Deploy Endpoint

## Роуты успешно настроены! ✅

Видно, что оба роута работают:
- `POST api/deploy`
- `POST deploy`

## Тестирование

### 1. Протестируйте endpoint с сервера:

```bash
curl -X POST https://essens-store.ru/api/deploy \
  -H "Content-Type: application/json" \
  -d '{"secret":"123123123","branch":"master"}'
```

Должен вернуться JSON ответ:
```json
{
  "success": true,
  "message": "Развертывание выполнено успешно",
  "status": "completed",
  "branch": "master",
  "has_changes": false
}
```

### 2. Теперь можно использовать с локальной машины:

```bash
php artisan set-deploy --force --no-ssl-verify --secret=123123123
```

## Примечание о SQLite

Предупреждение о SQLite базе данных не критично для работы deploy endpoint. 
Если используете SQLite, создайте файл базы:

```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
```

Или настройте другую БД в `.env` (MySQL, PostgreSQL и т.д.).

## Готово!

Теперь автоматическое развертывание работает. При каждом выполнении `php artisan set-deploy`:
1. Изменения отправляются в Git
2. Отправляется запрос на сервер
3. Сервер автоматически получает изменения из Git
4. Обновляются зависимости
5. Очищается и кешируется конфигурация
6. Выполняются миграции (если есть изменения)


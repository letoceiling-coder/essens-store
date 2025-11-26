# Синхронизация public/build на сервере

## Проблема

Файлы `public/build` есть в Git, но не появляются в `~/essens/public_html/public/build` после `git pull`.

## Решение

### Вариант 1: Проверьте структуру на сервере

```bash
# На сервере
cd ~/essens
ls -la public/build/manifest.json

# Если файл есть в ~/essens/public/build, но не в public_html
# Скопируйте его
cp -r ~/essens/public/build ~/essens/public_html/public/build
```

### Вариант 2: Если public_html - это отдельный репозиторий

```bash
# На сервере
cd ~/essens/public_html

# Проверьте, есть ли .git
ls -la .git

# Если .git есть, выполните
git pull origin master

# Если файлов все еще нет, проверьте .gitignore
cat .gitignore | grep build
```

### Вариант 3: Создайте симлинк (если поддерживается)

```bash
# На сервере
cd ~/essens/public_html
rm -rf public/build
ln -s ~/essens/public/build public/build
```

### Вариант 4: Обновите DeployController для копирования файлов

Можно добавить автоматическое копирование `public/build` в процесс развертывания.


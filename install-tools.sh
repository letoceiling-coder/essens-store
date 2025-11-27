#!/bin/bash
# Скрипт для установки Composer и Node.js/npm на сервере Beget
# Выполните: bash install-tools.sh

set -e  # Остановка при ошибке

echo "========================================="
echo "Установка Composer и Node.js/npm на Beget"
echo "========================================="
echo ""

# Создаем директорию для бинарников
mkdir -p ~/bin
echo "✓ Создана директория ~/bin"

# ============================================
# УСТАНОВКА COMPOSER
# ============================================
echo ""
echo "--- Установка Composer ---"

# Проверяем, установлен ли уже composer
if command -v composer &> /dev/null; then
    echo "✓ Composer уже установлен: $(composer --version)"
else
    echo "Скачивание установщика Composer..."
    cd ~
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    
    echo "Установка Composer в ~/bin..."
    php composer-setup.php --install-dir=~/bin --filename=composer
    
    echo "Удаление установщика..."
    php -r "unlink('composer-setup.php');"
    
    chmod +x ~/bin/composer
    
    echo "✓ Composer установлен в ~/bin/composer"
fi

# ============================================
# УСТАНОВКА NVM (Node Version Manager)
# ============================================
echo ""
echo "--- Установка NVM (Node Version Manager) ---"

# Проверяем, установлен ли уже NVM
if [ -s "$HOME/.nvm/nvm.sh" ]; then
    echo "✓ NVM уже установлен"
    source "$HOME/.nvm/nvm.sh"
else
    echo "Скачивание и установка NVM..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
    
    # Загружаем NVM в текущую сессию
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    
    echo "✓ NVM установлен"
fi

# ============================================
# УСТАНОВКА NODE.JS И NPM
# ============================================
echo ""
echo "--- Установка Node.js и npm ---"

# Загружаем NVM (на случай, если он только что установлен)
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Проверяем, установлен ли Node.js
if command -v node &> /dev/null; then
    echo "✓ Node.js уже установлен: $(node --version)"
    echo "✓ npm уже установлен: $(npm --version)"
else
    echo "Установка Node.js LTS версии..."
    nvm install --lts
    
    echo "Настройка версии по умолчанию..."
    nvm use --lts
    nvm alias default node
    
    echo "✓ Node.js установлен: $(node --version)"
    echo "✓ npm установлен: $(npm --version)"
fi

# ============================================
# НАСТРОЙКА PATH
# ============================================
echo ""
echo "--- Настройка PATH ---"

# Добавляем ~/bin в PATH
if ! grep -q 'export PATH="$HOME/bin:$PATH"' ~/.bashrc 2>/dev/null; then
    echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
    echo "✓ Добавлен ~/bin в PATH"
else
    echo "✓ ~/bin уже в PATH"
fi

# Добавляем NVM в .bashrc
if ! grep -q 'export NVM_DIR="$HOME/.nvm"' ~/.bashrc 2>/dev/null; then
    echo '' >> ~/.bashrc
    echo '# NVM' >> ~/.bashrc
    echo 'export NVM_DIR="$HOME/.nvm"' >> ~/.bashrc
    echo '[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"' >> ~/.bashrc
    echo '[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"' >> ~/.bashrc
    echo "✓ Добавлен NVM в .bashrc"
else
    echo "✓ NVM уже настроен в .bashrc"
fi

# ============================================
# ПРОВЕРКА УСТАНОВКИ
# ============================================
echo ""
echo "--- Проверка установки ---"

# Загружаем обновленный PATH
export PATH="$HOME/bin:$PATH"
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

echo ""
if command -v composer &> /dev/null; then
    echo "✓ Composer: $(composer --version)"
else
    echo "✗ Composer не найден (возможно, нужна новая сессия SSH)"
fi

if command -v node &> /dev/null; then
    echo "✓ Node.js: $(node --version)"
else
    echo "✗ Node.js не найден (возможно, нужна новая сессия SSH)"
fi

if command -v npm &> /dev/null; then
    echo "✓ npm: $(npm --version)"
else
    echo "✗ npm не найден (возможно, нужна новая сессия SSH)"
fi

echo ""
echo "========================================="
echo "Установка завершена!"
echo "========================================="
echo ""
echo "ВАЖНО: Если инструменты не найдены, выполните:"
echo "  source ~/.bashrc"
echo ""
echo "Или переподключитесь к серверу по SSH."
echo ""


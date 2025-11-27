<template>
    <div :class="['batnorton-layout', themeClass]">
        <!-- Header -->
        <header class="bn-header">
            <div class="bn-header-container">
                <div class="bn-logo">
                    <router-link to="/" class="bn-logo-link">Essens</router-link>
                </div>
                <nav class="bn-nav" :class="{ 'bn-nav-open': mobileMenuOpen }">
                    <ul class="bn-nav-list">
                        <li><router-link to="/catalog" class="bn-nav-link">Каталог</router-link></li>
                        <li><router-link to="/about" class="bn-nav-link">О нас</router-link></li>
                        <li><router-link to="/contacts" class="bn-nav-link">Контакты</router-link></li>
                    </ul>
                </nav>
                <div class="bn-header-actions">
                    <button 
                        @click="toggleTheme" 
                        class="bn-theme-toggle"
                        :title="currentTheme === 'dark' ? 'Светлая тема' : 'Темная тема'"
                    >
                        <svg v-if="currentTheme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="bn-menu-toggle"
                        :aria-label="mobileMenuOpen ? 'Закрыть меню' : 'Открыть меню'"
                    >
                        ☰
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="bn-main">
            <slot></slot>
        </main>

        <!-- Footer -->
        <footer class="bn-footer">
            <div class="bn-footer-content">
                <div class="bn-footer-logo">
                    <router-link to="/" class="bn-footer-logo-link">Essens</router-link>
                </div>
                <div class="bn-footer-info">
                    <p>© {{ currentYear }} Essens. Все права защищены.</p>
                    <nav class="bn-footer-nav">
                        <ul class="bn-footer-nav-list">
                            <li><router-link to="/about" class="bn-footer-nav-link">О нас</router-link></li>
                            <li><router-link to="/catalog" class="bn-footer-nav-link">Каталог</router-link></li>
                            <li><router-link to="/contacts" class="bn-footer-nav-link">Контакты</router-link></li>
                            <li><router-link to="/shipping" class="bn-footer-nav-link">Доставка</router-link></li>
                            <li><router-link to="/privacy" class="bn-footer-nav-link">Конфиденциальность</router-link></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
    name: 'BatNortonLayout',
    setup() {
        const mobileMenuOpen = ref(false);
        const currentTheme = ref('dark');

        const themeClass = computed(() => {
            return `bn-theme-${currentTheme.value}`;
        });

        const currentYear = computed(() => {
            return new Date().getFullYear();
        });

        const toggleTheme = () => {
            currentTheme.value = currentTheme.value === 'dark' ? 'light' : 'dark';
            localStorage.setItem('bn-theme', currentTheme.value);
            document.documentElement.setAttribute('data-bn-theme', currentTheme.value);
        };

        onMounted(() => {
            const savedTheme = localStorage.getItem('bn-theme') || 'dark';
            currentTheme.value = savedTheme;
            document.documentElement.setAttribute('data-bn-theme', currentTheme.value);
        });

        return {
            mobileMenuOpen,
            currentTheme,
            themeClass,
            currentYear,
            toggleTheme,
        };
    },
};
</script>

<style scoped>
/* Bat Norton Design System */
.batnorton-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Dark Theme (Default) */
.bn-theme-dark {
    --bn-bg: #000000;
    --bn-surface: #111111;
    --bn-text: #FFFFFF;
    --bn-text-secondary: #CCCCCC;
    --bn-border: #222222;
    --bn-accent: #FFFFFF;
}

/* Light Theme */
.bn-theme-light {
    --bn-bg: #FFFFFF;
    --bn-surface: #F5F5F5;
    --bn-text: #000000;
    --bn-text-secondary: #666666;
    --bn-border: #E0E0E0;
    --bn-accent: #000000;
}

.batnorton-layout {
    background-color: var(--bn-bg);
    color: var(--bn-text);
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 16px;
    line-height: 1.5;
}

/* Header */
.bn-header {
    background-color: var(--bn-bg);
    color: var(--bn-text);
    padding: 10px 20px;
    border-bottom: 1px solid var(--bn-border);
    position: sticky;
    top: 0;
    z-index: 100;
}

.bn-header-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bn-logo-link {
    color: var(--bn-text);
    font-size: 1.5em;
    text-decoration: none;
    font-weight: bold;
    transition: opacity 0.2s;
}

.bn-logo-link:hover {
    opacity: 0.8;
}

.bn-nav {
    display: flex;
}

.bn-nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 20px;
}

.bn-nav-link {
    color: var(--bn-text);
    text-decoration: none;
    font-size: 1em;
    transition: opacity 0.2s;
}

.bn-nav-link:hover,
.bn-nav-link.router-link-active {
    opacity: 0.8;
    text-decoration: underline;
}

.bn-header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.bn-theme-toggle {
    background: none;
    border: none;
    color: var(--bn-text);
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
    transition: opacity 0.2s;
}

.bn-theme-toggle:hover {
    opacity: 0.8;
}

.bn-menu-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--bn-text);
    font-size: 1.5em;
    cursor: pointer;
    padding: 5px;
}

/* Main Content */
.bn-main {
    flex: 1;
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

/* Footer */
.bn-footer {
    background-color: var(--bn-bg);
    color: var(--bn-text-secondary);
    padding: 20px;
    border-top: 1px solid var(--bn-border);
    margin-top: auto;
}

.bn-footer-content {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.bn-footer-logo-link {
    color: var(--bn-text);
    font-size: 1.3em;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    margin-bottom: 10px;
}

.bn-footer-nav-list {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
}

.bn-footer-nav-link {
    color: var(--bn-text-secondary);
    text-decoration: none;
    font-size: 0.9em;
    transition: color 0.2s;
}

.bn-footer-nav-link:hover {
    color: var(--bn-text);
}

/* Mobile Styles */
@media (max-width: 768px) {
    .bn-header-container {
        flex-wrap: wrap;
    }

    .bn-nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: var(--bn-bg);
        width: 100%;
        display: none;
        flex-direction: column;
        border-bottom: 1px solid var(--bn-border);
        padding: 10px 20px;
    }

    .bn-nav.bn-nav-open {
        display: flex;
    }

    .bn-nav-list {
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .bn-menu-toggle {
        display: block;
    }

    .bn-main {
        padding: 10px;
    }

    .bn-footer-nav-list {
        flex-direction: column;
        gap: 10px;
    }
}
</style>


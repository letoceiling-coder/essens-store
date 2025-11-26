<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-surface border-b border-border backdrop-blur-sm">
            <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <router-link to="/" class="flex items-center space-x-2">
                        <span class="text-2xl font-bold text-primary">Essens</span>
                    </router-link>

                    <!-- Desktop Navigation -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <router-link
                            to="/"
                            class="text-foreground hover:text-primary transition-colors"
                            active-class="text-primary font-medium"
                        >
                            Главная
                        </router-link>
                        <router-link
                            to="/catalog"
                            class="text-foreground hover:text-primary transition-colors"
                            active-class="text-primary font-medium"
                        >
                            Каталог
                        </router-link>
                        <router-link
                            to="/about"
                            class="text-foreground hover:text-primary transition-colors"
                            active-class="text-primary font-medium"
                        >
                            О нас
                        </router-link>
                        <router-link
                            to="/contacts"
                            class="text-foreground hover:text-primary transition-colors"
                            active-class="text-primary font-medium"
                        >
                            Контакты
                        </router-link>
                    </nav>

                    <!-- Right side actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <button
                            @click="toggleTheme"
                            class="p-2 rounded-lg hover:bg-muted transition-colors"
                            :aria-label="isDark ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
                        >
                            <svg
                                v-if="isDark"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                />
                            </svg>
                        </button>

                        <!-- Cart -->
                        <button class="p-2 rounded-lg hover:bg-muted transition-colors relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                            <span
                                v-if="cartCount > 0"
                                class="absolute top-0 right-0 bg-primary text-primary-contrast text-xs rounded-full w-5 h-5 flex items-center justify-center"
                            >
                                {{ cartCount }}
                            </span>
                        </button>

                        <!-- Mobile menu button -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden p-2 rounded-lg hover:bg-muted transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    v-if="!mobileMenuOpen"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div
                    v-if="mobileMenuOpen"
                    class="md:hidden py-4 border-t border-border"
                >
                    <router-link
                        to="/"
                        class="block py-2 text-foreground hover:text-primary transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        Главная
                    </router-link>
                    <router-link
                        to="/catalog"
                        class="block py-2 text-foreground hover:text-primary transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        Каталог
                    </router-link>
                    <router-link
                        to="/about"
                        class="block py-2 text-foreground hover:text-primary transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        О нас
                    </router-link>
                    <router-link
                        to="/contacts"
                        class="block py-2 text-foreground hover:text-primary transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        Контакты
                    </router-link>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="pb-16 md:pb-0">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav />

        <!-- Footer -->
        <footer class="bg-surface border-t border-border mt-auto">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-lg font-bold mb-4">Essens</h3>
                        <p class="text-muted-foreground text-sm">
                            Качественная продукция для вашего здоровья и красоты
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-semibold mb-4">Быстрые ссылки</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <router-link to="/catalog" class="text-muted-foreground hover:text-primary transition-colors">
                                    Каталог
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/about" class="text-muted-foreground hover:text-primary transition-colors">
                                    О нас
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/shipping" class="text-muted-foreground hover:text-primary transition-colors">
                                    Доставка и оплата
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/faq" class="text-muted-foreground hover:text-primary transition-colors">
                                    FAQ
                                </router-link>
                            </li>
                        </ul>
                    </div>

                    <!-- Information -->
                    <div>
                        <h4 class="font-semibold mb-4">Информация</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <router-link to="/contacts" class="text-muted-foreground hover:text-primary transition-colors">
                                    Контакты
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/privacy" class="text-muted-foreground hover:text-primary transition-colors">
                                    Политика конфиденциальности
                                </router-link>
                            </li>
                            <li>
                                <router-link to="/terms" class="text-muted-foreground hover:text-primary transition-colors">
                                    Пользовательское соглашение
                                </router-link>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="font-semibold mb-4">Контакты</h4>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li>Email: info@essens.ru</li>
                            <li>Телефон: +7 (999) 123-45-67</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-border text-center text-sm text-muted-foreground">
                    <p>&copy; {{ currentYear }} Essens. Все права защищены.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import { computed, ref } from 'vue';
import { useStore } from 'vuex';
import MobileBottomNav from '@/components/store/MobileBottomNav.vue';

export default {
    name: 'StoreLayout',
    components: {
        MobileBottomNav,
    },
    setup() {
        const store = useStore();
        const mobileMenuOpen = ref(false);
        const cartCount = ref(0);

        const isDark = computed(() => store.getters.isDarkMode);
        const currentYear = new Date().getFullYear();

        const toggleTheme = () => {
            store.dispatch('toggleTheme');
        };

        return {
            mobileMenuOpen,
            cartCount,
            isDark,
            currentYear,
            toggleTheme,
        };
    },
};
</script>



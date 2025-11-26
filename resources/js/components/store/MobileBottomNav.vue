<template>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface border-t border-border shadow-lg">
        <div class="flex items-center justify-around h-16 px-2">
            <router-link
                to="/"
                class="flex flex-col items-center justify-center flex-1 h-full transition-colors"
                active-class="text-primary"
                :class="{ 'text-primary': $route.path === '/', 'text-muted-foreground': $route.path !== '/' }"
            >
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs font-medium">Главная</span>
            </router-link>

            <router-link
                to="/catalog"
                class="flex flex-col items-center justify-center flex-1 h-full transition-colors"
                active-class="text-primary"
                :class="{ 'text-primary': $route.path === '/catalog', 'text-muted-foreground': $route.path !== '/catalog' }"
            >
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span class="text-xs font-medium">Каталог</span>
            </router-link>

            <button
                @click="toggleCart"
                class="flex flex-col items-center justify-center flex-1 h-full transition-colors relative"
                :class="cartCount > 0 ? 'text-primary' : 'text-muted-foreground'"
            >
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-xs font-medium">Корзина</span>
                <span
                    v-if="cartCount > 0"
                    class="absolute top-1 right-1/4 bg-primary text-primary-contrast text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
                >
                    {{ cartCount > 99 ? '99+' : cartCount }}
                </span>
            </button>

            <router-link
                to="/about"
                class="flex flex-col items-center justify-center flex-1 h-full transition-colors"
                active-class="text-primary"
                :class="{ 'text-primary': $route.path === '/about', 'text-muted-foreground': $route.path !== '/about' }"
            >
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-medium">О нас</span>
            </router-link>

            <router-link
                to="/contacts"
                class="flex flex-col items-center justify-center flex-1 h-full transition-colors"
                active-class="text-primary"
                :class="{ 'text-primary': $route.path === '/contacts', 'text-muted-foreground': $route.path !== '/contacts' }"
            >
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="text-xs font-medium">Контакты</span>
            </router-link>
        </div>
    </nav>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'MobileBottomNav',
    setup() {
        const cartCount = ref(0);

        const toggleCart = () => {
            // TODO: Implement cart toggle functionality
            console.log('Toggle cart');
        };

        return {
            cartCount,
            toggleCart,
        };
    },
};
</script>

<style scoped>
/* Добавляем отступ снизу для контента, чтобы он не перекрывался меню */
:deep(main) {
    padding-bottom: 4rem;
}
</style>


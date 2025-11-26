<template>
    <StoreLayout>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-primary/10 via-background to-background py-20 lg:py-32">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto text-center">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-foreground">
                        Essens — Продукция для здоровья и красоты
                    </h1>
                    <p class="text-xl text-muted-foreground mb-8">
                        Качественные натуральные продукты для вашего здоровья и красоты. 
                        Мы заботимся о вашем благополучии каждый день.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <router-link
                            to="/catalog"
                            class="inline-flex items-center justify-center px-8 py-3 bg-primary text-primary-contrast rounded-lg font-medium hover:opacity-90 transition-opacity"
                        >
                            Перейти в каталог
                        </router-link>
                        <router-link
                            to="/about"
                            class="inline-flex items-center justify-center px-8 py-3 border border-primary text-primary rounded-lg font-medium hover:bg-primary/10 transition-colors"
                        >
                            Узнать больше
                        </router-link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-16 lg:py-24 bg-surface">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Почему выбирают Essens</h2>
                    <p class="text-muted-foreground max-w-2xl mx-auto">
                        Мы предлагаем только качественную продукцию с гарантией качества
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-card p-6 rounded-lg border border-border">
                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">100% Натурально</h3>
                        <p class="text-muted-foreground">
                            Вся наша продукция изготавливается из натуральных ингредиентов без искусственных добавок
                        </p>
                    </div>
                    <div class="bg-card p-6 rounded-lg border border-border">
                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Быстрая доставка</h3>
                        <p class="text-muted-foreground">
                            Доставляем заказы по всей России в кратчайшие сроки с надежной упаковкой
                        </p>
                    </div>
                    <div class="bg-card p-6 rounded-lg border border-border">
                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Гарантия качества</h3>
                        <p class="text-muted-foreground">
                            Все товары проходят строгий контроль качества и имеют необходимые сертификаты
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Preview Section -->
        <section class="py-16 lg:py-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Популярные товары</h2>
                    <p class="text-muted-foreground max-w-2xl mx-auto">
                        Наши бестселлеры, которые выбирают тысячи довольных клиентов
                    </p>
                </div>
                <div v-if="loading" class="text-center py-12">
                    <p class="text-muted-foreground">Загрузка товаров...</p>
                </div>
                <div v-else-if="products.length === 0" class="text-center py-12">
                    <p class="text-muted-foreground">Товары не найдены</p>
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="bg-card rounded-lg border border-border overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                        @click="$router.push(`/product/${product.id}`)"
                    >
                        <div class="aspect-square bg-muted flex items-center justify-center overflow-hidden">
                            <img
                                v-if="product.primary_image?.url"
                                :src="product.primary_image.url"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />
                            <svg v-else class="w-16 h-16 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold mb-2 line-clamp-2">{{ product.name }}</h3>
                            <p v-if="product.description" class="text-muted-foreground text-sm mb-3 line-clamp-2">
                                {{ product.description }}
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-primary">{{ formatPrice(product.price) }}</span>
                                <button
                                    @click.stop
                                    class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity text-sm"
                                >
                                    В корзину
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-12">
                    <router-link
                        to="/catalog"
                        class="inline-flex items-center px-6 py-3 border border-primary text-primary rounded-lg font-medium hover:bg-primary/10 transition-colors"
                    >
                        Смотреть весь каталог
                    </router-link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 lg:py-24 bg-primary text-primary-contrast">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Готовы начать заботиться о своем здоровье?</h2>
                <p class="text-xl mb-8 opacity-90">
                    Присоединяйтесь к тысячам довольных клиентов Essens
                </p>
                <router-link
                    to="/catalog"
                    class="inline-flex items-center px-8 py-3 bg-white text-primary rounded-lg font-medium hover:opacity-90 transition-opacity"
                >
                    Начать покупки
                </router-link>
            </div>
        </section>
    </StoreLayout>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StoreLayout from '@/layouts/StoreLayout.vue';

export default {
    name: 'Home',
    components: {
        StoreLayout,
    },
    setup() {
        const products = ref([]);
        const loading = ref(true);

        const fetchFeaturedProducts = async () => {
            try {
                loading.value = true;
                const response = await axios.get('/api/store/products/featured', {
                    params: { limit: 8 }
                });
                products.value = response.data.data || response.data;
            } catch (error) {
                console.error('Error fetching featured products:', error);
                products.value = [];
            } finally {
                loading.value = false;
            }
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        };

        onMounted(() => {
            fetchFeaturedProducts();
            
            // SEO мета-теги
            document.title = 'Essens — Интернет-магазин продукции для здоровья и красоты';
            const metaDescription = document.querySelector('meta[name="description"]');
            if (metaDescription) {
                metaDescription.setAttribute('content', 'Essens — качественная натуральная продукция для здоровья и красоты. Широкий ассортимент товаров с гарантией качества. Доставка по всей России.');
            } else {
                const meta = document.createElement('meta');
                meta.name = 'description';
                meta.content = 'Essens — качественная натуральная продукция для здоровья и красоты. Широкий ассортимент товаров с гарантией качества. Доставка по всей России.';
                document.head.appendChild(meta);
            }
        });

        return {
            products,
            loading,
            formatPrice,
        };
    },
};
</script>


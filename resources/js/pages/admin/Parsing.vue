<template>
    <div class="parsing-page">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Парсинг товаров</h1>
                <p class="text-muted-foreground mt-1">Парсинг данных с сайта essensworld.ru</p>
            </div>
        </div>

        <!-- Availability and Authentication Check -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Availability Check -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Проверка доступности</h2>
                    <button
                        @click="checkAvailability"
                        :disabled="checkingAvailability"
                        class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span v-if="checkingAvailability">Проверка...</span>
                        <span v-else>Проверить</span>
                    </button>
                </div>
                <div v-if="availabilityResult" class="mt-4">
                    <div :class="[
                        'p-4 rounded-lg',
                        availabilityResult.available ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20'
                    ]">
                        <p :class="[
                            'font-medium',
                            availabilityResult.available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                        ]">
                            {{ availabilityResult.available ? '✓ Сайт доступен' : '✗ Сайт недоступен' }}
                        </p>
                        <p v-if="availabilityResult.status_code" class="text-sm text-muted-foreground mt-1">
                            HTTP статус: {{ availabilityResult.status_code }}
                        </p>
                        <p v-if="availabilityResult.error" class="text-sm text-red-600 dark:text-red-400 mt-1">
                            Ошибка: {{ availabilityResult.error }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Authentication Check -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Проверка авторизации</h2>
                    <button
                        @click="checkAuthentication"
                        :disabled="checkingAuth"
                        class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span v-if="checkingAuth">Проверка...</span>
                        <span v-else>Авторизоваться</span>
                    </button>
                </div>
                <div v-if="authResult" class="mt-4">
                    <div :class="[
                        'p-4 rounded-lg',
                        authResult.authenticated ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20'
                    ]">
                        <p :class="[
                            'font-medium',
                            authResult.authenticated ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                        ]">
                            {{ authResult.authenticated ? '✓ Авторизация успешна' : '✗ Ошибка авторизации' }}
                        </p>
                        <p v-if="authResult.message" class="text-sm text-muted-foreground mt-1">
                            {{ authResult.message }}
                        </p>
                        <p v-if="authResult.error" class="text-sm text-red-600 dark:text-red-400 mt-1">
                            Ошибка: {{ authResult.error }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Parse by URL -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Парсинг товара по ссылке</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">URL товара</label>
                        <input
                            v-model="productUrl"
                            type="url"
                            placeholder="https://www.essensworld.ru/gely-dlya-dusha-colostrum-d163684/"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <button
                        @click="parseProduct"
                        :disabled="!productUrl || parsingProduct"
                        class="w-full px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="parsingProduct">Парсинг...</span>
                        <span v-else>Парсить товар</span>
                    </button>
                </div>

                <!-- Product Result -->
                <div v-if="parsedProduct" class="mt-6 bg-card rounded-lg border border-border overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-2xl font-semibold mb-4">Результат парсинга</h3>
                        
                        <!-- Product Card -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Images -->
                            <div v-if="parsedProduct.images && parsedProduct.images.length > 0">
                                <div class="space-y-2">
                                    <div class="aspect-square bg-muted rounded-lg overflow-hidden">
                                        <img
                                            :src="parsedProduct.images[0]"
                                            :alt="parsedProduct.name"
                                            class="w-full h-full object-cover"
                                            @error="handleImageError"
                                        />
                                    </div>
                                    <div v-if="parsedProduct.images.length > 1" class="grid grid-cols-4 gap-2">
                                        <div
                                            v-for="(img, index) in parsedProduct.images.slice(1, 5)"
                                            :key="index"
                                            class="aspect-square bg-muted rounded overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                                            @click="parsedProduct.images[0] = img"
                                        >
                                            <img
                                                :src="img"
                                                :alt="`${parsedProduct.name} ${index + 2}`"
                                                class="w-full h-full object-cover"
                                                @error="handleImageError"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-xl font-bold mb-2">{{ parsedProduct.name || 'Название не найдено' }}</h4>
                                    <div v-if="parsedProduct.sku" class="text-sm text-muted-foreground">
                                        Артикул: <span class="font-medium text-foreground">{{ parsedProduct.sku }}</span>
                                    </div>
                                </div>
                                
                                <!-- Prices Section -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-4 flex-wrap">
                                        <div class="flex-1">
                                            <!-- Discounted Price (Main Price) -->
                                            <div v-if="parsedProduct.discounted_price" class="text-3xl font-bold text-primary">
                                                {{ formatPrice(parsedProduct.discounted_price) }}
                                            </div>
                                            <!-- Regular Price (if no discount) -->
                                            <div v-else-if="parsedProduct.price" class="text-3xl font-bold text-primary">
                                                {{ formatPrice(parsedProduct.price) }}
                                            </div>
                                            
                                            <!-- Old Price (if discounted) -->
                                            <div v-if="parsedProduct.old_price" class="text-lg text-muted-foreground line-through mt-1">
                                                {{ formatPrice(parsedProduct.old_price) }}
                                            </div>
                                            
                                            <!-- Recommended Price -->
                                            <div v-if="parsedProduct.recommended_price" class="text-sm text-muted-foreground mt-2">
                                                <span class="font-medium">Рекомендуемая розничная цена:</span>
                                                <span class="ml-1">{{ formatPrice(parsedProduct.recommended_price) }}</span>
                                            </div>
                                            
                                            <!-- Lowest Recent Price -->
                                            <div v-if="parsedProduct.lowest_recent_price" class="text-sm text-green-600 dark:text-green-400 mt-1">
                                                <span class="font-medium">Лучшая цена за последние 30 дней:</span>
                                                <span class="ml-1">{{ formatPrice(parsedProduct.lowest_recent_price) }}</span>
                                            </div>
                                        </div>
                                        
                                        <div :class="[
                                            'px-3 py-1 rounded-full text-sm font-medium',
                                            parsedProduct.in_stock 
                                                ? 'bg-green-500/10 text-green-600 dark:text-green-400' 
                                                : 'bg-red-500/10 text-red-600 dark:text-red-400'
                                        ]">
                                            {{ parsedProduct.in_stock ? 'В наличии' : 'Нет в наличии' }}
                                        </div>
                                    </div>
                                    
                                    <!-- Points and Cashback -->
                                    <div class="flex items-center gap-4 flex-wrap text-sm">
                                        <div v-if="parsedProduct.points" class="flex items-center gap-2 px-3 py-1 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="font-medium">{{ parsedProduct.points }} б</span>
                                        </div>
                                        
                                        <div v-if="parsedProduct.cashback" class="flex items-center gap-2 px-3 py-1 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-full">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="font-medium">Кэшбэк: {{ formatPrice(parsedProduct.cashback) }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="parsedProduct.volume" class="text-sm text-muted-foreground">
                                    Объем: <span class="font-medium text-foreground">{{ parsedProduct.volume }}</span>
                                </div>
                                
                                <div v-if="parsedProduct.rating" class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">Рейтинг:</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-yellow-500">★</span>
                                        <span class="font-medium">{{ parsedProduct.rating }}</span>
                                    </div>
                                </div>
                                
                                <div v-if="parsedProduct.description" class="mt-4">
                                    <h5 class="font-semibold mb-2">Описание:</h5>
                                    <div class="text-sm text-muted-foreground whitespace-pre-line max-h-64 overflow-y-auto">
                                        {{ parsedProduct.description }}
                                    </div>
                                </div>
                                
                                <div v-if="parsedProduct.url" class="mt-4 pt-4 border-t border-border">
                                    <a 
                                        :href="parsedProduct.url" 
                                        target="_blank"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        Открыть на сайте →
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- All Images Grid -->
                        <div v-if="parsedProduct.images && parsedProduct.images.length > 5" class="mt-6 pt-6 border-t border-border">
                            <h5 class="font-semibold mb-3">Все изображения ({{ parsedProduct.images.length }})</h5>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                <div
                                    v-for="(img, index) in parsedProduct.images"
                                    :key="index"
                                    class="aspect-square bg-muted rounded overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                                    @click="parsedProduct.images[0] = img"
                                >
                                    <img
                                        :src="img"
                                        :alt="`${parsedProduct.name} ${index + 1}`"
                                        class="w-full h-full object-cover"
                                        @error="handleImageError"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parse Categories -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Получить категории</h2>
                <button
                    @click="fetchCategories"
                    :disabled="loadingCategories"
                    class="w-full px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed mb-4"
                >
                    <span v-if="loadingCategories">Загрузка...</span>
                    <span v-else>Загрузить категории</span>
                </button>

                <!-- Categories List -->
                <div v-if="categories.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                    <div
                        v-for="category in categories"
                        :key="category.url"
                        class="p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
                        @click="selectCategory(category)"
                    >
                        <div class="font-medium">{{ category.name }}</div>
                        <div class="text-xs text-muted-foreground mt-1 break-all">{{ category.url }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Category Products -->
        <div v-if="selectedCategory" class="mt-6 bg-card rounded-lg border border-border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Товары из категории: {{ selectedCategory.name }}</h2>
                <button
                    @click="fetchCategoryProducts"
                    :disabled="loadingProducts"
                    class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loadingProducts">Загрузка...</span>
                    <span v-else>Загрузить товары</span>
                </button>
            </div>

            <div v-if="categoryProducts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="product in categoryProducts"
                    :key="product.url"
                    class="p-4 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
                    @click="productUrl = product.url; parseProduct()"
                >
                    <div class="font-medium">{{ product.name || 'Товар' }}</div>
                    <div class="text-xs text-muted-foreground mt-1 break-all">{{ product.url }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    name: 'Parsing',
    setup() {
        const checkingAvailability = ref(false);
        const availabilityResult = ref(null);
        const checkingAuth = ref(false);
        const authResult = ref(null);
        const productUrl = ref('https://www.essensworld.ru/gely-dlya-dusha-colostrum-d163684/');
        const parsingProduct = ref(false);
        const parsedProduct = ref(null);
        const loadingCategories = ref(false);
        const categories = ref([]);
        const selectedCategory = ref(null);
        const loadingProducts = ref(false);
        const categoryProducts = ref([]);

        const checkAvailability = async () => {
            checkingAvailability.value = true;
            try {
                const response = await axios.get('/api/admin/parsing/check-availability');
                availabilityResult.value = response.data.data;
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось проверить доступность сайта',
                });
            } finally {
                checkingAvailability.value = false;
            }
        };

        const checkAuthentication = async () => {
            checkingAuth.value = true;
            authResult.value = null;
            try {
                const response = await axios.get('/api/admin/parsing/check-authentication');
                authResult.value = response.data.data;
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно',
                        text: 'Авторизация выполнена',
                        timer: 2000,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ошибка',
                        text: response.data.data?.message || 'Не удалось авторизоваться',
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось проверить авторизацию',
                });
            } finally {
                checkingAuth.value = false;
            }
        };

        const parseProduct = async () => {
            if (!productUrl.value) return;

            parsingProduct.value = true;
            parsedProduct.value = null;

            try {
                const response = await axios.post('/api/admin/parsing/product', {
                    url: productUrl.value,
                });

                if (response.data.success) {
                    parsedProduct.value = response.data.data;
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно',
                        text: 'Товар успешно распарсен',
                        timer: 2000,
                    });
                } else {
                    throw new Error(response.data.message || 'Ошибка при парсинге');
                }
            } catch (error) {
                console.error('Error parsing product:', error);
                const errorMessage = error.response?.data?.message || error.message || 'Не удалось получить данные товара';
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: errorMessage,
                    footer: error.response?.status === 404 
                        ? '<small>Убедитесь, что URL правильный и товар доступен. Также проверьте, что авторизация прошла успешно (нажмите кнопку "Авторизоваться" перед парсингом).</small>'
                        : '<small>Проверьте логи сервера для получения дополнительной информации</small>',
                });
            } finally {
                parsingProduct.value = false;
            }
        };

        const fetchCategories = async () => {
            loadingCategories.value = true;
            categories.value = [];

            try {
                const response = await axios.get('/api/admin/parsing/categories');
                if (response.data.success) {
                    categories.value = response.data.data;
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно',
                        text: `Найдено категорий: ${response.data.count}`,
                        timer: 2000,
                    });
                } else {
                    throw new Error(response.data.message || 'Ошибка при получении категорий');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось получить категории',
                });
            } finally {
                loadingCategories.value = false;
            }
        };

        const selectCategory = (category) => {
            selectedCategory.value = category;
            categoryProducts.value = [];
        };

        const fetchCategoryProducts = async () => {
            if (!selectedCategory.value) return;

            loadingProducts.value = true;
            categoryProducts.value = [];

            try {
                const response = await axios.post('/api/admin/parsing/category-products', {
                    category_url: selectedCategory.value.url,
                    page: 1,
                });

                if (response.data.success) {
                    categoryProducts.value = response.data.data;
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно',
                        text: `Найдено товаров: ${response.data.count}`,
                        timer: 2000,
                    });
                } else {
                    throw new Error(response.data.message || 'Ошибка при получении товаров');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось получить товары',
                });
            } finally {
                loadingProducts.value = false;
            }
        };

        const handleImageError = (event) => {
            event.target.style.display = 'none';
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        };

        return {
            checkingAvailability,
            availabilityResult,
            checkingAuth,
            authResult,
            productUrl,
            parsingProduct,
            parsedProduct,
            loadingCategories,
            categories,
            selectedCategory,
            loadingProducts,
            categoryProducts,
            checkAvailability,
            checkAuthentication,
            parseProduct,
            fetchCategories,
            selectCategory,
            fetchCategoryProducts,
            handleImageError,
            formatPrice,
        };
    },
};
</script>

<style scoped>
.parsing-page {
    min-height: 100vh;
}
</style>


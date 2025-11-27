<template>
    <div class="category-parsing-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Парсинг категорий</h1>
                <p class="text-muted-foreground mt-1">Парсинг категорий и товаров с eshop.php</p>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="bg-card rounded-lg border border-border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Категории товаров</h2>
                <div class="flex gap-2">
                    <button
                        @click="fetchCategories"
                        :disabled="loadingCategories"
                        class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loadingCategories">Загрузка...</span>
                        <span v-else>Загрузить категории</span>
                    </button>
                    <button
                        @click="testWithKnownCategory"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        Тест (cat_id=19)
                    </button>
                </div>
            </div>
            
            <!-- Manual Category Input -->
            <div class="mb-4 p-4 bg-muted/30 rounded-lg">
                <label class="text-sm font-medium mb-2 block">Или введите cat_id вручную:</label>
                <div class="flex gap-2">
                    <input
                        v-model.number="manualCatId"
                        type="number"
                        min="1"
                        placeholder="Например: 19"
                        class="flex-1 h-10 px-3 border border-border rounded bg-background"
                    />
                    <button
                        @click="selectManualCategory"
                        :disabled="!manualCatId"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50"
                    >
                        Использовать
                    </button>
                </div>
            </div>

            <!-- Categories List -->
            <div v-if="categories.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                <div
                    v-for="category in categories"
                    :key="category.id"
                    class="p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors"
                    :class="selectedCategory?.id === category.id ? 'ring-2 ring-accent' : ''"
                >
                    <div 
                        class="flex items-center justify-between cursor-pointer"
                        @click="selectCategory(category)"
                    >
                        <div class="flex-1">
                            <div class="font-medium">{{ category.name }}</div>
                            <div class="text-xs text-muted-foreground mt-1">ID: {{ category.id }}</div>
                            <div class="text-xs text-muted-foreground break-all">{{ category.url }}</div>
                        </div>
                        <div v-if="selectedCategory?.id === category.id" class="text-accent ml-2">
                            ✓
                        </div>
                    </div>
                    
                    <!-- Subcategories -->
                    <div v-if="category.subcategories && category.subcategories.length > 0" class="mt-2 ml-4 space-y-1 border-l-2 border-accent/30 pl-3">
                        <div class="text-xs font-medium text-muted-foreground mb-1">Подкатегории:</div>
                        <div
                            v-for="subcategory in category.subcategories"
                            :key="subcategory.id"
                            class="p-2 bg-muted/20 rounded hover:bg-muted/40 transition-colors cursor-pointer text-sm"
                            :class="selectedCategory?.id === subcategory.id ? 'ring-1 ring-accent' : ''"
                            @click.stop="selectCategory(subcategory)"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ subcategory.name }}</div>
                                    <div class="text-xs text-muted-foreground mt-0.5">ID: {{ subcategory.id }}</div>
                                </div>
                                <div v-if="selectedCategory?.id === subcategory.id" class="text-accent ml-2">
                                    ✓
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="!loadingCategories" class="text-center text-muted-foreground py-8">
                Нажмите "Загрузить категории" для получения списка категорий
            </div>
        </div>

        <!-- Selected Category Products -->
        <div v-if="selectedCategory" class="bg-card rounded-lg border border-border p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold">Товары из категории: {{ selectedCategory.name }}</h2>
                    <p class="text-sm text-muted-foreground mt-1">ID категории: {{ selectedCategory.id }}</p>
                </div>
                <div class="flex gap-2">
                    <input
                        v-model.number="currentPage"
                        type="number"
                        min="1"
                        placeholder="Страница"
                        class="w-20 h-10 px-3 border border-border rounded bg-background"
                    />
                    <button
                        @click="fetchCategoryProducts"
                        :disabled="loadingProducts"
                        class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loadingProducts">Загрузка...</span>
                        <span v-else>Загрузить товары</span>
                    </button>
                </div>
            </div>

            <div v-if="categoryProducts.length > 0" class="space-y-4">
                <div class="text-sm text-muted-foreground">
                    Найдено товаров: {{ categoryProducts.length }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[600px] overflow-y-auto">
                    <div
                        v-for="product in categoryProducts"
                        :key="product.url"
                        class="bg-card rounded-lg border border-border overflow-hidden hover:shadow-lg transition-all cursor-pointer group"
                        @click="openProduct(product)"
                    >
                        <!-- Product Image -->
                        <div class="aspect-square bg-muted flex items-center justify-center overflow-hidden relative">
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                @error="handleImageError"
                            />
                            <svg v-else class="w-16 h-16 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="p-4">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2 min-h-[2.5rem]">
                                {{ product.name || 'Товар' }}
                            </h3>
                            
                            <!-- Price -->
                            <div v-if="product.price || product.price_text" class="mb-2">
                                <span class="font-bold text-primary text-lg">
                                    {{ formatPrice(product.price) || product.price_text }}
                                </span>
                            </div>
                            
                            <!-- SKU -->
                            <div v-if="product.sku" class="mb-2">
                                <code class="px-2 py-1 bg-muted rounded text-xs">
                                    Артикул: {{ product.sku }}
                                </code>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex gap-2 mt-3">
                                <button
                                    @click.stop="openProduct(product)"
                                    class="flex-1 px-3 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors text-sm"
                                >
                                    Открыть
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="!loadingProducts && selectedCategory" class="text-center text-muted-foreground py-8">
                Нажмите "Загрузить товары" для получения списка товаров из категории
            </div>
        </div>

        <!-- Product Parsing Section -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-xl font-semibold mb-4">Парсинг товаров в БД</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Категория из БД</label>
                        <select
                            v-model.number="parsingSettings.categoryId"
                            @change="onCategoryChange"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        >
                            <option :value="null">Выберите категорию</option>
                            <option
                                v-for="category in dbCategories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }} ({{ category.subcategories?.length || 0 }} подкатегорий)
                            </option>
                        </select>
                        <p v-if="dbCategories.length === 0" class="text-xs text-muted-foreground mt-1">
                            Категории не загружены. Нажмите "Обновить список категорий" или запустите команду "php artisan set-categories"
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Подкатегория из БД</label>
                        <select
                            v-model.number="parsingSettings.subcategoryId"
                            :disabled="!parsingSettings.categoryId || availableSubcategories.length === 0"
                            class="w-full h-10 px-3 border border-border rounded bg-background disabled:opacity-50"
                        >
                            <option :value="null">Выберите подкатегорию</option>
                            <option
                                v-for="subcategory in availableSubcategories"
                                :key="subcategory.id"
                                :value="subcategory.id"
                            >
                                {{ subcategory.name }}
                            </option>
                        </select>
                        <p v-if="parsingSettings.categoryId && availableSubcategories.length === 0" class="text-xs text-muted-foreground mt-1">
                            У выбранной категории нет подкатегорий
                        </p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Или укажите cat_id с сайта</label>
                    <input
                        v-model.number="parsingSettings.catId"
                        type="number"
                        min="1"
                        placeholder="Например: 21"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                    <p class="text-xs text-muted-foreground mt-1">cat_id из essensworld.ru (если не выбрана категория из БД)</p>
                </div>
                <div class="mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="parsingSettings.saveImages"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span class="text-sm">Сохранять изображения товаров в Media (папка "общая")</span>
                    </label>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="startParsingProducts"
                        :disabled="parsingProducts || (!parsingSettings.categoryId && !parsingSettings.subcategoryId && !parsingSettings.catId)"
                        class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                    >
                        <span v-if="parsingProducts">Парсинг...</span>
                        <span v-else>🚀 Запустить парсинг товаров</span>
                    </button>
                    <button
                        @click="loadDbCategories"
                        :disabled="loadingDbCategories"
                        class="px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
                    >
                        <span v-if="loadingDbCategories">Загрузка...</span>
                        <span v-else>Обновить список категорий</span>
                    </button>
                </div>
                <div v-if="parsingResult" class="mt-4 p-4 rounded-lg" :class="parsingResult.success ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20'">
                    <div class="font-medium" :class="parsingResult.success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ parsingResult.success ? '✓ Парсинг завершен' : '✗ Ошибка парсинга' }}
                    </div>
                    <div v-if="parsingResult.data" class="text-sm text-muted-foreground mt-2">
                        <div>Найдено товаров: {{ parsingResult.data.total_found || 0 }}</div>
                        <div>Сохранено: {{ parsingResult.data.saved || 0 }}</div>
                        <div v-if="parsingResult.data.errors > 0">Ошибок: {{ parsingResult.data.errors }}</div>
                        <div v-if="parsingResult.data.skipped > 0">Пропущено: {{ parsingResult.data.skipped }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-xl font-semibold mb-4">Настройки парсинга</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium mb-1 block">Начальная страница</label>
                    <input
                        v-model.number="settings.startPage"
                        type="number"
                        min="1"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                    <p class="text-xs text-muted-foreground mt-1">С какой страницы начинать парсинг</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Количество страниц</label>
                    <input
                        v-model.number="settings.pagesCount"
                        type="number"
                        min="1"
                        max="100"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                    <p class="text-xs text-muted-foreground mt-1">Сколько страниц парсить (макс. 100)</p>
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="settings.autoParse"
                        type="checkbox"
                        class="w-4 h-4"
                    />
                    <span class="text-sm">Автоматически парсить все страницы</span>
                </label>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    name: 'CategoryParsing',
    setup() {
        const checkingAvailability = ref(false);
        const availabilityResult = ref(null);
        const checkingAuth = ref(false);
        const authResult = ref(null);
        const loadingCategories = ref(false);
        const categories = ref([]);
        const selectedCategory = ref(null);
        const loadingProducts = ref(false);
        const categoryProducts = ref([]);
        const currentPage = ref(1);
        const manualCatId = ref(null);
        const settings = ref({
            startPage: 1,
            pagesCount: 1,
            autoParse: false,
        });
        
        // Для парсинга товаров в БД
        const dbCategories = ref([]);
        const loadingDbCategories = ref(false);
        const parsingSettings = ref({
            categoryId: null,
            subcategoryId: null,
            catId: null,
            saveImages: false,
        });
        const parsingProducts = ref(false);
        const parsingResult = ref(null);

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

        const fetchCategories = async () => {
            loadingCategories.value = true;
            categories.value = [];

            try {
                const response = await axios.get('/api/admin/parsing/eshop/categories');
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
            currentPage.value = settings.value.startPage;
        };

        const fetchCategoryProducts = async () => {
            if (!selectedCategory.value) return;

            loadingProducts.value = true;
            categoryProducts.value = [];

            try {
                const response = await axios.post('/api/admin/parsing/eshop/category-products', {
                    cat_id: selectedCategory.value.id,
                    page: currentPage.value,
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

        const openProduct = (product) => {
            // Открываем товар в новой вкладке
            window.open(product.url, '_blank');
        };

        const formatPrice = (price) => {
            if (!price) return null;
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(price);
        };

        const handleImageError = (event) => {
            // Скрываем изображение при ошибке загрузки
            event.target.style.display = 'none';
        };

        const testWithKnownCategory = () => {
            manualCatId.value = 19;
            selectManualCategory();
        };

        const selectManualCategory = () => {
            if (!manualCatId.value || manualCatId.value < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ошибка',
                    text: 'Введите корректный cat_id (число больше 0)',
                });
                return;
            }

            selectedCategory.value = {
                id: manualCatId.value,
                name: 'Категория ' + manualCatId.value,
                url: `https://www.essensworld.ru/eshop.php?cat_id=${manualCatId.value}`,
            };
            categoryProducts.value = [];
            currentPage.value = settings.value.startPage;
        };

        const loadDbCategories = async () => {
            loadingDbCategories.value = true;
            try {
                const response = await axios.get('/api/admin/categories', {
                    params: {
                        per_page: 1000, // Получаем все категории
                    },
                });
                
                // API возвращает пагинированные данные, нужно извлечь data
                let categories = [];
                if (response.data && response.data.data) {
                    // Если это пагинированный ответ
                    if (Array.isArray(response.data.data)) {
                        categories = response.data.data;
                    } else if (response.data.data.data && Array.isArray(response.data.data.data)) {
                        // Если это вложенная структура пагинации
                        categories = response.data.data.data;
                    }
                } else if (Array.isArray(response.data)) {
                    // Если ответ - массив напрямую
                    categories = response.data;
                }
                
                dbCategories.value = categories;
                
                if (categories.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Информация',
                        text: 'Категории не найдены. Запустите команду "php artisan set-categories" для синхронизации категорий.',
                    });
                }
            } catch (error) {
                console.error('Error loading categories:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось загрузить категории из БД',
                });
            } finally {
                loadingDbCategories.value = false;
            }
        };

        const onCategoryChange = () => {
            // Сбрасываем подкатегорию при изменении категории
            parsingSettings.value.subcategoryId = null;
        };

        const availableSubcategories = computed(() => {
            if (!parsingSettings.value.categoryId) {
                return [];
            }
            const category = dbCategories.value.find(c => {
                // Проверяем как число и как строку, так как ID может быть строкой
                return c.id == parsingSettings.value.categoryId || c.id === parsingSettings.value.categoryId;
            });
            
            if (!category) {
                return [];
            }
            
            // Подкатегории могут быть в разных форматах
            if (Array.isArray(category.subcategories)) {
                return category.subcategories;
            }
            
            return [];
        });

        const startParsingProducts = async () => {
            if (!parsingSettings.value.categoryId && !parsingSettings.value.subcategoryId && !parsingSettings.value.catId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ошибка',
                    text: 'Выберите категорию/подкатегорию или укажите cat_id',
                });
                return;
            }

            parsingProducts.value = true;
            parsingResult.value = null;

            try {
                const response = await axios.post('/api/admin/parsing/eshop/parse-and-save-products', {
                    category_id: parsingSettings.value.categoryId || null,
                    subcategory_id: parsingSettings.value.subcategoryId || null,
                    cat_id: parsingSettings.value.catId || null,
                    save_images: parsingSettings.value.saveImages || false,
                });

                parsingResult.value = response.data;

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Успешно',
                        html: `
                            <div class="text-left">
                                <p><strong>Найдено товаров:</strong> ${response.data.data.total_found}</p>
                                <p><strong>Сохранено:</strong> ${response.data.data.saved}</p>
                                ${response.data.data.errors > 0 ? `<p><strong>Ошибок:</strong> ${response.data.data.errors}</p>` : ''}
                                ${response.data.data.skipped > 0 ? `<p><strong>Пропущено:</strong> ${response.data.data.skipped}</p>` : ''}
                            </div>
                        `,
                    });
                } else {
                    throw new Error(response.data.message || 'Ошибка при парсинге');
                }
            } catch (error) {
                parsingResult.value = {
                    success: false,
                    message: error.response?.data?.message || 'Ошибка при парсинге товаров',
                };
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.response?.data?.message || 'Не удалось выполнить парсинг товаров',
                });
            } finally {
                parsingProducts.value = false;
            }
        };

        return {
            checkingAvailability,
            availabilityResult,
            checkingAuth,
            authResult,
            loadingCategories,
            categories,
            selectedCategory,
            loadingProducts,
            categoryProducts,
            currentPage,
            manualCatId,
            settings,
            checkAvailability,
            checkAuthentication,
            fetchCategories,
            selectCategory,
            fetchCategoryProducts,
            openProduct,
            testWithKnownCategory,
            selectManualCategory,
            formatPrice,
            handleImageError,
            dbCategories,
            loadingDbCategories,
            parsingSettings,
            parsingProducts,
            parsingResult,
            availableSubcategories,
            loadDbCategories,
            onCategoryChange,
            startParsingProducts,
        };
        
        // Загружаем категории из БД при монтировании компонента
        onMounted(() => {
            loadDbCategories();
        });
    },
};
</script>

<style scoped>
.category-parsing-page {
    min-height: 100vh;
}
</style>


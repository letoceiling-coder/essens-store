<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                <!-- Breadcrumbs -->
            <nav v-if="product" class="mb-6 text-sm text-muted-foreground">
                <router-link to="/" class="hover:text-primary transition-colors">Главная</router-link>
                <span class="mx-2">/</span>
                <router-link to="/catalog" class="hover:text-primary transition-colors">Каталог</router-link>
                <span v-if="product.subcategory?.category" class="mx-2">/</span>
                <router-link
                    v-if="product.subcategory?.category"
                    :to="`/catalog?category_id=${product.subcategory.category.id}`"
                    class="hover:text-primary transition-colors"
                >
                    {{ product.subcategory.category.name }}
                </router-link>
                <span v-if="product.subcategory" class="mx-2">/</span>
                <router-link
                    v-if="product.subcategory"
                    :to="`/catalog?subcategory_id=${product.subcategory.id}`"
                    class="hover:text-primary transition-colors"
                >
                    {{ product.subcategory.name }}
                </router-link>
                <span class="mx-2">/</span>
                <span class="text-foreground">{{ product.name }}</span>
            </nav>
            <nav v-else class="mb-6 text-sm text-muted-foreground">
                <router-link to="/" class="hover:text-primary transition-colors">Главная</router-link>
                <span class="mx-2">/</span>
                <router-link to="/catalog" class="hover:text-primary transition-colors">Каталог</router-link>
                <span class="mx-2">/</span>
                <span class="text-foreground">Товар</span>
            </nav>

            <div v-if="loading" class="text-center py-12">
                <p class="text-muted-foreground">Загрузка товара...</p>
            </div>
            <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-12">
                <!-- Product Images -->
                <div class="bg-card rounded-lg border border-border overflow-hidden">
                    <div class="aspect-square bg-muted flex items-center justify-center overflow-hidden">
                        <img
                            v-if="primaryImage?.url"
                            :src="primaryImage.url"
                            :alt="product.name"
                            class="w-full h-full object-cover"
                        />
                        <svg v-else class="w-32 h-32 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <!-- Дополнительные изображения -->
                    <div v-if="product.images && product.images.length > 1" class="grid grid-cols-4 gap-2 p-2">
                        <div
                            v-for="image in product.images.slice(0, 4)"
                            :key="image.id"
                            class="aspect-square bg-muted rounded overflow-hidden cursor-pointer hover:opacity-75 transition-opacity border-2"
                            :class="primaryImage?.id === image.id ? 'border-primary' : 'border-transparent'"
                            @click="setPrimaryImage(image)"
                        >
                            <img :src="image.url" :alt="product.name" class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ product.name }}</h1>
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-3xl font-bold text-primary">{{ formatPrice(currentPrice) }}</span>
                            <span v-if="product.promotions && product.promotions.length > 0" class="px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                                Акция
                            </span>
                        </div>
                        <p v-if="product.sku" class="text-muted-foreground">
                            Артикул: {{ product.sku }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div v-if="product.description" class="mb-6">
                        <h2 class="text-lg font-semibold mb-2">Описание</h2>
                        <p class="text-muted-foreground leading-relaxed">
                            {{ product.description }}
                        </p>
                    </div>

                    <!-- Characteristics -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold mb-2">Характеристики</h2>
                        <dl class="space-y-2">
                            <div v-if="product.type" class="flex">
                                <dt class="text-muted-foreground w-32">Тип:</dt>
                                <dd class="text-foreground">{{ getTypeLabel(product.type) }}</dd>
                            </div>
                            <div v-if="product.volume" class="flex">
                                <dt class="text-muted-foreground w-32">Объем:</dt>
                                <dd class="text-foreground">{{ product.volume }}</dd>
                            </div>
                            <div v-if="product.gender_target" class="flex">
                                <dt class="text-muted-foreground w-32">Для:</dt>
                                <dd class="text-foreground">{{ getGenderLabel(product.gender_target) }}</dd>
                            </div>
                            <div v-if="product.subcategory" class="flex">
                                <dt class="text-muted-foreground w-32">Категория:</dt>
                                <dd class="text-foreground">{{ product.subcategory.name }}</dd>
                            </div>
                            <div class="flex">
                                <dt class="text-muted-foreground w-32">Наличие:</dt>
                                <dd class="text-foreground">
                                    <span :class="product.in_stock ? 'text-green-600' : 'text-red-600'">
                                        {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
                                    </span>
                                    <span v-if="product.stock_qty !== null" class="text-muted-foreground ml-2">
                                        ({{ product.stock_qty }} шт.)
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Variants -->
                    <div v-if="product.variants && product.variants.length > 0" class="mb-6">
                        <h2 class="text-lg font-semibold mb-2">Варианты</h2>
                        <div class="space-y-2">
                            <label
                                v-for="variant in product.variants"
                                :key="variant.id"
                                class="flex items-center justify-between p-3 border border-border rounded-lg cursor-pointer hover:border-primary transition-colors"
                                :class="selectedVariant?.id === variant.id ? 'border-primary bg-primary/5' : ''"
                                @click="selectedVariant = variant"
                            >
                                <span>{{ variant.variant_name }}</span>
                                <span class="font-semibold text-primary">{{ formatPrice(variant.price) }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Add to Cart -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            <label class="text-sm font-medium">Количество:</label>
                            <div class="flex items-center border border-border rounded-lg">
                                <button
                                    @click="quantity > 1 ? quantity-- : null"
                                    class="px-3 py-2 hover:bg-muted transition-colors"
                                >
                                    -
                                </button>
                                <input
                                    type="number"
                                    v-model.number="quantity"
                                    min="1"
                                    :max="product?.stock_qty || 999"
                                    class="w-16 text-center border-0 bg-transparent focus:outline-none"
                                />
                                <button
                                    @click="quantity++"
                                    class="px-3 py-2 hover:bg-muted transition-colors"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                        <button
                            :disabled="!product?.in_stock"
                            class="w-full py-3 bg-primary text-primary-contrast rounded-lg font-medium hover:opacity-90 transition-opacity mb-3 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ product?.in_stock ? 'Добавить в корзину' : 'Нет в наличии' }}
                        </button>
                        <button
                            :disabled="!product?.in_stock"
                            class="w-full py-3 border border-primary text-primary rounded-lg font-medium hover:bg-primary/10 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Купить в один клик
                        </button>
                    </div>

                    <!-- Delivery Info -->
                    <div class="bg-muted/50 rounded-lg p-4 text-sm">
                        <div class="flex items-start gap-3 mb-2">
                            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Бесплатная доставка при заказе от 3000 ₽</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Гарантия качества и возврат в течение 14 дней</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-border mb-6">
                <div class="flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'pb-4 px-1 font-medium transition-colors',
                            activeTab === tab.id
                                ? 'text-primary border-b-2 border-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div v-if="product" class="prose max-w-none">
                <div v-if="activeTab === 'description'">
                    <h3 class="text-xl font-semibold mb-4">Подробное описание</h3>
                    <p v-if="product.description" class="text-muted-foreground leading-relaxed mb-4">
                        {{ product.description }}
                    </p>
                    <p v-else class="text-muted-foreground">Описание товара отсутствует</p>
                </div>
                <div v-if="activeTab === 'specifications'">
                    <h3 class="text-xl font-semibold mb-4">Технические характеристики</h3>
                    <dl class="space-y-3">
                        <div v-if="product.sku" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Артикул:</dt>
                            <dd class="text-foreground">{{ product.sku }}</dd>
                        </div>
                        <div v-if="product.type" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Тип товара:</dt>
                            <dd class="text-foreground">{{ getTypeLabel(product.type) }}</dd>
                        </div>
                        <div v-if="product.volume" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Объем/Вес:</dt>
                            <dd class="text-foreground">{{ product.volume }}</dd>
                        </div>
                        <div v-if="product.gender_target" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Целевая аудитория:</dt>
                            <dd class="text-foreground">{{ getGenderLabel(product.gender_target) }}</dd>
                        </div>
                        <div v-if="product.subcategory" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Подкатегория:</dt>
                            <dd class="text-foreground">{{ product.subcategory.name }}</dd>
                        </div>
                        <div v-if="product.subcategory?.category" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Категория:</dt>
                            <dd class="text-foreground">{{ product.subcategory.category.name }}</dd>
                        </div>
                        <div v-if="product.tags && product.tags.length > 0" class="flex border-b border-border pb-2">
                            <dt class="text-muted-foreground w-48">Теги:</dt>
                            <dd class="text-foreground">
                                <span
                                    v-for="(tag, index) in product.tags"
                                    :key="index"
                                    class="inline-block px-2 py-1 bg-muted rounded text-sm mr-2 mb-2"
                                >
                                    {{ tag }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div v-if="activeTab === 'reviews'">
                    <h3 class="text-xl font-semibold mb-4">Отзывы</h3>
                    <p class="text-muted-foreground">Отзывы о товаре появятся здесь</p>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import StoreLayout from '@/layouts/StoreLayout.vue';

export default {
    name: 'Product',
    components: {
        StoreLayout,
    },
    setup() {
        const route = useRoute();
        const product = ref(null);
        const loading = ref(true);
        const quantity = ref(1);
        const selectedVariant = ref(null);
        const primaryImage = ref(null);
        const activeTab = ref('description');
        const tabs = [
            { id: 'description', label: 'Описание' },
            { id: 'specifications', label: 'Характеристики' },
            { id: 'reviews', label: 'Отзывы' },
        ];

        const fetchProduct = async () => {
            try {
                loading.value = true;
                const productSlug = route.params.slug;
                const response = await axios.get(`/api/store/products/${productSlug}`);
                product.value = response.data.data || response.data;
                
                // Устанавливаем основное изображение
                if (product.value.primary_image) {
                    primaryImage.value = product.value.primary_image;
                } else if (product.value.images && product.value.images.length > 0) {
                    primaryImage.value = product.value.images[0];
                }

                // Устанавливаем первый вариант по умолчанию
                if (product.value.variants && product.value.variants.length > 0) {
                    selectedVariant.value = product.value.variants[0];
                }

                // SEO мета-теги
                document.title = `${product.value.name} — Essens`;
                const metaDescription = document.querySelector('meta[name="description"]');
                if (metaDescription) {
                    metaDescription.setAttribute('content', product.value.description || `Купить ${product.value.name} в интернет-магазине Essens. Качественная продукция для здоровья и красоты.`);
                }
            } catch (error) {
                console.error('Error fetching product:', error);
                if (error.response?.status === 404) {
                    // Товар не найден
                }
            } finally {
                loading.value = false;
            }
        };

        const setPrimaryImage = (image) => {
            primaryImage.value = image;
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        };

        const getTypeLabel = (type) => {
            const labels = {
                'perfume': 'Парфюм',
                'cream': 'Крем',
                'spray': 'Спрей',
                'supplement': 'БАД',
                'cleaning': 'Чистящее средство',
                'makeup': 'Косметика',
                'set': 'Набор',
            };
            return labels[type] || type;
        };

        const getGenderLabel = (gender) => {
            const labels = {
                'male': 'Мужской',
                'female': 'Женский',
                'unisex': 'Унисекс',
                'children': 'Детский',
            };
            return labels[gender] || gender;
        };

        const currentPrice = computed(() => {
            if (selectedVariant.value) {
                return selectedVariant.value.price;
            }
            return product.value?.price || 0;
        });

        onMounted(() => {
            fetchProduct();
        });

        return {
            product,
            loading,
            quantity,
            selectedVariant,
            primaryImage,
            activeTab,
            tabs,
            setPrimaryImage,
            formatPrice,
            getTypeLabel,
            getGenderLabel,
            currentPrice,
        };
    },
};
</script>


<template>
    <div class="products-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Товары</h1>
                <p class="text-muted-foreground mt-1">Управление товарами</p>
            </div>
            <button
                v-if="!showEditModal"
                @click="showCreateModal = true"
                class="h-11 px-6 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать товар</span>
            </button>
        </div>

        <!-- Search and Filters (hidden when editing) -->
        <div v-if="!showEditModal" class="bg-card rounded-lg border border-border p-4">
            <div class="flex gap-4 flex-wrap">
                <div class="flex-1 min-w-[200px]">
                    <input
                        v-model="searchQuery"
                        @input="debouncedSearch"
                        type="text"
                        placeholder="Поиск по названию, SKU или описанию..."
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <select
                    v-model="filterCategoryId"
                    @change="onCategoryChange"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Все категории</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
                <select
                    v-model="filterSubcategoryId"
                    @change="fetchProducts"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Все подкатегории</option>
                    <option v-for="subcategory in subcategories" :key="subcategory.id" :value="subcategory.id">
                        {{ subcategory.name }}
                    </option>
                </select>
                <select
                    v-model="filterInStock"
                    @change="fetchProducts"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Все товары</option>
                    <option value="1">В наличии</option>
                    <option value="0">Нет в наличии</option>
                </select>
                <select
                    v-model="sortBy"
                    @change="fetchProducts"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="created_at">По дате создания</option>
                    <option value="name">По названию</option>
                    <option value="price">По цене</option>
                </select>
                <select
                    v-model="sortOrder"
                    @change="fetchProducts"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="desc">По убыванию</option>
                    <option value="asc">По возрастанию</option>
                </select>
            </div>
        </div>

        <!-- Edit Form (replaces list when editing) -->
        <div v-if="showEditModal" class="bg-card rounded-lg border border-border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <button
                        @click="closeEditForm"
                        class="p-2 hover:bg-muted rounded-lg transition-colors"
                        title="Назад к списку"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    <h2 class="text-2xl font-semibold">Редактировать товар</h2>
                </div>
            </div>
            <form @submit.prevent="saveProduct" @change.prevent class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Подкатегория *</label>
                        <select
                            v-model="form.subcategory_id"
                            required
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Выберите подкатегорию</option>
                            <option v-for="subcategory in subcategories" :key="subcategory.id" :value="subcategory.id">
                                {{ subcategory.category?.name || '' }} / {{ subcategory.name }}
                            </option>
                        </select>
                        <p v-if="errors.subcategory_id" class="text-red-500 text-xs mt-1">{{ errors.subcategory_id[0] }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">SKU</label>
                        <input
                            v-model="form.sku"
                            type="text"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="errors.sku" class="text-red-500 text-xs mt-1">{{ errors.sku[0] }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Название *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Тип</label>
                        <input
                            v-model="form.type"
                            type="text"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Целевой пол</label>
                        <select
                            v-model="form.gender_target"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Не указан</option>
                            <option value="male">Мужской</option>
                            <option value="female">Женский</option>
                            <option value="unisex">Унисекс</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Объем</label>
                        <input
                            v-model="form.volume"
                            type="text"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Цена *</label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="errors.price" class="text-red-500 text-xs mt-1">{{ errors.price[0] }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Валюта</label>
                        <select
                            v-model="form.currency"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="RUB">RUB</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Количество на складе</label>
                        <input
                            v-model.number="form.stock_qty"
                            type="number"
                            min="0"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.in_stock"
                            type="checkbox"
                            class="w-4 h-4 rounded border-border"
                        />
                        <span class="text-sm font-medium">В наличии</span>
                    </label>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    ></textarea>
                    <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Теги (через запятую)</label>
                    <input
                        v-model="tagsInput"
                        type="text"
                        placeholder="тег1, тег2, тег3"
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Изображения</label>
                    <MediaSelector
                        :model-value="form.images"
                        :multiple="true"
                        @update:modelValue="handleImagesUpdate"
                    />
                    <p v-if="errors.images" class="text-red-500 text-xs mt-1">{{ errors.images[0] }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Варианты товара</label>
                    <div class="space-y-2">
                        <div
                            v-for="(variant, index) in form.variants"
                            :key="index"
                            class="flex gap-2 items-end p-3 bg-muted/30 rounded-lg"
                        >
                            <div class="flex-1">
                                <label class="text-xs font-medium mb-1 block">Название варианта</label>
                                <input
                                    v-model="variant.variant_name"
                                    type="text"
                                    placeholder="Например: 50мл"
                                    class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                />
                            </div>
                            <div class="w-32">
                                <label class="text-xs font-medium mb-1 block">Цена</label>
                                <input
                                    v-model.number="variant.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                />
                            </div>
                            <div class="w-32">
                                <label class="text-xs font-medium mb-1 block">Количество</label>
                                <input
                                    v-model.number="variant.stock_qty"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                />
                            </div>
                            <button
                                type="button"
                                @click="removeVariant(index)"
                                class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm"
                            >
                                ✕
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="addVariant"
                            class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors text-sm"
                        >
                            + Добавить вариант
                        </button>
                    </div>
                </div>
                <div class="flex gap-2 pt-4">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex-1 px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                        {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                    <button
                        type="button"
                        @click="closeEditForm"
                        class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors"
                    >
                        Отмена
                    </button>
                </div>
            </form>
        </div>

        <!-- Products List (shown when not editing) -->
        <template v-else>
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <p class="text-muted-foreground">Загрузка товаров...</p>
            </div>

            <!-- Error State -->
            <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-destructive">{{ error }}</p>
            </div>

            <!-- Products Table -->
        <div v-if="!loading && products.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Изображение</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Категория</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Цена</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Наличие</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="product in products" :key="product.id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">{{ product.id }}</td>
                            <td class="px-6 py-4 text-sm">
                                <img
                                    v-if="product.primary_image?.url"
                                    :src="product.primary_image.url"
                                    :alt="product.name"
                                    class="w-16 h-16 object-cover rounded"
                                />
                                <div v-else class="w-16 h-16 bg-muted rounded flex items-center justify-center">
                                    <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ product.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                {{ product.subcategory?.category?.name || '-' }} / {{ product.subcategory?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <code class="px-2 py-1 bg-muted rounded text-xs">{{ product.sku || '-' }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ formatPrice(product.price) }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-md',
                                        product.in_stock
                                            ? 'bg-green-500/10 text-green-500'
                                            : 'bg-red-500/10 text-red-500'
                                    ]"
                                >
                                    {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editProduct(product)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteProduct(product)"
                                        class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
                </p>
                <div class="flex gap-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 border border-border rounded hover:bg-muted transition-colors disabled:opacity-50"
                    >
                        Предыдущая
                    </button>
                    <button
                        v-for="page in getPageNumbers()"
                        :key="page"
                        @click="changePage(page)"
                        :class="[
                            'px-3 py-1 rounded transition-colors',
                            page === pagination.current_page
                                ? 'bg-primary text-primary-contrast'
                                : 'border border-border hover:bg-muted'
                        ]"
                    >
                        {{ page }}
                    </button>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 border border-border rounded hover:bg-muted transition-colors disabled:opacity-50"
                    >
                        Следующая
                    </button>
                </div>
            </div>
        </div>

            <!-- Empty State -->
            <div v-if="!loading && products.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
                <p class="text-muted-foreground">Товары не найдены</p>
            </div>
        </template>

        <!-- Create Modal (only for creating new products) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        Создать товар
                    </h3>
                    <button @click="closeModal" class="p-2 hover:bg-muted rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveProduct" @change.prevent class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Подкатегория *</label>
                            <select
                                v-model="form.subcategory_id"
                                required
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">Выберите подкатегорию</option>
                                <option v-for="subcategory in subcategories" :key="subcategory.id" :value="subcategory.id">
                                    {{ subcategory.category?.name || '' }} / {{ subcategory.name }}
                                </option>
                            </select>
                            <p v-if="errors.subcategory_id" class="text-red-500 text-xs mt-1">{{ errors.subcategory_id[0] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">SKU</label>
                            <input
                                v-model="form.sku"
                                type="text"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="errors.sku" class="text-red-500 text-xs mt-1">{{ errors.sku[0] }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Название *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Тип</label>
                            <input
                                v-model="form.type"
                                type="text"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Целевой пол</label>
                            <select
                                v-model="form.gender_target"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">Не указан</option>
                                <option value="male">Мужской</option>
                                <option value="female">Женский</option>
                                <option value="unisex">Унисекс</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Объем</label>
                            <input
                                v-model="form.volume"
                                type="text"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Цена *</label>
                            <input
                                v-model.number="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="errors.price" class="text-red-500 text-xs mt-1">{{ errors.price[0] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Валюта</label>
                            <select
                                v-model="form.currency"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="RUB">RUB</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Количество на складе</label>
                            <input
                                v-model.number="form.stock_qty"
                                type="number"
                                min="0"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.in_stock"
                                type="checkbox"
                                class="w-4 h-4 rounded border-border"
                            />
                            <span class="text-sm font-medium">В наличии</span>
                        </label>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        ></textarea>
                        <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Теги (через запятую)</label>
                        <input
                            v-model="tagsInput"
                            type="text"
                            placeholder="тег1, тег2, тег3"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Изображения</label>
                        <MediaSelector
                            :model-value="form.images"
                            :multiple="true"
                            @update:modelValue="handleImagesUpdate"
                        />
                        <p v-if="errors.images" class="text-red-500 text-xs mt-1">{{ errors.images[0] }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Варианты товара</label>
                        <div class="space-y-2">
                            <div
                                v-for="(variant, index) in form.variants"
                                :key="index"
                                class="flex gap-2 items-end p-3 bg-muted/30 rounded-lg"
                            >
                                <div class="flex-1">
                                    <label class="text-xs font-medium mb-1 block">Название варианта</label>
                                    <input
                                        v-model="variant.variant_name"
                                        type="text"
                                        placeholder="Например: 50мл"
                                        class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                    />
                                </div>
                                <div class="w-32">
                                    <label class="text-xs font-medium mb-1 block">Цена</label>
                                    <input
                                        v-model.number="variant.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                    />
                                </div>
                                <div class="w-32">
                                    <label class="text-xs font-medium mb-1 block">Количество</label>
                                    <input
                                        v-model.number="variant.stock_qty"
                                        type="number"
                                        min="0"
                                        class="w-full px-3 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removeVariant(index)"
                                    class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm"
                                >
                                    ✕
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="addVariant"
                                class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors text-sm"
                            >
                                + Добавить вариант
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-4">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                        >
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors"
                        >
                            Отмена
                        </button>
                    </div>
                </form>
        </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import MediaSelector from '@/components/admin/MediaSelector.vue';

export default {
    name: 'Products',
    components: {
        MediaSelector,
    },
    setup() {
        const products = ref([]);
        const categories = ref([]);
        const subcategories = ref([]);
        const loading = ref(true);
        const error = ref(null);
        const showCreateModal = ref(false);
        const showEditModal = ref(false);
        const saving = ref(false);
        const searchQuery = ref('');
        const filterCategoryId = ref('');
        const filterSubcategoryId = ref('');
        const filterInStock = ref('');
        const sortBy = ref('created_at');
        const sortOrder = ref('desc');
        const pagination = ref({
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0,
        });
        const form = ref({
            id: null,
            subcategory_id: '',
            name: '',
            sku: '',
            type: '',
            gender_target: '',
            volume: '',
            price: 0,
            currency: 'RUB',
            in_stock: true,
            stock_qty: null,
            description: '',
            tags: [],
            images: [],
            variants: [],
        });
        const originalImages = ref([]); // Сохраняем исходные изображения товара
        const tagsInput = ref('');
        const errors = ref({});
        let searchTimeout = null;
        const isUpdatingImages = ref(false); // Флаг для предотвращения автоматического сохранения при обновлении изображений

        const fetchCategories = async () => {
            try {
                const response = await axios.get('/api/admin/categories');
                categories.value = response.data.data || response.data;
            } catch (err) {
                console.error('Error fetching categories:', err);
            }
        };

        const fetchSubcategories = async () => {
            try {
                const params = {};
                if (filterCategoryId.value) {
                    params.category_id = filterCategoryId.value;
                }
                const response = await axios.get('/api/admin/subcategories', { params });
                subcategories.value = response.data.data || response.data;
            } catch (err) {
                console.error('Error fetching subcategories:', err);
            }
        };

        const fetchProducts = async () => {
            try {
                loading.value = true;
                error.value = null;
                const params = {
                    page: pagination.value.current_page,
                    per_page: pagination.value.per_page,
                    sort_by: sortBy.value,
                    sort_order: sortOrder.value,
                };

                if (searchQuery.value) {
                    params.search = searchQuery.value;
                }

                if (filterCategoryId.value) {
                    params.category_id = filterCategoryId.value;
                }

                if (filterSubcategoryId.value) {
                    params.subcategory_id = filterSubcategoryId.value;
                }

                if (filterInStock.value !== '') {
                    params.in_stock = filterInStock.value;
                }

                const response = await axios.get('/api/admin/products', { params });
                
                if (response.data.data) {
                    products.value = response.data.data;
                    pagination.value = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        per_page: response.data.per_page || 15,
                        total: response.data.total || 0,
                        from: response.data.from || 0,
                        to: response.data.to || 0,
                    };
                } else {
                    products.value = response.data;
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке товаров';
                console.error('Error fetching products:', err);
            } finally {
                loading.value = false;
            }
        };

        const debouncedSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                pagination.value.current_page = 1;
                fetchProducts();
            }, 500);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                pagination.value.current_page = page;
                fetchProducts();
            }
        };

        const getPageNumbers = () => {
            const pages = [];
            const current = pagination.value.current_page;
            const last = pagination.value.last_page;
            
            if (last <= 7) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
            } else {
                if (current <= 3) {
                    for (let i = 1; i <= 5; i++) pages.push(i);
                    if (last > 5) pages.push('...');
                    pages.push(last);
                } else if (current >= last - 2) {
                    pages.push(1);
                    if (last > 5) pages.push('...');
                    for (let i = last - 4; i <= last; i++) pages.push(i);
                } else {
                    pages.push(1);
                    pages.push('...');
                    for (let i = current - 1; i <= current + 1; i++) pages.push(i);
                    pages.push('...');
                    pages.push(last);
                }
            }
            
            return pages.filter(p => p !== '...' || pages.indexOf(p) !== pages.lastIndexOf(p));
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        };

        const addVariant = () => {
            form.value.variants.push({
                variant_name: '',
                price: form.value.price || 0,
                stock_qty: null,
            });
        };

        const removeVariant = (index) => {
            form.value.variants.splice(index, 1);
        };

        const editProduct = async (product) => {
            try {
                const response = await axios.get(`/api/admin/products/${product.id}`);
                const productData = response.data.data || response.data;
                
                form.value = {
                    id: productData.id,
                    subcategory_id: productData.subcategory_id,
                    name: productData.name,
                    sku: productData.sku || '',
                    type: productData.type || '',
                    gender_target: productData.gender_target || '',
                    volume: productData.volume || '',
                    price: productData.price,
                    currency: productData.currency || 'RUB',
                    in_stock: productData.in_stock ?? true,
                    stock_qty: productData.stock_qty,
                    description: productData.description || '',
                    tags: productData.tags || [],
                    images: (productData.images || []).map(img => ({
                        id: img.id,
                        url: img.url,
                        is_primary: img.is_primary || false,
                        order: img.order || 0,
                    })),
                };
                
                // Сохраняем исходные изображения для сравнения при сохранении
                originalImages.value = (productData.images || []).map(img => ({
                    id: img.id,
                    url: img.url,
                }));
                
                form.value.variants = (productData.variants || []).map(v => ({
                    id: v.id,
                    variant_name: v.variant_name,
                    price: v.price,
                    stock_qty: v.stock_qty,
                }));
                
                tagsInput.value = form.value.tags.join(', ');
                showEditModal.value = true;
                await fetchSubcategories();
                // Прокручиваем к началу формы
                await nextTick();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось загрузить товар', 'error');
            }
        };

        const deleteProduct = async (product) => {
            const result = await Swal.fire({
                title: 'Удалить товар?',
                text: `Вы уверены, что хотите удалить товар "${product.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
            });

            if (result.isConfirmed) {
                try {
                    await axios.delete(`/api/admin/products/${product.id}`);
                    await Swal.fire('Удалено!', 'Товар успешно удален', 'success');
                    fetchProducts();
                } catch (err) {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось удалить товар', 'error');
                }
            }
        };

        // Обработчик обновления изображений (предотвращает автоматическое сохранение)
        const handleImagesUpdate = (images) => {
            console.log('[Products] handleImagesUpdate called', {
                imagesCount: images?.length || 0,
                isUpdatingImages: isUpdatingImages.value
            });
            isUpdatingImages.value = true;
            form.value.images = images;
            // Сбрасываем флаг после небольшой задержки (увеличена для надежности)
            setTimeout(() => {
                isUpdatingImages.value = false;
                console.log('[Products] isUpdatingImages reset to false');
            }, 300);
        };

        const saveProduct = async (event) => {
            // Проверяем, что это явный submit формы (кнопка "Сохранить")
            // Если событие не передано или это не submit события, не сохраняем
            if (!event || event.type !== 'submit') {
                console.log('[Products] saveProduct prevented - not a submit event', {
                    event,
                    eventType: event?.type,
                    hasEvent: !!event
                });
                return;
            }
            
            // Проверяем, что submit не пришел из модального окна MediaSelector
            // Это защита от случайного submit при клике на кнопку выбора фото
            if (event.target && event.target.closest('.media-selector')) {
                console.log('[Products] saveProduct prevented - submit from media selector');
                event.preventDefault();
                return;
            }
            
            // Проверяем, что это не автоматическое сохранение при обновлении изображений
            // Но разрешаем сохранение, если это явный submit формы (кнопка "Сохранить")
            // Ждем немного, если идет обновление изображений, чтобы дать время завершиться
            if (isUpdatingImages.value) {
                console.log('[Products] saveProduct - waiting for images update to complete');
                // Ждем завершения обновления изображений
                await new Promise(resolve => {
                    const checkInterval = setInterval(() => {
                        if (!isUpdatingImages.value) {
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 50);
                    // Максимальное время ожидания - 1 секунда
                    setTimeout(() => {
                        clearInterval(checkInterval);
                        resolve();
                    }, 1000);
                });
            }
            
            // Проверяем, что это не повторный вызов
            if (saving.value) {
                console.log('[Products] saveProduct prevented - already saving');
                return;
            }
            
            console.log('[Products] saveProduct called', {
                formId: form.value.id,
                imagesCount: form.value.images?.length || 0,
                eventType: event?.type,
                isUpdatingImages: isUpdatingImages.value
            });
            
            try {
                saving.value = true;
                errors.value = {};

                // Преобразуем теги из строки в массив
                const tags = tagsInput.value
                    .split(',')
                    .map(tag => tag.trim())
                    .filter(tag => tag.length > 0);

                // Преобразуем изображения для отправки
                // Если изображение новое (выбрано из медиа-библиотеки), не отправляем ID
                const images = form.value.images.map((img, index) => {
                    const imageData = {
                        url: img.url,
                        is_primary: img.is_primary || false,
                        order: img.order || index,
                    };
                    
                    // Отправляем ID только если это существующее изображение товара
                    // Проверяем, было ли это изображение в исходных данных товара
                    if (form.value.id && img.id && originalImages.value.length > 0) {
                        const existingImage = originalImages.value.find(origImg => origImg.id === img.id && origImg.url === img.url);
                        if (existingImage) {
                            // Это существующее изображение товара - отправляем ID
                            imageData.id = img.id;
                        }
                        // Если не найдено в исходных, значит это новое изображение - не отправляем ID
                    }
                    
                    return imageData;
                });

                const payload = {
                    ...form.value,
                    tags,
                    images,
                };

                const url = form.value.id
                    ? `/api/admin/products/${form.value.id}`
                    : '/api/admin/products';
                
                const method = form.value.id ? 'put' : 'post';
                const response = await axios[method](url, payload);

                await Swal.fire('Успешно!', response.data.message, 'success');
                if (showCreateModal.value) {
                    closeModal();
                } else if (showEditModal.value) {
                    closeEditForm();
                }
                fetchProducts();
            } catch (err) {
                if (err.response?.status === 422) {
                    errors.value = err.response.data.errors || {};
                } else {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось сохранить товар', 'error');
                }
            } finally {
                saving.value = false;
            }
        };

        const onCategoryChange = async () => {
            filterSubcategoryId.value = '';
            await fetchSubcategories();
            fetchProducts();
        };

        const closeModal = () => {
            showCreateModal.value = false;
            form.value = {
                id: null,
                subcategory_id: '',
                name: '',
                sku: '',
                type: '',
                gender_target: '',
                volume: '',
                price: 0,
                currency: 'RUB',
                in_stock: true,
                stock_qty: null,
                description: '',
                tags: [],
                images: [],
                variants: [],
            };
            originalImages.value = [];
            tagsInput.value = '';
            errors.value = {};
        };

        const closeEditForm = () => {
            showEditModal.value = false;
            form.value = {
                id: null,
                subcategory_id: '',
                name: '',
                sku: '',
                type: '',
                gender_target: '',
                volume: '',
                price: 0,
                currency: 'RUB',
                in_stock: true,
                stock_qty: null,
                description: '',
                tags: [],
                images: [],
                variants: [],
            };
            tagsInput.value = '';
            errors.value = {};
        };

        onMounted(() => {
            fetchCategories();
            fetchSubcategories();
            fetchProducts();
        });

        return {
            products,
            categories,
            subcategories,
            loading,
            error,
            showCreateModal,
            showEditModal,
            saving,
            searchQuery,
            filterCategoryId,
            filterSubcategoryId,
            filterInStock,
            sortBy,
            sortOrder,
            pagination,
            form,
            tagsInput,
            errors,
            fetchSubcategories,
            fetchProducts,
            debouncedSearch,
            changePage,
            getPageNumbers,
            formatPrice,
            addVariant,
            removeVariant,
            editProduct,
            deleteProduct,
            saveProduct,
            handleImagesUpdate,
            onCategoryChange,
            closeModal,
            closeEditForm,
        };
    },
};
</script>

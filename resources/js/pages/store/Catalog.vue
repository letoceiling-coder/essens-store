<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Каталог товаров</h1>
                <p class="text-muted-foreground">
                    Широкий ассортимент продукции для здоровья и красоты
                </p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Mobile Filter Button -->
                <div class="lg:hidden mb-4 flex items-center justify-between">
                    <p class="text-muted-foreground text-sm" v-if="!loading && !isApplyingFilters">
                        Найдено: <span class="font-medium text-foreground">{{ pagination.total || products.length || 0 }}</span>
                    </p>
                    <button
                        @click="showFilters = !showFilters"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity text-sm font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Фильтры
                        <span v-if="hasActiveFilters" class="bg-white/20 text-xs px-2 py-0.5 rounded-full">
                            {{ getActiveFiltersCount() }}
                        </span>
                    </button>
                </div>

                <!-- Mobile Filter Drawer -->
                <transition name="drawer">
                    <div
                        v-if="showFilters"
                        class="lg:hidden fixed inset-0 z-50 flex"
                        @click.self="showFilters = false"
                    >
                        <!-- Backdrop -->
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm drawer-backdrop" @click="showFilters = false"></div>
                        
                        <!-- Drawer -->
                        <div class="relative bg-card w-full max-w-sm h-full overflow-y-auto shadow-xl drawer-panel">
                        <div class="sticky top-0 bg-card border-b border-border p-4 flex items-center justify-between z-10">
                            <h2 class="text-lg font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Фильтры
                                <svg v-if="isApplyingFilters" class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </h2>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="hasActiveFilters"
                                    @click="resetFilters"
                                    class="text-sm text-primary hover:underline transition-opacity px-2 py-1"
                                    :class="{ 'opacity-50': isApplyingFilters }"
                                >
                                    Сбросить
                                </button>
                                <button
                                    @click="showFilters = false"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-4 space-y-6" :class="{ 'opacity-75': isApplyingFilters }">
                            <!-- Category Filter -->
                            <div>
                                <h3 class="font-medium mb-3 text-sm">Категории</h3>
                                <div v-if="loadingCategories" class="text-sm text-muted-foreground">
                                    Загрузка...
                                </div>
                                <div v-else class="space-y-1 max-h-48 overflow-y-auto">
                                    <label class="flex items-center cursor-pointer hover:bg-muted/50 p-2 rounded transition-colors">
                                        <input
                                            type="radio"
                                            name="category-mobile"
                                            :value="null"
                                            v-model="selectedCategoryId"
                                            @change="applyFilters"
                                            class="rounded border-border"
                                        />
                                        <span class="ml-2 text-sm">Все категории</span>
                                    </label>
                                    <template v-for="category in categories" :key="category.id">
                                        <label class="flex items-center cursor-pointer hover:bg-muted/50 p-2 rounded transition-colors">
                                            <input
                                                type="radio"
                                                name="category-mobile"
                                                :value="category.id"
                                                v-model="selectedCategoryId"
                                                @change="applyFilters"
                                                class="rounded border-border"
                                            />
                                            <span class="ml-2 text-sm font-medium">{{ category.name }}</span>
                                        </label>
                                        <!-- Subcategories -->
                                        <div v-if="selectedCategoryId === category.id && category.subcategories?.length" class="ml-6 space-y-1">
                                            <label
                                                v-for="subcategory in category.subcategories"
                                                :key="subcategory.id"
                                                class="flex items-center cursor-pointer hover:bg-muted/50 p-1 rounded transition-colors"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="subcategory.id"
                                                    v-model="selectedSubcategoryIds"
                                                    @change="debouncedApplyFilters"
                                                    class="rounded border-border"
                                                />
                                                <span class="ml-2 text-xs text-muted-foreground">{{ subcategory.name }}</span>
                                            </label>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Price Filter -->
                            <div>
                                <h3 class="font-medium mb-3 text-sm">Цена</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-xs text-muted-foreground">
                                        <span>От: {{ formatPrice(minPrice) }}</span>
                                        <span>До: {{ formatPrice(maxPrice) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model.number="minPrice"
                                            type="number"
                                            min="0"
                                            :max="maxPrice"
                                            placeholder="От"
                                            class="w-full px-3 py-2 bg-surface border border-border rounded-lg text-sm transition-all"
                                            @input="debouncedApplyFilters"
                                        />
                                        <input
                                            v-model.number="maxPrice"
                                            type="number"
                                            :min="minPrice"
                                            max="50000"
                                            placeholder="До"
                                            class="w-full px-3 py-2 bg-surface border border-border rounded-lg text-sm transition-all"
                                            @input="debouncedApplyFilters"
                                        />
                                    </div>
                                    <input
                                        type="range"
                                        :min="0"
                                        :max="50000"
                                        :step="100"
                                        v-model="maxPrice"
                                        @input="debouncedApplyFilters"
                                        class="w-full transition-opacity"
                                        :class="{ 'opacity-50': isApplyingFilters }"
                                    />
                                </div>
                            </div>

                            <!-- Type Filter -->
                            <div v-if="availableTypes.length > 0">
                                <h3 class="font-medium mb-3 text-sm">Тип</h3>
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    <label
                                        v-for="type in availableTypes"
                                        :key="type"
                                        class="flex items-center cursor-pointer hover:bg-muted/50 p-2 rounded transition-colors"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="type"
                                            v-model="selectedTypes"
                                            @change="debouncedApplyFilters"
                                            class="rounded border-border"
                                        />
                                        <span class="ml-2 text-sm">{{ type }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Gender Target Filter -->
                            <div v-if="availableGenders.length > 0">
                                <h3 class="font-medium mb-3 text-sm">Целевая аудитория</h3>
                                <div class="space-y-1">
                                    <label
                                        v-for="gender in availableGenders"
                                        :key="gender.value"
                                        class="flex items-center cursor-pointer hover:bg-muted/50 p-2 rounded transition-colors"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="gender.value"
                                            v-model="selectedGenders"
                                            @change="debouncedApplyFilters"
                                            class="rounded border-border"
                                        />
                                        <span class="ml-2 text-sm">{{ gender.label }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- In Stock Filter -->
                            <div>
                                <label class="flex items-center cursor-pointer hover:bg-muted/50 p-2 rounded transition-colors">
                                    <input
                                        type="checkbox"
                                        v-model="onlyInStock"
                                        @change="debouncedApplyFilters"
                                        class="rounded border-border"
                                    />
                                    <span class="ml-2 text-sm font-medium">Только в наличии</span>
                                </label>
                            </div>

                            <!-- Sort -->
                            <div>
                                <h3 class="font-medium mb-3 text-sm">Сортировка</h3>
                                <select
                                    v-model="sortBy"
                                    @change="applyFilters"
                                    class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary transition-all"
                                >
                                    <option value="created_at">По популярности</option>
                                    <option value="price-asc">Цена: по возрастанию</option>
                                    <option value="price-desc">Цена: по убыванию</option>
                                    <option value="name">По названию (А-Я)</option>
                                    <option value="name-desc">По названию (Я-А)</option>
                                </select>
                            </div>

                            <!-- Apply Button -->
                            <div class="sticky bottom-0 bg-card border-t border-border p-4 -mx-4 -mb-4 mt-6">
                                <button
                                    @click="showFilters = false"
                                    class="w-full px-4 py-3 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity font-medium"
                                >
                                    Применить фильтры
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </transition>

                <!-- Desktop Sidebar Filters -->
                <aside class="hidden lg:block lg:w-64 flex-shrink-0">
                    <div class="bg-card rounded-lg border border-border p-4 sticky top-24 transition-all" :class="{ 'opacity-75': isApplyingFilters }">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-semibold">Фильтры</h2>
                                <svg v-if="isApplyingFilters" class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <button
                                v-if="hasActiveFilters"
                                @click="resetFilters"
                                class="text-xs text-primary hover:underline transition-opacity px-2 py-1"
                                :class="{ 'opacity-50': isApplyingFilters }"
                            >
                                Сбросить
                            </button>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-4">
                            <h3 class="font-medium mb-2 text-sm">Категории</h3>
                            <div v-if="loadingCategories" class="text-xs text-muted-foreground">
                                Загрузка...
                            </div>
                            <div v-else class="space-y-1 max-h-48 overflow-y-auto">
                                <label class="flex items-center cursor-pointer hover:bg-muted/50 p-1.5 rounded transition-colors">
                                    <input
                                        type="radio"
                                        name="category"
                                        :value="null"
                                        v-model="selectedCategoryId"
                                        @change="applyFilters"
                                        class="rounded border-border w-3 h-3"
                                    />
                                    <span class="ml-2 text-xs">Все категории</span>
                                </label>
                                <template v-for="category in categories" :key="category.id">
                                    <label class="flex items-center cursor-pointer hover:bg-muted/50 p-1.5 rounded transition-colors">
                                        <input
                                            type="radio"
                                            name="category"
                                            :value="category.id"
                                            v-model="selectedCategoryId"
                                            @change="applyFilters"
                                            class="rounded border-border w-3 h-3"
                                        />
                                        <span class="ml-2 text-xs font-medium">{{ category.name }}</span>
                                    </label>
                                    <!-- Subcategories -->
                                    <div v-if="selectedCategoryId === category.id && category.subcategories?.length" class="ml-5 space-y-1">
                                        <label
                                            v-for="subcategory in category.subcategories"
                                            :key="subcategory.id"
                                            class="flex items-center cursor-pointer hover:bg-muted/50 p-1 rounded transition-colors"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="subcategory.id"
                                                v-model="selectedSubcategoryIds"
                                                @change="debouncedApplyFilters"
                                                class="rounded border-border w-3 h-3"
                                            />
                                            <span class="ml-2 text-xs text-muted-foreground">{{ subcategory.name }}</span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="mb-4">
                            <h3 class="font-medium mb-2 text-sm">Цена</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>{{ formatPrice(minPrice) }}</span>
                                    <span>{{ formatPrice(maxPrice) }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model.number="minPrice"
                                        type="number"
                                        min="0"
                                        :max="maxPrice"
                                        placeholder="От"
                                        class="w-full px-2 py-1.5 bg-surface border border-border rounded text-xs transition-all"
                                        @input="debouncedApplyFilters"
                                    />
                                    <input
                                        v-model.number="maxPrice"
                                        type="number"
                                        :min="minPrice"
                                        max="50000"
                                        placeholder="До"
                                        class="w-full px-2 py-1.5 bg-surface border border-border rounded text-xs transition-all"
                                        @input="debouncedApplyFilters"
                                    />
                                </div>
                                <input
                                    type="range"
                                    :min="0"
                                    :max="50000"
                                    :step="100"
                                    v-model="maxPrice"
                                    @input="debouncedApplyFilters"
                                    class="w-full transition-opacity"
                                    :class="{ 'opacity-50': isApplyingFilters }"
                                />
                            </div>
                        </div>

                        <!-- Type Filter -->
                        <div class="mb-4" v-if="availableTypes.length > 0">
                            <h3 class="font-medium mb-2 text-sm">Тип</h3>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                <label
                                    v-for="type in availableTypes"
                                    :key="type"
                                    class="flex items-center cursor-pointer hover:bg-muted/50 p-1.5 rounded transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        :value="type"
                                        v-model="selectedTypes"
                                        @change="debouncedApplyFilters"
                                        class="rounded border-border w-3 h-3"
                                    />
                                    <span class="ml-2 text-xs">{{ type }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Gender Target Filter -->
                        <div class="mb-4" v-if="availableGenders.length > 0">
                            <h3 class="font-medium mb-2 text-sm">Целевая аудитория</h3>
                            <div class="space-y-1">
                                <label
                                    v-for="gender in availableGenders"
                                    :key="gender.value"
                                    class="flex items-center cursor-pointer hover:bg-muted/50 p-1.5 rounded transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        :value="gender.value"
                                        v-model="selectedGenders"
                                        @change="debouncedApplyFilters"
                                        class="rounded border-border w-3 h-3"
                                    />
                                    <span class="ml-2 text-xs">{{ gender.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- In Stock Filter -->
                        <div class="mb-4">
                            <label class="flex items-center cursor-pointer hover:bg-muted/50 p-1.5 rounded transition-colors">
                                <input
                                    type="checkbox"
                                    v-model="onlyInStock"
                                    @change="debouncedApplyFilters"
                                    class="rounded border-border w-3 h-3"
                                />
                                <span class="ml-2 text-xs font-medium">Только в наличии</span>
                            </label>
                        </div>

                        <!-- Sort -->
                        <div>
                            <h3 class="font-medium mb-2 text-sm">Сортировка</h3>
                            <select
                                v-model="sortBy"
                                @change="applyFilters"
                                class="w-full bg-surface border border-border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary transition-all"
                            >
                                <option value="created_at">По популярности</option>
                                <option value="price-asc">Цена: по возрастанию</option>
                                <option value="price-desc">Цена: по убыванию</option>
                                <option value="name">По названию (А-Я)</option>
                                <option value="name-desc">По названию (Я-А)</option>
                            </select>
                        </div>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="flex-1">
                    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
                        <p class="text-muted-foreground text-sm lg:text-base" v-if="!loading && !isApplyingFilters">
                            Найдено товаров: <span class="font-medium text-foreground">{{ pagination.total || products.length || 0 }}</span>
                        </p>
                        <p v-else-if="isApplyingFilters" class="text-muted-foreground text-sm">
                            Применение фильтров...
                        </p>
                        
                        <!-- Grid View Toggle -->
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="text-sm text-muted-foreground hidden sm:inline">Отображение:</span>
                            <div class="flex items-center gap-1 bg-surface border border-border rounded-lg p-1">
                                <button
                                    @click="setGridColumns(3)"
                                    :class="[
                                        'p-2 rounded transition-colors',
                                        gridColumns === 3
                                            ? 'bg-primary text-primary-contrast'
                                            : 'text-muted-foreground hover:bg-muted'
                                    ]"
                                    title="3 колонки"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="setGridColumns(4)"
                                    :class="[
                                        'p-2 rounded transition-colors',
                                        gridColumns === 4
                                            ? 'bg-primary text-primary-contrast'
                                            : 'text-muted-foreground hover:bg-muted'
                                    ]"
                                    title="4 колонки"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 15a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-3z" />
                                    </svg>
                                </button>
                                <button
                                    @click="setGridColumns(5)"
                                    :class="[
                                        'p-2 rounded transition-colors',
                                        gridColumns === 5
                                            ? 'bg-primary text-primary-contrast'
                                            : 'text-muted-foreground hover:bg-muted'
                                    ]"
                                    title="5 колонок"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4a1 1 0 011-1h2.5a1 1 0 011 1v2.5a1 1 0 01-1 1H5a1 1 0 01-1-1V4zM14 4a1 1 0 011-1h2.5a1 1 0 011 1v2.5a1 1 0 01-1 1H15a1 1 0 01-1-1V4zM4 14a1 1 0 011-1h2.5a1 1 0 011 1v2.5a1 1 0 01-1 1H5a1 1 0 01-1-1v-2.5zM14 14a1 1 0 011-1h2.5a1 1 0 011 1v2.5a1 1 0 01-1 1H15a1 1 0 01-1-1v-2.5zM9 9a1 1 0 011-1h2.5a1 1 0 011 1v2.5a1 1 0 01-1 1H10a1 1 0 01-1-1V9z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="loading" class="text-center py-12">
                        <div class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-muted-foreground">Загрузка товаров...</p>
                        </div>
                    </div>
                    <div v-else-if="products.length === 0" class="text-center py-12">
                        <p class="text-muted-foreground mb-4">Товары не найдены</p>
                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity"
                        >
                            Сбросить фильтры
                        </button>
                    </div>
                    <transition-group
                        v-else
                        name="fade"
                        tag="div"
                        :class="[
                            'grid',
                            gridColumns === 3 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6' : '',
                            gridColumns === 4 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4' : '',
                            gridColumns === 5 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3' : '',
                            { 'opacity-50': isApplyingFilters }
                        ]"
                    >
                        <div
                            v-for="product in products"
                            :key="product.id"
                            class="bg-card rounded-lg border border-border overflow-hidden hover:shadow-lg transition-all duration-300 cursor-pointer group"
                            @click="$router.push(`/product/${product.id}`)"
                        >
                            <div class="aspect-square bg-muted flex items-center justify-center overflow-hidden relative">
                                <img
                                    v-if="product.primary_image?.url"
                                    :src="product.primary_image.url"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <svg v-else :class="[
                                    'text-muted-foreground',
                                    gridColumns === 4 ? 'w-12 h-12' : gridColumns === 5 ? 'w-10 h-10' : 'w-16 h-16'
                                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div v-if="product.promotions?.length > 0" :class="[
                                    'absolute top-1 left-1 bg-red-500 text-white rounded',
                                    gridColumns === 4 ? 'text-[10px] px-1.5 py-0.5' : gridColumns === 5 ? 'text-[9px] px-1 py-0.5' : 'text-xs px-2 py-1'
                                ]">
                                    Акция
                                </div>
                                <div v-if="!product.in_stock" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span :class="[
                                        'bg-white text-foreground rounded-lg font-medium',
                                        gridColumns === 4 ? 'px-2 py-1 text-xs' : gridColumns === 5 ? 'px-1.5 py-0.5 text-[10px]' : 'px-4 py-2'
                                    ]">Нет в наличии</span>
                                </div>
                            </div>
                            <div :class="[
                                gridColumns === 4 ? 'p-3' : gridColumns === 5 ? 'p-2' : 'p-4'
                            ]">
                                <h3 :class="[
                                    'font-semibold mb-1.5 line-clamp-2',
                                    gridColumns === 4 ? 'text-sm' : gridColumns === 5 ? 'text-xs' : 'text-base'
                                ]">{{ product.name }}</h3>
                                <p v-if="product.description && gridColumns < 5" :class="[
                                    'text-muted-foreground mb-2 line-clamp-2',
                                    gridColumns === 4 ? 'text-xs' : 'text-sm'
                                ]">
                                    {{ product.description }}
                                </p>
                                <div :class="[
                                    'flex items-center justify-between',
                                    gridColumns === 5 ? 'flex-col gap-2' : ''
                                ]">
                                    <div :class="gridColumns === 5 ? 'w-full' : ''">
                                        <div :class="gridColumns === 5 ? 'flex flex-col' : ''">
                                            <span :class="[
                                                'font-bold text-primary',
                                                gridColumns === 4 ? 'text-lg' : gridColumns === 5 ? 'text-base' : 'text-xl'
                                            ]">{{ formatPrice(getProductPrice(product)) }}</span>
                                            <span v-if="product.promotions?.length > 0" :class="[
                                                'text-muted-foreground line-through',
                                                gridColumns === 4 ? 'ml-1.5 text-xs' : gridColumns === 5 ? 'ml-1 text-[10px]' : 'ml-2 text-sm'
                                            ]">
                                                {{ formatPrice(product.price) }}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        @click.stop="addToCart(product)"
                                        :disabled="!product.in_stock"
                                        :class="[
                                            'bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed',
                                            gridColumns === 4 ? 'px-3 py-1.5 text-xs' : gridColumns === 5 ? 'px-2 py-1 text-[10px] w-full' : 'px-4 py-2 text-sm'
                                        ]"
                                    >
                                        В корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition-group>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="mt-12 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <button
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-2 rounded-lg border border-border hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Предыдущая
                            </button>
                            <button
                                v-for="page in getPageNumbers()"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-3 py-2 rounded-lg transition-colors',
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
                                class="px-3 py-2 rounded-lg border border-border hover:bg-muted transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Следующая
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import StoreLayout from '@/layouts/StoreLayout.vue';

export default {
    name: 'Catalog',
    components: {
        StoreLayout,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const products = ref([]);
        const loading = ref(true);
        const categories = ref([]);
        const loadingCategories = ref(true);
        const showFilters = ref(false);
        
        // Grid columns (default: 4)
        const gridColumns = ref(parseInt(localStorage.getItem('catalogGridColumns')) || 4);
        
        // Filters
        const selectedCategoryId = ref(null);
        const selectedSubcategoryIds = ref([]);
        const minPrice = ref(0);
        const maxPrice = ref(50000);
        const selectedTypes = ref([]);
        const selectedGenders = ref([]);
        const onlyInStock = ref(true);
        const sortBy = ref('created_at');
        
        const pagination = ref({
            current_page: 1,
            last_page: 1,
            per_page: 12,
            total: 0,
        });

        const availableTypes = ref([]);
        const availableGenders = ref([
            { value: 'male', label: 'Мужской' },
            { value: 'female', label: 'Женский' },
            { value: 'unisex', label: 'Унисекс' },
        ]);

        const isApplyingFilters = ref(false);
        let debounceTimer = null;
        const DEBOUNCE_DELAY = 500; // 500ms задержка

        const hasActiveFilters = computed(() => {
            return selectedCategoryId.value !== null ||
                   selectedSubcategoryIds.value.length > 0 ||
                   minPrice.value > 0 ||
                   maxPrice.value < 50000 ||
                   selectedTypes.value.length > 0 ||
                   selectedGenders.value.length > 0 ||
                   onlyInStock.value === false;
        });

        const fetchCategories = async () => {
            try {
                loadingCategories.value = true;
                const response = await axios.get('/api/store/categories');
                categories.value = response.data.data || response.data;
            } catch (error) {
                console.error('Error fetching categories:', error);
            } finally {
                loadingCategories.value = false;
            }
        };

        const fetchProducts = async () => {
            try {
                if (!isApplyingFilters.value) {
                    loading.value = true;
                }
                const params = {
                    page: pagination.value.current_page,
                    per_page: pagination.value.per_page,
                    in_stock: onlyInStock.value,
                };

                // Category filter
                if (selectedCategoryId.value) {
                    params.category_id = selectedCategoryId.value;
                }

                // Subcategory filter
                if (selectedSubcategoryIds.value.length > 0) {
                    // Если выбраны подкатегории, фильтруем по ним
                    // Для этого нужно будет обновить API или фильтровать на клиенте
                    // Пока используем только одну подкатегорию
                    if (selectedSubcategoryIds.value.length === 1) {
                        params.subcategory_id = selectedSubcategoryIds.value[0];
                    }
                }

                // Price filter
                if (minPrice.value > 0) {
                    params.min_price = minPrice.value;
                }
                if (maxPrice.value < 50000) {
                    params.max_price = maxPrice.value;
                }

                // Type filter
                if (selectedTypes.value.length > 0) {
                    params.type = selectedTypes.value[0]; // Пока только один тип
                }

                // Gender filter
                if (selectedGenders.value.length > 0) {
                    params.gender_target = selectedGenders.value[0]; // Пока только один пол
                }

                // Sort
                if (sortBy.value === 'price-asc') {
                    params.sort_by = 'price';
                    params.sort_order = 'asc';
                } else if (sortBy.value === 'price-desc') {
                    params.sort_by = 'price';
                    params.sort_order = 'desc';
                } else if (sortBy.value === 'name') {
                    params.sort_by = 'name';
                    params.sort_order = 'asc';
                } else if (sortBy.value === 'name-desc') {
                    params.sort_by = 'name';
                    params.sort_order = 'desc';
                } else {
                    params.sort_by = 'created_at';
                    params.sort_order = 'desc';
                }

                const response = await axios.get('/api/store/products', { params });
                
                // Laravel Resource Collection с пагинацией возвращает:
                // { data: [...], meta: { current_page, last_page, per_page, total, from, to }, links: {...} }
                if (response.data.data && Array.isArray(response.data.data)) {
                    products.value = response.data.data;
                    
                    // Проверяем наличие метаданных пагинации
                    if (response.data.meta) {
                        pagination.value = {
                            current_page: response.data.meta.current_page || 1,
                            last_page: response.data.meta.last_page || 1,
                            per_page: response.data.meta.per_page || 12,
                            total: response.data.meta.total || 0,
                        };
                    } else if (response.data.current_page !== undefined) {
                        // Альтернативный формат: данные пагинации в корне объекта
                        pagination.value = {
                            current_page: response.data.current_page || 1,
                            last_page: response.data.last_page || 1,
                            per_page: response.data.per_page || 12,
                            total: response.data.total || 0,
                        };
                    } else {
                        // Нет пагинации, устанавливаем значения по умолчанию
                        pagination.value = {
                            current_page: 1,
                            last_page: 1,
                            per_page: 12,
                            total: products.value.length,
                        };
                    }

                    // Extract unique types from products
                    const types = new Set();
                    products.value.forEach(product => {
                        if (product.type) {
                            types.add(product.type);
                        }
                    });
                    availableTypes.value = Array.from(types).sort();
                } else {
                    // Если структура ответа не соответствует ожидаемой
                    products.value = Array.isArray(response.data) ? response.data : [];
                    pagination.value = {
                        current_page: 1,
                        last_page: 1,
                        per_page: 12,
                        total: products.value.length,
                    };
                }
            } catch (error) {
                console.error('Error fetching products:', error);
                products.value = [];
            } finally {
                if (!isApplyingFilters.value) {
                    loading.value = false;
                }
            }
        };

        const applyFilters = () => {
            pagination.value.current_page = 1;
            fetchProducts();
        };

        const debouncedApplyFilters = () => {
            isApplyingFilters.value = true;
            
            // Очищаем предыдущий таймер
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            
            // Устанавливаем новый таймер
            debounceTimer = setTimeout(() => {
                pagination.value.current_page = 1;
                fetchProducts().finally(() => {
                    isApplyingFilters.value = false;
                });
            }, DEBOUNCE_DELAY);
        };

        const resetFilters = () => {
            // Очищаем таймер при сбросе
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            isApplyingFilters.value = false;
            
            selectedCategoryId.value = null;
            selectedSubcategoryIds.value = [];
            minPrice.value = 0;
            maxPrice.value = 50000;
            selectedTypes.value = [];
            selectedGenders.value = [];
            onlyInStock.value = true;
            sortBy.value = 'created_at';
            applyFilters();
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                pagination.value.current_page = page;
                fetchProducts();
                window.scrollTo({ top: 0, behavior: 'smooth' });
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
                    pages.push('...');
                    pages.push(last);
                } else if (current >= last - 2) {
                    pages.push(1);
                    pages.push('...');
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

        const getProductPrice = (product) => {
            // Если есть активные промо-акции, можно вернуть цену со скидкой
            // Пока возвращаем обычную цену
            return product.price;
        };

        const addToCart = (product) => {
            // TODO: Implement cart functionality
            console.log('Add to cart:', product);
        };

        const getActiveFiltersCount = () => {
            let count = 0;
            if (selectedCategoryId.value !== null) count++;
            if (selectedSubcategoryIds.value.length > 0) count += selectedSubcategoryIds.value.length;
            if (minPrice.value > 0) count++;
            if (maxPrice.value < 50000) count++;
            if (selectedTypes.value.length > 0) count += selectedTypes.value.length;
            if (selectedGenders.value.length > 0) count += selectedGenders.value.length;
            if (onlyInStock.value === false) count++;
            return count;
        };

        const setGridColumns = (columns) => {
            gridColumns.value = columns;
            localStorage.setItem('catalogGridColumns', columns.toString());
        };

        // Watch for route changes
        watch(() => route.query, () => {
            if (route.query.category_id) {
                selectedCategoryId.value = parseInt(route.query.category_id);
            }
            if (route.query.subcategory_id) {
                selectedSubcategoryIds.value = [parseInt(route.query.subcategory_id)];
            }
            pagination.value.current_page = 1;
            fetchProducts();
        }, { immediate: false });

        onMounted(() => {
            fetchCategories();
            fetchProducts();
            
            // Initialize from route query
            if (route.query.category_id) {
                selectedCategoryId.value = parseInt(route.query.category_id);
            }
            if (route.query.subcategory_id) {
                selectedSubcategoryIds.value = [parseInt(route.query.subcategory_id)];
            }
            
            document.title = 'Каталог товаров Essens — Интернет-магазин продукции для здоровья';
            const metaDescription = document.querySelector('meta[name="description"]');
            if (metaDescription) {
                metaDescription.setAttribute('content', 'Каталог товаров Essens. Широкий ассортимент натуральной продукции для здоровья и красоты. Витамины, БАДы, косметика и многое другое.');
            }
        });

        return {
            products,
            loading,
            categories,
            loadingCategories,
            showFilters,
            selectedCategoryId,
            selectedSubcategoryIds,
            minPrice,
            maxPrice,
            selectedTypes,
            selectedGenders,
            onlyInStock,
            sortBy,
            pagination,
            availableTypes,
            availableGenders,
            hasActiveFilters,
            isApplyingFilters,
            applyFilters,
            debouncedApplyFilters,
            resetFilters,
            changePage,
            getPageNumbers,
            formatPrice,
            getProductPrice,
            addToCart,
            getActiveFiltersCount,
            gridColumns,
            setGridColumns,
        };
    },
};
</script>

<style scoped>
/* Product grid animations */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-move {
    transition: transform 0.3s ease;
}

/* Drawer animations */
.drawer-enter-active {
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-leave-active {
    transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-enter-from {
    opacity: 0;
}

.drawer-leave-to {
    opacity: 0;
}

/* Backdrop fade animation */
.drawer-enter-active .drawer-backdrop {
    animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-leave-active .drawer-backdrop {
    animation: fadeOut 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Drawer slide animation */
.drawer-enter-active .drawer-panel {
    animation: slideInLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-leave-active .drawer-panel {
    animation: slideOutLeft 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}

@keyframes slideOutLeft {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-100%);
    }
}
</style>


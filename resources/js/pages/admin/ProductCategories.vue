<template>
    <div class="product-categories-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Категории товаров</h1>
                <p class="text-muted-foreground mt-1">Управление категориями товаров</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать категорию</span>
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input
                        v-model="searchQuery"
                        @input="debouncedSearch"
                        type="text"
                        placeholder="Поиск по названию или slug..."
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <select
                    v-model="sortBy"
                    @change="fetchCategories"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="position">По позиции</option>
                    <option value="created_at">По дате создания</option>
                    <option value="name">По названию</option>
                    <option value="slug">По slug</option>
                </select>
                <select
                    v-model="sortOrder"
                    @change="fetchCategories"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="asc">По возрастанию</option>
                    <option value="desc">По убыванию</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка категорий...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Categories Table -->
        <div v-if="!loading && categories.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Позиция</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Подкатегории</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody ref="categoriesTableBody" class="divide-y divide-border">
                        <tr v-for="category in categories" :key="category.id" class="hover:bg-muted/10 cursor-move" :data-id="category.id">
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-muted-foreground cursor-grab active:cursor-grabbing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                    </svg>
                                    <input
                                        type="number"
                                        v-model.number="category.position"
                                        @change="updatePosition(category)"
                                        class="w-16 px-2 py-1 bg-surface border border-border rounded text-xs"
                                        min="0"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ category.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ category.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <code class="px-2 py-1 bg-muted rounded text-xs">{{ category.slug }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-md',
                                        category.is_active
                                            ? 'bg-green-500/10 text-green-500'
                                            : 'bg-red-500/10 text-red-500'
                                    ]"
                                >
                                    {{ category.is_active ? 'Активна' : 'Неактивна' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                {{ category.subcategories_count || category.subcategories?.length || 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editCategory(category)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
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
        <div v-if="!loading && categories.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Категории не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        {{ showEditModal ? 'Редактировать категорию' : 'Создать категорию' }}
                    </h3>
                    <button @click="closeModal" class="p-2 hover:bg-muted rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveCategory" class="space-y-4">
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
                    <div>
                        <label class="text-sm font-medium mb-1 block">Slug</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Оставьте пустым для автоматической генерации</p>
                        <p v-if="errors.slug" class="text-red-500 text-xs mt-1">{{ errors.slug[0] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Позиция</label>
                            <input
                                v-model.number="form.position"
                                type="number"
                                min="0"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="errors.position" class="text-red-500 text-xs mt-1">{{ errors.position[0] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Активность</label>
                            <label class="flex items-center gap-2 mt-2">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-border"
                                />
                                <span class="text-sm">Активна</span>
                            </label>
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
import { ref, onMounted, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Sortable from 'sortablejs';

export default {
    name: 'ProductCategories',
    setup() {
        const categories = ref([]);
        const loading = ref(true);
        const error = ref(null);
        const showCreateModal = ref(false);
        const showEditModal = ref(false);
        const saving = ref(false);
        const searchQuery = ref('');
        const sortBy = ref('position');
        const sortOrder = ref('asc');
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
            name: '',
            slug: '',
            is_active: true,
            position: 0,
        });
        const errors = ref({});
        let searchTimeout = null;
        const categoriesTableBody = ref(null);
        let sortableInstance = null;

        const fetchCategories = async () => {
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

                const response = await axios.get('/api/admin/categories', { params });
                
                if (response.data.data) {
                    categories.value = response.data.data;
                    pagination.value = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        per_page: response.data.per_page || 15,
                        total: response.data.total || 0,
                        from: response.data.from || 0,
                        to: response.data.to || 0,
                    };
                } else {
                    categories.value = response.data;
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке категорий';
                console.error('Error fetching categories:', err);
            } finally {
                loading.value = false;
                // Переинициализируем Sortable после загрузки данных
                nextTick(() => {
                    initSortable();
                });
            }
        };

        const debouncedSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                pagination.value.current_page = 1;
                fetchCategories();
            }, 500);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                pagination.value.current_page = page;
                fetchCategories();
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

        const editCategory = (category) => {
            form.value = {
                id: category.id,
                name: category.name,
                slug: category.slug,
                is_active: category.is_active ?? true,
                position: category.position ?? 0,
            };
            showEditModal.value = true;
        };

        const updatePosition = async (category) => {
            try {
                await axios.put(`/api/admin/categories/${category.id}`, {
                    name: category.name,
                    slug: category.slug,
                    is_active: category.is_active,
                    position: category.position,
                });
                // Обновляем список для правильной сортировки
                fetchCategories();
            } catch (err) {
                Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось обновить позицию', 'error');
                fetchCategories(); // Восстанавливаем исходное значение
            }
        };

        const updatePositions = async () => {
            try {
                // Обновляем позиции всех категорий в текущем порядке
                const updates = categories.value.map((category, index) => {
                    const newPosition = index + 1;
                    if (category.position !== newPosition) {
                        return axios.put(`/api/admin/categories/${category.id}`, {
                            name: category.name,
                            slug: category.slug,
                            is_active: category.is_active,
                            position: newPosition,
                        });
                    }
                    return Promise.resolve();
                });

                await Promise.all(updates);
                // Обновляем позиции в локальном массиве
                categories.value.forEach((category, index) => {
                    category.position = index + 1;
                });
            } catch (err) {
                console.error('Error updating positions:', err);
                Swal.fire('Ошибка!', 'Не удалось обновить позиции', 'error');
                fetchCategories(); // Восстанавливаем исходный порядок
            }
        };

        const initSortable = () => {
            if (sortableInstance) {
                sortableInstance.destroy();
            }

            if (categoriesTableBody.value) {
                sortableInstance = Sortable.create(categoriesTableBody.value, {
                    handle: 'td:first-child, .cursor-grab',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: async (evt) => {
                        const { oldIndex, newIndex } = evt;
                        if (oldIndex !== newIndex) {
                            // Обновляем позиции в массиве
                            const movedItem = categories.value.splice(oldIndex, 1)[0];
                            categories.value.splice(newIndex, 0, movedItem);
                            
                            // Обновляем позиции на сервере
                            await updatePositions();
                        }
                    },
                });
            }
        };

        const deleteCategory = async (category) => {
            const result = await Swal.fire({
                title: 'Удалить категорию?',
                text: `Вы уверены, что хотите удалить категорию "${category.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
            });

            if (result.isConfirmed) {
                try {
                    await axios.delete(`/api/admin/categories/${category.id}`);
                    await Swal.fire('Удалено!', 'Категория успешно удалена', 'success');
                    fetchCategories();
                } catch (err) {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось удалить категорию', 'error');
                }
            }
        };

        const saveCategory = async () => {
            try {
                saving.value = true;
                errors.value = {};

                const url = form.value.id
                    ? `/api/admin/categories/${form.value.id}`
                    : '/api/admin/categories';
                
                const method = form.value.id ? 'put' : 'post';
                const response = await axios[method](url, form.value);

                await Swal.fire('Успешно!', response.data.message, 'success');
                closeModal();
                fetchCategories();
            } catch (err) {
                if (err.response?.status === 422) {
                    errors.value = err.response.data.errors || {};
                } else {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось сохранить категорию', 'error');
                }
            } finally {
                saving.value = false;
            }
        };

        const closeModal = () => {
            showCreateModal.value = false;
            showEditModal.value = false;
            form.value = {
                id: null,
                name: '',
                slug: '',
                is_active: true,
                position: 0,
            };
            errors.value = {};
        };

        onMounted(async () => {
            await fetchCategories();
            await nextTick();
            initSortable();
        });

        onBeforeUnmount(() => {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
        });

        return {
            categories,
            loading,
            error,
            showCreateModal,
            showEditModal,
            saving,
            searchQuery,
            sortBy,
            sortOrder,
            pagination,
            form,
            errors,
            fetchCategories,
            debouncedSearch,
            changePage,
            getPageNumbers,
            editCategory,
            updatePosition,
            updatePositions,
            deleteCategory,
            saveCategory,
            closeModal,
            categoriesTableBody,
        };
    },
};
</script>

<style scoped>
.cursor-move {
    cursor: move;
}

.cursor-move:hover {
    background-color: rgba(var(--muted) / 0.1);
}

.cursor-grab:active {
    cursor: grabbing;
}

/* Стили для Sortable.js */
.sortable-ghost {
    opacity: 0.5;
    background-color: rgba(var(--primary) / 0.1);
}

.sortable-chosen {
    background-color: rgba(var(--primary) / 0.05);
}

.sortable-drag {
    background-color: rgba(var(--primary) / 0.1);
}
</style>

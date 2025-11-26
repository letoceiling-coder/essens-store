<template>
    <div class="product-subcategories-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Подкатегории товаров</h1>
                <p class="text-muted-foreground mt-1">Управление подкатегориями товаров</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать подкатегорию</span>
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
                    v-model="filterCategoryId"
                    @change="fetchSubcategories"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Все категории</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
                <select
                    v-model="sortBy"
                    @change="fetchSubcategories"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="position">По позиции</option>
                    <option value="created_at">По дате создания</option>
                    <option value="name">По названию</option>
                </select>
                <select
                    v-model="sortOrder"
                    @change="fetchSubcategories"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="desc">По убыванию</option>
                    <option value="asc">По возрастанию</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка подкатегорий...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Subcategories Table -->
        <div v-if="!loading && subcategories.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Позиция</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Категория</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Статус</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody ref="subcategoriesTableBody" class="divide-y divide-border">
                        <tr v-for="subcategory in subcategories" :key="subcategory.id" class="hover:bg-muted/10 cursor-move" :data-id="subcategory.id">
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-muted-foreground cursor-grab active:cursor-grabbing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                    </svg>
                                    <input
                                        type="number"
                                        v-model.number="subcategory.position"
                                        @change="updatePosition(subcategory)"
                                        class="w-16 px-2 py-1 bg-surface border border-border rounded text-xs"
                                        min="0"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ subcategory.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ subcategory.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                {{ subcategory.category?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <code class="px-2 py-1 bg-muted rounded text-xs">{{ subcategory.slug }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-md',
                                        subcategory.is_active
                                            ? 'bg-green-500/10 text-green-500'
                                            : 'bg-red-500/10 text-red-500'
                                    ]"
                                >
                                    {{ subcategory.is_active ? 'Активна' : 'Неактивна' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editSubcategory(subcategory)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteSubcategory(subcategory)"
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
        <div v-if="!loading && subcategories.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Подкатегории не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        {{ showEditModal ? 'Редактировать подкатегорию' : 'Создать подкатегорию' }}
                    </h3>
                    <button @click="closeModal" class="p-2 hover:bg-muted rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="saveSubcategory" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Категория *</label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">Выберите категорию</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="errors.category_id" class="text-red-500 text-xs mt-1">{{ errors.category_id[0] }}</p>
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
    name: 'ProductSubcategories',
    setup() {
        const subcategories = ref([]);
        const categories = ref([]);
        const loading = ref(true);
        const error = ref(null);
        const showCreateModal = ref(false);
        const showEditModal = ref(false);
        const saving = ref(false);
        const searchQuery = ref('');
        const filterCategoryId = ref('');
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
            category_id: '',
            name: '',
            slug: '',
            is_active: true,
            position: 0,
        });
        const errors = ref({});
        let searchTimeout = null;
        const subcategoriesTableBody = ref(null);
        let sortableInstance = null;

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

                const response = await axios.get('/api/admin/subcategories', { params });
                
                if (response.data.data) {
                    subcategories.value = response.data.data;
                    pagination.value = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        per_page: response.data.per_page || 15,
                        total: response.data.total || 0,
                        from: response.data.from || 0,
                        to: response.data.to || 0,
                    };
                } else {
                    subcategories.value = response.data;
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке подкатегорий';
                console.error('Error fetching subcategories:', err);
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
                fetchSubcategories();
            }, 500);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                pagination.value.current_page = page;
                fetchSubcategories();
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

        const editSubcategory = (subcategory) => {
            form.value = {
                id: subcategory.id,
                category_id: subcategory.category_id,
                name: subcategory.name,
                slug: subcategory.slug,
                is_active: subcategory.is_active ?? true,
                position: subcategory.position ?? 0,
            };
            showEditModal.value = true;
        };

        const updatePosition = async (subcategory) => {
            try {
                await axios.put(`/api/admin/subcategories/${subcategory.id}`, {
                    category_id: subcategory.category_id,
                    name: subcategory.name,
                    slug: subcategory.slug,
                    is_active: subcategory.is_active,
                    position: subcategory.position,
                });
                fetchSubcategories();
            } catch (err) {
                Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось обновить позицию', 'error');
                fetchSubcategories();
            }
        };

        const updatePositions = async () => {
            try {
                // Обновляем позиции всех подкатегорий в текущем порядке
                const updates = subcategories.value.map((subcategory, index) => {
                    const newPosition = index + 1;
                    if (subcategory.position !== newPosition) {
                        return axios.put(`/api/admin/subcategories/${subcategory.id}`, {
                            category_id: subcategory.category_id,
                            name: subcategory.name,
                            slug: subcategory.slug,
                            is_active: subcategory.is_active,
                            position: newPosition,
                        });
                    }
                    return Promise.resolve();
                });

                await Promise.all(updates);
                // Обновляем позиции в локальном массиве
                subcategories.value.forEach((subcategory, index) => {
                    subcategory.position = index + 1;
                });
            } catch (err) {
                console.error('Error updating positions:', err);
                Swal.fire('Ошибка!', 'Не удалось обновить позиции', 'error');
                fetchSubcategories(); // Восстанавливаем исходный порядок
            }
        };

        const initSortable = () => {
            if (sortableInstance) {
                sortableInstance.destroy();
            }

            if (subcategoriesTableBody.value) {
                sortableInstance = Sortable.create(subcategoriesTableBody.value, {
                    handle: 'td:first-child, .cursor-grab',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: async (evt) => {
                        const { oldIndex, newIndex } = evt;
                        if (oldIndex !== newIndex) {
                            // Обновляем позиции в массиве
                            const movedItem = subcategories.value.splice(oldIndex, 1)[0];
                            subcategories.value.splice(newIndex, 0, movedItem);
                            
                            // Обновляем позиции на сервере
                            await updatePositions();
                        }
                    },
                });
            }
        };

        const deleteSubcategory = async (subcategory) => {
            const result = await Swal.fire({
                title: 'Удалить подкатегорию?',
                text: `Вы уверены, что хотите удалить подкатегорию "${subcategory.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
            });

            if (result.isConfirmed) {
                try {
                    await axios.delete(`/api/admin/subcategories/${subcategory.id}`);
                    await Swal.fire('Удалено!', 'Подкатегория успешно удалена', 'success');
                    fetchSubcategories();
                } catch (err) {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось удалить подкатегорию', 'error');
                }
            }
        };

        const saveSubcategory = async () => {
            try {
                saving.value = true;
                errors.value = {};

                const url = form.value.id
                    ? `/api/admin/subcategories/${form.value.id}`
                    : '/api/admin/subcategories';
                
                const method = form.value.id ? 'put' : 'post';
                const response = await axios[method](url, form.value);

                await Swal.fire('Успешно!', response.data.message, 'success');
                closeModal();
                fetchSubcategories();
            } catch (err) {
                if (err.response?.status === 422) {
                    errors.value = err.response.data.errors || {};
                } else {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось сохранить подкатегорию', 'error');
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
                category_id: '',
                name: '',
                slug: '',
                is_active: true,
                position: 0,
            };
            errors.value = {};
        };

        onMounted(async () => {
            await fetchCategories();
            await fetchSubcategories();
            await nextTick();
            initSortable();
        });

        onBeforeUnmount(() => {
            if (sortableInstance) {
                sortableInstance.destroy();
            }
        });

        return {
            subcategories,
            categories,
            loading,
            error,
            showCreateModal,
            showEditModal,
            saving,
            searchQuery,
            filterCategoryId,
            sortBy,
            sortOrder,
            pagination,
            form,
            errors,
            fetchSubcategories,
            debouncedSearch,
            changePage,
            getPageNumbers,
            editSubcategory,
            updatePosition,
            updatePositions,
            deleteSubcategory,
            saveSubcategory,
            closeModal,
            subcategoriesTableBody,
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

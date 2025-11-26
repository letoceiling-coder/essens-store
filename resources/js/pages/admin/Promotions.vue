<template>
    <div class="promotions-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Промо-акции</h1>
                <p class="text-muted-foreground mt-1">Управление промо-акциями</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать промо-акцию</span>
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
                        placeholder="Поиск по названию или описанию..."
                        class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <select
                    v-model="filterIsActive"
                    @change="fetchPromotions"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Все акции</option>
                    <option value="1">Активные</option>
                    <option value="0">Неактивные</option>
                </select>
                <select
                    v-model="sortBy"
                    @change="fetchPromotions"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="created_at">По дате создания</option>
                    <option value="name">По названию</option>
                    <option value="start_date">По дате начала</option>
                </select>
                <select
                    v-model="sortOrder"
                    @change="fetchPromotions"
                    class="px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="desc">По убыванию</option>
                    <option value="asc">По возрастанию</option>
                </select>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка промо-акций...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Promotions Table -->
        <div v-if="!loading && promotions.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Период</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Товаров</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Статус</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="promotion in promotions" :key="promotion.id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">{{ promotion.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ promotion.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div v-if="promotion.start_date || promotion.end_date" class="text-xs">
                                    <div v-if="promotion.start_date">
                                        С: {{ formatDate(promotion.start_date) }}
                                    </div>
                                    <div v-if="promotion.end_date">
                                        По: {{ formatDate(promotion.end_date) }}
                                    </div>
                                    <div v-if="!promotion.start_date && !promotion.end_date" class="text-muted-foreground">
                                        Без ограничений
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                {{ promotion.products?.length || 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs rounded-md',
                                        promotion.is_active
                                            ? 'bg-green-500/10 text-green-500'
                                            : 'bg-red-500/10 text-red-500'
                                    ]"
                                >
                                    {{ promotion.is_active ? 'Активна' : 'Неактивна' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editPromotion(promotion)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deletePromotion(promotion)"
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
        <div v-if="!loading && promotions.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Промо-акции не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        {{ showEditModal ? 'Редактировать промо-акцию' : 'Создать промо-акцию' }}
                    </h3>
                    <button @click="closeModal" class="p-2 hover:bg-muted rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="savePromotion" class="space-y-4">
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
                        <label class="text-sm font-medium mb-1 block">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        ></textarea>
                        <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Дата начала</label>
                            <input
                                v-model="form.start_date"
                                type="datetime-local"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="errors.start_date" class="text-red-500 text-xs mt-1">{{ errors.start_date[0] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Дата окончания</label>
                            <input
                                v-model="form.end_date"
                                type="datetime-local"
                                class="w-full px-4 py-2 bg-surface border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="errors.end_date" class="text-red-500 text-xs mt-1">{{ errors.end_date[0] }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 rounded border-border"
                            />
                            <span class="text-sm font-medium">Активна</span>
                        </label>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Товары</label>
                        <div class="max-h-60 overflow-y-auto border border-border rounded-lg p-2">
                            <div v-if="products.length === 0" class="text-muted-foreground text-sm py-4 text-center">
                                Загрузка товаров...
                            </div>
                            <div v-else class="space-y-2">
                                <label
                                    v-for="product in products"
                                    :key="product.id"
                                    class="flex items-center gap-2 p-2 hover:bg-muted/50 rounded cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="product.id"
                                        v-model="form.product_ids"
                                        class="w-4 h-4 rounded border-border"
                                    />
                                    <span class="text-sm">{{ product.name }}</span>
                                    <span class="text-xs text-muted-foreground ml-auto">{{ formatPrice(product.price) }}</span>
                                </label>
                            </div>
                        </div>
                        <p v-if="errors.product_ids" class="text-red-500 text-xs mt-1">{{ errors.product_ids[0] }}</p>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    name: 'Promotions',
    setup() {
        const promotions = ref([]);
        const products = ref([]);
        const loading = ref(true);
        const error = ref(null);
        const showCreateModal = ref(false);
        const showEditModal = ref(false);
        const saving = ref(false);
        const searchQuery = ref('');
        const filterIsActive = ref('');
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
            name: '',
            description: '',
            start_date: '',
            end_date: '',
            is_active: true,
            product_ids: [],
        });
        const errors = ref({});
        let searchTimeout = null;

        const fetchProducts = async () => {
            try {
                const response = await axios.get('/api/admin/products', { params: { per_page: 1000 } });
                products.value = response.data.data || response.data;
            } catch (err) {
                console.error('Error fetching products:', err);
            }
        };

        const fetchPromotions = async () => {
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

                if (filterIsActive.value !== '') {
                    params.is_active = filterIsActive.value;
                }

                const response = await axios.get('/api/admin/promotions', { params });
                
                if (response.data.data) {
                    promotions.value = response.data.data;
                    pagination.value = {
                        current_page: response.data.current_page || 1,
                        last_page: response.data.last_page || 1,
                        per_page: response.data.per_page || 15,
                        total: response.data.total || 0,
                        from: response.data.from || 0,
                        to: response.data.to || 0,
                    };
                } else {
                    promotions.value = response.data;
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке промо-акций';
                console.error('Error fetching promotions:', err);
            } finally {
                loading.value = false;
            }
        };

        const debouncedSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                pagination.value.current_page = 1;
                fetchPromotions();
            }, 500);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                pagination.value.current_page = page;
                fetchPromotions();
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

        const formatDate = (dateString) => {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price);
        };

        const formatDateTimeLocal = (dateString) => {
            if (!dateString) return '';
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        };

        const editPromotion = (promotion) => {
            form.value = {
                id: promotion.id,
                name: promotion.name,
                description: promotion.description || '',
                start_date: formatDateTimeLocal(promotion.start_date),
                end_date: formatDateTimeLocal(promotion.end_date),
                is_active: promotion.is_active ?? true,
                product_ids: promotion.products?.map(p => p.id) || [],
            };
            showEditModal.value = true;
        };

        const deletePromotion = async (promotion) => {
            const result = await Swal.fire({
                title: 'Удалить промо-акцию?',
                text: `Вы уверены, что хотите удалить промо-акцию "${promotion.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
            });

            if (result.isConfirmed) {
                try {
                    await axios.delete(`/api/admin/promotions/${promotion.id}`);
                    await Swal.fire('Удалено!', 'Промо-акция успешно удалена', 'success');
                    fetchPromotions();
                } catch (err) {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось удалить промо-акцию', 'error');
                }
            }
        };

        const savePromotion = async () => {
            try {
                saving.value = true;
                errors.value = {};

                const url = form.value.id
                    ? `/api/admin/promotions/${form.value.id}`
                    : '/api/admin/promotions';
                
                const method = form.value.id ? 'put' : 'post';
                const response = await axios[method](url, form.value);

                await Swal.fire('Успешно!', response.data.message, 'success');
                closeModal();
                fetchPromotions();
            } catch (err) {
                if (err.response?.status === 422) {
                    errors.value = err.response.data.errors || {};
                } else {
                    Swal.fire('Ошибка!', err.response?.data?.message || 'Не удалось сохранить промо-акцию', 'error');
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
                description: '',
                start_date: '',
                end_date: '',
                is_active: true,
                product_ids: [],
            };
            errors.value = {};
        };

        onMounted(() => {
            fetchProducts();
            fetchPromotions();
        });

        return {
            promotions,
            products,
            loading,
            error,
            showCreateModal,
            showEditModal,
            saving,
            searchQuery,
            filterIsActive,
            sortBy,
            sortOrder,
            pagination,
            form,
            errors,
            fetchPromotions,
            debouncedSearch,
            changePage,
            getPageNumbers,
            formatDate,
            formatPrice,
            editPromotion,
            deletePromotion,
            savePromotion,
            closeModal,
        };
    },
};
</script>

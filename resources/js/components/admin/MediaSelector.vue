<template>
    <div class="media-selector" data-media-selector="true">
        <!-- Selected Images Preview -->
        <div v-if="selectedImages.length > 0" class="mb-4">
            <label class="block text-sm font-medium mb-2">Выбранные изображения ({{ selectedImages.length }})</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div
                    v-for="(image, index) in selectedImages"
                    :key="image.id || index"
                    class="relative group aspect-square bg-muted rounded-lg overflow-hidden border-2"
                    :class="image.is_primary ? 'border-primary' : 'border-border'"
                >
                    <img
                        :src="image.url"
                        :alt="image.original_name || 'Image'"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button
                            @click="setPrimary(index)"
                            :class="[
                                'px-2 py-1 rounded text-xs',
                                image.is_primary
                                    ? 'bg-primary text-primary-contrast'
                                    : 'bg-white/90 text-foreground hover:bg-white'
                            ]"
                            :title="image.is_primary ? 'Основное изображение' : 'Сделать основным'"
                        >
                            {{ image.is_primary ? '★' : '☆' }}
                        </button>
                        <button
                            @click="removeImage(index)"
                            class="px-2 py-1 rounded text-xs bg-destructive text-white hover:bg-destructive/90"
                            title="Удалить"
                        >
                            ✕
                        </button>
                    </div>
                    <div
                        v-if="image.is_primary"
                        class="absolute top-2 left-2 bg-primary text-primary-contrast text-xs px-2 py-1 rounded"
                    >
                        Основное
                    </div>
                    <div class="absolute bottom-2 left-2 right-2">
                        <input
                            type="number"
                            v-model.number="image.order"
                            min="0"
                            class="w-full px-2 py-1 text-xs bg-black/70 text-white rounded border-0 focus:outline-none focus:ring-1 focus:ring-primary"
                            placeholder="Порядок"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Media Library Button -->
        <div class="flex items-center gap-4">
            <button
                @click="showMediaModal = true"
                type="button"
                class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ selectedImages.length > 0 ? 'Изменить изображения' : 'Выбрать изображения' }}
            </button>
            <span v-if="selectedImages.length > 0" class="text-sm text-muted-foreground">
                Выбрано: {{ selectedImages.length }}
            </span>
        </div>

        <!-- Media Modal -->
        <div
            v-if="showMediaModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div class="bg-card rounded-lg border border-border w-full max-w-6xl max-h-[90vh] flex flex-col m-4" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-border">
                    <h2 class="text-xl font-semibold">Выбор изображений</h2>
                    <button
                        @click="closeModal"
                        class="p-2 hover:bg-muted rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Media Content -->
                <div class="flex-1 overflow-y-auto min-h-0" @click.stop>
                    <Media
                        :selection-mode="true"
                        :count-file="multiple ? 999 : 1"
                        :selected-files="selectedImages"
                        @file-selected="handleFileSelected"
                    />
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between p-6 border-t border-border">
                    <span class="text-sm text-muted-foreground">
                        Выбрано: {{ selectedImages.length }} {{ selectedImages.length === 1 ? 'изображение' : 'изображений' }}
                    </span>
                    <div class="flex gap-2">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors"
                        >
                            Закрыть
                        </button>
                        <button
                            v-if="multiple"
                            @click="confirmSelection"
                            class="px-4 py-2 bg-primary text-primary-contrast rounded-lg hover:opacity-90 transition-opacity"
                        >
                            Подтвердить выбор
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, watch } from 'vue';
import Media from '@/pages/admin/Media.vue';

export default {
    name: 'MediaSelector',
    components: {
        Media,
    },
    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        multiple: {
            type: Boolean,
            default: true,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const showMediaModal = ref(false);
        const selectedImages = ref([]);

        // Инициализация выбранных изображений
        watch(() => props.modelValue, (newValue) => {
            if (Array.isArray(newValue)) {
                selectedImages.value = newValue.map(img => ({
                    ...img,
                    is_primary: img.is_primary || false,
                    order: img.order || 0,
                }));
            } else {
                selectedImages.value = [];
            }
        }, { immediate: true });

        // Синхронизация при открытии модального окна
        watch(() => showMediaModal.value, (isOpen) => {
            if (isOpen && Array.isArray(props.modelValue)) {
                selectedImages.value = props.modelValue.map(img => ({
                    ...img,
                    is_primary: img.is_primary || false,
                    order: img.order || 0,
                }));
            }
        });

        // Обновление модели при изменении выбранных изображений
        const updateModel = () => {
            emit('update:modelValue', selectedImages.value);
        };

        const handleFileSelected = (files) => {
            // Обновляем выбранные изображения из Media компонента (без применения к модели)
            if (Array.isArray(files)) {
                // Сохраняем текущие значения is_primary и order для существующих изображений
                const existingImages = new Map();
                selectedImages.value.forEach(img => {
                    existingImages.set(img.id, {
                        is_primary: img.is_primary,
                        order: img.order,
                    });
                });
                
                selectedImages.value = files.map((file, index) => {
                    const existing = existingImages.get(file.id);
                    return {
                        id: file.id,
                        url: file.url,
                        original_name: file.original_name || file.name,
                        is_primary: existing ? existing.is_primary : (index === 0 && files.length > 0 && selectedImages.value.length === 0),
                        order: existing ? existing.order : index,
                    };
                });
                
                // Если нет основного изображения, делаем первое основным
                if (selectedImages.value.length > 0 && !selectedImages.value.some(img => img.is_primary)) {
                    selectedImages.value[0].is_primary = true;
                }
                
                // Обновляем порядок для новых изображений
                selectedImages.value.forEach((img, index) => {
                    if (!existingImages.has(img.id)) {
                        img.order = index;
                    }
                });

                // НЕ применяем изменения к модели сразу - только при нажатии "Подтвердить выбор"
            }
        };

        // Подтверждение выбора (кнопка "Подтвердить выбор")
        const confirmSelection = () => {
            // Применяем выбранные изображения к модели (только обновляем v-model, без сохранения товара)
            updateModel();
            // Закрываем модальное окно
            showMediaModal.value = false;
        };

        const closeModal = () => {
            showMediaModal.value = false;
            // Восстанавливаем выбранные изображения из props при закрытии без применения
            if (Array.isArray(props.modelValue)) {
                selectedImages.value = props.modelValue.map(img => ({
                    ...img,
                    is_primary: img.is_primary || false,
                    order: img.order || 0,
                }));
            }
        };

        const setPrimary = (index) => {
            selectedImages.value.forEach((img, i) => {
                img.is_primary = i === index;
            });
            sortImages();
            // НЕ применяем изменения к модели сразу - только при нажатии "Подтвердить выбор"
        };

        const removeImage = (index) => {
            selectedImages.value.splice(index, 1);
            // Если удалили основное изображение, делаем первое основным
            if (selectedImages.value.length > 0 && !selectedImages.value.some(img => img.is_primary)) {
                selectedImages.value[0].is_primary = true;
            }
            sortImages();
            // НЕ применяем изменения к модели сразу - только при нажатии "Подтвердить выбор"
        };

        const sortImages = () => {
            selectedImages.value.sort((a, b) => {
                // Сначала основное изображение
                if (a.is_primary && !b.is_primary) return -1;
                if (!a.is_primary && b.is_primary) return 1;
                // Затем по порядку
                return (a.order || 0) - (b.order || 0);
            });
            // Обновляем порядок после сортировки
            selectedImages.value.forEach((img, index) => {
                img.order = index;
            });
        };

        return {
            showMediaModal,
            selectedImages,
            multiple: props.multiple,
            handleFileSelected,
            confirmSelection,
            closeModal,
            setPrimary,
            removeImage,
            sortImages,
            updateModel,
        };
    },
};
</script>

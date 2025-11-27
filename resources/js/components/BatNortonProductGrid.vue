<template>
    <div class="bn-catalog-grid">
        <div
            v-for="product in products"
            :key="product.id"
            class="bn-item-card"
        >
            <router-link :to="`/product/${product.slug || product.id}`" class="bn-item-link">
                <img
                    :src="getImageUrl(product.primary_image?.url || product.images?.[0]?.url)"
                    :alt="product.name"
                    class="bn-item-image"
                    @error="handleImageError"
                />
                <div class="bn-item-info">
                    <h3 class="bn-item-name">{{ product.name }}</h3>
                    <div class="bn-item-price">
                        {{ formatPrice(getProductPrice(product)) }}
                        <span v-if="product.old_price && (product.recommended_price || product.discounted_price)" class="bn-item-old-price">
                            {{ formatPrice(product.old_price) }}
                        </span>
                    </div>
                </div>
            </router-link>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';

export default {
    name: 'BatNortonProductGrid',
    props: {
        products: {
            type: Array,
            required: true,
            default: () => [],
        },
    },
    setup(props) {
        const getImageUrl = (url) => {
            if (!url) return '';
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            if (url.startsWith('//')) {
                return `https:${url}`;
            }
            return `${window.location.origin}${url}`;
        };

        const handleImageError = (event) => {
            event.target.style.display = 'none';
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0,
            }).format(price || 0);
        };

        const getProductPrice = (product) => {
            return product.recommended_price || product.discounted_price || product.price || 0;
        };

        return {
            getImageUrl,
            handleImageError,
            formatPrice,
            getProductPrice,
        };
    },
};
</script>

<style scoped>
.bn-catalog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.bn-item-card {
    background-color: var(--bn-surface, #111);
    border: 1px solid var(--bn-border, #222);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.bn-item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
}

.bn-item-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.bn-item-image {
    width: 100%;
    height: auto;
    object-fit: cover;
    display: block;
    aspect-ratio: 1;
}

.bn-item-info {
    padding: 10px;
    color: var(--bn-text, #fff);
}

.bn-item-name {
    font-size: 16px;
    margin: 10px 0 5px 0;
    font-weight: normal;
    line-height: 1.4;
    color: var(--bn-text, #fff);
}

.bn-item-price {
    font-size: 14px;
    color: var(--bn-text, #fff);
    display: flex;
    align-items: center;
    gap: 8px;
}

.bn-item-old-price {
    font-size: 12px;
    color: var(--bn-text-secondary, #ccc);
    text-decoration: line-through;
}

/* Light Theme Overrides */
.bn-theme-light .bn-item-card {
    background-color: var(--bn-surface, #F5F5F5);
    border-color: var(--bn-border, #E0E0E0);
}

.bn-theme-light .bn-item-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Mobile */
@media (max-width: 768px) {
    .bn-catalog-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 10px 0;
    }
}
</style>


<script setup>
import {
    Heart,
    ShoppingBag,
    Star,
} from '@lucide/vue'

defineProps({
    product: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits([
    'add-to-cart',
    'toggle-wishlist',
])

function formatPrice(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value)
}
</script>

<template>
    <article class="product-card">
        <div class="product-card__media">
            <RouterLink :to="{
                name: 'product-detail',
                params: {
                    slug: product.slug,
                },
            }" class="product-card__image-link">
                <img :src="product.image" :alt="product.name" class="product-card__image" />
            </RouterLink>

            <span v-if="product.badge" class="product-card__badge">
                {{ product.badge }}
            </span>

            <button type="button" class="product-card__wishlist" :aria-label="`Thêm ${product.name} vào yêu thích`
                " @click="
                    emit('toggle-wishlist', product)
                    ">
                <Heart :size="19" :fill="product.is_wishlisted
                        ? 'currentColor'
                        : 'none'
                    " />
            </button>
        </div>

        <div class="product-card__content">
            <p class="product-card__category">
                {{ product.category }}
            </p>

            <RouterLink :to="{
                name: 'product-detail',
                params: {
                    slug: product.slug,
                },
            }" class="product-card__name">
                {{ product.name }}
            </RouterLink>

            <div class="product-card__rating">
                <Star :size="15" fill="currentColor" />

                <span>
                    {{ product.rating }}
                </span>

                <span class="product-card__reviews">
                    ({{ product.reviews }})
                </span>
            </div>

            <div class="product-card__footer">
                <div class="product-card__prices">
                    <span class="product-card__price">
                        {{ formatPrice(product.price) }}
                    </span>

                    <span v-if="product.originalPrice" class="product-card__original-price">
                        {{
                            formatPrice(
                                product.originalPrice,
                            )
                        }}
                    </span>
                </div>

                <button type="button" class="product-card__cart" :aria-label="`Thêm ${product.name} vào giỏ hàng`
                    " @click="
                        emit('add-to-cart', product)
                        ">
                    <ShoppingBag :size="18" />
                </button>
            </div>
        </div>
    </article>
</template>

<style scoped>
.product-card {
    overflow: hidden;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    transition:
        border-color var(--transition-base),
        box-shadow var(--transition-base),
        transform var(--transition-base);
}

.product-card:hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-md);
    transform: translateY(-3px);
}

.product-card__media {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: var(--color-gray-100);
}

.product-card__image-link {
    display: block;
    width: 100%;
    height: 100%;
}

.product-card__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 320ms ease;
}

.product-card:hover .product-card__image {
    transform: scale(1.035);
}

.product-card__badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 5px 9px;
    color: var(--color-primary-800);
    background: var(--color-primary-100);
    border-radius: var(--radius-pill);
    font-size: 11px;
    font-weight: 700;
}

.product-card__wishlist {
    position: absolute;
    top: 12px;
    right: 12px;
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    padding: 0;
    color: var(--color-text-secondary);
    background: rgb(255 255 255 / 92%);
    border: 1px solid rgb(255 255 255 / 70%);
    border-radius: 50%;
    box-shadow: var(--shadow-sm);
    transition:
        color var(--transition-fast),
        background-color var(--transition-fast);
}

.product-card__wishlist:hover {
    color: var(--color-primary-700);
    background: var(--color-white);
}

.product-card__content {
    display: grid;
    gap: 10px;
    padding: 16px;
}

.product-card__category {
    color: var(--color-text-muted);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.product-card__name {
    min-height: 44px;
    color: var(--color-text-primary);
    font-size: 14px;
    font-weight: 600;
    line-height: 1.55;
    transition: color var(--transition-fast);
}

.product-card__name:hover {
    color: var(--color-primary-700);
}

.product-card__rating {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #a15c08;
    font-size: 12px;
    font-weight: 600;
}

.product-card__reviews {
    color: var(--color-text-muted);
    font-weight: 400;
}

.product-card__footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding-top: 4px;
}

.product-card__prices {
    display: grid;
    gap: 3px;
}

.product-card__price {
    color: var(--color-primary-700);
    font-size: 16px;
    font-weight: 700;
}

.product-card__original-price {
    color: var(--color-text-muted);
    font-size: 12px;
    text-decoration: line-through;
}

.product-card__cart {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 40px;
    height: 40px;
    padding: 0;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 0;
    border-radius: var(--radius-md);
    transition:
        background-color var(--transition-fast),
        transform var(--transition-fast);
}

.product-card__cart:hover {
    background: var(--color-primary-800);
}

.product-card__cart:active {
    transform: translateY(1px);
}
</style>
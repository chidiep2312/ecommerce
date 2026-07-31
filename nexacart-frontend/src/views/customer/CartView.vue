<script setup>
import {
    ArrowLeft,
    ChevronRight,
    Minus,
    PackageOpen,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Trash2,
    Truck,
} from '@lucide/vue'

import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { useCartStore } from '@/stores/cart'

const router = useRouter()
const cartStore = useCartStore()

const shippingFee = computed(() => {
    if (cartStore.isEmpty) {
        return 0
    }

    if (cartStore.subtotal >= 500000) {
        return 0
    }

    return 30000
})

const grandTotal = computed(() => {
    return (
        cartStore.subtotal +
        shippingFee.value
    )
})

function formatPrice(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value)
}

function goToCheckout() {
    if (cartStore.isEmpty) {
        return
    }

    router.push('/checkout')
}
</script>

<template>
    <div class="cart-page">
        <nav
            class="breadcrumb"
            aria-label="Breadcrumb"
        >
            <RouterLink to="/">
                Trang chủ
            </RouterLink>

            <ChevronRight :size="14" />

            <span aria-current="page">
                Giỏ hàng
            </span>
        </nav>

        <header class="cart-header">
            <div>
                <p class="cart-header__eyebrow">
                    Giỏ hàng của bạn
                </p>

                <h1>Giỏ hàng</h1>

                <p>
                    Kiểm tra sản phẩm và số lượng trước
                    khi chuyển sang thanh toán.
                </p>
            </div>

            <span class="cart-header__count">
                {{ cartStore.itemCount }}
                sản phẩm
            </span>
        </header>

        <div
            v-if="!cartStore.isEmpty"
            class="cart-layout"
        >
            <section class="cart-items">
                <div class="cart-items__heading">
                    <h2>
                        Sản phẩm đã chọn
                    </h2>

                    <button
                        type="button"
                        class="cart-items__clear"
                        @click="cartStore.clearCart"
                    >
                        Xóa tất cả
                    </button>
                </div>

                <article
                    v-for="item in cartStore.items"
                    :key="item.id"
                    class="cart-item"
                >
                    <RouterLink
                        :to="{
                            name: 'product-detail',
                            params: {
                                slug: item.slug,
                            },
                        }"
                        class="cart-item__image"
                    >
                        <img
                            :src="item.image"
                            :alt="item.name"
                        />
                    </RouterLink>

                    <div class="cart-item__information">
                        <div>
                            <p
                                v-if="item.seller"
                                class="cart-item__seller"
                            >
                                {{ item.seller }}
                            </p>

                            <RouterLink
                                :to="{
                                    name:
                                        'product-detail',
                                    params: {
                                        slug: item.slug,
                                    },
                                }"
                                class="cart-item__name"
                            >
                                {{ item.name }}
                            </RouterLink>
                        </div>

                        <div class="cart-item__prices">
                            <span
                                class="cart-item__price"
                            >
                                {{
                                    formatPrice(
                                        item.price,
                                    )
                                }}
                            </span>

                            <span
                                v-if="
                                    item.originalPrice
                                "
                                class="cart-item__original-price"
                            >
                                {{
                                    formatPrice(
                                        item.originalPrice,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="cart-item__quantity">
                        <span>Số lượng</span>

                        <div class="quantity-control">
                            <button
                                type="button"
                                aria-label="Giảm số lượng"
                                :disabled="
                                    item.quantity <= 1
                                "
                                @click="
                                    cartStore.decreaseQuantity(
                                        item.id,
                                    )
                                "
                            >
                                <Minus :size="15" />
                            </button>

                            <input
                                :value="item.quantity"
                                type="number"
                                min="1"
                                :max="
                                    item.stock ?? undefined
                                "
                                aria-label="Số lượng"
                                @change="
                                    cartStore.updateQuantity(
                                        item.id,
                                        $event.target.value,
                                    )
                                "
                            />

                            <button
                                type="button"
                                aria-label="Tăng số lượng"
                                :disabled="
                                    item.stock !== null &&
                                    item.quantity >=
                                        item.stock
                                "
                                @click="
                                    cartStore.increaseQuantity(
                                        item.id,
                                    )
                                "
                            >
                                <Plus :size="15" />
                            </button>
                        </div>

                        <span
                            v-if="item.stock !== null"
                            class="cart-item__stock"
                        >
                            Còn {{ item.stock }} sản phẩm
                        </span>
                    </div>

                    <div class="cart-item__total">
                        <span>Thành tiền</span>

                        <strong>
                            {{
                                formatPrice(
                                    item.price *
                                        item.quantity,
                                )
                            }}
                        </strong>
                    </div>

                    <button
                        type="button"
                        class="cart-item__remove"
                        :aria-label="
                            `Xóa ${item.name} khỏi giỏ hàng`
                        "
                        @click="
                            cartStore.removeItem(
                                item.id,
                            )
                        "
                    >
                        <Trash2 :size="18" />
                    </button>
                </article>

                <RouterLink
                    to="/products"
                    class="continue-shopping"
                >
                    <ArrowLeft :size="17" />

                    Tiếp tục mua sắm
                </RouterLink>
            </section>

            <aside class="order-summary">
                <div class="order-summary__header">
                    <ShoppingBag :size="20" />

                    <h2>Tóm tắt đơn hàng</h2>
                </div>

                <dl class="order-summary__details">
                    <div>
                        <dt>
                            Tạm tính
                        </dt>

                        <dd>
                            {{
                                formatPrice(
                                    cartStore.subtotal,
                                )
                            }}
                        </dd>
                    </div>

                    <div
                        v-if="
                            cartStore.discountAmount >
                            0
                        "
                    >
                        <dt>
                            Tiết kiệm
                        </dt>

                        <dd
                            class="order-summary__discount"
                        >
                            −{{
                                formatPrice(
                                    cartStore.discountAmount,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Phí vận chuyển
                        </dt>

                        <dd>
                            <span
                                v-if="
                                    shippingFee === 0
                                "
                                class="order-summary__free"
                            >
                                Miễn phí
                            </span>

                            <template v-else>
                                {{
                                    formatPrice(
                                        shippingFee,
                                    )
                                }}
                            </template>
                        </dd>
                    </div>
                </dl>

                <div
                    v-if="
                        shippingFee > 0
                    "
                    class="shipping-progress"
                >
                    <p>
                        Mua thêm
                        <strong>
                            {{
                                formatPrice(
                                    500000 -
                                        cartStore.subtotal,
                                )
                            }}
                        </strong>
                        để được miễn phí vận chuyển.
                    </p>

                    <div
                        class="shipping-progress__track"
                    >
                        <span
                            :style="{
                                width: `${Math.min(
                                    100,
                                    (cartStore.subtotal /
                                        500000) *
                                        100,
                                )}%`,
                            }"
                        />
                    </div>
                </div>

                <div class="order-summary__total">
                    <span>
                        Tổng thanh toán
                    </span>

                    <strong>
                        {{ formatPrice(grandTotal) }}
                    </strong>
                </div>

                <button
                    type="button"
                    class="checkout-button"
                    @click="goToCheckout"
                >
                    Tiến hành thanh toán
                </button>

                <div class="order-summary__benefits">
                    <article>
                        <ShieldCheck :size="18" />

                        <span>
                            Thanh toán được xử lý an toàn
                        </span>
                    </article>

                    <article>
                        <Truck :size="18" />

                        <span>
                            Theo dõi trạng thái giao hàng
                        </span>
                    </article>
                </div>
            </aside>
        </div>

        <section
            v-else
            class="empty-cart"
        >
            <div class="empty-cart__icon">
                <PackageOpen :size="30" />
            </div>

            <h2>Giỏ hàng đang trống</h2>

            <p>
                Bạn chưa thêm sản phẩm nào vào giỏ hàng.
                Hãy khám phá danh sách sản phẩm và chọn
                sản phẩm phù hợp.
            </p>

            <RouterLink
                to="/products"
                class="empty-cart__button"
            >
                <ShoppingBag :size="18" />

                Khám phá sản phẩm
            </RouterLink>
        </section>
    </div>
</template>

<style scoped>
.cart-page {
    display: grid;
    gap: 28px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    color: var(--color-text-muted);
    font-size: 12px;
}

.breadcrumb a:hover {
    color: var(--color-primary-700);
}

.cart-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 28px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--color-border);
}

.cart-header__eyebrow {
    margin-bottom: 9px;
    color: var(--color-primary-700);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.cart-header h1 {
    font-size: 36px;
}

.cart-header p {
    margin-top: 10px;
    font-size: 14px;
}

.cart-header__count {
    flex-shrink: 0;
    color: var(--color-text-muted);
    font-size: 13px;
}

.cart-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        360px;
    gap: 28px;
    align-items: start;
}

.cart-items {
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.cart-items__heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 64px;
    padding-inline: 20px;
    border-bottom: 1px solid var(--color-border);
}

.cart-items__heading h2 {
    font-size: 16px;
}

.cart-items__clear {
    padding: 0;
    color: var(--color-danger);
    background: transparent;
    border: 0;
    font-size: 12px;
    font-weight: 600;
}

.cart-item {
    position: relative;
    display: grid;
    grid-template-columns:
        112px
        minmax(0, 1fr)
        auto
        130px
        38px;
    gap: 20px;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--color-border);
}

.cart-item__image {
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: var(--color-gray-100);
    border-radius: var(--radius-md);
}

.cart-item__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item__information {
    display: grid;
    align-content: space-between;
    gap: 14px;
    min-width: 0;
}

.cart-item__seller {
    margin-bottom: 6px;
    color: var(--color-text-muted);
    font-size: 11px;
}

.cart-item__name {
    display: block;
    color: var(--color-text-primary);
    font-size: 14px;
    font-weight: 600;
    line-height: 1.55;
}

.cart-item__name:hover {
    color: var(--color-primary-700);
}

.cart-item__prices {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.cart-item__price {
    color: var(--color-primary-700);
    font-size: 14px;
    font-weight: 700;
}

.cart-item__original-price {
    color: var(--color-text-muted);
    font-size: 11px;
    text-decoration: line-through;
}

.cart-item__quantity {
    display: grid;
    gap: 8px;
}

.cart-item__quantity > span:first-child,
.cart-item__total > span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.quantity-control {
    display: grid;
    grid-template-columns: 34px 44px 34px;
    overflow: hidden;
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-md);
}

.quantity-control button {
    display: grid;
    place-items: center;
    height: 36px;
    padding: 0;
    color: var(--color-text-primary);
    background: var(--color-white);
    border: 0;
}

.quantity-control button:hover:not(:disabled) {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
}

.quantity-control button:disabled {
    cursor: not-allowed;
    opacity: 0.35;
}

.quantity-control input {
    width: 100%;
    height: 36px;
    padding: 0;
    appearance: textfield;
    border: 0;
    border-right: 1px solid var(--color-border);
    border-left: 1px solid var(--color-border);
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    outline: 0;
}

.quantity-control input::-webkit-inner-spin-button,
.quantity-control input::-webkit-outer-spin-button {
    appearance: none;
}

.cart-item__stock {
    color: var(--color-text-muted);
    font-size: 10px;
}

.cart-item__total {
    display: grid;
    gap: 7px;
    text-align: right;
}

.cart-item__total strong {
    color: var(--color-text-primary);
    font-size: 14px;
}

.cart-item__remove {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    padding: 0;
    color: var(--color-text-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
}

.cart-item__remove:hover {
    color: var(--color-danger);
    background: #fff1f3;
    border-color: #ffd6dc;
}

.continue-shopping {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 18px 20px;
    color: var(--color-primary-700);
    font-size: 13px;
    font-weight: 600;
}

.order-summary {
    position: sticky;
    top: 20px;
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.order-summary__header {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 64px;
    padding-inline: 20px;
    border-bottom: 1px solid var(--color-border);
}

.order-summary__header svg {
    color: var(--color-primary-700);
}

.order-summary__header h2 {
    font-size: 16px;
}

.order-summary__details {
    display: grid;
    gap: 16px;
    margin: 0;
    padding: 22px 20px;
}

.order-summary__details > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.order-summary__details dt {
    color: var(--color-text-muted);
    font-size: 13px;
}

.order-summary__details dd {
    margin: 0;
    color: var(--color-text-primary);
    font-size: 13px;
    font-weight: 600;
}

.order-summary__discount {
    color: var(--color-success) !important;
}

.order-summary__free {
    color: var(--color-success);
}

.shipping-progress {
    margin: 0 20px 20px;
    padding: 14px;
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-100);
    border-radius: var(--radius-md);
}

.shipping-progress p {
    color: var(--color-text-secondary);
    font-size: 11px;
    line-height: 1.55;
}

.shipping-progress__track {
    overflow: hidden;
    height: 5px;
    margin-top: 10px;
    background: var(--color-primary-100);
    border-radius: var(--radius-pill);
}

.shipping-progress__track span {
    display: block;
    height: 100%;
    background: var(--color-primary-700);
    border-radius: inherit;
    transition: width var(--transition-base);
}

.order-summary__total {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    padding: 20px;
    border-top: 1px solid var(--color-border);
}

.order-summary__total span {
    color: var(--color-text-secondary);
    font-size: 13px;
    font-weight: 600;
}

.order-summary__total strong {
    color: var(--color-primary-700);
    font-size: 22px;
}

.checkout-button {
    width: calc(100% - 40px);
    min-height: 48px;
    margin: 0 20px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 0;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
}

.checkout-button:hover {
    background: var(--color-primary-800);
}

.order-summary__benefits {
    display: grid;
    gap: 10px;
    padding: 20px;
}

.order-summary__benefits article {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--color-text-muted);
    font-size: 11px;
}

.order-summary__benefits svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.empty-cart {
    display: grid;
    place-items: center;
    min-height: 460px;
    padding: 48px 24px;
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.empty-cart__icon {
    display: grid;
    place-items: center;
    width: 68px;
    height: 68px;
    margin-bottom: 20px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 20px;
}

.empty-cart h2 {
    font-size: 22px;
}

.empty-cart p {
    max-width: 470px;
    margin-top: 11px;
    line-height: 1.7;
}

.empty-cart__button {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 46px;
    margin-top: 24px;
    padding-inline: 18px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.empty-cart__button:hover {
    background: var(--color-primary-800);
}

@media (max-width: 1100px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }

    .order-summary {
        position: static;
    }
}

@media (max-width: 820px) {
    .cart-item {
        grid-template-columns:
            96px
            minmax(0, 1fr)
            36px;
    }

    .cart-item__quantity,
    .cart-item__total {
        grid-row: 2;
    }

    .cart-item__quantity {
        grid-column: 2;
    }

    .cart-item__total {
        grid-column: 3;
    }

    .cart-item__remove {
        grid-column: 3;
        grid-row: 1;
    }
}

@media (max-width: 560px) {
    .cart-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 14px;
    }

    .cart-header h1 {
        font-size: 31px;
    }

    .cart-item {
        grid-template-columns: 86px 1fr 34px;
        gap: 14px;
        padding: 16px;
    }

    .cart-item__quantity {
        grid-column: 1 / 3;
        grid-row: 2;
    }

    .cart-item__total {
        grid-column: 3;
        grid-row: 2;
    }

    .quantity-control {
        width: fit-content;
    }

    .cart-item__stock {
        display: none;
    }

    .order-summary__total {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
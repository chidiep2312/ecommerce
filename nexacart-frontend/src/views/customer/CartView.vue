<script setup>
import {
    ArrowLeft,
    ChevronRight,
    CircleAlert,
    Minus,
    PackageOpen,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Store,
    Trash2,
    Truck,
} from '@lucide/vue'

import {
    computed,
    ref,
    watch,
} from 'vue'

import { useRouter } from 'vue-router'

import { useCartStore } from '@/stores/cart'

const CHECKOUT_CONTEXT_KEY =
    'nexacart_checkout_context'

const router = useRouter()
const cartStore = useCartStore()

const selectedItemIds = ref(
    new Set(),
)

const selectionError = ref('')

function normalizeItemId(itemId) {
    return String(itemId)
}

function resolveSellerId(item) {
    const sellerId = Number(
        item.seller_id ??
        item.seller?.id,
    )

    if (
        !Number.isInteger(sellerId) ||
        sellerId <= 0
    ) {
        return null
    }

    return sellerId
}

function resolveSellerName(item) {
    if (
        typeof item.seller ===
        'string'
    ) {
        return item.seller
    }

    return (
        item.seller?.name ??
        `Người bán #${resolveSellerId(item)}`
    )
}

/*
 * Khi giỏ hàng thay đổi:
 * - Giữ lại các lựa chọn còn tồn tại.
 * - Loại bỏ ID đã bị xóa khỏi giỏ.
 * - Không tự động chọn tất cả sản phẩm.
 */
watch(
    () => {
        return cartStore.items
            .map((item) => {
                return normalizeItemId(
                    item.id,
                )
            })
            .sort()
            .join('|')
    },
    () => {
        const validIds = new Set(
            cartStore.items.map(
                (item) => {
                    return normalizeItemId(
                        item.id,
                    )
                },
            ),
        )

        selectedItemIds.value = new Set(
            [
                ...selectedItemIds.value,
            ].filter((itemId) => {
                return validIds.has(
                    itemId,
                )
            }),
        )
    },
    {
        immediate: true,
    },
)

const sellerGroups = computed(() => {
    const groups = new Map()

    cartStore.items.forEach((item) => {
        const sellerId =
            resolveSellerId(item)

        const groupKey =
            sellerId === null
                ? 'unknown'
                : String(sellerId)

        if (!groups.has(groupKey)) {
            groups.set(groupKey, {
                key: groupKey,
                sellerId,
                sellerName:
                    resolveSellerName(
                        item,
                    ),
                items: [],
            })
        }

        groups
            .get(groupKey)
            .items.push(item)
    })

    return [
        ...groups.values(),
    ]
})

function isItemSelected(itemId) {
    return selectedItemIds.value.has(
        normalizeItemId(itemId),
    )
}

const selectedItems = computed(() => {
    return cartStore.items.filter(
        (item) => {
            return isItemSelected(
                item.id,
            )
        },
    )
})

const selectedSellerIds = computed(() => {
    const ids = selectedItems.value
        .map((item) => {
            return resolveSellerId(item)
        })
        .filter((sellerId) => {
            return sellerId !== null
        })

    return [
        ...new Set(ids),
    ]
})

const selectedSellerId = computed(() => {
    if (
        selectedSellerIds.value
            .length !== 1
    ) {
        return null
    }

    return selectedSellerIds.value[0]
})

function isSellerSelected(group) {
    if (group.items.length === 0) {
        return false
    }

    return group.items.every(
        (item) => {
            return isItemSelected(
                item.id,
            )
        },
    )
}

function isSellerPartiallySelected(
    group,
) {
    const selectedCount =
        group.items.filter((item) => {
            return isItemSelected(
                item.id,
            )
        }).length

    return (
        selectedCount > 0 &&
        selectedCount <
            group.items.length
    )
}

/*
 * Một lần checkout chỉ thuộc một seller.
 * Khi chọn seller mới, lựa chọn của seller cũ bị bỏ.
 */
function toggleSeller(
    group,
    checked,
) {
    selectionError.value = ''

    if (group.sellerId === null) {
        selectionError.value =
            'Không xác định được người bán của nhóm sản phẩm này.'

        return
    }

    if (checked) {
        selectedItemIds.value =
            new Set(
                group.items.map(
                    (item) => {
                        return normalizeItemId(
                            item.id,
                        )
                    },
                ),
            )

        return
    }

    const nextSelection = new Set(
        selectedItemIds.value,
    )

    group.items.forEach((item) => {
        nextSelection.delete(
            normalizeItemId(
                item.id,
            ),
        )
    })

    selectedItemIds.value =
        nextSelection
}

function toggleItem(
    item,
    checked,
) {
    selectionError.value = ''

    const itemId = normalizeItemId(
        item.id,
    )
    console.log(item.seller_id)
    const sellerId =
        resolveSellerId(item)
    console.log(sellerId)
    if (sellerId === null) {
        selectionError.value =
            'Không xác định được người bán của sản phẩm.'

        return
    }

    if (!checked) {
        const nextSelection = new Set(
            selectedItemIds.value,
        )

        nextSelection.delete(itemId)

        selectedItemIds.value =
            nextSelection

        return
    }

    const hasAnotherSeller =
        selectedSellerIds.value.some(
            (selectedId) => {
                return (
                    selectedId !== sellerId
                )
            },
        )

    if (hasAnotherSeller) {
        selectedItemIds.value =
            new Set([itemId])

        return
    }

    const nextSelection = new Set(
        selectedItemIds.value,
    )

    nextSelection.add(itemId)

    selectedItemIds.value =
        nextSelection
}

const selectedLineCount = computed(() => {
    return selectedItems.value.length
})

const selectedItemCount = computed(() => {
    return selectedItems.value.reduce(
        (total, item) => {
            return (
                total +
                Number(item.quantity)
            )
        },
        0,
    )
})

const selectedSubtotal = computed(() => {
    return selectedItems.value.reduce(
        (total, item) => {
            return (
                total +
                Number(item.price) *
                    Number(item.quantity)
            )
        },
        0,
    )
})

/*
 * Backend hiện đặt shipping_fee = 0.
 */
const shippingFee = computed(() => {
    return 0
})

const grandTotal = computed(() => {
    return (
        selectedSubtotal.value +
        shippingFee.value
    )
})

const canCheckout = computed(() => {
    return (
        selectedItems.value.length > 0 &&
        selectedSellerIds.value.length === 1
    )
})

function formatPrice(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(
        Number(value ?? 0),
    )
}

function removeItem(itemId) {
    const normalizedId =
        normalizeItemId(itemId)

    const nextSelection = new Set(
        selectedItemIds.value,
    )

    nextSelection.delete(
        normalizedId,
    )

    selectedItemIds.value =
        nextSelection

    cartStore.removeItem(itemId)
}

function clearCart() {
    selectedItemIds.value =
        new Set()

    sessionStorage.removeItem(
        CHECKOUT_CONTEXT_KEY,
    )

    cartStore.clearCart()
}

function goToCheckout() {
    selectionError.value = ''

    if (selectedItems.value.length === 0) {
        selectionError.value =
            'Vui lòng chọn sản phẩm để thanh toán.'

        return
    }

    if (
        selectedSellerIds.value.length !== 1
    ) {
        selectionError.value =
            'Mỗi lần checkout chỉ được chọn sản phẩm của một người bán.'

        return
    }

    const sellerId =
        selectedSellerId.value

    if (
        !Number.isInteger(sellerId) ||
        sellerId <= 0
    ) {
        selectionError.value =
            'Người bán được chọn không hợp lệ.'

        return
    }

    const cartItemIds =
        selectedItems.value.map(
            (item) => Number(item.id),
        )

    const hasInvalidCartItemId =
        cartItemIds.some(
            (itemId) => {
                return (
                    !Number.isInteger(
                        itemId,
                    ) ||
                    itemId <= 0
                )
            },
        )

    if (hasInvalidCartItemId) {
        selectionError.value =
            'Danh sách sản phẩm được chọn không hợp lệ.'

        return
    }

    const checkoutContext = {
        seller_id: sellerId,
        cart_item_ids: [
            ...new Set(cartItemIds),
        ],
        idempotency_key:
            crypto.randomUUID(),
    }

    sessionStorage.setItem(
        CHECKOUT_CONTEXT_KEY,
        JSON.stringify(
            checkoutContext,
        ),
    )

    router.push({
        name: 'checkout',
    })
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
                    Chọn sản phẩm cần mua và kiểm tra
                    số lượng trước khi thanh toán.
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
                    <div>
                        <h2>
                            Sản phẩm trong giỏ hàng
                        </h2>

                        <span
                            class="cart-items__selected-count"
                        >
                            Đã chọn
                            {{ selectedLineCount }}
                            mặt hàng,
                            {{ selectedItemCount }}
                            sản phẩm
                        </span>
                    </div>

                    <button
                        type="button"
                        class="cart-items__clear"
                        @click="clearCart"
                    >
                        Xóa tất cả
                    </button>
                </div>

                <div
                    v-if="selectionError"
                    class="selection-error"
                    role="alert"
                >
                    <CircleAlert :size="17" />

                    <span>
                        {{ selectionError }}
                    </span>
                </div>

                <section
                    v-for="group in sellerGroups"
                    :key="group.key"
                    class="seller-group"
                >
                    <header
                        class="seller-group__header"
                    >
                        <label
                            class="selection-checkbox"
                        >
                            <input
                                type="checkbox"
                                :checked="
                                    isSellerSelected(
                                        group,
                                    )
                                "
                                :indeterminate="
                                    isSellerPartiallySelected(
                                        group,
                                    )
                                "
                                :disabled="
                                    group.sellerId ===
                                    null
                                "
                                @change="
                                    toggleSeller(
                                        group,
                                        $event.target
                                            .checked,
                                    )
                                "
                            />

                            <span class="sr-only">
                                Chọn tất cả sản phẩm của
                                {{ group.sellerName }}
                            </span>
                        </label>

                        <Store :size="17" />

                        <strong>
                            {{ group.sellerName }}
                        </strong>

                        <span
                            class="seller-group__count"
                        >
                            {{
                                group.items.filter(
                                    (item) =>
                                        isItemSelected(
                                            item.id,
                                        ),
                                ).length
                            }}/{{ group.items.length }}
                            đã chọn
                        </span>
                    </header>

                    <article
                        v-for="item in group.items"
                        :key="item.id"
                        class="cart-item"
                        :class="{
                            'cart-item--selected':
                                isItemSelected(
                                    item.id,
                                ),
                        }"
                    >
                        <label
                            class="cart-item__selector"
                        >
                            <input
                                type="checkbox"
                                :checked="
                                    isItemSelected(
                                        item.id,
                                    )
                                "
                                @change="
                                    toggleItem(
                                        item,
                                        $event.target
                                            .checked,
                                    )
                                "
                            />

                            <span class="sr-only">
                                Chọn {{ item.name }}
                                để thanh toán
                            </span>
                        </label>

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

                        <div
                            class="cart-item__information"
                        >
                            <div>
                                <p
                                    class="cart-item__seller"
                                >
                                    {{ group.sellerName }}
                                </p>

                                <RouterLink
                                    :to="{
                                        name:
                                            'product-detail',
                                        params: {
                                            slug:
                                                item.slug,
                                        },
                                    }"
                                    class="cart-item__name"
                                >
                                    {{ item.name }}
                                </RouterLink>
                            </div>

                            <div
                                class="cart-item__prices"
                            >
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
                                    v-if="item.originalPrice"
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

                        <div
                            class="cart-item__quantity"
                        >
                            <span>Số lượng</span>

                            <div
                                class="quantity-control"
                            >
                                <button
                                    type="button"
                                    aria-label="Giảm số lượng"
                                    :disabled="
                                        item.quantity <= 1
                                    "
                                    @click="
                                        cartStore
                                            .decreaseQuantity(
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
                                        item.stock ??
                                        undefined
                                    "
                                    aria-label="Số lượng"
                                    @change="
                                        cartStore
                                            .updateQuantity(
                                                item.id,
                                                $event.target
                                                    .value,
                                            )
                                    "
                                />

                                <button
                                    type="button"
                                    aria-label="Tăng số lượng"
                                    :disabled="
                                        item.stock !==
                                            null &&
                                        item.quantity >=
                                            item.stock
                                    "
                                    @click="
                                        cartStore
                                            .increaseQuantity(
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
                                Còn {{ item.stock }}
                                sản phẩm
                            </span>
                        </div>

                        <div
                            class="cart-item__total"
                        >
                            <span>Thành tiền</span>

                            <strong>
                                {{
                                    formatPrice(
                                        Number(
                                            item.price,
                                        ) *
                                            Number(
                                                item.quantity,
                                            ),
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
                                removeItem(item.id)
                            "
                        >
                            <Trash2 :size="18" />
                        </button>
                    </article>
                </section>

                <RouterLink
                    to="/products"
                    class="continue-shopping"
                >
                    <ArrowLeft :size="17" />

                    Tiếp tục mua sắm
                </RouterLink>
            </section>

            <aside class="order-summary">
                <div
                    class="order-summary__header"
                >
                    <ShoppingBag :size="20" />

                    <h2>Tóm tắt đơn hàng</h2>
                </div>

                <dl
                    class="order-summary__details"
                >
                    <div>
                        <dt>Mặt hàng đã chọn</dt>

                        <dd>
                            {{ selectedLineCount }}
                        </dd>
                    </div>

                    <div>
                        <dt>Tổng số lượng</dt>

                        <dd>
                            {{ selectedItemCount }}
                        </dd>
                    </div>

                    <div>
                        <dt>Tạm tính</dt>

                        <dd>
                            {{
                                formatPrice(
                                    selectedSubtotal,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Phí vận chuyển</dt>

                        <dd>
                            <span
                                v-if="shippingFee === 0"
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
                    class="order-summary__total"
                >
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
                    :disabled="!canCheckout"
                    @click="goToCheckout"
                >
                    Tiến hành thanh toán
                </button>

                <p class="order-summary__hint">
                    Mỗi lần thanh toán chỉ chọn sản phẩm
                    của một người bán.
                </p>

                <div
                    class="order-summary__benefits"
                >
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
        minmax(0, 1fr) 360px;
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
    gap: 20px;
    min-height: 64px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--color-border);
}

.cart-items__heading > div {
    display: grid;
    gap: 5px;
}

.cart-items__heading h2 {
    font-size: 16px;
}

.cart-items__selected-count {
    color: var(--color-text-muted);
    font-size: 11px;
}

.cart-items__clear {
    padding: 0;
    color: var(--color-danger);
    background: transparent;
    border: 0;
    font-size: 12px;
    font-weight: 600;
}

.selection-error {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 14px 20px 0;
    padding: 11px 12px;
    color: var(--color-danger);
    background: #fff1f3;
    border: 1px solid #ffd6dc;
    border-radius: var(--radius-md);
    font-size: 12px;
}

.selection-error svg {
    flex-shrink: 0;
}

.seller-group {
    border-bottom: 1px solid var(--color-border);
}

.seller-group__header {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 52px;
    padding: 0 20px;
    background: var(--color-primary-50);
    border-bottom: 1px solid var(--color-border);
}

.seller-group__header svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.seller-group__header strong {
    color: var(--color-text-primary);
    font-size: 13px;
}

.seller-group__count {
    margin-left: auto;
    color: var(--color-text-muted);
    font-size: 11px;
}

.selection-checkbox,
.cart-item__selector {
    display: grid;
    place-items: center;
}

.selection-checkbox input,
.cart-item__selector input {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
    accent-color: var(--color-primary-700);
}

.cart-item {
    position: relative;
    display: grid;
    grid-template-columns:
        24px 112px minmax(0, 1fr)
        auto 130px 38px;
    gap: 20px;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--color-border);
    transition:
        background-color var(--transition-base);
}

.cart-item:last-child {
    border-bottom: 0;
}

.cart-item--selected {
    background: var(--color-primary-50);
}

.cart-item__selector {
    align-self: center;
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

.order-summary__free {
    color: var(--color-success);
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

.checkout-button:hover:not(:disabled) {
    background: var(--color-primary-800);
}

.checkout-button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.order-summary__hint {
    margin: 12px 20px 0;
    color: var(--color-text-muted);
    font-size: 10px;
    line-height: 1.55;
    text-align: center;
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

.sr-only {
    position: absolute;
    overflow: hidden;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    white-space: nowrap;
    clip: rect(0, 0, 0, 0);
    border: 0;
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
            24px 96px minmax(0, 1fr)
            36px;
    }

    .cart-item__selector {
        grid-column: 1;
        grid-row: 1;
    }

    .cart-item__image {
        grid-column: 2;
        grid-row: 1;
    }

    .cart-item__information {
        grid-column: 3;
        grid-row: 1;
    }

    .cart-item__remove {
        grid-column: 4;
        grid-row: 1;
    }

    .cart-item__quantity {
        grid-column: 2 / 4;
        grid-row: 2;
    }

    .cart-item__total {
        grid-column: 4;
        grid-row: 2;
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

    .cart-items__heading {
        align-items: flex-start;
    }

    .seller-group__header {
        padding-inline: 16px;
    }

    .seller-group__count {
        display: none;
    }

    .cart-item {
        grid-template-columns:
            24px 76px minmax(0, 1fr)
            34px;
        gap: 12px;
        padding: 16px;
    }

    .cart-item__quantity {
        grid-column: 2 / 4;
    }

    .cart-item__total {
        grid-column: 4;
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

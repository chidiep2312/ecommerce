<script setup>
import {
    Heart,
    PackagePlus,
    ShoppingBag,
    Trash2,
} from '@lucide/vue'

import {
    onMounted,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    getWishlist,
    removeFromWishlist,
} from '@/api/wishlist'

import {
    useCartStore,
} from '@/stores/cart'

const router = useRouter()
const cartStore = useCartStore()

const products = ref([])
const pagination = ref(null)

const isLoading = ref(false)
const removingProductId = ref(null)
const addingProductId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const currentPage = ref(1)

async function fetchWishlist() {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getWishlist({
                page:
                    currentPage.value,

                per_page: 12,
            })

        products.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []

        pagination.value =
            response.data?.meta ??
            null
    } catch (error) {
        products.value = []
        pagination.value = null

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách yêu thích.'
    } finally {
        isLoading.value = false
    }
}

function openProduct(product) {
    router.push({
        name: 'product-detail',

        params: {
            slug: product.slug,
        },
    })
}

async function removeWishlistProduct(
    product,
) {
    if (removingProductId.value) {
        return
    }

    const confirmed =
        window.confirm(
            `Bạn có chắc muốn bỏ "${product.name}" khỏi danh sách yêu thích không?`,
        )

    if (!confirmed) {
        return
    }

    removingProductId.value =
        product.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await removeFromWishlist(
                product.id,
            )

        products.value =
            products.value.filter(
                item => {
                    return (
                        item.id !==
                        product.id
                    )
                },
            )

        successMessage.value =
            response.data?.message ??
            'Đã xóa sản phẩm khỏi danh sách yêu thích.'

        /*
         * Nếu trang hiện tại hết sản phẩm
         * và không phải trang đầu,
         * quay về trang trước.
         */
        if (
            products.value.length === 0 &&
            currentPage.value > 1
        ) {
            currentPage.value--

            await fetchWishlist()
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể xóa sản phẩm khỏi danh sách yêu thích.'
    } finally {
        removingProductId.value = null
    }
}

async function addProductToCart(
    product,
) {
    if (
        addingProductId.value ||
        !product.in_stock ||
        product.is_suspended ||
        product.status !== 'active'
    ) {
        return
    }

    addingProductId.value =
        product.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        /*
         * Điều chỉnh theo signature
         * addItem() của cartStore hiện tại.
         */
        await cartStore.addItem(
            product,
            1,
        )

        successMessage.value =
            'Đã thêm sản phẩm vào giỏ hàng.'
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            error.message ??
            'Không thể thêm sản phẩm vào giỏ hàng.'
    } finally {
        addingProductId.value = null
    }
}

function changePage(page) {
    if (
        page < 1 ||
        page >
            (
                pagination.value
                    ?.last_page ?? 1
            ) ||
        page === currentPage.value
    ) {
        return
    }

    currentPage.value = page

    fetchWishlist()

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

function getProductImage(product) {
    return (
        product.main_image?.url ??
        product.main_image?.path ??
        product.image ??
        ''
    )
}

function getProductPrice(product) {
    return (
        product.effective_price ??
        product.sale_price ??
        product.price ??
        0
    )
}

function getOriginalPrice(product) {
    if (
        product.sale_price === null ||
        product.sale_price === undefined
    ) {
        return null
    }

    if (
        Number(product.sale_price) >=
        Number(product.price)
    ) {
        return null
    }

    return product.price
}

function getSellerName(product) {
    return (
        product.seller?.name ??
        'Người bán'
    )
}

function getUnavailableMessage(
    product,
) {
    if (product.is_suspended) {
        return 'Sản phẩm đã bị tạm khóa.'
    }

    if (
        product.status !==
        'active'
    ) {
        return 'Sản phẩm hiện ngừng bán.'
    }

    if (!product.in_stock) {
        return 'Sản phẩm đã hết hàng.'
    }

    return ''
}

function formatCurrency(value) {
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

onMounted(() => {
    fetchWishlist()
})
</script>

<template>
    <div class="wishlist-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">
                    DANH SÁCH YÊU THÍCH
                </p>

                <h2>
                    Sản phẩm yêu thích
                </h2>

                <p>
                    Lưu lại những sản phẩm
                    bạn quan tâm để xem hoặc
                    mua sau.
                </p>
            </div>
        </header>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="isLoading"
            class="state-box"
        >
            Đang tải danh sách yêu thích...
        </div>

        <div
            v-else-if="
                products.length === 0
            "
            class="empty-state"
        >
            <Heart :size="44" />

            <h3>
                Chưa có sản phẩm yêu thích
            </h3>

            <p>
                Nhấn biểu tượng trái tim
                trên sản phẩm để lưu lại
                những sản phẩm bạn quan tâm.
            </p>

            <RouterLink
                to="/products"
                class="shopping-button"
            >
                <ShoppingBag
                    :size="17"
                />

                Khám phá sản phẩm
            </RouterLink>
        </div>

        <template v-else>
            <section class="wishlist-toolbar">
                <div>
                    <strong>
                        {{
                            pagination?.total ??
                            products.length
                        }}
                    </strong>

                    <span>
                        sản phẩm đã lưu
                    </span>
                </div>
            </section>

            <section class="product-grid">
                <article
                    v-for="product in products"
                    :key="product.id"
                    class="product-card"
                >
                    <button
                        type="button"
                        class="remove-button"
                        :disabled="
                            removingProductId ===
                            product.id
                        "
                        aria-label="Bỏ khỏi yêu thích"
                        @click="
                            removeWishlistProduct(
                                product,
                            )
                        "
                    >
                        <Trash2
                            :size="17"
                        />
                    </button>

                    <button
                        type="button"
                        class="product-image"
                        @click="
                            openProduct(
                                product,
                            )
                        "
                    >
                        <img
                            v-if="
                                getProductImage(
                                    product,
                                )
                            "
                            :src="
                                getProductImage(
                                    product,
                                )
                            "
                            :alt="
                                product.name
                            "
                        >

                        <div
                            v-else
                            class="image-placeholder"
                        >
                            <ShoppingBag
                                :size="34"
                            />
                        </div>

                        <span
                            v-if="
                                product.sale_price
                            "
                            class="sale-badge"
                        >
                            Giảm giá
                        </span>
                    </button>

                    <div class="product-information">
                        <span class="seller-name">
                            {{
                                getSellerName(
                                    product,
                                )
                            }}
                        </span>

                        <button
                            type="button"
                            class="product-name"
                            @click="
                                openProduct(
                                    product,
                                )
                            "
                        >
                            {{ product.name }}
                        </button>

                        <div class="product-price">
                            <strong>
                                {{
                                    formatCurrency(
                                        getProductPrice(
                                            product,
                                        ),
                                    )
                                }}
                            </strong>

                            <span
                                v-if="
                                    getOriginalPrice(
                                        product,
                                    )
                                "
                            >
                                {{
                                    formatCurrency(
                                        getOriginalPrice(
                                            product,
                                        ),
                                    )
                                }}
                            </span>
                        </div>

                        <div class="product-meta">
                            <span>
                                Kho:
                                {{
                                    product.stock ??
                                    0
                                }}
                            </span>

                            <span
                                v-if="
                                    product
                                        .average_rating
                                "
                            >
                                ★
                                {{
                                    product
                                        .average_rating
                                }}
                            </span>
                        </div>

                        <div
                            v-if="
                                getUnavailableMessage(
                                    product,
                                )
                            "
                            class="unavailable-message"
                        >
                            {{
                                getUnavailableMessage(
                                    product,
                                )
                            }}
                        </div>

                        <button
                            type="button"
                            class="cart-button"
                            :disabled="
                                addingProductId ===
                                    product.id ||
                                !product.in_stock ||
                                product
                                    .is_suspended ||
                                product.status !==
                                    'active'
                            "
                            @click="
                                addProductToCart(
                                    product,
                                )
                            "
                        >
                            <PackagePlus
                                :size="17"
                            />

                            {{
                                addingProductId ===
                                product.id
                                    ? 'Đang thêm...'
                                    : 'Thêm vào giỏ'
                            }}
                        </button>
                    </div>
                </article>
            </section>

            <footer
                v-if="
                    pagination?.last_page >
                    1
                "
                class="pagination"
            >
                <button
                    type="button"
                    :disabled="
                        currentPage <= 1
                    "
                    @click="
                        changePage(
                            currentPage - 1,
                        )
                    "
                >
                    Trước
                </button>

                <span>
                    Trang
                    <strong>
                        {{ currentPage }}
                    </strong>
                    /
                    <strong>
                        {{
                            pagination
                                .last_page
                        }}
                    </strong>
                </span>

                <button
                    type="button"
                    :disabled="
                        currentPage >=
                        pagination.last_page
                    "
                    @click="
                        changePage(
                            currentPage + 1,
                        )
                    "
                >
                    Sau
                </button>
            </footer>
        </template>
    </div>
</template>

<style scoped>
.wishlist-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1d2921;
    font-family: Roboto, Arial, sans-serif;
}

.page-header {
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h2 {
    margin: 0;
    font-size: 22px;
}

.page-header > div > p:last-child {
    margin: 7px 0 0;
    color: #6f7c73;
    font-size: 13px;
}

.wishlist-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 52px;
    padding: 0 18px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.wishlist-toolbar div {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.wishlist-toolbar strong {
    color: #24734a;
    font-size: 16px;
}

.wishlist-toolbar span {
    color: #718078;
    font-size: 12px;
}

/* GRID */

.product-grid {
    display: grid;
    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );
    gap: 16px;
}

.product-card {
    position: relative;
    min-width: 0;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.product-card:hover {
    border-color: #aebfb4;
}

.remove-button {
    position: absolute;
    z-index: 3;
    top: 10px;
    right: 10px;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid #d6dfd9;
    background: #ffffff;
    color: #a34242;
    cursor: pointer;
}

.remove-button:hover:not(:disabled) {
    border-color: #d3a5a5;
    background: #fff3f3;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* IMAGE */

.product-image {
    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 1 / 0.78;
    overflow: hidden;
    padding: 0;
    border: 0;
    border-bottom: 1px solid #e6ece8;
    background: #f5f7f6;
    cursor: pointer;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 180ms ease;
}

.product-card:hover
.product-image img {
    transform: scale(1.03);
}

.image-placeholder {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    color: #97a59c;
}

.sale-badge {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: inline-flex;
    min-height: 25px;
    align-items: center;
    padding: 0 8px;
    background: #24734a;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
}

/* PRODUCT */

.product-information {
    padding: 16px;
}

.seller-name {
    display: block;
    overflow: hidden;
    color: #7b887f;
    font-size: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-name {
    display: -webkit-box;
    width: 100%;
    min-height: 42px;
    margin-top: 6px;
    overflow: hidden;
    padding: 0;
    border: 0;
    background: transparent;
    color: #26332b;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.6;
    text-align: left;
    cursor: pointer;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.product-name:hover {
    color: #24734a;
}

.product-price {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    min-height: 31px;
    margin-top: 10px;
}

.product-price strong {
    color: #24734a;
    font-size: 16px;
}

.product-price span {
    color: #9aa49e;
    font-size: 11px;
    text-decoration: line-through;
}

.product-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 7px;
    color: #77847c;
    font-size: 10px;
}

.unavailable-message {
    margin-top: 12px;
    padding: 9px 10px;
    border: 1px solid #e0b1b1;
    background: #fff4f4;
    color: #983838;
    font-size: 10px;
}

.cart-button {
    display: inline-flex;
    width: 100%;
    min-height: 39px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 14px;
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.cart-button:hover:not(:disabled) {
    background: #1d633f;
}

/* EMPTY */

.state-box,
.empty-state {
    display: flex;
    min-height: 350px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #718078;
    text-align: center;
}

.empty-state svg {
    color: #24734a;
}

.empty-state h3 {
    margin: 15px 0 6px;
    color: #29362e;
    font-size: 17px;
}

.empty-state p {
    max-width: 430px;
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
}

.shopping-button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    gap: 7px;
    margin-top: 18px;
    padding: 0 15px;
    background: #24734a;
    color: #ffffff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

/* ALERT */

.alert {
    padding: 12px 15px;
    border: 1px solid;
    font-size: 12px;
}

.alert-error {
    border-color: #deb0b0;
    background: #fff3f3;
    color: #983737;
}

.alert-success {
    border-color: #9dc6aa;
    background: #edf7f0;
    color: #21633f;
}

/* PAGINATION */

.pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 11px;
    padding: 14px 16px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.pagination button {
    min-height: 35px;
    padding: 0 12px;
    border: 1px solid #c7d2cb;
    background: #ffffff;
    color: #45534a;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.pagination span {
    color: #748178;
    font-size: 11px;
}

@media (max-width: 1050px) {
    .product-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }
}

@media (max-width: 620px) {
    .product-grid {
        grid-template-columns: 1fr;
    }

    .pagination {
        justify-content: space-between;
    }
}
</style>
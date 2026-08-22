<script setup>
import {
    ChevronLeft,
    ChevronRight,
    Grid2X2,
    PackageOpen,
    Search,
    ShoppingBag,
    Store,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getPublicShop,
    getPublicShopProducts,
} from '@/api/shop'

const route = useRoute()
const router = useRouter()

const shop = ref(null)
const products = ref([])

const pagination = ref(null)

const isLoadingShop = ref(false)
const isLoadingProducts = ref(false)

const errorMessage = ref('')

const filters = reactive({
    keyword: '',
    category: '',
    brand: '',
    sort: 'latest',
    page: 1,
    per_page: 12,
})

let searchTimer = null

const currentPage = computed(() => {
    return Number(
        pagination.value?.current_page ??
        1,
    )
})

const lastPage = computed(() => {
    return Number(
        pagination.value?.last_page ??
        1,
    )
})

const totalProducts = computed(() => {
    return Number(
        pagination.value?.total ??
        products.value.length,
    )
})

const shopInitial = computed(() => {
    const name =
        shop.value?.name?.trim()

    if (!name) {
        return 'S'
    }

    return name
        .charAt(0)
        .toUpperCase()
})

async function fetchShop() {
    const slug =
        route.params.slug

    if (!slug) {
        errorMessage.value =
            'Không xác định được cửa hàng.'

        return
    }

    isLoadingShop.value = true
    errorMessage.value = ''

    try {
        const response =
            await getPublicShop(
                slug,
            )

        shop.value =
            response.data?.data ??
            response.data ??
            null
    } catch (error) {
        shop.value = null

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tải thông tin cửa hàng.'
    } finally {
        isLoadingShop.value = false
    }
}

async function fetchProducts() {
    const slug =
        route.params.slug

    if (!slug) {
        return
    }

    isLoadingProducts.value = true

    try {
        const response =
            await getPublicShopProducts(
                slug,
                {
                    page:
                        filters.page,

                    per_page:
                        filters.per_page,

                    keyword:
                        filters.keyword.trim() ||
                        undefined,

                    category:
                        filters.category ||
                        undefined,

                    brand:
                        filters.brand ||
                        undefined,

                    sort:
                        filters.sort ||
                        undefined,
                },
            )

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
            error.response?.data
                ?.message ??
            'Không thể tải sản phẩm của cửa hàng.'
    } finally {
        isLoadingProducts.value = false
    }
}

function handleSearch() {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(
        () => {
            filters.page = 1
            fetchProducts()
        },
        400,
    )
}

function changeSort() {
    filters.page = 1
    fetchProducts()
}

function changePage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return
    }

    filters.page = page

    fetchProducts()

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

function openProduct(product) {
    router.push({
        name: 'product-detail',

        params: {
            slug:
                product.slug,
        },
    })
}

function getImageUrl(product) {
    return (
        product.main_image?.url ??
        product.main_image?.path ??
        product.image ??
        ''
    )
}

function getProductPrice(product) {
    return Number(
        product.effective_price ??
        product.sale_price ??
        product.price ??
        0,
    )
}

function getOriginalPrice(product) {
    const price =
        Number(
            product.price ?? 0,
        )

    const effectivePrice =
        getProductPrice(product)

    if (
        effectivePrice >= price
    ) {
        return null
    }

    return price
}

function getDiscountPercent(
    product,
) {
    const originalPrice =
        getOriginalPrice(product)

    if (!originalPrice) {
        return 0
    }

    return Math.round(
        (
            1 -
            getProductPrice(product) /
                originalPrice
        ) * 100,
    )
}

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

watch(
    () => route.params.slug,
    async () => {
        filters.keyword = ''
        filters.page = 1

        await fetchShop()
        await fetchProducts()
    },
)

onMounted(async () => {
    await fetchShop()
    await fetchProducts()
})
</script>

<template>
    <div class="shop-page">
        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="isLoadingShop"
            class="state-box"
        >
            Đang tải thông tin cửa hàng...
        </div>

        <template v-else-if="shop">
            <!-- SHOP BANNER -->
            <section class="shop-hero">
                <div class="shop-banner">
                    <img
                        v-if="shop.banner_url"
                        :src="
                            shop.banner_url
                        "
                        :alt="
                            shop.name
                        "
                    >

                    <div
                        v-else
                        class="banner-placeholder"
                    >
                        <Store
                            :size="44"
                        />
                    </div>
                </div>

                <div class="shop-profile">
                    <div class="shop-avatar">
                        <img
                            v-if="shop.logo_url"
                            :src="
                                shop.logo_url
                            "
                            :alt="
                                shop.name
                            "
                        >

                        <span v-else>
                            {{
                                shopInitial
                            }}
                        </span>
                    </div>

                    <div class="shop-information">
                        <div class="shop-name-row">
                            <h1>
                                {{
                                    shop.name
                                }}
                            </h1>

                            <span
                                v-if="
                                    shop.status ===
                                    'active'
                                "
                                class="shop-status"
                            >
                                Đang hoạt động
                            </span>
                        </div>

                        <p
                            v-if="
                                shop.description
                            "
                        >
                            {{
                                shop.description
                            }}
                        </p>

                        <div class="shop-meta">
                            <span>
                                <ShoppingBag
                                    :size="15"
                                />

                                {{
                                    shop.products_count ??
                                    totalProducts
                                }}
                                sản phẩm
                            </span>

                            <span
                                v-if="
                                    shop.phone
                                "
                            >
                                Liên hệ:
                                {{
                                    shop.phone
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTENT -->
            <section class="shop-content">
                <header class="product-section-header">
                    <div>
                        <p class="eyebrow">
                            SẢN PHẨM
                        </p>

                        <h2>
                            Sản phẩm của cửa hàng
                        </h2>

                        <span>
                            {{
                                totalProducts
                            }}
                            sản phẩm đang được bán
                        </span>
                    </div>
                </header>

                <!-- TOOLBAR -->
                <div class="product-toolbar">
                    <div class="search-box">
                        <Search
                            :size="17"
                        />

                        <input
                            v-model="
                                filters.keyword
                            "
                            type="search"
                            placeholder="Tìm sản phẩm trong cửa hàng..."
                            @input="
                                handleSearch
                            "
                        >
                    </div>

                    <div class="toolbar-right">
                        <Grid2X2
                            :size="16"
                        />

                        <select
                            v-model="
                                filters.sort
                            "
                            @change="
                                changeSort
                            "
                        >
                            <option
                                value="latest"
                            >
                                Mới nhất
                            </option>

                            <option
                                value="oldest"
                            >
                                Cũ nhất
                            </option>

                            <option
                                value="price_asc"
                            >
                                Giá thấp đến cao
                            </option>

                            <option
                                value="price_desc"
                            >
                                Giá cao đến thấp
                            </option>

                            <option
                                value="name_asc"
                            >
                                Tên A - Z
                            </option>

                            <option
                                value="name_desc"
                            >
                                Tên Z - A
                            </option>
                        </select>
                    </div>
                </div>

                <!-- LOADING -->
                <div
                    v-if="
                        isLoadingProducts
                    "
                    class="state-box"
                >
                    Đang tải sản phẩm...
                </div>

                <!-- EMPTY -->
                <div
                    v-else-if="
                        products.length ===
                        0
                    "
                    class="empty-state"
                >
                    <PackageOpen
                        :size="44"
                    />

                    <h3>
                        Không có sản phẩm
                    </h3>

                    <p>
                        Không tìm thấy sản phẩm
                        phù hợp trong cửa hàng.
                    </p>
                </div>

                <!-- PRODUCT GRID -->
                <div
                    v-else
                    class="product-grid"
                >
                    <article
                        v-for="
                            product in
                            products
                        "
                        :key="
                            product.id
                        "
                        class="product-card"
                        @click="
                            openProduct(
                                product,
                            )
                        "
                    >
                        <div class="product-image">
                            <img
                                v-if="
                                    getImageUrl(
                                        product,
                                    )
                                "
                                :src="
                                    getImageUrl(
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
                                    getDiscountPercent(
                                        product,
                                    ) > 0
                                "
                                class="discount-badge"
                            >
                                -
                                {{
                                    getDiscountPercent(
                                        product,
                                    )
                                }}%
                            </span>
                        </div>

                        <div class="product-info">
                            <span
                                v-if="
                                    product.brand
                                "
                                class="product-brand"
                            >
                                {{
                                    product.brand
                                        .name
                                }}
                            </span>

                            <h3>
                                {{
                                    product.name
                                }}
                            </h3>

                            <div class="product-price">
                                <strong>
                                    {{
                                        formatPrice(
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
                                        formatPrice(
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
                        </div>
                    </article>
                </div>

                <!-- PAGINATION -->
                <footer
                    v-if="
                        !isLoadingProducts &&
                        products.length >
                        0 &&
                        lastPage > 1
                    "
                    class="pagination"
                >
                    <button
                        type="button"
                        :disabled="
                            currentPage <=
                            1
                        "
                        @click="
                            changePage(
                                currentPage -
                                1,
                            )
                        "
                    >
                        <ChevronLeft
                            :size="16"
                        />

                        Trước
                    </button>

                    <span>
                        Trang
                        <strong>
                            {{
                                currentPage
                            }}
                        </strong>
                        /
                        <strong>
                            {{
                                lastPage
                            }}
                        </strong>
                    </span>

                    <button
                        type="button"
                        :disabled="
                            currentPage >=
                            lastPage
                        "
                        @click="
                            changePage(
                                currentPage +
                                1,
                            )
                        "
                    >
                        Sau

                        <ChevronRight
                            :size="16"
                        />
                    </button>
                </footer>
            </section>
        </template>
    </div>
</template>

<style scoped>
.shop-page {
    display: flex;
    width: min(
        calc(100% - 40px),
        1280px
    );
    flex-direction: column;
    gap: 24px;
    margin: 0 auto;
    padding: 28px 0 60px;
    color: #1f2c24;
    font-family: Roboto, Arial, sans-serif;
}

/* SHOP HERO */

.shop-hero {
    overflow: hidden;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.shop-banner {
    position: relative;
    display: grid;
    width: 100%;
    height: 220px;
    place-items: center;
    overflow: hidden;
    background:
        linear-gradient(
            135deg,
            #edf6f0,
            #dcece1
        );
}

.shop-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.banner-placeholder {
    color: #24734a;
}

.shop-profile {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    padding: 0 24px 24px;
}

.shop-avatar {
    display: grid;
    width: 92px;
    height: 92px;
    flex: 0 0 92px;
    place-items: center;
    margin-top: -34px;
    overflow: hidden;
    color: #ffffff;
    background: #24734a;
    border: 5px solid #ffffff;
    font-size: 30px;
    font-weight: 700;
}

.shop-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shop-information {
    min-width: 0;
    flex: 1;
    padding-top: 17px;
}

.shop-name-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.shop-name-row h1 {
    margin: 0;
    color: #1d2921;
    font-size: 24px;
}

.shop-status {
    display: inline-flex;
    min-height: 25px;
    align-items: center;
    padding: 0 8px;
    color: #226440;
    background: #edf7f0;
    border: 1px solid #a2c7ae;
    font-size: 9px;
    font-weight: 700;
}

.shop-information > p {
    max-width: 760px;
    margin: 8px 0 0;
    color: #68766d;
    font-size: 12px;
    line-height: 1.65;
}

.shop-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 11px;
}

.shop-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #748178;
    font-size: 10px;
}

.shop-meta svg {
    color: #24734a;
}

/* CONTENT */

.shop-content {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.product-section-header {
    padding: 20px 22px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 6px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.product-section-header h2 {
    margin: 0;
    font-size: 20px;
}

.product-section-header span {
    display: block;
    margin-top: 6px;
    color: #78857c;
    font-size: 11px;
}

/* TOOLBAR */

.product-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 13px 15px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.search-box {
    display: flex;
    width: min(
        100%,
        430px
    );
    height: 40px;
    align-items: center;
    border: 1px solid #cad5ce;
}

.search-box:focus-within {
    border-color: #24734a;
}

.search-box svg {
    flex: 0 0 auto;
    margin-left: 12px;
    color: #7a877f;
}

.search-box input {
    width: 100%;
    height: 100%;
    padding: 0 11px;
    color: #26332b;
    background: transparent;
    border: 0;
    outline: none;
    font: inherit;
    font-size: 11px;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toolbar-right svg {
    color: #68766d;
}

.toolbar-right select {
    height: 40px;
    padding: 0 10px;
    color: #3e4c43;
    background: #ffffff;
    border: 1px solid #cad5ce;
    outline: none;
    font: inherit;
    font-size: 11px;
}

/* PRODUCT GRID */

.product-grid {
    display: grid;
    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );
    gap: 15px;
}

.product-card {
    min-width: 0;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #dce5df;
    cursor: pointer;
    transition:
        border-color 150ms ease,
        transform 150ms ease;
}

.product-card:hover {
    border-color: #a9bcae;
    transform: translateY(-2px);
}

.product-image {
    position: relative;
    width: 100%;
    overflow: hidden;
    aspect-ratio: 1;
    background: #f4f7f5;
    border-bottom: 1px solid #e7ece9;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition:
        transform 180ms ease;
}

.product-card:hover
.product-image img {
    transform: scale(1.035);
}

.image-placeholder {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    color: #93a097;
}

.discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    display: inline-flex;
    min-height: 25px;
    align-items: center;
    padding: 0 7px;
    color: #ffffff;
    background: #24734a;
    font-size: 9px;
    font-weight: 700;
}

/* PRODUCT INFORMATION */

.product-info {
    padding: 14px;
}

.product-brand {
    display: block;
    overflow: hidden;
    color: #89958d;
    font-size: 9px;
    font-weight: 600;
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.product-info h3 {
    display: -webkit-box;
    min-height: 40px;
    margin: 6px 0 0;
    overflow: hidden;
    color: #29362e;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.6;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.product-price {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    margin-top: 11px;
}

.product-price strong {
    color: #24734a;
    font-size: 15px;
}

.product-price span {
    color: #9ca69f;
    font-size: 10px;
    text-decoration: line-through;
}

.product-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 8px;
    color: #7a877f;
    font-size: 9px;
}

/* STATES */

.state-box,
.empty-state {
    display: flex;
    min-height: 320px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: #748178;
    background: #ffffff;
    border: 1px solid #dce5df;
    text-align: center;
}

.empty-state svg {
    color: #24734a;
}

.empty-state h3 {
    margin: 13px 0 5px;
    color: #29362e;
    font-size: 16px;
}

.empty-state p {
    margin: 0;
    font-size: 11px;
}

/* PAGINATION */

.pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 13px 15px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.pagination button {
    display: inline-flex;
    min-height: 35px;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0 11px;
    color: #46544b;
    background: #ffffff;
    border: 1px solid #c8d3cc;
    font: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

.pagination button:hover:not(
    :disabled
) {
    color: #24734a;
    border-color: #24734a;
}

.pagination button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.pagination span {
    color: #748178;
    font-size: 10px;
}

/* ALERT */

.alert {
    padding: 11px 14px;
    border: 1px solid;
    font-size: 11px;
}

.alert-error {
    color: #973737;
    background: #fff3f3;
    border-color: #dfb1b1;
}

/* RESPONSIVE */

@media (max-width: 1050px) {
    .product-grid {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
    }
}

@media (max-width: 800px) {
    .product-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .product-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .search-box {
        width: 100%;
    }

    .toolbar-right {
        justify-content: flex-end;
    }
}

@media (max-width: 600px) {
    .shop-page {
        width: calc(
            100% - 28px
        );
    }

    .shop-banner {
        height: 160px;
    }

    .shop-profile {
        align-items: flex-start;
        flex-direction: column;
        padding:
            0
            16px
            18px;
    }

    .shop-avatar {
        width: 76px;
        height: 76px;
        flex-basis: 76px;
        margin-top: -30px;
    }

    .shop-information {
        padding-top: 0;
    }

    .shop-name-row h1 {
        font-size: 20px;
    }

    .product-grid {
        grid-template-columns: 1fr;
    }

    .toolbar-right {
        align-items: stretch;
        flex-direction: column;
    }

    .toolbar-right svg {
        display: none;
    }

    .toolbar-right select {
        width: 100%;
    }

    .pagination {
        justify-content: space-between;
    }
}
</style>
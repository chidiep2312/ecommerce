<script setup>
import {
    ChevronDown,
    Filter,
    LayoutGrid,
    RotateCcw,
    SlidersHorizontal,
    X,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    onUnmounted,
    reactive,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import { getProducts } from '@/api/products'
import { getCategories } from '@/api/categories'
import { getBrands } from '@/api/brands'
import BaseCheckbox from '@/components/base/BaseCheckbox.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import ProductCard from '@/components/customer/ProductCard.vue'
import { useCartStore } from '@/stores/cart'
import {
    getWishlist,
    removeFromWishlist,
    addToWishlist
} from '@/api/wishlist'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const PRODUCTS_PER_PAGE = 8
const FILTER_DEBOUNCE_TIME = 350

const isFilterDrawerOpen = ref(false)
const isLoading = ref(false)
const loadError = ref('')

const products = ref([])
const categories = ref([])
const brands = ref([])

const isLoadingFilters = ref(false)
const filterLoadError = ref('')

const currentPage = ref(
    parsePositiveInteger(
        route.query.page,
        1, 
    ),
)

const sortBy = ref(
    typeof route.query.sort === 'string'
        ? route.query.sort
        : 'newest',
)

const filters = reactive({
    categories:
        typeof route.query.category ===
        'string'
            ? [route.query.category]
            : [],

    brands:
        typeof route.query.brand ===
        'string'
            ? [route.query.brand]
            : [],

    minPrice:
        typeof route.query.min_price ===
        'string'
            ? route.query.min_price
            : '',

    maxPrice:
        typeof route.query.max_price ===
        'string'
            ? route.query.max_price
            : '',

    rating:
        route.query.min_rating
            ? Number(
                route.query.min_rating,
            )
            : null,

    inStockOnly:
        route.query.in_stock === '1',

    promotionOnly:
        route.query.promotion === '1',
})

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: PRODUCTS_PER_PAGE,
    total: 0,
})

function extractCollection(response) {
    const body = response?.data

    if (Array.isArray(body?.data)) {
        return body.data
    }

    if (
        Array.isArray(
            body?.data?.data,
        )
    ) {
        return body.data.data
    }

    if (Array.isArray(body)) {
        return body
    }

    return []
}

function normalizeCategory(category) {
    return {
        id: category.id,

        label:
            category.name,

        value:
            category.slug,

        count:
            Number(
                category.products_count ??
                category.product_count ??
                0,
            ),
    }
}

function normalizeBrand(brand) {
    return {
        id: brand.id,

        label:
            brand.name,

        value:
            brand.slug,

        count:
            Number(
                brand.products_count ??
                brand.product_count ??
                0,
            ),
    }
}


async function fetchFilterOptions() {
    isLoadingFilters.value = true
    filterLoadError.value = ''

    try {
        const [
            categoryResponse,
            brandResponse,
        ] = await Promise.all([
            getCategories(),
            getBrands(),
        ])


        const categoryItems =
            extractCollection(
                categoryResponse,
            )

        const brandItems =
            extractCollection(
                brandResponse,
            )

        categories.value =
            categoryItems.map(
                normalizeCategory,
            )

        brands.value =
            brandItems.map(
                normalizeBrand,
            )
    } catch (error) {
        categories.value = []
        brands.value = []

        filterLoadError.value =
            error.response?.data
                ?.message ??
            'Không thể tải danh mục và thương hiệu.'

        console.error(
            'Fetch filter options error:',
            error,
        )
    } finally {
        isLoadingFilters.value = false
    }
}


const activeFilterCount = computed(() => {
    let count = 0

    count += filters.categories.length
    count += filters.brands.length

    if (filters.minPrice !== '') {
        count++
    }

    if (filters.maxPrice !== '') {
        count++
    }

    if (filters.rating !== null) {
        count++
    }

    if (filters.inStockOnly) {
        count++
    }

    if (filters.promotionOnly) {
        count++
    }

    return count
})

const totalProducts = computed(() => {
    return pagination.total
})

const lastPage = computed(() => {
    return pagination.lastPage
})

function parsePositiveInteger(
    value,
    fallback,
) {
    const parsedValue = Number(value)

    if (
        !Number.isInteger(parsedValue) ||
        parsedValue <= 0
    ) {
        return fallback
    }

    return parsedValue
}

function toNumber(
    value,
    fallback = 0,
) {
    const parsedValue = Number(value)

    return Number.isFinite(parsedValue)
        ? parsedValue
        : fallback
}

function resolveImageUrl(product) {
    return (
        product.main_image?.url ??
       
        product.image_url ??
        product.images ??
        '/images/product-placeholder.png'
    )
}

function normalizeProduct(product) {
    const regularPrice = toNumber(
        product.price,
    )

    const salePrice =
        product.sale_price !== null &&
        product.sale_price !== undefined
            ? toNumber(
                product.sale_price,
            )
            : null

    const effectivePrice = toNumber(
        product.effective_price ??
            salePrice ??
            regularPrice,
    )

    const sellerId =
        product.seller?.id ??
        product.seller_id ??
        null

    return {
        id: product.id,
        name: product.name,
        slug: product.slug,
        sku: product.sku,

        price: effectivePrice,

        originalPrice:
            salePrice !== null
                ? regularPrice
                : null,

        stock: toNumber(
            product.stock,
        ),

        image:
            resolveImageUrl(product),

        category:
            product.category?.name ??
            'Chưa phân loại',

        categorySlug:
            product.category?.slug ??
            null,

        brand:
            product.brand?.slug ??
            null,

        brandName:
            product.brand?.name ??
            null,

        rating: toNumber(
            product.average_rating ??
                product.rating,
        ),

        reviews: toNumber(
            product.reviews_count ??
                product.reviews,
        ),

        badge:
            salePrice !== null
                ? 'Ưu đãi'
                : null,

        seller_id: sellerId,

        seller: {
            id: sellerId,

            name:
                product.seller?.name ??
                'Người bán',
        },

        description:
            product.description ??
            '',

        inStock:
            product.in_stock !==
                undefined
                ? Boolean(
                    product.in_stock,
                )
                : toNumber(
                    product.stock,
                ) > 0,
    }
}

function resolveBackendSort(sort) {
    const sortMap = {
        newest: 'newest',
        rating: 'rating_desc',
        'price-asc': 'price_asc',
        'price-desc': 'price_desc',
    }

    return (
        sortMap[sort] ??
        'newest'
    )
}

function removeEmptyParams(params) {
    return Object.fromEntries(
        Object.entries(params).filter(
            ([, value]) => {
                return (
                    value !== undefined &&
                    value !== null &&
                    value !== ''
                )
            },
        ),
    )
}

function buildProductParams() {
    return removeEmptyParams({
        page: currentPage.value,
        per_page: PRODUCTS_PER_PAGE,

        keyword:
            typeof route.query.keyword ===
            'string'
                ? route.query.keyword
                : typeof route.query.search ===
                    'string'
                    ? route.query.search
                    : undefined,


        category:
            filters.categories[0],

        brand:
            filters.brands[0],

        min_price:
            filters.minPrice,

        max_price:
            filters.maxPrice,

        min_rating:
            filters.rating,

        in_stock:
            filters.inStockOnly
                ? 1
                : undefined,

        promotion:
            filters.promotionOnly
                ? 1
                : undefined,

        sort:
            resolveBackendSort(
                sortBy.value,
            ),
    })
}

function extractPaginatedPayload(response) {
    const body =
        response?.data ?? {}

    if (Array.isArray(body.data)) {
        return {
            items: body.data,
            meta: body.meta ?? {},
        }
    }

    if (
        Array.isArray(
            body.data?.data,
        )
    ) {
        return {
            items:
                body.data.data,

            meta:
                body.data.meta ??
                body.meta ??
                {},
        }
    }

    if (Array.isArray(body)) {
        return {
            items: body,
            meta: {},
        }
    }

    return {
        items: [],
        meta: {},
    }
}

function updatePagination(meta) {
    pagination.currentPage =
        parsePositiveInteger(
            meta.current_page,
            currentPage.value,
        )

    pagination.lastPage =
        parsePositiveInteger(
            meta.last_page,
            1,
        )

    pagination.perPage =
        parsePositiveInteger(
            meta.per_page,
            PRODUCTS_PER_PAGE,
        )

    pagination.total =
        Number.isFinite(
            Number(meta.total),
        )
            ? Number(meta.total)
            : products.value.length

    currentPage.value =
        pagination.currentPage
}

async function fetchProducts() {
    isLoading.value = true
    loadError.value = ''

    try {
        const response =
            await getProducts(
                buildProductParams(),
            )

       console.log(response)
     
        const {
            items,
            meta,
        } =
            extractPaginatedPayload(
                response,
            )

        products.value =
            items.map(
                normalizeProduct,
            )

        updatePagination(meta)
    } catch (error) {
        products.value = []

        pagination.currentPage = 1
        pagination.lastPage = 1
        pagination.total = 0

        loadError.value =
            error.response?.data
                ?.message ??
            'Không thể tải danh sách sản phẩm.'
    } finally {
        isLoading.value = false
    }
}

async function replaceRouteQuery() {
    const query =
        removeEmptyParams({
            ...route.query,

            page:
                currentPage.value > 1
                    ? currentPage.value
                    : undefined,

            sort:
                sortBy.value !==
                'newest'
                    ? sortBy.value
                    : undefined,

            category:
                filters.categories[0],

            brand:
                filters.brands[0],

            min_price:
                filters.minPrice,

            max_price:
                filters.maxPrice,

            min_rating:
                filters.rating,

            in_stock:
                filters.inStockOnly
                    ? '1'
                    : undefined,

            promotion:
                filters.promotionOnly
                    ? '1'
                    : undefined,
        })

    await router.replace({
        query,
    })
}

async function applyFilters() {
    currentPage.value = 1

    await replaceRouteQuery()
    await fetchProducts()
}

let filterTimer = null

function scheduleApplyFilters() {
    window.clearTimeout(
        filterTimer, //hủy bộ hẹn giờ cũ nếu nó vẫn đang chờ
    )

    filterTimer =
        window.setTimeout(
            () => {
                applyFilters()//hàm cần chạy
            },
            FILTER_DEBOUNCE_TIME,// thời gian cần chờ
        )
}

function resetFilters() {
    window.clearTimeout(
        filterTimer,
    )

    filters.categories = []
    filters.brands = []
    filters.minPrice = ''
    filters.maxPrice = ''
    filters.rating = null
    filters.inStockOnly = false
    filters.promotionOnly = false

    sortBy.value = 'newest'
    currentPage.value = 1

    scheduleApplyFilters()
}

function openFilterDrawer() {
    isFilterDrawerOpen.value = true

    document.body.style.overflow =
        'hidden'
}

function closeFilterDrawer() {
    isFilterDrawerOpen.value = false

    document.body.style.overflow = ''
}

async function changePage(page) {
    const nextPage =
        parsePositiveInteger(
            page,
            1,
        )

    if (
        nextPage ===
        currentPage.value
    ) {
        return
    }

    currentPage.value = nextPage

    await replaceRouteQuery()
    await fetchProducts()

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

function handleEscape(event) {
    if (
        event.key === 'Escape' &&
        isFilterDrawerOpen.value
    ) {
        closeFilterDrawer()
    }
}

async function handleAddToCart(product) {
    try {
        await cartStore.addItem(
            product.id,
            1,
        )
        
    } catch (error) {
        console.error(
            'Không thể thêm sản phẩm:',
            error,
        )
    }
}

async  function handleToggleWishlist(product) {
   if (product.is_wishlisted) {
        await removeFromWishlist(
            product.id,
        )

        product.is_wishlisted =
            false

        return
    }

    await addToWishlist(
        product.id,
    )

    product.is_wishlisted =
        true
}

watch(
    sortBy,
    () => {
        scheduleApplyFilters()
    },
)

watch(
    () => [
        ...filters.categories,
    ],
    (
        values,
        previousValues = [],
    ) => {
       
        if (values.length > 1) {
            const addedValue =
                values.find((value) => {
                    return !previousValues.includes(
                        value,
                    )
                }) ??
                values.at(-1)

            filters.categories = [
                addedValue,
            ]

            return
        }

        scheduleApplyFilters()
    },
)

watch(
    () => [
        ...filters.brands,
    ],
    (
        values,
        previousValues = [],
    ) => {
       
        if (values.length > 1) {
            const addedValue =
                values.find((value) => {
                    return !previousValues.includes(
                        value,
                    )
                }) ??
                values.at(-1)

            filters.brands = [
                addedValue,
            ]

            return
        }

        scheduleApplyFilters()
    },
)

watch(
    () => [
        filters.minPrice,
        filters.maxPrice,
        filters.rating,
        filters.inStockOnly,
        filters.promotionOnly,
    ],
    () => {
        scheduleApplyFilters()
    },
)

onMounted(async () => {
    window.addEventListener(
        'keydown',
        handleEscape,
    )
    await fetchFilterOptions()
    await fetchProducts()
})

onUnmounted(() => {
    window.clearTimeout(
        filterTimer,
    )

    window.removeEventListener(
        'keydown',
        handleEscape,
    )

    document.body.style.overflow = ''
})
</script>

<template>
    <div class="product-list-page">
        <nav
            class="breadcrumb"
            aria-label="Breadcrumb"
        >
            <RouterLink to="/">
                Trang chủ
            </RouterLink>

            <span>/</span>

            <span aria-current="page">
                Sản phẩm
            </span>
        </nav>

        <header class="product-list-header">
            <div>
                <p class="product-list-header__eyebrow">
                    Danh mục sản phẩm
                </p>

                <h1>Tất cả sản phẩm</h1>

                <p class="product-list-header__description">
                    Khám phá sản phẩm từ các nhà bán hàng
                    trên NexaCart.
                </p>
            </div>

            <div class="product-list-header__summary">
                <span>
                    {{ totalProducts }}
                    sản phẩm
                </span>
            </div>
        </header>

        <div class="product-list-toolbar">
            <button
                type="button"
                class="filter-mobile-button"
                @click="openFilterDrawer"
            >
                <Filter :size="18" />

                Bộ lọc

                <span
                    v-if="activeFilterCount > 0"
                    class="filter-mobile-button__count"
                >
                    {{ activeFilterCount }}
                </span>
            </button>

            <div class="product-list-toolbar__view">
                <LayoutGrid :size="18" />

                <span>
                    Hiển thị dạng lưới
                </span>
            </div>

            <label class="sort-control">
                <span>
                    Sắp xếp
                </span>

                <div class="sort-control__select">
                    <select v-model="sortBy">
                        <option value="newest">
                            Mới nhất
                        </option>

                        <option value="rating">
                            Đánh giá cao
                        </option>

                        <option value="price-asc">
                            Giá thấp đến cao
                        </option>

                        <option value="price-desc">
                            Giá cao đến thấp
                        </option>
                    </select>

                    <ChevronDown :size="16" />
                </div>
            </label>
        </div>

        <div class="product-list-layout">
            <aside class="filter-sidebar">
                <div class="filter-sidebar__header">
                    <div>
                        <SlidersHorizontal
                            :size="18"
                        />

                        <h2>Bộ lọc</h2>
                    </div>

                    <button
                        v-if="activeFilterCount > 0"
                        type="button"
                        class="filter-sidebar__reset"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>

                <div class="filter-group">
                    <h3>Danh mục</h3>

                    <div class="filter-group__options">
                        <BaseCheckbox
                            v-for="category in categories"
                            :key="category.value"
                            v-model="filters.categories"
                            name="categories"
                            :value="category.value"
                            :label="category.label"
                            :count="category.count"
                        />
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Thương hiệu</h3>

                    <div class="filter-group__options">
                        <BaseCheckbox
                            v-for="brand in brands"
                            :key="brand.value"
                            v-model="filters.brands"
                            name="brands"
                            :value="brand.value"
                            :label="brand.label"
                            :count="brand.count"
                        />
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Khoảng giá</h3>

                    <div class="price-filter">
                        <label>
                            <span>Từ</span>

                            <input
                                v-model="filters.minPrice"
                                type="number"
                                min="0"
                                placeholder="0"
                            />
                        </label>

                        <span class="price-filter__separator">
                            –
                        </span>

                        <label>
                            <span>Đến</span>

                            <input
                                v-model="filters.maxPrice"
                                type="number"
                                min="0"
                                placeholder="10.000.000"
                            />
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Đánh giá tối thiểu</h3>

                    <div class="rating-options">
                        <button
                            v-for="rating in [4, 3, 2]"
                            :key="rating"
                            type="button"
                            class="rating-option"
                            :class="{
                                'rating-option--active':
                                    filters.rating ===
                                    rating,
                            }"
                            @click="
                                filters.rating =
                                    filters.rating ===
                                    rating
                                        ? null
                                        : rating
                            "
                        >
                            {{ rating }} sao trở lên
                        </button>
                    </div>
                </div>

                <div class="filter-group">
                    <h3>Tùy chọn</h3>

                    <div class="filter-group__options">
                        <BaseCheckbox
                            v-model="
                                filters.inStockOnly
                            "
                            name="in-stock"
                            label="Chỉ sản phẩm còn hàng"
                        />

                        <BaseCheckbox
                            v-model="
                                filters.promotionOnly
                            "
                            name="promotion"
                            label="Sản phẩm đang giảm giá"
                        />
                    </div>
                </div>
            </aside>

            <section class="product-results">
                <div
                    v-if="isLoading"
                    class="product-loading"
                    aria-live="polite"
                >
                    <span
                        class="product-loading__spinner"
                    />

                    <p>
                        Đang tải sản phẩm...
                    </p>
                </div>

                <div
                    v-else-if="loadError"
                    class="empty-results"
                >
                    <div class="empty-results__icon">
                        <Filter :size="26" />
                    </div>

                    <h2>
                        Không thể tải sản phẩm
                    </h2>

                    <p>
                        {{ loadError }}
                    </p>

                    <button
                        type="button"
                        class="empty-results__button"
                        @click="fetchProducts"
                    >
                        <RotateCcw :size="17" />

                        Thử lại
                    </button>
                </div>

                <div
                    v-else-if="products.length > 0"
                    class="product-grid"
                >
                    <ProductCard
                        v-for="product in products"
                        :key="product.id"
                        :product="product"
                        @add-to-cart="
                            handleAddToCart
                        "
                        @toggle-wishlist="
                            handleToggleWishlist
                        "
                    />
                </div>

                <div
                    v-else
                    class="empty-results"
                >
                    <div class="empty-results__icon">
                        <Filter :size="26" />
                    </div>

                    <h2>
                        Không tìm thấy sản phẩm
                    </h2>

                    <p>
                        Hãy thử thay đổi điều kiện lọc
                        hoặc đặt lại bộ lọc hiện tại.
                    </p>

                    <button
                        type="button"
                        class="empty-results__button"
                        @click="resetFilters"
                    >
                        <RotateCcw :size="17" />

                        Đặt lại bộ lọc
                    </button>
                </div>

                <BasePagination
                    v-if="
                        !isLoading &&
                        !loadError &&
                        lastPage > 1
                    "
                    :current-page="
                        pagination.currentPage
                    "
                    :last-page="lastPage"
                    @change="changePage"
                />
            </section>
        </div>

        <div
            v-if="isFilterDrawerOpen"
            class="filter-drawer"
        >
            <button
                type="button"
                class="filter-drawer__backdrop"
                aria-label="Đóng bộ lọc"
                @click="closeFilterDrawer"
            />

            <aside
                class="filter-drawer__panel"
                aria-label="Bộ lọc sản phẩm"
            >
                <header class="filter-drawer__header">
                    <div>
                        <SlidersHorizontal
                            :size="19"
                        />

                        <h2>Bộ lọc sản phẩm</h2>
                    </div>

                    <button
                        type="button"
                        aria-label="Đóng bộ lọc"
                        @click="closeFilterDrawer"
                    >
                        <X :size="21" />
                    </button>
                </header>

                <div class="filter-drawer__content">
                    <div class="filter-group">
                        <h3>Danh mục</h3>

                        <div class="filter-group__options">
                            <BaseCheckbox
                                v-for="category in categories"
                                :key="category.value"
                                v-model="
                                    filters.categories
                                "
                                name="mobile-categories"
                                :value="category.value"
                                :label="category.label"
                                :count="category.count"
                            />
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>Thương hiệu</h3>

                        <div class="filter-group__options">
                            <BaseCheckbox
                                v-for="brand in brands"
                                :key="brand.value"
                                v-model="
                                    filters.brands
                                "
                                name="mobile-brands"
                                :value="brand.value"
                                :label="brand.label"
                                :count="brand.count"
                            />
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>Khoảng giá</h3>

                        <div class="price-filter">
                            <label>
                                <span>Từ</span>

                                <input
                                    v-model="
                                        filters.minPrice
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                />
                            </label>

                            <span
                                class="price-filter__separator"
                            >
                                –
                            </span>

                            <label>
                                <span>Đến</span>

                                <input
                                    v-model="
                                        filters.maxPrice
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="10.000.000"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>
                            Đánh giá tối thiểu
                        </h3>

                        <div class="rating-options">
                            <button
                                v-for="rating in [
                                    4, 3, 2,
                                ]"
                                :key="rating"
                                type="button"
                                class="rating-option"
                                :class="{
                                    'rating-option--active':
                                        filters.rating ===
                                        rating,
                                }"
                                @click="
                                    filters.rating =
                                        filters.rating ===
                                        rating
                                            ? null
                                            : rating
                                "
                            >
                                {{ rating }} sao trở lên
                            </button>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>Tùy chọn</h3>

                        <div class="filter-group__options">
                            <BaseCheckbox
                                v-model="
                                    filters.inStockOnly
                                "
                                name="mobile-in-stock"
                                label="Chỉ sản phẩm còn hàng"
                            />

                            <BaseCheckbox
                                v-model="
                                    filters.promotionOnly
                                "
                                name="mobile-promotion"
                                label="Sản phẩm đang giảm giá"
                            />
                        </div>
                    </div>
                </div>

                <footer class="filter-drawer__footer">
                    <button
                        type="button"
                        class="filter-drawer__reset"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>

                    <button
                        type="button"
                        class="filter-drawer__apply"
                        @click="closeFilterDrawer"
                    >
                        Xem
                        {{ totalProducts }}
                        sản phẩm
                    </button>
                </footer>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.product-list-page {
    display: grid;
    gap: 28px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text-muted);
    font-size: 12px;
}

.breadcrumb a {
    transition: color var(--transition-fast);
}

.breadcrumb a:hover {
    color: var(--color-primary-700);
}

.product-list-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 32px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--color-border);
}

.product-list-header__eyebrow {
    margin-bottom: 10px;
    color: var(--color-primary-700);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.product-list-header h1 {
    font-size: 36px;
}

.product-list-header__description {
    margin-top: 12px;
    font-size: 15px;
}

.product-list-header__summary {
    flex-shrink: 0;
    color: var(--color-text-muted);
    font-size: 13px;
}

.product-list-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    min-height: 58px;
    padding: 9px 12px 9px 18px;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.product-list-toolbar__view {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--color-text-secondary);
    font-size: 13px;
}

.filter-mobile-button {
    display: none;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding-inline: 13px;
    color: var(--color-text-primary);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.filter-mobile-button__count {
    display: grid;
    place-items: center;
    min-width: 20px;
    height: 20px;
    padding-inline: 5px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border-radius: var(--radius-pill);
    font-size: 10px;
}

.sort-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-control > span {
    color: var(--color-text-muted);
    font-size: 12px;
}

.sort-control__select {
    position: relative;
    display: flex;
    align-items: center;
}

.sort-control__select select {
    min-width: 190px;
    height: 40px;
    padding: 0 38px 0 13px;
    color: var(--color-text-primary);
    appearance: none;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 13px;
    outline: none;
}

.sort-control__select select:focus {
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 4px rgb(113 56 214 / 10%);
}

.sort-control__select svg {
    position: absolute;
    right: 12px;
    color: var(--color-text-muted);
    pointer-events: none;
}

.product-list-layout {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 28px;
    align-items: start;
}

.filter-sidebar {
    position: sticky;
    top: 20px;
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.filter-sidebar__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 62px;
    padding-inline: 18px;
    border-bottom: 1px solid var(--color-border);
}

.filter-sidebar__header > div {
    display: flex;
    align-items: center;
    gap: 9px;
}

.filter-sidebar__header h2 {
    font-size: 15px;
}

.filter-sidebar__reset {
    padding: 0;
    color: var(--color-primary-700);
    background: transparent;
    border: 0;
    font-size: 12px;
    font-weight: 600;
}

.filter-group {
    padding: 20px 18px;
    border-bottom: 1px solid var(--color-border);
}

.filter-group:last-child {
    border-bottom: 0;
}

.filter-group h3 {
    margin-bottom: 14px;
    font-size: 13px;
}

.filter-group__options {
    display: grid;
    gap: 5px;
}

.price-filter {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: end;
    gap: 8px;
}

.price-filter label {
    display: grid;
    gap: 6px;
}

.price-filter label span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.price-filter input {
    width: 100%;
    height: 40px;
    padding-inline: 10px;
    color: var(--color-text-primary);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12px;
    outline: none;
}

.price-filter input:focus {
    background: var(--color-white);
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 3px rgb(113 56 214 / 10%);
}

.price-filter__separator {
    padding-bottom: 11px;
    color: var(--color-text-muted);
}

.rating-options {
    display: grid;
    gap: 8px;
}

.rating-option {
    width: 100%;
    min-height: 38px;
    padding-inline: 12px;
    color: var(--color-text-secondary);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12px;
    text-align: left;
}

.rating-option:hover {
    border-color: var(--color-primary-200);
}

.rating-option--active {
    color: var(--color-primary-800);
    background: var(--color-primary-50);
    border-color: var(--color-primary-300);
    font-weight: 600;
}

.product-results {
    display: grid;
    gap: 34px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}


.product-loading {
    display: grid;
    place-items: center;
    align-content: center;
    gap: 12px;
    min-height: 420px;
    padding: 40px;
    color: var(--color-text-muted);
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    font-size: 13px;
}

.product-loading__spinner {
    width: 28px;
    height: 28px;
    border: 3px solid var(--color-primary-100);
    border-top-color: var(--color-primary-700);
    border-radius: 50%;
    animation:
        product-loading-spin 700ms linear infinite;
}

@keyframes product-loading-spin {
    to {
        transform: rotate(360deg);
    }
}

.empty-results {
    display: grid;
    place-items: center;
    min-height: 420px;
    padding: 40px;
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.empty-results__icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    margin-bottom: 18px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 18px;
}

.empty-results h2 {
    font-size: 20px;
}

.empty-results p {
    max-width: 420px;
    margin-top: 10px;
}

.empty-results__button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    margin-top: 22px;
    padding-inline: 15px;
    color: var(--color-primary-800);
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-200);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.filter-drawer {
    position: fixed;
    inset: 0;
    z-index: 120;
}

.filter-drawer__backdrop {
    position: absolute;
    inset: 0;
    width: 100%;
    padding: 0;
    background: rgb(24 16 34 / 48%);
    border: 0;
}

.filter-drawer__panel {
    position: relative;
    display: grid;
    grid-template-rows: auto 1fr auto;
    width: min(90%, 390px);
    height: 100%;
    margin-left: auto;
    background: var(--color-white);
    box-shadow: var(--shadow-md);
}

.filter-drawer__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 66px;
    padding-inline: 20px;
    border-bottom: 1px solid var(--color-border);
}

.filter-drawer__header > div {
    display: flex;
    align-items: center;
    gap: 9px;
}

.filter-drawer__header h2 {
    font-size: 16px;
}

.filter-drawer__header button {
    display: grid;
    place-items: center;
    width: 39px;
    height: 39px;
    padding: 0;
    color: var(--color-text-primary);
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.filter-drawer__content {
    overflow-y: auto;
}

.filter-drawer__footer {
    display: grid;
    grid-template-columns: 0.8fr 1.2fr;
    gap: 10px;
    padding: 16px;
    background: var(--color-white);
    border-top: 1px solid var(--color-border);
}

.filter-drawer__reset,
.filter-drawer__apply {
    min-height: 44px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.filter-drawer__reset {
    color: var(--color-text-primary);
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
}

.filter-drawer__apply {
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 1px solid var(--color-primary-700);
}

@media (max-width: 1180px) {
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 860px) {
    .product-list-header h1 {
        font-size: 31px;
    }

    .product-list-layout {
        grid-template-columns: 1fr;
    }

    .filter-sidebar {
        display: none;
    }

    .filter-mobile-button {
        display: inline-flex;
    }

    .product-list-toolbar__view {
        display: none;
    }
}

@media (max-width: 620px) {
    .product-list-page {
        gap: 22px;
    }

    .product-list-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 16px;
    }

    .product-list-toolbar {
        align-items: stretch;
        flex-direction: column;
        padding: 12px;
    }

    .filter-mobile-button {
        justify-content: center;
    }

    .sort-control {
        justify-content: space-between;
    }

    .sort-control__select {
        flex: 1;
    }

    .sort-control__select select {
        width: 100%;
        min-width: 0;
    }

    .product-grid {
        grid-template-columns: 1fr;
    }
}
</style>
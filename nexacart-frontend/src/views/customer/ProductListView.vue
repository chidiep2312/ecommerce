<script setup>
import {
    ChevronDown,
    Filter,
    LayoutGrid,
    RotateCcw,
    SlidersHorizontal,
    X,
} from '@lucide/vue'
import { useCartStore } from '@/stores/cart'
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

import BaseCheckbox from '@/components/base/BaseCheckbox.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import ProductCard from '@/components/customer/ProductCard.vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const isFilterDrawerOpen = ref(false)
const currentPage = ref(
    Number(route.query.page) || 1,
)

const sortBy = ref(
    route.query.sort || 'newest',
)

const filters = reactive({
    categories: route.query.category
        ? [route.query.category]
        : [],

    brands: [],

    minPrice: '',
    maxPrice: '',

    rating: null,

    inStockOnly: false,
    promotionOnly:
        route.query.promotion === '1',
})

const categories = [
    {
        label: 'Điện tử',
        value: 'electronics',
        count: 126,
    },
    {
        label: 'Thời trang',
        value: 'fashion',
        count: 94,
    },
    {
        label: 'Nhà cửa',
        value: 'home-living',
        count: 78,
    },
    {
        label: 'Phụ kiện',
        value: 'accessories',
        count: 63,
    },
    {
        label: 'Thiết bị âm thanh',
        value: 'audio',
        count: 42,
    },
]

const brands = [
    {
        label: 'Auralis',
        value: 'auralis',
        count: 28,
    },
    {
        label: 'Nova',
        value: 'nova',
        count: 35,
    },
    {
        label: 'Urban Form',
        value: 'urban-form',
        count: 19,
    },
    {
        label: 'Nest Living',
        value: 'nest-living',
        count: 24,
    },
]

const products = [
    {
        id: 1,
        name: 'Tai nghe không dây chống ồn chủ động',
        slug: 'tai-nghe-khong-day-chong-on',
        category: 'Thiết bị âm thanh',
        categorySlug: 'audio',
        brand: 'auralis',
        price: 1890000,
        originalPrice: 2290000,
        rating: 4.8,
        reviews: 128,
        badge: 'Bán chạy',
        stock: 18,
        image:
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 2,
        name: 'Đồng hồ tối giản dây kim loại',
        slug: 'dong-ho-toi-gian-day-kim-loai',
        category: 'Phụ kiện',
        categorySlug: 'accessories',
        brand: 'nova',
        price: 1290000,
        originalPrice: null,
        rating: 4.7,
        reviews: 86,
        badge: 'Hàng mới',
        stock: 12,
        image:
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 3,
        name: 'Giày thể thao phong cách đô thị',
        slug: 'giay-the-thao-phong-cach-do-thi',
        category: 'Thời trang',
        categorySlug: 'fashion',
        brand: 'urban-form',
        price: 1590000,
        originalPrice: 1890000,
        rating: 4.9,
        reviews: 214,
        badge: null,
        stock: 21,
        image:
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 4,
        name: 'Máy ảnh kỹ thuật số nhỏ gọn',
        slug: 'may-anh-ky-thuat-so-nho-gon',
        category: 'Điện tử',
        categorySlug: 'electronics',
        brand: 'nova',
        price: 8790000,
        originalPrice: 9290000,
        rating: 4.6,
        reviews: 47,
        badge: 'Ưu đãi',
        stock: 7,
        image:
            'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 5,
        name: 'Loa bluetooth để bàn thiết kế tối giản',
        slug: 'loa-bluetooth-de-ban-toi-gian',
        category: 'Thiết bị âm thanh',
        categorySlug: 'audio',
        brand: 'auralis',
        price: 2190000,
        originalPrice: null,
        rating: 4.5,
        reviews: 73,
        badge: null,
        stock: 14,
        image:
            'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 6,
        name: 'Ghế thư giãn bọc vải hiện đại',
        slug: 'ghe-thu-gian-boc-vai-hien-dai',
        category: 'Nhà cửa',
        categorySlug: 'home-living',
        brand: 'nest-living',
        price: 3490000,
        originalPrice: 3990000,
        rating: 4.8,
        reviews: 56,
        badge: 'Ưu đãi',
        stock: 5,
        image:
            'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 7,
        name: 'Ba lô công sở chống thấm nước',
        slug: 'ba-lo-cong-so-chong-tham',
        category: 'Phụ kiện',
        categorySlug: 'accessories',
        brand: 'urban-form',
        price: 890000,
        originalPrice: null,
        rating: 4.4,
        reviews: 38,
        badge: null,
        stock: 0,
        image:
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 8,
        name: 'Đèn bàn kim loại ánh sáng dịu',
        slug: 'den-ban-kim-loai-anh-sang-diu',
        category: 'Nhà cửa',
        categorySlug: 'home-living',
        brand: 'nest-living',
        price: 750000,
        originalPrice: 890000,
        rating: 4.7,
        reviews: 65,
        badge: 'Hàng mới',
        stock: 24,
        image:
            'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',
    },
]

const filteredProducts = computed(() => {
    let result = [...products]

    if (filters.categories.length > 0) {
        result = result.filter((product) => {
            return filters.categories.includes(
                product.categorySlug,
            )
        })
    }

    if (filters.brands.length > 0) {
        result = result.filter((product) => {
            return filters.brands.includes(
                product.brand,
            )
        })
    }

    const minPrice = Number(
        filters.minPrice,
    )

    const maxPrice = Number(
        filters.maxPrice,
    )

    if (filters.minPrice !== '') {
        result = result.filter((product) => {
            return product.price >= minPrice
        })
    }

    if (filters.maxPrice !== '') {
        result = result.filter((product) => {
            return product.price <= maxPrice
        })
    }

    if (filters.rating) {
        result = result.filter((product) => {
            return product.rating >= filters.rating
        })
    }

    if (filters.inStockOnly) {
        result = result.filter((product) => {
            return product.stock > 0
        })
    }

    if (filters.promotionOnly) {
        result = result.filter((product) => {
            return Boolean(
                product.originalPrice,
            )
        })
    }

    if (sortBy.value === 'price-asc') {
        result.sort(
            (a, b) => a.price - b.price,
        )
    }

    if (sortBy.value === 'price-desc') {
        result.sort(
            (a, b) => b.price - a.price,
        )
    }

    if (sortBy.value === 'rating') {
        result.sort(
            (a, b) => b.rating - a.rating,
        )
    }

    return result
})

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

    if (filters.rating) {
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

const lastPage = computed(() => {
    return Math.max(
        1,
        Math.ceil(
            filteredProducts.value.length / 8,
        ),
    )
})

function resetFilters() {
    filters.categories = []
    filters.brands = []
    filters.minPrice = ''
    filters.maxPrice = ''
    filters.rating = null
    filters.inStockOnly = false
    filters.promotionOnly = false

    currentPage.value = 1
}

function openFilterDrawer() {
    isFilterDrawerOpen.value = true
    document.body.style.overflow = 'hidden'
}

function closeFilterDrawer() {
    isFilterDrawerOpen.value = false
    document.body.style.overflow = ''
}

function changePage(page) {
    currentPage.value = page

    router.replace({
        query: {
            ...route.query,
            page,
        },
    })

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

function handleAddToCart(product) {
    if (product.stock <= 0) {
        return
    }

    cartStore.addItem(product, 1)
}

function handleToggleWishlist(product) {
    console.log('Toggle wishlist:', product)
}

watch(sortBy, (value) => {
    router.replace({
        query: {
            ...route.query,
            sort: value,
            page: undefined,
        },
    })

    currentPage.value = 1
})

watch(
    () => [
        filters.categories,
        filters.brands,
        filters.minPrice,
        filters.maxPrice,
        filters.rating,
        filters.inStockOnly,
        filters.promotionOnly,
    ],
    () => {
        currentPage.value = 1
    },
    {
        deep: true,
    },
)

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleEscape,
    )
})

onUnmounted(() => {
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
                    {{ filteredProducts.length }}
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
                    v-if="
                        filteredProducts.length > 0
                    "
                    class="product-grid"
                >
                    <ProductCard
                        v-for="product in filteredProducts"
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
                    :current-page="currentPage"
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
                        {{ filteredProducts.length }}
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
<script setup>
import {
    ArrowRight,
    CheckCircle2,
    Headphones,
    Heart,
    ShieldCheck,
    ShoppingBag,
    Sparkles,
    Star,
    Store,
    Tags,
    Truck,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import ProductCard from '@/components/customer/ProductCard.vue'
import { getProducts } from '@/api/products'
import { addToWishlist, removeFromWishlist } from '@/api/wishlist'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()

const cartStore = useCartStore()
const authStore = useAuthStore()

const products = ref([])
const productMeta = ref(null)

const isLoadingProducts = ref(false)

const cartLoadingId = ref(null)
const wishlistLoadingId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const categories = computed(() => {
    const map = new Map()

    products.value.forEach(product => {
        const category = product.category
        if (category?.id && !map.has(category.id)) {
            map.set(category.id, category)
        }
    })

    return [...map.values()].slice(0, 4)
})

const featuredProduct = computed(() => {
    return products.value[0] ?? null
})

const secondaryProducts = computed(() => {
    return products.value.slice(1, 5)
})

const totalProducts = computed(() => {
    return Number(productMeta.value?.total ?? products.value.length)
})

const loadedReviewCount = computed(() => {
    return products.value.reduce((total, product) => {
        return total + Number(product.reviews_count ?? 0)
    }, 0)
})

const averageRating = computed(() => {
    const ratings = products.value
        .map(product => Number(product.average_rating ?? 0))
        .filter(rating => rating > 0)

    if (!ratings.length) {
        return '0.0'
    }

    const total = ratings.reduce((sum, rating) => sum + rating, 0)
    return (total / ratings.length).toFixed(1)
})

const featuredReviews = computed(() => {
    return products.value
        .flatMap(product => {
            const reviews = Array.isArray(product.reviews) ? product.reviews : []
            return reviews.map(review => ({
                ...review,
                product_name: product.name,
                product_slug: product.slug,
            }))
        })
        .filter(review => Boolean(review.comment))
        .slice(0, 3)
})

const benefits = [
    {
        title: 'Nguồn hàng đa dạng',
        description: 'Sản phẩm chọn lọc từ các nhà bán hàng uy tín.',
        icon: Store,
    },
    {
        title: 'Giá cả minh bạch',
        description: 'Giá niêm yết rõ ràng, nhiều ưu đãi hấp dẫn.',
        icon: Tags,
    },
    {
        title: 'Giao hàng tin cậy',
        description: 'Đóng gói cẩn thận, theo dõi lộ trình trực tiếp.',
        icon: Truck,
    },
    {
        title: 'Hỗ trợ chu đáo',
        description: 'Dễ dàng tra cứu đơn hàng và đổi trả tiện lợi.',
        icon: Headphones,
    },
]

async function fetchProducts() {
    isLoadingProducts.value = true
    errorMessage.value = ''

    try {
        const response = await getProducts({
            per_page: 8,
            sort: 'newest',
        })

        products.value = Array.isArray(response.data?.data)
            ? response.data.data
            : []

        productMeta.value = response.data?.meta ?? null
    } catch (error) {
        console.error('Không thể tải sản phẩm:', error)
        products.value = []
        productMeta.value = null
        errorMessage.value = error.response?.data?.message ?? 'Không thể tải sản phẩm.'
    } finally {
        isLoadingProducts.value = false
    }
}

async function handleAddToCart(product) {
    if (!product || cartLoadingId.value) return
    cartLoadingId.value = product.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        await cartStore.addItem(product.id, 1)
        successMessage.value = `Đã thêm "${product.name}" vào giỏ hàng.`
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            error.message ??
            'Không thể thêm sản phẩm vào giỏ hàng.'
    } finally {
        cartLoadingId.value = null
    }
}

async function handleToggleWishlist(product) {
    if (!product) return

    if (!authStore.isAuthenticated) {
        router.push({
            name: 'login',
            query: { redirect: route.fullPath },
        })
        return
    }

    if (wishlistLoadingId.value) return
    wishlistLoadingId.value = product.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        if (product.is_wishlisted) {
            await removeFromWishlist(product.id)
            product.is_wishlisted = false
            successMessage.value = 'Đã bỏ sản phẩm khỏi danh sách yêu thích.'
        } else {
            await addToWishlist(product.id)
            product.is_wishlisted = true
            successMessage.value = 'Đã thêm sản phẩm vào danh sách yêu thích.'
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể cập nhật danh sách yêu thích.'
    } finally {
        wishlistLoadingId.value = null
    }
}

function getImageUrl(product) {
    if (!product) return ''
    return product.main_image?.url ?? product.main_image?.path ?? ''
}

function getCurrentPrice(product) {
    return Number(
        product?.effective_price ??
        product?.sale_price ??
        product?.price ??
        0,
    )
}

function formatPrice(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

function formatReviewDate(value) {
    if (!value) return ''
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date)
}

onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <main class="clean-ecom">
        <!-- 1. Marquee Ticker (Dải chữ chạy vô hạn - Nền sáng Xanh Mint) -->
        <div class="marquee-ribbon">
            <div class="ticker-content">
                <span>★ NEXACART MULTI-STORE</span>
                <span>• GIAO HÀNG HỎA TỐC 2H</span>
                <span>• 100% HÀNG CHÍNH HÃNG</span>
                <span>• ĐỔI TRẢ TRONG 7 NGÀY</span>
                <span>★ DEAL ĐỘC QUYỀN MỖI NGÀY</span>
                <span>• BẢO HÀNH TOÀN QUỐC</span>
                <span>★ NEXACART MULTI-STORE</span>
                <span>• GIAO HÀNG HỎA TỐC 2H</span>
                <span>• 100% HÀNG CHÍNH HÃNG</span>
                <span>• ĐỔI TRẢ TRONG 7 NGÀY</span>
            </div>
        </div>

        <!-- Thông Báo -->
        <div v-if="errorMessage" class="notice-box notice-error">
            <span>{{ errorMessage }}</span>
        </div>

        <div v-if="successMessage" class="notice-box notice-success">
            <CheckCircle2 :size="16" />
            <span>{{ successMessage }}</span>
        </div>

        <!-- ========================================
             2. HERO ASYMMETRIC (Phá Cách & Tươi Sáng)
        ======================================== -->
        <section class="hero-showcase-stage">
            <!-- Cột Trái: Typography Lớn & Thao Tác -->
            <div class="hero-left-col">
                <div class="meta-tag-group">
                    <span class="tag-badge">BỘ SƯU TẬP 2026</span>
                    <span class="tag-text">NỀN TẢNG THƯƠNG MẠI CHỌN LỌC</span>
                </div>

                <h1 class="hero-big-title">
                    ĐA DẠNG<br />
                    <span class="title-accent">LỰA CHỌN</span><br />
                    <span class="title-emerald">MỖI NGÀY.</span>
                </h1>

                <p class="hero-intro">
                    Khám phá hàng ngàn mặt hàng từ các nhà bán hàng đối tác tuyển chọn. Giá tốt mỗi ngày, giao hàng hỏa tốc và quy trình mua sắm minh bạch.
                </p>

                <div class="hero-btn-row">
                    <RouterLink :to="{ name: 'products' }" class="btn-action btn-green">
                        <span>Khám phá sản phẩm</span>
                        <ArrowRight :size="16" />
                    </RouterLink>
                    <RouterLink :to="{ name: 'customer-profile' }" class="btn-action btn-light">
                        <span>Kênh bán hàng</span>
                    </RouterLink>
                </div>
            </div>

            <!-- Cột Phải: Khung Trưng Bày Sản Phẩm Nổi Bật -->
            <div class="hero-right-col">
                <RouterLink
                    v-if="featuredProduct"
                    :to="{ name: 'product-detail', params: { slug: featuredProduct.slug } }"
                    class="featured-frame"
                >
                    <div class="sticker-tag">NỔI BẬT HÔM NAY</div>

                    <div class="image-stage">
                        <img
                            v-if="getImageUrl(featuredProduct)"
                            :src="getImageUrl(featuredProduct)"
                            :alt="featuredProduct.name"
                        />
                        <div v-else class="stage-placeholder">
                            <ShoppingBag :size="50" />
                        </div>
                    </div>

                    <div class="caption-bar">
                        <div class="caption-text">
                            <span class="caption-category">{{ featuredProduct.category?.name ?? 'SẢN PHẨM' }}</span>
                            <h3 class="caption-title">{{ featuredProduct.name }}</h3>
                        </div>
                        <div class="caption-price">
                            {{ formatPrice(getCurrentPrice(featuredProduct)) }}
                        </div>
                    </div>
                </RouterLink>

                <!-- 2 Sản Phẩm Gợi Ý Phía Dưới -->
                <div v-if="secondaryProducts.length" class="secondary-items-grid">
                    <RouterLink
                        v-for="product in secondaryProducts.slice(0, 2)"
                        :key="product.id"
                        :to="{ name: 'product-detail', params: { slug: product.slug } }"
                        class="secondary-tile"
                    >
                        <div class="tile-thumb">
                            <img v-if="getImageUrl(product)" :src="getImageUrl(product)" :alt="product.name" />
                            <ShoppingBag v-else :size="16" />
                        </div>
                        <div class="tile-info">
                            <h4 class="tile-title">{{ product.name }}</h4>
                            <span class="tile-price">{{ formatPrice(getCurrentPrice(product)) }}</span>
                        </div>
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- ========================================
             3. THANH CAM KẾT (01 – 04)
        ======================================== -->
        <section class="commitments-row">
            <div v-for="(benefit, index) in benefits" :key="benefit.title" class="commitment-card">
                <span class="card-num">0{{ index + 1 }}</span>
                <div class="card-body">
                    <h3 class="card-heading">{{ benefit.title }}</h3>
                    <p class="card-desc">{{ benefit.description }}</p>
                </div>
            </div>
        </section>

        <!-- ========================================
             4. DANH MỤC INDEX (01 – 04)
        ======================================== -->
        <section class="section-container">
            <header class="section-top-bar">
                <div class="top-bar-title">
                    <span class="label-pill">DANH MỤC</span>
                    <h2>Ngành Hàng Nổi Bật</h2>
                </div>
            </header>

            <div v-if="categories.length" class="category-index-grid">
                <RouterLink
                    v-for="(category, idx) in categories"
                    :key="category.id"
                    :to="{ name: 'products', query: { category: category.slug } }"
                    class="index-card"
                >
                    <div class="index-card-head">
                        <span class="index-number">0{{ idx + 1 }}</span>
                        <ArrowRight :size="18" class="index-arrow" />
                    </div>
                    <div class="index-card-foot">
                        <h3 class="index-title">{{ category.name }}</h3>
                        <span class="index-link">Khám phá bộ sưu tập &rarr;</span>
                    </div>
                    <div class="card-hover-indicator"></div>
                </RouterLink>
            </div>
        </section>

        <!-- ========================================
             5. SẢN PHẨM MỚI (PRODUCTS)
        ======================================== -->
        <section class="section-container">
            <header class="section-top-bar">
                <div class="top-bar-title">
                    <span class="label-pill">BỘ SƯU TẬP MỚI</span>
                    <h2>Sản Phẩm Vừa Cập Nhật</h2>
                </div>
                <RouterLink :to="{ name: 'products' }" class="see-all-link">
                    <span>Xem tất cả gian hàng</span>
                    <ArrowRight :size="16" />
                </RouterLink>
            </header>

            <!-- Skeleton Loading -->
            <div v-if="isLoadingProducts" class="products-grid-view">
                <div v-for="i in 8" :key="i" class="product-skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-line full"></div>
                    <div class="skeleton-line short"></div>
                </div>
            </div>

            <!-- Empty -->
            <div v-else-if="products.length === 0" class="empty-view">
                <ShoppingBag :size="36" />
                <p>Chưa có sản phẩm nào được bày bán.</p>
            </div>

            <!-- Grid Sản Phẩm -->
            <div v-else class="products-grid-view">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    :wishlist-loading="wishlistLoadingId === product.id"
                    :cart-loading="cartLoadingId === product.id"
                    @add-to-cart="handleAddToCart"
                    @toggle-wishlist="handleToggleWishlist"
                />
            </div>
        </section>

        <!-- ========================================
             6. DẢI CHỈ SỐ LỚN (BIG NUMBERS)
        ======================================== -->
        <section class="metrics-strip">
            <div class="metric-item">
                <strong class="metric-val">{{ totalProducts }}+</strong>
                <span class="metric-lbl">Sản phẩm sẵn có</span>
            </div>
            <div class="metric-item">
                <strong class="metric-val green-text">{{ averageRating }}★</strong>
                <span class="metric-lbl">Điểm đánh giá trung bình</span>
            </div>
            <div class="metric-item">
                <strong class="metric-val">{{ loadedReviewCount }}+</strong>
                <span class="metric-lbl">Lượt phản hồi xác thực</span>
            </div>
            <div class="metric-item">
                <strong class="metric-val">{{ categories.length }}+</strong>
                <span class="metric-lbl">Danh mục hàng hóa</span>
            </div>
        </section>

        <!-- ========================================
             7. ĐÁNH GIÁ TẠP CHÍ (EDITORIAL REVIEWS)
        ======================================== -->
        <section class="section-container">
            <header class="section-top-bar">
                <div class="top-bar-title">
                    <span class="label-pill">Ý KIẾN KHÁCH HÀNG</span>
                    <h2>Trải Nghiệm Mua Sắm Thực Tế</h2>
                </div>
            </header>

            <div v-if="featuredReviews.length" class="editorial-reviews-grid">
                <article v-for="review in featuredReviews" :key="review.id" class="review-editorial-card">
                    <div class="quote-symbol">“</div>
                    <div class="review-stars-box">
                        <Star
                            v-for="star in 5"
                            :key="star"
                            :size="13"
                            :fill="star <= Number(review.rating) ? 'currentColor' : 'none'"
                            class="star-gold"
                        />
                    </div>
                    <p class="review-quote-body">{{ review.comment }}</p>
                    <div class="review-buyer-line">
                        <strong class="buyer-name">{{ review.user?.name ?? 'Khách hàng' }}</strong>
                        <span class="buyer-date">{{ formatReviewDate(review.created_at) }} • Đã mua hàng</span>
                    </div>
                </article>
            </div>

            <div v-else class="empty-view">
                <p>Chưa có đánh giá nào được hiển thị.</p>
            </div>
        </section>
    </main>
</template>

<style scoped>
/* ================= Base Styles (No black borders, Fresh Green Palette) ================= */
.clean-ecom {
    --brand-green: #15803d;         /* Xanh lá chủ đạo */
    --brand-green-hover: #166534;   /* Xanh đậm khi hover */
    --brand-green-soft: #f0fdf4;    /* Nền xanh nhạt */
    --brand-green-border: #bbf7d0;  /* Viền xanh nhạt */
    --color-text-main: #0f172a;     /* Màu chữ đậm thanh lịch (Slate 900) */
    --color-text-sub: #475569;      /* Màu chữ phụ (Slate 600) */
    --color-text-muted: #64748b;    /* Màu chữ mờ (Slate 500) */
    --color-border: #e2e8f0;        /* Viền xám sáng tinh tế */
    --color-bg-subtle: #f8fafc;     /* Nền xám nhạt */
    --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
    --shadow-hover: 0 10px 20px -3px rgba(21, 128, 61, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.03);

    display: flex;
    flex-direction: column;
    gap: 40px;
    width: min(calc(100% - 40px), 1280px);
    margin: 0 auto;
    padding: 0 0 36px 0;
    color: var(--color-text-main);
    font-family: Roboto, Arial, sans-serif;
    box-sizing: border-box;
}

/* ================= 1. Marquee Ticker (Nền sáng Xanh Mint) ================= */
.marquee-ribbon {
    overflow: hidden;
    white-space: nowrap;
    background: var(--brand-green-soft);
    color: var(--brand-green);
    padding: 8px 0;
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--brand-green-border);
}

.ticker-content {
    display: inline-flex;
    gap: 32px;
    animation: ticker-loop 25s linear infinite;
}

@keyframes ticker-loop {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ================= Notice Boxes ================= */
.notice-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    font-size: 0.8125rem;
    font-weight: 600;
    border: 1px solid;
}
.notice-error {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.notice-success {
    background: var(--brand-green-soft);
    border-color: var(--brand-green-border);
    color: var(--brand-green);
}

/* ================= 2. Hero Stage ================= */
.hero-showcase-stage {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(400px, 0.9fr);
    gap: 32px;
    align-items: stretch;
    padding-top: 10px;
}

.hero-left-col {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 0;
}

.meta-tag-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tag-badge {
    background: var(--brand-green-soft);
    color: var(--brand-green);
    border: 1px solid var(--brand-green-border);
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 800;
    padding: 3px 8px;
}

.tag-text {
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--color-text-muted);
    letter-spacing: 0.05em;
}

.hero-big-title {
    font-size: clamp(2.25rem, 4.5vw, 3.75rem);
    font-weight: 900;
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0;
    text-transform: uppercase;
}

.title-accent {
    color: var(--color-text-main);
}

.title-emerald {
    color: var(--brand-green);
}

.hero-intro {
    font-size: 0.9375rem;
    line-height: 1.6;
    color: var(--color-text-sub);
    max-width: 480px;
    margin: 0;
}

.hero-btn-row {
    display: flex;
    gap: 12px;
    margin-top: 4px;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 46px;
    padding: 0 22px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
}

.btn-green {
    background: var(--brand-green);
    color: #ffffff;
    border-color: var(--brand-green);
}
.btn-green:hover {
    background: var(--brand-green-hover);
}

.btn-light {
    background: #ffffff;
    color: var(--color-text-main);
    border-color: var(--color-border);
}
.btn-light:hover {
    background: var(--color-bg-subtle);
    border-color: #cbd5e1;
}

/* ================= 2.1 Hero Right Frame ================= */
.hero-right-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.featured-frame {
    position: relative;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-card);
    text-decoration: none;
    color: inherit;
    overflow: hidden;
    flex: 1;
    transition: all 0.2s ease;
}

.featured-frame:hover {
    border-color: var(--brand-green-border);
    box-shadow: var(--shadow-hover);
}

.sticker-tag {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
    background: var(--brand-green);
    color: #ffffff;
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 800;
    padding: 3px 8px;
}

.image-stage {
    width: 100%;
    height: 260px;
    background: var(--color-bg-subtle);
    overflow: hidden;
}

.image-stage img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.featured-frame:hover .image-stage img {
    transform: scale(1.04);
}

.stage-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--color-text-muted);
}

.caption-bar {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 14px 18px;
    background: #ffffff;
    border-top: 1px solid var(--color-border);
}

.caption-category {
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--brand-green);
    text-transform: uppercase;
}

.caption-title {
    margin: 2px 0 0 0;
    font-size: 0.9375rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.caption-price {
    font-size: 1.1875rem;
    font-weight: 800;
    color: var(--brand-green);
    font-feature-settings: "tnum";
}

/* Secondary Items Grid */
.secondary-items-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.secondary-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #ffffff;
    border: 1px solid var(--color-border);
    text-decoration: none;
    color: inherit;
    transition: all 0.15s ease;
}

.secondary-tile:hover {
    border-color: var(--brand-green);
    background: var(--color-bg-subtle);
}

.tile-thumb {
    width: 46px;
    height: 46px;
    background: var(--color-bg-subtle);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tile-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tile-info {
    min-width: 0;
}

.tile-title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tile-price {
    display: block;
    margin-top: 2px;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--brand-green);
}

/* ================= 3. Commitments Row (01 – 04) ================= */
.commitments-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--brand-green);
    background: #ffffff;
}

.commitment-card {
    display: flex;
    gap: 12px;
    padding: 18px 20px;
    border-right: 1px solid var(--color-border);
}
.commitment-card:last-child {
    border-right: none;
}

.card-num {
    font-family: monospace;
    font-size: 1.0625rem;
    font-weight: 800;
    color: var(--brand-green);
}

.card-heading {
    margin: 0 0 3px 0;
    font-size: 0.8125rem;
    font-weight: 700;
}

.card-desc {
    margin: 0;
    font-size: 0.6875rem;
    line-height: 1.45;
    color: var(--color-text-muted);
}

/* ================= 4. Section Container & Headers ================= */
.section-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 10px;
}

.top-bar-title {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.label-pill {
    font-family: monospace;
    font-size: 0.6875rem;
    font-weight: 800;
    color: var(--brand-green);
    letter-spacing: 0.06em;
}

.section-top-bar h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.see-all-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--brand-green);
    text-decoration: none;
}
.see-all-link:hover {
    text-decoration: underline;
}

/* Category Index Grid (01 - 04) */
.category-index-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.index-card {
    position: relative;
    background: #ffffff;
    border: 1px solid var(--color-border);
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 130px;
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow-card);
    transition: all 0.2s ease;
}

.index-card:hover {
    border-color: var(--brand-green-border);
    background: var(--brand-green-soft);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.index-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.index-number {
    font-family: monospace;
    font-size: 1.125rem;
    font-weight: 800;
    color: var(--brand-green);
}

.index-arrow {
    color: var(--color-text-muted);
    transition: transform 0.2s ease, color 0.2s ease;
}

.index-card:hover .index-arrow {
    color: var(--brand-green);
    transform: translateX(3px);
}

.index-title {
    margin: 0 0 3px 0;
    font-size: 0.9375rem;
    font-weight: 700;
}

.index-link {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--brand-green);
}

.card-hover-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--brand-green);
    transform: scaleX(0);
    transition: transform 0.2s ease;
}

.index-card:hover .card-hover-indicator {
    transform: scaleX(1);
}

/* ================= 5. Products Grid ================= */
.products-grid-view {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.product-skeleton-card {
    background: #ffffff;
    border: 1px solid var(--color-border);
    padding: 10px;
}
.skeleton-image {
    width: 100%;
    height: 180px;
    background: #f1f5f9;
}
.skeleton-line {
    height: 10px;
    background: #f1f5f9;
    margin-top: 10px;
}
.skeleton-line.full { width: 90%; }
.skeleton-line.short { width: 45%; }

.empty-view {
    padding: 48px 20px;
    text-align: center;
    border: 1px solid var(--color-border);
    background: var(--color-bg-subtle);
    color: var(--color-text-muted);
    font-size: 0.8125rem;
}

/* ================= 6. Big Metrics Strip ================= */
.metrics-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 1px solid var(--color-border);
    background: #ffffff;
    padding: 20px 0;
    box-shadow: var(--shadow-card);
}

.metric-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 1px solid var(--color-border);
    padding: 0 16px;
}
.metric-item:last-child {
    border-right: none;
}

.metric-val {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--color-text-main);
    letter-spacing: -0.02em;
    font-feature-settings: "tnum";
}

.metric-val.green-text {
    color: var(--brand-green);
}

.metric-lbl {
    font-size: 0.6875rem;
    color: var(--color-text-muted);
    margin-top: 2px;
}

/* ================= 7. Editorial Reviews Grid ================= */
.editorial-reviews-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.review-editorial-card {
    position: relative;
    background: #ffffff;
    border: 1px solid var(--color-border);
    border-left: 3px solid var(--brand-green);
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: var(--shadow-card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.review-editorial-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.quote-symbol {
    font-family: Georgia, serif;
    font-size: 2rem;
    line-height: 1;
    color: var(--brand-green);
    margin-bottom: -6px;
}

.review-stars-box {
    display: flex;
    gap: 2px;
    color: #eab308;
}

.review-quote-body {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.55;
    color: var(--color-text-sub);
    flex: 1;
}

.review-buyer-line {
    display: flex;
    flex-direction: column;
    padding-top: 8px;
    border-top: 1px solid var(--color-border);
}

.buyer-name {
    font-size: 0.75rem;
    font-weight: 700;
}

.buyer-date {
    font-size: 0.6875rem;
    color: var(--color-text-muted);
}

/* ================= 8. Responsive Breakpoints ================= */
@media (max-width: 1100px) {
    .hero-showcase-stage {
        grid-template-columns: 1fr;
    }
    .commitments-row,
    .category-index-grid,
    .metrics-strip {
        grid-template-columns: repeat(2, 1fr);
    }
    .commitment-card:nth-child(2),
    .metric-item:nth-child(2) {
        border-right: none;
    }
    .commitment-card,
    .metric-item {
        border-bottom: 1px solid var(--color-border);
    }
    .products-grid-view {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .products-grid-view,
    .editorial-reviews-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .clean-ecom {
        width: calc(100% - 24px);
        gap: 28px;
    }
    .hero-big-title {
        font-size: 2rem;
    }
    .commitments-row,
    .category-index-grid,
    .products-grid-view,
    .metrics-strip,
    .editorial-reviews-grid {
        grid-template-columns: 1fr;
    }
    .metric-item,
    .commitment-card {
        border-right: none;
    }
    .hero-btn-row {
        flex-direction: column;
    }
    .btn-action {
        width: 100%;
    }
    .secondary-items-grid {
        grid-template-columns: 1fr;
    }
}
</style>
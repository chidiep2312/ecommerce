<script setup>
import {
    ArrowRight,
    Check,
    ChevronRight,
    Heart,
    PackageCheck,
    RefreshCcw,
    ShieldCheck,
    ShoppingBag,
    Star,
    Store,
    Truck,
} from '@lucide/vue'

import {
    computed,
    ref,
} from 'vue'

import { useRoute } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import ProductCard from '@/components/customer/ProductCard.vue'
import QuantitySelector from '@/components/customer/QuantitySelector.vue'
const cartStore = useCartStore()
const route = useRoute()

const quantity = ref(1)
const selectedImageIndex = ref(0)
const activeTab = ref('description')
const isWishlisted = ref(false)

const product = ref({
    id: 1,
    name: 'Tai nghe không dây chống ồn chủ động',
    slug: 'tai-nghe-khong-day-chong-on',
    sku: 'NC-AU-001',
    category: 'Thiết bị âm thanh',
    brand: 'Auralis',
    price: 1890000,
    originalPrice: 2290000,
    rating: 4.8,
    reviews: 128,
    sold: 364,
    stock: 18,
    badge: 'Bán chạy',

    images: [
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=85',

        'https://images.unsplash.com/photo-1484704849700-f032a568e944?auto=format&fit=crop&w=1200&q=85',

        'https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=1200&q=85',

        'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&w=1200&q=85',
    ],

    shortDescription:
        'Tai nghe không dây với khả năng chống ồn chủ động, âm thanh cân bằng và thiết kế phù hợp cho việc sử dụng hằng ngày.',

    description: [
        'Sản phẩm được thiết kế cho nhu cầu nghe nhạc, làm việc và di chuyển trong môi trường có nhiều tiếng ồn.',

        'Công nghệ chống ồn chủ động giúp hạn chế âm thanh từ môi trường, trong khi chế độ xuyên âm hỗ trợ người dùng theo dõi những âm thanh quan trọng xung quanh.',

        'Phần đệm tai mềm và khung tai nghe có thể điều chỉnh giúp duy trì sự thoải mái khi sử dụng trong thời gian dài.',
    ],

    specifications: [
        {
            label: 'Kiểu kết nối',
            value: 'Bluetooth 5.3',
        },
        {
            label: 'Thời lượng pin',
            value: 'Lên đến 35 giờ',
        },
        {
            label: 'Sạc',
            value: 'USB Type-C',
        },
        {
            label: 'Chống ồn',
            value: 'Chống ồn chủ động ANC',
        },
        {
            label: 'Trọng lượng',
            value: '254 g',
        },
        {
            label: 'Bảo hành',
            value: '12 tháng',
        },
    ],

    seller: {
        name: 'Auralis Official Store',
        rating: 4.9,
        products: 42,
        joinedAt: '2025',
    },
})

const relatedProducts = [
    {
        id: 2,
        name: 'Loa bluetooth để bàn thiết kế tối giản',
        slug: 'loa-bluetooth-de-ban-toi-gian',
        category: 'Thiết bị âm thanh',
        price: 2190000,
        originalPrice: null,
        rating: 4.5,
        reviews: 73,
        badge: null,
        image:
            'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 3,
        name: 'Tai nghe nhét tai không dây nhỏ gọn',
        slug: 'tai-nghe-nhet-tai-khong-day',
        category: 'Thiết bị âm thanh',
        price: 1190000,
        originalPrice: 1390000,
        rating: 4.6,
        reviews: 91,
        badge: 'Hàng mới',
        image:
            'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 4,
        name: 'Giá đỡ tai nghe bằng kim loại',
        slug: 'gia-do-tai-nghe-kim-loai',
        category: 'Phụ kiện',
        price: 390000,
        originalPrice: null,
        rating: 4.7,
        reviews: 54,
        badge: null,
        image:
            'https://images.unsplash.com/photo-1599669454699-248893623440?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 5,
        name: 'Loa di động chống nước',
        slug: 'loa-di-dong-chong-nuoc',
        category: 'Thiết bị âm thanh',
        price: 1490000,
        originalPrice: 1690000,
        rating: 4.8,
        reviews: 146,
        badge: 'Bán chạy',
        image:
            'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=800&q=80',
    },
]

const currentImage = computed(() => {
    return product.value.images[
        selectedImageIndex.value
    ]
})

const discountPercent = computed(() => {
    if (!product.value.originalPrice) {
        return 0
    }

    return Math.round(
        (
            1 -
            product.value.price /
                product.value.originalPrice
        ) * 100,
    )
})

const isOutOfStock = computed(() => {
    return product.value.stock <= 0
})

const totalPrice = computed(() => {
    return product.value.price * quantity.value
})

function formatPrice(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value)
}

function selectImage(index) {
    selectedImageIndex.value = index
}

function toggleWishlist() {
    isWishlisted.value =
        !isWishlisted.value
}

function addToCart() {
    if (isOutOfStock.value) {
        return
    }

    cartStore.addItem(
        {
            ...product.value,
            image: product.value.images[0],
        },
        quantity.value,
    )
}

function buyNow() {
    if (isOutOfStock.value) {
        return
    }

    console.log('Buy now:', {
        productId: product.value.id,
        quantity: quantity.value,
    })
}

function handleRelatedAddToCart(item) {
    cartStore.addItem(item, 1)
}

function handleRelatedWishlist(item) {
    console.log('Related wishlist:', item)
}
</script>

<template>
    <div class="product-detail-page">
        <nav
            class="breadcrumb"
            aria-label="Breadcrumb"
        >
            <RouterLink to="/">
                Trang chủ
            </RouterLink>

            <ChevronRight :size="14" />

            <RouterLink to="/products">
                Sản phẩm
            </RouterLink>

            <ChevronRight :size="14" />

            <span aria-current="page">
                {{ product.name }}
            </span>
        </nav>

        <section class="product-overview">
            <div class="product-gallery">
                <div class="product-gallery__thumbnails">
                    <button
                        v-for="(image, index) in product.images"
                        :key="image"
                        type="button"
                        class="product-gallery__thumbnail"
                        :class="{
                            'product-gallery__thumbnail--active':
                                selectedImageIndex ===
                                index,
                        }"
                        :aria-label="
                            `Xem ảnh sản phẩm ${index + 1}`
                        "
                        @click="selectImage(index)"
                    >
                        <img
                            :src="image"
                            :alt="
                                `${product.name} - ảnh ${index + 1}`
                            "
                        />
                    </button>
                </div>

                <div class="product-gallery__main">
                    <span
                        v-if="product.badge"
                        class="product-gallery__badge"
                    >
                        {{ product.badge }}
                    </span>

                    <img
                        :src="currentImage"
                        :alt="product.name"
                        class="product-gallery__main-image"
                    />
                </div>
            </div>

            <div class="product-information">
                <div class="product-information__meta">
                    <RouterLink
                        :to="{
                            name: 'products',
                            query: {
                                category: product.category,
                            },
                        }"
                    >
                        {{ product.category }}
                    </RouterLink>

                    <span>
                        Mã sản phẩm:
                        {{ product.sku }}
                    </span>
                </div>

                <h1>
                    {{ product.name }}
                </h1>

                <div class="product-rating">
                    <div class="product-rating__score">
                        <Star
                            :size="16"
                            fill="currentColor"
                        />

                        <strong>
                            {{ product.rating }}
                        </strong>
                    </div>

                    <span>
                        {{ product.reviews }}
                        đánh giá
                    </span>

                    <span>
                        Đã bán
                        {{ product.sold }}
                    </span>
                </div>

                <p class="product-information__description">
                    {{ product.shortDescription }}
                </p>

                <div class="product-price">
                    <span class="product-price__current">
                        {{
                            formatPrice(
                                product.price,
                            )
                        }}
                    </span>

                    <span
                        v-if="product.originalPrice"
                        class="product-price__original"
                    >
                        {{
                            formatPrice(
                                product.originalPrice,
                            )
                        }}
                    </span>

                    <span
                        v-if="discountPercent > 0"
                        class="product-price__discount"
                    >
                        -{{ discountPercent }}%
                    </span>
                </div>

                <div
                    class="stock-status"
                    :class="{
                        'stock-status--available':
                            !isOutOfStock,
                        'stock-status--unavailable':
                            isOutOfStock,
                    }"
                >
                    <Check
                        v-if="!isOutOfStock"
                        :size="16"
                    />

                    <span>
                        {{
                            isOutOfStock
                                ? 'Sản phẩm tạm hết hàng'
                                : `Còn ${product.stock} sản phẩm`
                        }}
                    </span>
                </div>

                <div class="purchase-section">
                    <div class="purchase-section__quantity">
                        <span>Số lượng</span>

                        <QuantitySelector
                            v-model="quantity"
                            :max="product.stock"
                            :disabled="isOutOfStock"
                        />
                    </div>

                    <div class="purchase-section__total">
                        <span>Tạm tính</span>

                        <strong>
                            {{
                                formatPrice(
                                    totalPrice,
                                )
                            }}
                        </strong>
                    </div>

                    <div class="purchase-section__actions">
                        <button
                            type="button"
                            class="purchase-button purchase-button--primary"
                            :disabled="isOutOfStock"
                            @click="addToCart"
                        >
                            <ShoppingBag :size="19" />

                            Thêm vào giỏ hàng
                        </button>

                        <button
                            type="button"
                            class="purchase-button purchase-button--secondary"
                            :disabled="isOutOfStock"
                            @click="buyNow"
                        >
                            Mua ngay
                        </button>

                        <button
                            type="button"
                            class="wishlist-button"
                            :class="{
                                'wishlist-button--active':
                                    isWishlisted,
                            }"
                            :aria-label="
                                isWishlisted
                                    ? 'Xóa khỏi yêu thích'
                                    : 'Thêm vào yêu thích'
                            "
                            @click="toggleWishlist"
                        >
                            <Heart
                                :size="20"
                                :fill="
                                    isWishlisted
                                        ? 'currentColor'
                                        : 'none'
                                "
                            />
                        </button>
                    </div>
                </div>

                <div class="purchase-benefits">
                    <article>
                        <Truck :size="20" />

                        <div>
                            <strong>
                                Giao hàng theo dõi được
                            </strong>

                            <span>
                                Kiểm tra trạng thái đơn hàng
                                trong tài khoản.
                            </span>
                        </div>
                    </article>

                    <article>
                        <RefreshCcw :size="20" />

                        <div>
                            <strong>
                                Chính sách đổi trả
                            </strong>

                            <span>
                                Điều kiện đổi trả được công
                                khai trước khi đặt hàng.
                            </span>
                        </div>
                    </article>

                    <article>
                        <ShieldCheck :size="20" />

                        <div>
                            <strong>
                                Thanh toán an toàn
                            </strong>

                            <span>
                                Thông tin thanh toán được xử
                                lý qua kết nối bảo mật.
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="seller-card">
            <div class="seller-card__identity">
                <span class="seller-card__avatar">
                    <Store :size="25" />
                </span>

                <div>
                    <p>Được bán bởi</p>

                    <h2>
                        {{ product.seller.name }}
                    </h2>

                    <div class="seller-card__rating">
                        <Star
                            :size="14"
                            fill="currentColor"
                        />

                        <span>
                            {{ product.seller.rating }}
                        </span>
                    </div>
                </div>
            </div>

            <dl class="seller-card__stats">
                <div>
                    <dt>
                        {{ product.seller.products }}
                    </dt>

                    <dd>Sản phẩm</dd>
                </div>

                <div>
                    <dt>
                        {{ product.seller.joinedAt }}
                    </dt>

                    <dd>Tham gia</dd>
                </div>
            </dl>

            <RouterLink
                :to="{
                    name: 'products',
                    query: {
                        seller: product.seller.name,
                    },
                }"
                class="seller-card__link"
            >
                Xem cửa hàng

                <ArrowRight :size="17" />
            </RouterLink>
        </section>

        <section class="product-content">
            <div
                class="product-content__tabs"
                role="tablist"
            >
                <button
                    type="button"
                    role="tab"
                    :aria-selected="
                        activeTab === 'description'
                    "
                    :class="{
                        'product-content__tab--active':
                            activeTab ===
                            'description',
                    }"
                    @click="
                        activeTab = 'description'
                    "
                >
                    Mô tả sản phẩm
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="
                        activeTab ===
                        'specifications'
                    "
                    :class="{
                        'product-content__tab--active':
                            activeTab ===
                            'specifications',
                    }"
                    @click="
                        activeTab =
                            'specifications'
                    "
                >
                    Thông số kỹ thuật
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="
                        activeTab === 'shipping'
                    "
                    :class="{
                        'product-content__tab--active':
                            activeTab ===
                            'shipping',
                    }"
                    @click="
                        activeTab = 'shipping'
                    "
                >
                    Vận chuyển và đổi trả
                </button>
            </div>

            <div class="product-content__body">
                <div
                    v-if="
                        activeTab === 'description'
                    "
                    class="description-content"
                >
                    <h2>
                        Thông tin sản phẩm
                    </h2>

                    <p
                        v-for="paragraph in product.description"
                        :key="paragraph"
                    >
                        {{ paragraph }}
                    </p>
                </div>

                <div
                    v-else-if="
                        activeTab ===
                        'specifications'
                    "
                    class="specification-content"
                >
                    <h2>
                        Thông số kỹ thuật
                    </h2>

                    <dl class="specification-table">
                        <div
                            v-for="item in product.specifications"
                            :key="item.label"
                        >
                            <dt>
                                {{ item.label }}
                            </dt>

                            <dd>
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-else
                    class="shipping-content"
                >
                    <h2>
                        Vận chuyển và đổi trả
                    </h2>

                    <article>
                        <PackageCheck :size="22" />

                        <div>
                            <h3>
                                Xử lý đơn hàng
                            </h3>

                            <p>
                                Nhà bán hàng xác nhận và xử
                                lý đơn hàng theo thời gian
                                hiển thị tại trang thanh
                                toán.
                            </p>
                        </div>
                    </article>

                    <article>
                        <Truck :size="22" />

                        <div>
                            <h3>
                                Giao hàng
                            </h3>

                            <p>
                                Phí và thời gian dự kiến
                                được tính dựa trên địa chỉ
                                nhận hàng.
                            </p>
                        </div>
                    </article>

                    <article>
                        <RefreshCcw :size="22" />

                        <div>
                            <h3>
                                Đổi trả
                            </h3>

                            <p>
                                Yêu cầu đổi trả phải đáp ứng
                                điều kiện của sản phẩm và
                                chính sách của NexaCart.
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="related-section">
            <div class="related-section__heading">
                <div>
                    <p>Sản phẩm tương tự</p>

                    <h2>Có thể bạn cũng quan tâm</h2>
                </div>

                <RouterLink
                    to="/products"
                    class="related-section__link"
                >
                    Xem tất cả

                    <ArrowRight :size="17" />
                </RouterLink>
            </div>

            <div class="related-product-grid">
                <ProductCard
                    v-for="item in relatedProducts"
                    :key="item.id"
                    :product="item"
                    @add-to-cart="
                        handleRelatedAddToCart
                    "
                    @toggle-wishlist="
                        handleRelatedWishlist
                    "
                />
            </div>
        </section>
    </div>
</template>

<style scoped>
.product-detail-page {
    display: grid;
    gap: 44px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: 12px;
    white-space: nowrap;
}

.breadcrumb a {
    flex-shrink: 0;
}

.breadcrumb a:hover {
    color: var(--color-primary-700);
}

.breadcrumb span {
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-overview {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(420px, 0.9fr);
    gap: 52px;
    align-items: start;
}

.product-gallery {
    display: grid;
    grid-template-columns: 82px minmax(0, 1fr);
    gap: 16px;
}

.product-gallery__thumbnails {
    display: grid;
    align-content: start;
    gap: 12px;
}

.product-gallery__thumbnail {
    overflow: hidden;
    aspect-ratio: 1 / 1;
    padding: 0;
    background: var(--color-gray-100);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    transition:
        border-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.product-gallery__thumbnail:hover,
.product-gallery__thumbnail--active {
    border-color: var(--color-primary-500);
}

.product-gallery__thumbnail--active {
    box-shadow:
        0 0 0 3px rgb(113 56 214 / 11%);
}

.product-gallery__thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-gallery__main {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: var(--color-gray-100);
    border: 1px solid var(--color-border);
    border-radius: 20px;
}

.product-gallery__main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-gallery__badge {
    position: absolute;
    top: 18px;
    left: 18px;
    z-index: 1;
    padding: 6px 10px;
    color: var(--color-primary-800);
    background: rgb(243 239 252 / 94%);
    border-radius: var(--radius-pill);
    font-size: 11px;
    font-weight: 700;
}

.product-information {
    display: grid;
    align-content: start;
}

.product-information__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    color: var(--color-text-muted);
    font-size: 12px;
}

.product-information__meta a {
    color: var(--color-primary-700);
    font-weight: 600;
}

.product-information h1 {
    margin-top: 16px;
    font-size: clamp(32px, 4vw, 46px);
    line-height: 1.15;
    letter-spacing: -0.045em;
}

.product-rating {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;
    margin-top: 18px;
    color: var(--color-text-muted);
    font-size: 12px;
}

.product-rating__score {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #a15c08;
}

.product-information__description {
    margin-top: 22px;
    font-size: 15px;
    line-height: 1.75;
}

.product-price {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 26px;
}

.product-price__current {
    color: var(--color-primary-700);
    font-size: 30px;
    font-weight: 700;
}

.product-price__original {
    color: var(--color-text-muted);
    font-size: 15px;
    text-decoration: line-through;
}

.product-price__discount {
    padding: 5px 8px;
    color: var(--color-danger);
    background: #fff0f2;
    border-radius: var(--radius-pill);
    font-size: 11px;
    font-weight: 700;
}

.stock-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    margin-top: 18px;
    padding: 8px 11px;
    border-radius: var(--radius-pill);
    font-size: 12px;
    font-weight: 600;
}

.stock-status--available {
    color: var(--color-success);
    background: #edf9f4;
}

.stock-status--unavailable {
    color: var(--color-danger);
    background: #fff0f2;
}

.purchase-section {
    display: grid;
    gap: 20px;
    margin-top: 28px;
    padding-top: 26px;
    border-top: 1px solid var(--color-border);
}

.purchase-section__quantity,
.purchase-section__total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.purchase-section__quantity > span,
.purchase-section__total > span {
    color: var(--color-text-muted);
    font-size: 13px;
}

.purchase-section__total strong {
    color: var(--color-text-primary);
    font-size: 17px;
}

.purchase-section__actions {
    display: grid;
    grid-template-columns:
        minmax(0, 1.4fr)
        minmax(0, 0.8fr)
        48px;
    gap: 10px;
}

.purchase-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 48px;
    padding-inline: 18px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.purchase-button--primary {
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 1px solid var(--color-primary-700);
}

.purchase-button--primary:hover:not(:disabled) {
    background: var(--color-primary-800);
}

.purchase-button--secondary {
    color: var(--color-primary-800);
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-200);
}

.purchase-button:disabled {
    cursor: not-allowed;
    opacity: 0.48;
}

.wishlist-button {
    display: grid;
    place-items: center;
    min-height: 48px;
    padding: 0;
    color: var(--color-text-secondary);
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-md);
}

.wishlist-button:hover,
.wishlist-button--active {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-color: var(--color-primary-200);
}

.purchase-benefits {
    display: grid;
    gap: 1px;
    margin-top: 26px;
    overflow: hidden;
    background: var(--color-border);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.purchase-benefits article {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 12px;
    padding: 15px;
    background: var(--color-white);
}

.purchase-benefits article > svg {
    color: var(--color-primary-700);
}

.purchase-benefits strong {
    display: block;
    font-size: 12px;
}

.purchase-benefits span {
    display: block;
    margin-top: 4px;
    color: var(--color-text-muted);
    font-size: 11px;
    line-height: 1.5;
}

.seller-card {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        auto
        auto;
    align-items: center;
    gap: 42px;
    padding: 24px;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.seller-card__identity {
    display: flex;
    align-items: center;
    gap: 15px;
}

.seller-card__avatar {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 54px;
    height: 54px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 16px;
}

.seller-card__identity p {
    font-size: 11px;
}

.seller-card__identity h2 {
    margin-top: 4px;
    font-size: 16px;
}

.seller-card__rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    color: #a15c08;
    font-size: 12px;
    font-weight: 600;
}

.seller-card__stats {
    display: flex;
    gap: 32px;
    margin: 0;
}

.seller-card__stats div {
    display: grid;
    gap: 4px;
    text-align: center;
}

.seller-card__stats dt {
    font-size: 16px;
    font-weight: 700;
}

.seller-card__stats dd {
    margin: 0;
    color: var(--color-text-muted);
    font-size: 11px;
}

.seller-card__link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding-inline: 15px;
    color: var(--color-primary-800);
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-200);
    border-radius: var(--radius-md);
    font-size: 12px;
    font-weight: 600;
}

.product-content {
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.product-content__tabs {
    display: flex;
    align-items: stretch;
    gap: 8px;
    overflow-x: auto;
    padding: 0 24px;
    border-bottom: 1px solid var(--color-border);
}

.product-content__tabs button {
    position: relative;
    flex-shrink: 0;
    min-height: 58px;
    padding: 0 10px;
    color: var(--color-text-muted);
    background: transparent;
    border: 0;
    font-size: 13px;
    font-weight: 600;
}

.product-content__tabs button::after {
    position: absolute;
    right: 10px;
    bottom: -1px;
    left: 10px;
    height: 2px;
    background: var(--color-primary-700);
    content: '';
    opacity: 0;
}

.product-content__tabs
    .product-content__tab--active {
    color: var(--color-primary-700);
}

.product-content__tabs
    .product-content__tab--active::after {
    opacity: 1;
}

.product-content__body {
    padding: 32px;
}

.product-content__body h2 {
    margin-bottom: 20px;
    font-size: 22px;
}

.description-content {
    max-width: 820px;
}

.description-content p {
    margin-top: 14px;
    font-size: 14px;
    line-height: 1.8;
}

.specification-table {
    max-width: 760px;
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.specification-table div {
    display: grid;
    grid-template-columns: 240px 1fr;
    border-bottom: 1px solid var(--color-border);
}

.specification-table div:last-child {
    border-bottom: 0;
}

.specification-table dt,
.specification-table dd {
    margin: 0;
    padding: 14px 16px;
    font-size: 13px;
}

.specification-table dt {
    color: var(--color-text-secondary);
    background: var(--color-gray-50);
    font-weight: 600;
}

.shipping-content {
    display: grid;
    gap: 16px;
    max-width: 780px;
}

.shipping-content article {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    padding: 18px;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.shipping-content article > svg {
    color: var(--color-primary-700);
}

.shipping-content h3 {
    font-size: 14px;
}

.shipping-content p {
    margin-top: 5px;
    font-size: 13px;
    line-height: 1.65;
}

.related-section {
    display: grid;
    gap: 26px;
}

.related-section__heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
}

.related-section__heading p {
    margin-bottom: 8px;
    color: var(--color-primary-700);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.related-section__heading h2 {
    font-size: 27px;
}

.related-section__link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--color-primary-700);
    font-size: 13px;
    font-weight: 600;
}

.related-product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 1100px) {
    .product-overview {
        grid-template-columns:
            minmax(0, 1fr)
            minmax(360px, 0.85fr);
        gap: 32px;
    }

    .product-gallery {
        grid-template-columns: 68px minmax(0, 1fr);
    }

    .purchase-section__actions {
        grid-template-columns: 1fr 48px;
    }

    .purchase-button--secondary {
        grid-column: 1 / -1;
        grid-row: 2;
    }

    .related-product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 820px) {
    .product-overview {
        grid-template-columns: 1fr;
    }

    .seller-card {
        grid-template-columns: 1fr auto;
    }

    .seller-card__stats {
        grid-column: 1 / -1;
        grid-row: 2;
        justify-content: flex-start;
    }
}

@media (max-width: 620px) {
    .product-detail-page {
        gap: 32px;
    }

    .product-gallery {
        grid-template-columns: 1fr;
    }

    .product-gallery__thumbnails {
        grid-row: 2;
        grid-template-columns: repeat(4, 1fr);
    }

    .product-information h1 {
        font-size: 32px;
    }

    .product-price__current {
        font-size: 26px;
    }

    .purchase-section__actions {
        grid-template-columns: 1fr 46px;
    }

    .seller-card {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .seller-card__stats {
        grid-column: auto;
        grid-row: auto;
    }

    .seller-card__link {
        justify-content: center;
    }

    .product-content__body {
        padding: 24px 18px;
    }

    .specification-table div {
        grid-template-columns: 1fr;
    }

    .specification-table dd {
        border-top: 1px solid var(--color-border);
    }

    .related-section__heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .related-product-grid {
        grid-template-columns: 1fr;
    }
}
</style>
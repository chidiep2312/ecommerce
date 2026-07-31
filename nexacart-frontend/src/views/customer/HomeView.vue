<script setup>
import {
    ArrowRight,
    Headphones,
    PackageCheck,
    RefreshCcw,
    ShieldCheck,
    Smartphone,
    Shirt,
    Sofa,
    Watch,
} from '@lucide/vue'

import ProductCard from '@/components/customer/ProductCard.vue'
import { useCartStore } from '@/stores/cart'
const categories = [
    {
        name: 'Điện thoại',
        slug: 'dien-thoai',
        description: 'Thiết bị di động và phụ kiện',
        icon: Smartphone,
    },
    {
        name: 'Thời trang',
        slug: 'thoi-trang',
        description: 'Phong cách dành cho mọi ngày',
        icon: Shirt,
    },
    {
        name: 'Nhà cửa',
        slug: 'nha-cua',
        description: 'Không gian sống tiện nghi',
        icon: Sofa,
    },
    {
        name: 'Đồng hồ',
        slug: 'dong-ho',
        description: 'Phụ kiện hoàn thiện phong cách',
        icon: Watch,
    },
]
const cartStore = useCartStore()
const products = [
    {
        id: 1,
        name: 'Tai nghe không dây chống ồn chủ động',
        slug: 'tai-nghe-khong-day-chong-on',
        category: 'Thiết bị âm thanh',
        price: 1890000,
        originalPrice: 2290000,
        rating: 4.8,
        reviews: 128,
        badge: 'Bán chạy',
        image:
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 2,
        name: 'Đồng hồ tối giản dây kim loại',
        slug: 'dong-ho-toi-gian-day-kim-loai',
        category: 'Phụ kiện',
        price: 1290000,
        originalPrice: null,
        rating: 4.7,
        reviews: 86,
        badge: 'Hàng mới',
        image:
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 3,
        name: 'Giày thể thao phong cách đô thị',
        slug: 'giay-the-thao-phong-cach-do-thi',
        category: 'Thời trang',
        price: 1590000,
        originalPrice: 1890000,
        rating: 4.9,
        reviews: 214,
        badge: null,
        image:
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
    },
    {
        id: 4,
        name: 'Máy ảnh kỹ thuật số nhỏ gọn',
        slug: 'may-anh-ky-thuat-so-nho-gon',
        category: 'Điện tử',
        price: 8790000,
        originalPrice: 9290000,
        rating: 4.6,
        reviews: 47,
        badge: 'Ưu đãi',
        image:
            'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
    },
]

const services = [
    {
        title: 'Sản phẩm được kiểm duyệt',
        description:
            'Thông tin sản phẩm rõ ràng và minh bạch.',
        icon: ShieldCheck,
    },
    {
        title: 'Giao hàng theo dõi được',
        description:
            'Cập nhật trạng thái đơn hàng thuận tiện.',
        icon: PackageCheck,
    },
    {
        title: 'Hỗ trợ khách hàng',
        description:
            'Tiếp nhận và xử lý yêu cầu nhanh chóng.',
        icon: Headphones,
    },
    {
        title: 'Đổi trả rõ ràng',
        description:
            'Chính sách minh bạch theo từng sản phẩm.',
        icon: RefreshCcw,
    },
]

function handleAddToCart(product) {
    cartStore.addItem(product, 1)
}
function handleToggleWishlist(product) {
    console.log('Toggle wishlist:', product)
}
</script>

<template>
    <div class="home-page">
        <section class="hero-section">
            <div class="hero-section__content">
                <p class="hero-section__eyebrow">
                    Bộ sưu tập mới
                </p>

                <h1 class="hero-section__title">
                    Những lựa chọn phù hợp cho nhịp sống
                    hiện đại.
                </h1>

                <p class="hero-section__description">
                    Khám phá sản phẩm từ nhiều nhà bán
                    hàng với thông tin rõ ràng, trải
                    nghiệm mua sắm nhất quán và thuận
                    tiện.
                </p>

                <div class="hero-section__actions">
                    <RouterLink
                        to="/products"
                        class="hero-section__primary"
                    >
                        Khám phá sản phẩm

                        <ArrowRight :size="18" />
                    </RouterLink>

                    <RouterLink
                        :to="{
                            name: 'products',
                            query: {
                                sort: 'newest',
                            },
                        }"
                        class="hero-section__secondary"
                    >
                        Xem hàng mới
                    </RouterLink>
                </div>

                <dl class="hero-section__stats">
                    <div>
                        <dt>1.200+</dt>
                        <dd>Sản phẩm đang bán</dd>
                    </div>

                    <div>
                        <dt>80+</dt>
                        <dd>Nhà bán hàng</dd>
                    </div>

                    <div>
                        <dt>4,8/5</dt>
                        <dd>Đánh giá trung bình</dd>
                    </div>
                </dl>
            </div>

            <div class="hero-section__visual">
                <div class="hero-product hero-product--main">
                    <img
                        src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1000&q=85"
                        alt="Giày thể thao màu đỏ"
                    />
                </div>

                <div class="hero-product hero-product--small">
                    <img
                        src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=85"
                        alt="Đồng hồ đeo tay"
                    />
                </div>

                <div class="hero-section__note">
                    <span>
                        Lựa chọn nổi bật
                    </span>

                    <strong>
                        Bộ sưu tập đô thị
                    </strong>
                </div>
            </div>
        </section>

        <section class="home-section">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow">
                        Khám phá
                    </p>

                    <h2>Danh mục nổi bật</h2>
                </div>

                <RouterLink
                    to="/products"
                    class="section-heading__link"
                >
                    Xem tất cả

                    <ArrowRight :size="17" />
                </RouterLink>
            </div>

            <div class="category-grid">
                <RouterLink
                    v-for="category in categories"
                    :key="category.slug"
                    :to="{
                        name: 'products',
                        query: {
                            category: category.slug,
                        },
                    }"
                    class="category-card"
                >
                    <span class="category-card__icon">
                        <component
                            :is="category.icon"
                            :size="24"
                        />
                    </span>

                    <span class="category-card__content">
                        <strong>
                            {{ category.name }}
                        </strong>

                        <span>
                            {{ category.description }}
                        </span>
                    </span>

                    <ArrowRight
                        :size="18"
                        class="category-card__arrow"
                    />
                </RouterLink>
            </div>
        </section>

        <section class="home-section">
            <div class="section-heading">
                <div>
                    <p class="section-heading__eyebrow">
                        Được quan tâm
                    </p>

                    <h2>Sản phẩm nổi bật</h2>
                </div>

                <RouterLink
                    to="/products"
                    class="section-heading__link"
                >
                    Xem tất cả

                    <ArrowRight :size="17" />
                </RouterLink>
            </div>

            <div class="product-grid">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    @add-to-cart="handleAddToCart"
                    @toggle-wishlist="
                        handleToggleWishlist
                    "
                />
            </div>
        </section>

        <section class="promotion-section">
            <div class="promotion-section__content">
                <p class="promotion-section__eyebrow">
                    Ưu đãi trong tuần
                </p>

                <h2>
                    Tối ưu không gian sống với những
                    sản phẩm được chọn lọc.
                </h2>

                <p>
                    Khám phá bộ sưu tập đồ dùng gia đình
                    với mức giá ưu đãi trong thời gian
                    giới hạn.
                </p>

                <RouterLink
                    :to="{
                        name: 'products',
                        query: {
                            promotion: 1,
                        },
                    }"
                    class="promotion-section__link"
                >
                    Xem ưu đãi

                    <ArrowRight :size="18" />
                </RouterLink>
            </div>

            <div class="promotion-section__image">
                <img
                    src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1000&q=85"
                    alt="Không gian phòng khách hiện đại"
                />
            </div>
        </section>

        <section class="service-section">
            <article
                v-for="service in services"
                :key="service.title"
                class="service-item"
            >
                <span class="service-item__icon">
                    <component
                        :is="service.icon"
                        :size="22"
                    />
                </span>

                <div>
                    <h3>
                        {{ service.title }}
                    </h3>

                    <p>
                        {{ service.description }}
                    </p>
                </div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.home-page {
    display: grid;
    gap: 76px;
}

.hero-section {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(420px, 0.9fr);
    gap: 72px;
    align-items: center;
    min-height: 560px;
    padding: 64px;
    overflow: hidden;
    background:
        linear-gradient(
            135deg,
            #f3effc 0%,
            #faf9fc 55%,
            #f0ebf8 100%
        );
    border: 1px solid var(--color-border);
    border-radius: 24px;
}

.hero-section__content {
    max-width: 650px;
}

.hero-section__eyebrow,
.section-heading__eyebrow,
.promotion-section__eyebrow {
    margin-bottom: 14px;
    color: var(--color-primary-700);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.hero-section__title {
    font-size: clamp(42px, 5vw, 64px);
    line-height: 1.04;
    letter-spacing: -0.055em;
}

.hero-section__description {
    max-width: 600px;
    margin-top: 24px;
    font-size: 16px;
    line-height: 1.8;
}

.hero-section__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
}

.hero-section__primary,
.hero-section__secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 46px;
    padding-inline: 20px;
    border-radius: var(--radius-md);
    font-size: 14px;
    font-weight: 600;
}

.hero-section__primary {
    color: var(--color-white);
    background: var(--color-primary-700);
}

.hero-section__primary:hover {
    background: var(--color-primary-800);
}

.hero-section__secondary {
    color: var(--color-text-primary);
    background: rgb(255 255 255 / 76%);
    border: 1px solid var(--color-border-strong);
}

.hero-section__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 36px;
    margin: 42px 0 0;
}

.hero-section__stats div {
    display: grid;
    gap: 4px;
}

.hero-section__stats dt {
    color: var(--color-text-primary);
    font-size: 19px;
    font-weight: 700;
}

.hero-section__stats dd {
    margin: 0;
    color: var(--color-text-muted);
    font-size: 12px;
}

.hero-section__visual {
    position: relative;
    min-height: 440px;
}

.hero-product {
    position: absolute;
    overflow: hidden;
    background: var(--color-gray-100);
    border: 7px solid rgb(255 255 255 / 85%);
    box-shadow: var(--shadow-md);
}

.hero-product img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-product--main {
    inset: 16px 36px 36px 0;
    border-radius: 120px 24px 80px 24px;
}

.hero-product--small {
    right: 0;
    bottom: 8px;
    width: 160px;
    height: 190px;
    border-radius: 24px;
}

.hero-section__note {
    position: absolute;
    top: 0;
    right: 18px;
    display: grid;
    gap: 4px;
    width: 180px;
    padding: 16px;
    background: rgb(255 255 255 / 92%);
    border: 1px solid rgb(255 255 255 / 75%);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.hero-section__note span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.hero-section__note strong {
    font-size: 13px;
}

.home-section {
    display: grid;
    gap: 28px;
}

.section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
}

.section-heading h2 {
    font-size: 28px;
}

.section-heading__link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--color-primary-700);
    font-size: 13px;
    font-weight: 600;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.category-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 14px;
    min-height: 112px;
    padding: 20px;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    transition:
        border-color var(--transition-base),
        box-shadow var(--transition-base),
        transform var(--transition-base);
}

.category-card:hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-sm);
    transform: translateY(-2px);
}

.category-card__icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 14px;
}

.category-card__content {
    display: grid;
    gap: 5px;
}

.category-card__content strong {
    font-size: 14px;
}

.category-card__content span {
    color: var(--color-text-muted);
    font-size: 12px;
    line-height: 1.5;
}

.category-card__arrow {
    color: var(--color-text-muted);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.promotion-section {
    display: grid;
    grid-template-columns:
        minmax(0, 0.9fr)
        minmax(420px, 1.1fr);
    overflow: hidden;
    min-height: 390px;
    color: var(--color-white);
    background: #2b1c3d;
    border-radius: 22px;
}

.promotion-section__content {
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    justify-content: center;
    padding: 54px;
}

.promotion-section__eyebrow {
    color: var(--color-primary-200);
}

.promotion-section h2 {
    max-width: 560px;
    color: var(--color-white);
    font-size: 36px;
    line-height: 1.2;
}

.promotion-section p {
    max-width: 520px;
    margin-top: 18px;
    color: rgb(255 255 255 / 68%);
    font-size: 15px;
}

.promotion-section__link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 26px;
    padding: 12px 18px;
    color: var(--color-primary-900);
    background: var(--color-white);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
}

.promotion-section__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-section {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    overflow: hidden;
    background: var(--color-border);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.service-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    padding: 24px;
    background: var(--color-white);
}

.service-item__icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: var(--radius-md);
}

.service-item h3 {
    font-size: 14px;
}

.service-item p {
    margin-top: 5px;
    font-size: 12px;
    line-height: 1.55;
}

@media (max-width: 1180px) {
    .hero-section {
        grid-template-columns: 1fr 420px;
        gap: 42px;
        padding: 48px;
    }

    .category-grid,
    .product-grid,
    .service-section {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 860px) {
    .home-page {
        gap: 56px;
    }

    .hero-section {
        grid-template-columns: 1fr;
        min-height: auto;
        padding: 36px;
    }

    .hero-section__visual {
        min-height: 380px;
    }

    .promotion-section {
        grid-template-columns: 1fr;
    }

    .promotion-section__content {
        padding: 40px;
    }

    .promotion-section__image {
        height: 320px;
    }
}

@media (max-width: 620px) {
    .hero-section {
        padding: 28px 22px;
        border-radius: 18px;
    }

    .hero-section__title {
        font-size: 38px;
    }

    .hero-section__stats {
        gap: 20px;
    }

    .hero-section__visual {
        min-height: 300px;
    }

    .hero-product--main {
        right: 10px;
    }

    .hero-product--small,
    .hero-section__note {
        display: none;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .category-grid,
    .product-grid,
    .service-section {
        grid-template-columns: 1fr;
    }

    .promotion-section h2 {
        font-size: 29px;
    }

    .promotion-section__content {
        padding: 32px 24px;
    }

    .promotion-section__image {
        height: 240px;
    }
}
</style>
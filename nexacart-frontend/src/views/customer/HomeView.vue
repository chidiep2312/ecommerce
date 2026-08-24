<script setup>
import {
    ArrowRight,
    CheckCircle2,
    Headphones,
    Heart,
    PackageCheck,
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

import ProductCard
    from '@/components/customer/ProductCard.vue'

import {
    getProducts,
} from '@/api/products'

import {
    addToWishlist,
    removeFromWishlist,
} from '@/api/wishlist'

import {
    useCartStore,
} from '@/stores/cart'

import {
    useAuthStore,
} from '@/stores/auth'

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

    products.value.forEach(
        product => {
            const category =
                product.category

            if (
                category?.id &&
                !map.has(category.id)
            ) {
                map.set(
                    category.id,
                    category,
                )
            }
        },
    )

    return [
        ...map.values(),
    ].slice(0, 4)
})

const featuredProduct =
    computed(() => {
        return (
            products.value[0] ??
            null
        )
    })

const secondaryProducts =
    computed(() => {
        return products.value
            .slice(1, 5)
    })

const totalProducts =
    computed(() => {
        return Number(
            productMeta.value
                ?.total ??
            products.value.length,
        )
    })

const loadedReviewCount =
    computed(() => {
        return products.value.reduce(
            (
                total,
                product,
            ) => {
                return (
                    total +
                    Number(
                        product
                            .reviews_count ??
                        0,
                    )
                )
            },
            0,
        )
    })

const averageRating =
    computed(() => {
        const ratings =
            products.value
                .map(
                    product => {
                        return Number(
                            product
                                .average_rating ??
                            0,
                        )
                    },
                )
                .filter(
                    rating => {
                        return rating > 0
                    },
                )

        if (!ratings.length) {
            return '0.0'
        }

        const total =
            ratings.reduce(
                (
                    sum,
                    rating,
                ) => {
                    return (
                        sum +
                        rating
                    )
                },
                0,
            )

        return (
            total /
            ratings.length
        ).toFixed(1)
    })

const featuredReviews =
    computed(() => {
        return products.value
            .flatMap(
                product => {
                    const reviews =
                        Array.isArray(
                            product.reviews,
                        )
                            ? product.reviews
                            : []

                    return reviews.map(
                        review => {
                            return {
                                ...review,

                                product_name:
                                    product.name,

                                product_slug:
                                    product.slug,
                            }
                        },
                    )
                },
            )
            .filter(
                review => {
                    return Boolean(
                        review.comment,
                    )
                },
            )
            .slice(0, 3)
    })

const benefits = [
    {
        title:
            'Nguồn hàng đa dạng',

        description:
            'Khám phá nhiều sản phẩm từ nhiều nhà bán hàng khác nhau.',

        icon:
            Store,
    },

    {
        title:
            'Giá minh bạch',

        description:
            'Giá sản phẩm và chương trình giảm giá được hiển thị rõ ràng.',

        icon:
            Tags,
    },

    {
        title:
            'Theo dõi đơn hàng',

        description:
            'Theo dõi quá trình xác nhận, vận chuyển và hoàn tất đơn hàng.',

        icon:
            Truck,
    },

    {
        title:
            'Hỗ trợ thuận tiện',

        description:
            'Quản lý tài khoản, wishlist, địa chỉ và đơn hàng trong một nơi.',

        icon:
            Headphones,
    },
]

async function fetchProducts() {
    isLoadingProducts.value =
        true

    errorMessage.value = ''

    try {
        const response =
            await getProducts({
                per_page: 8,
                sort: 'newest',
            })
            console.log(response)

        products.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []

        productMeta.value =
            response.data?.meta ??
            null
    } catch (error) {
        console.error(
            'Không thể tải sản phẩm:',
            error,
        )

        products.value = []
        productMeta.value = null

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tải sản phẩm.'
    } finally {
        isLoadingProducts.value =
            false
    }
}

async function handleAddToCart(
    product,
) {
    if (
        !product ||
        cartLoadingId.value
    ) {
        return
    }

    cartLoadingId.value =
        product.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        await cartStore.addItem(
            product.id,
            1,
        )

        successMessage.value =
            `Đã thêm "${product.name}" vào giỏ hàng.`
    } catch (error) {
        errorMessage.value =
            error.response?.data
                ?.message ??
            error.message ??
            'Không thể thêm sản phẩm vào giỏ hàng.'
    } finally {
        cartLoadingId.value =
            null
    }
}

async function handleToggleWishlist(
    product,
) {
    if (!product) {
        return
    }

    if (!authStore.isAuthenticated) {
        router.push({
            name: 'login',

            query: {
                redirect:
                    route.fullPath,
            },
        })

        return
    }

    if (wishlistLoadingId.value) {
        return
    }

    wishlistLoadingId.value =
        product.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        if (
            product.is_wishlisted
        ) {
            await removeFromWishlist(
                product.id,
            )

            product.is_wishlisted =
                false

            successMessage.value =
                'Đã bỏ sản phẩm khỏi danh sách yêu thích.'
        } else {
            await addToWishlist(
                product.id,
            )

            product.is_wishlisted =
                true

            successMessage.value =
                'Đã thêm sản phẩm vào danh sách yêu thích.'
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể cập nhật danh sách yêu thích.'
    } finally {
        wishlistLoadingId.value =
            null
    }
}

function getImageUrl(product) {
    if (!product) {
        return ''
    }

    return (
        product.main_image?.url ??
        product.main_image?.path ??
        ''
    )
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

function formatReviewDate(value) {
    if (!value) {
        return ''
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return ''
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
    ).format(date)
}

onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <main class="home-page">
        <!-- MESSAGE -->

        <div
            v-if="errorMessage"
            class="home-alert home-alert--error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="successMessage"
            class="home-alert home-alert--success"
        >
            {{ successMessage }}
        </div>

        <!-- =====================
             HERO
        ====================== -->

        <section class="hero">
            <div class="hero__content">
                <div class="hero__badge">
                    <Sparkles
                        :size="14"
                    />

                    <span>
                        NỀN TẢNG MUA SẮM ĐA NHÀ BÁN
                    </span>
                </div>

                <h1>
                    Nguồn hàng đa dạng
                    <br>

                    cho

                    <span>
                        mọi lựa chọn
                    </span>
                </h1>

                <p class="hero__description">
                    NexaCart kết nối khách hàng với
                    nhiều nhà bán hàng, giúp bạn
                    khám phá sản phẩm, so sánh giá,
                    xem đánh giá và quản lý đơn hàng
                    trong cùng một nền tảng.
                </p>

                <div class="hero__features">
                    <article>
                        <span>
                            <ShieldCheck
                                :size="19"
                            />
                        </span>

                        <div>
                            <strong>
                                Mua sắm an toàn
                            </strong>

                            <small>
                                Thông tin rõ ràng
                            </small>
                        </div>
                    </article>

                    <article>
                        <span>
                            <Truck
                                :size="19"
                            />
                        </span>

                        <div>
                            <strong>
                                Theo dõi giao hàng
                            </strong>

                            <small>
                                Cập nhật trạng thái
                            </small>
                        </div>
                    </article>

                    <article>
                        <span>
                            <Tags
                                :size="19"
                            />
                        </span>

                        <div>
                            <strong>
                                Giá minh bạch
                            </strong>

                            <small>
                                Dễ dàng so sánh
                            </small>
                        </div>
                    </article>
                </div>

                <div class="hero__actions">
                    <RouterLink
                        :to="{
                            name: 'products',
                        }"
                        class="button button--primary"
                    >
                        Mua hàng ngay

                        <ArrowRight
                            :size="17"
                        />
                    </RouterLink>

                    <RouterLink
                        :to="{
                            name:
                                'customer-profile',
                        }"
                        class="button button--secondary"
                    >
                        Trở thành người bán
                    </RouterLink>
                </div>
            </div>

            <!-- HERO VISUAL -->

            <div class="hero__visual">
                <div class="hero-background" />

                <RouterLink
                    v-if="
                        featuredProduct
                    "
                    :to="{
                        name:
                            'product-detail',

                        params: {
                            slug:
                                featuredProduct
                                    .slug,
                        },
                    }"
                    class="hero-main-product"
                >
                    <img
                        v-if="
                            getImageUrl(
                                featuredProduct,
                            )
                        "
                        :src="
                            getImageUrl(
                                featuredProduct,
                            )
                        "
                        :alt="
                            featuredProduct
                                .name
                        "
                    >

                    <div
                        v-else
                        class="hero-image-placeholder"
                    >
                        <ShoppingBag
                            :size="70"
                        />
                    </div>

                    <div class="hero-main-product__label">
                        Nổi bật hôm nay
                    </div>

                    <div class="hero-main-product__info">
                        <span>
                            {{
                                featuredProduct
                                    .category
                                    ?.name ??
                                'Sản phẩm'
                            }}
                        </span>

                        <strong>
                            {{
                                featuredProduct
                                    .name
                            }}
                        </strong>

                        <b>
                            {{
                                formatPrice(
                                    getCurrentPrice(
                                        featuredProduct,
                                    ),
                                )
                            }}
                        </b>
                    </div>
                </RouterLink>

                <div
                    v-if="
                        secondaryProducts
                            .length
                    "
                    class="hero-side-products"
                >
                    <RouterLink
                        v-for="
                            product in
                            secondaryProducts
                                .slice(
                                    0,
                                    2,
                                )
                        "
                        :key="
                            product.id
                        "
                        :to="{
                            name:
                                'product-detail',

                            params: {
                                slug:
                                    product.slug,
                            },
                        }"
                        class="hero-side-product"
                    >
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
                            class="hero-side-product__empty"
                        >
                            <ShoppingBag
                                :size="23"
                            />
                        </div>

                        <div>
                            <strong>
                                {{
                                    product.name
                                }}
                            </strong>

                            <span>
                                {{
                                    formatPrice(
                                        getCurrentPrice(
                                            product,
                                        ),
                                    )
                                }}
                            </span>
                        </div>
                    </RouterLink>
                </div>

                <div class="delivery-card">
                    <div class="delivery-card__icon">
                        <Truck
                            :size="21"
                        />
                    </div>

                    <div>
                        <strong>
                            Giao hàng được theo dõi
                        </strong>

                        <span>
                            Kiểm tra trạng thái đơn hàng
                            ngay trong tài khoản
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================
             WHY NEXACART
        ====================== -->

        <section class="benefit-section">
            <header class="center-heading">
                <h2>
                    VÌ SAO CHỌN NEXACART?
                </h2>

                <span />
            </header>

            <div class="benefit-grid">
                <article
                    v-for="
                        benefit in
                        benefits
                    "
                    :key="
                        benefit.title
                    "
                >
                    <div class="benefit-grid__icon">
                        <component
                            :is="
                                benefit.icon
                            "
                            :size="21"
                        />
                    </div>

                    <div>
                        <strong>
                            {{
                                benefit.title
                            }}
                        </strong>

                        <p>
                            {{
                                benefit
                                    .description
                            }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <!-- =====================
             CATEGORIES
        ====================== -->

        <section class="category-section">
            <header class="center-heading">
                <h2>
                    Khám phá theo danh mục
                </h2>

                <p>
                    Chọn nhanh nhóm sản phẩm
                    phù hợp với nhu cầu của bạn
                </p>

                <span />
            </header>

            <div
                v-if="
                    categories.length
                "
                class="category-showcase"
            >
                <RouterLink
                    v-for="
                        category in
                        categories
                    "
                    :key="
                        category.id
                    "
                    :to="{
                        name:
                            'products',

                        query: {
                            category:
                                category.slug,
                        },
                    }"
                    class="business-card"
                >
                    <div class="business-card__visual">
                        <Store
                            :size="48"
                        />
                    </div>

                    <div class="business-card__content">
                        <div class="business-card__icon">
                            <ShoppingBag
                                :size="17"
                            />
                        </div>

                        <strong>
                            {{
                                category.name
                            }}
                        </strong>

                        <p>
                            Khám phá những sản phẩm
                            thuộc danh mục
                            {{
                                category.name
                            }}.
                        </p>
                    </div>
                </RouterLink>
            </div>
        </section>

        <!-- =====================
             PRODUCTS
        ====================== -->

        <section class="product-section">
            <header class="section-heading">
                <div>
                    <span>
                        SẢN PHẨM MỚI
                    </span>

                    <h2>
                        Khám phá sản phẩm
                        trên NexaCart
                    </h2>
                </div>

                <RouterLink
                    :to="{
                        name:
                            'products',
                    }"
                >
                    Xem tất cả

                    <ArrowRight
                        :size="16"
                    />
                </RouterLink>
            </header>

            <div
                v-if="
                    isLoadingProducts
                "
                class="product-loading"
            >
                <article
                    v-for="
                        item in 8
                    "
                    :key="item"
                    class="product-skeleton"
                >
                    <div
                        class="product-skeleton__image"
                    />

                    <div
                        class="product-skeleton__line"
                    />

                    <div
                        class="product-skeleton__line product-skeleton__line--short"
                    />
                </article>
            </div>

            <div
                v-else-if="
                    products.length ===
                    0
                "
                class="product-empty"
            >
                <ShoppingBag
                    :size="34"
                />

                <strong>
                    Chưa có sản phẩm
                </strong>
            </div>

            <div
                v-else
                class="product-grid"
            >
                <ProductCard
                    v-for="
                        product in
                        products
                    "
                    :key="
                        product.id
                    "
                    :product="
                        product
                    "
                    :wishlist-loading="
                        wishlistLoadingId ===
                        product.id
                    "
                    :cart-loading="
                        cartLoadingId ===
                        product.id
                    "
                    @add-to-cart="
                        handleAddToCart
                    "
                    @toggle-wishlist="
                        handleToggleWishlist
                    "
                />
            </div>
        </section>

        <!-- =====================
             STATS
        ====================== -->

        <section class="stats-section">
            <article>
                <ShoppingBag
                    :size="24"
                />

                <div>
                    <strong>
                        {{
                            totalProducts
                        }}+
                    </strong>

                    <span>
                        Sản phẩm trên hệ thống
                    </span>
                </div>
            </article>

            <article>
                <Star
                    :size="24"
                    fill="currentColor"
                />

                <div>
                    <strong>
                        {{
                            averageRating
                        }}/5
                    </strong>

                    <span>
                        Điểm đánh giá trung bình
                    </span>
                </div>
            </article>

            <article>
                <Heart
                    :size="24"
                />

                <div>
                    <strong>
                        {{
                            loadedReviewCount
                        }}+
                    </strong>

                    <span>
                        Lượt đánh giá
                    </span>
                </div>
            </article>

            <article>
                <Store
                    :size="24"
                />

                <div>
                    <strong>
                        {{
                            categories.length
                        }}+
                    </strong>

                    <span>
                        Danh mục đang hiển thị
                    </span>
                </div>
            </article>
        </section>

        <!-- =====================
             REVIEWS
        ====================== -->

        <section class="review-section">
            <header class="center-heading">
                <h2>
                    Khách hàng nói gì
                    về NexaCart
                </h2>

                <span />
            </header>

            <div
                v-if="
                    featuredReviews.length
                "
                class="review-grid"
            >
                <article
                    v-for="
                        review in
                        featuredReviews
                    "
                    :key="
                        review.id
                    "
                    class="review-card"
                >
                    <div class="review-card__quote">
                        “
                    </div>

                    <div class="review-card__stars">
                        <Star
                            v-for="
                                star in 5
                            "
                            :key="star"
                            :size="14"
                            :fill="
                                star <=
                                Number(
                                    review.rating,
                                )
                                    ? 'currentColor'
                                    : 'none'
                            "
                        />
                    </div>

                    <p>
                        {{
                            review.comment
                        }}
                    </p>

                    <footer>
                        <div class="review-card__avatar">
                            {{
                                review.user
                                    ?.name
                                    ?.charAt(0)
                                    ?.toUpperCase() ??
                                'U'
                            }}
                        </div>

                        <div>
                            <strong>
                                {{
                                    review.user
                                        ?.name ??
                                    'Khách hàng'
                                }}
                            </strong>

                            <span>
                                {{
                                    formatReviewDate(
                                        review
                                            .created_at,
                                    )
                                }}
                            </span>
                        </div>
                    </footer>
                </article>
            </div>

            <div
                v-else
                class="review-empty"
            >
                <Star
                    :size="30"
                />

                <strong>
                    Chưa có đánh giá nổi bật
                </strong>

                <span>
                    Đánh giá của người mua
                    sẽ xuất hiện tại đây.
                </span>
            </div>
        </section>

        <!-- =====================
             CTA
        ====================== -->

        <section class="bottom-cta">
            <div class="bottom-cta__content">
                <span>
                    NEXACART
                </span>

                <h2>
                    Bắt đầu trải nghiệm
                    mua sắm tiện lợi hơn
                    ngay hôm nay.
                </h2>

                <p>
                    Khám phá sản phẩm từ nhiều
                    nhà bán hàng, lưu wishlist
                    và quản lý đơn hàng trong
                    cùng một tài khoản.
                </p>

                <div class="bottom-cta__actions">
                    <RouterLink
                        :to="{
                            name:
                                'products',
                        }"
                        class="bottom-cta__primary"
                    >
                        Mua sắm ngay
                    </RouterLink>

                    <RouterLink
                        :to="{
                            name:
                                'customer-profile',
                        }"
                        class="bottom-cta__secondary"
                    >
                        Tìm hiểu thêm
                    </RouterLink>
                </div>
            </div>

            <div class="bottom-cta__visual">
                <ShoppingBag
                    :size="82"
                />

                <Store
                    :size="66"
                />

                <PackageCheck
                    :size="76"
                />
            </div>
        </section>
    </main>
</template>

<style scoped>
.home-page {
    --green-950: #063c25;
    --green-900: #07532f;
    --green-800: #08703d;
    --green-700: #0b8b49;
    --green-600: #10a457;
    --green-100: #e8f6ee;
    --green-50: #f2faf5;

    display: grid;
    gap: 62px;

    width: min(
        calc(100% - 40px),
        1360px
    );

    margin: 0 auto;
    padding: 30px 0 74px;

    color: #17221c;

    font-family:
        Roboto,
        Arial,
        sans-serif;
}

/* ========================================
   ALERT
======================================== */

.home-alert {
    padding: 12px 15px;

    border: 1px solid;

    font-size: 12px;
}

.home-alert--error {
    border-color: #dfb2b2;

    color: #963838;
    background: #fff3f3;
}

.home-alert--success {
    border-color: #9fc8ad;

    color: #216640;
    background: #eff9f2;
}

/* ========================================
   HERO
======================================== */

.hero {
    display: grid;

    grid-template-columns:
        minmax(0, 0.94fr)
        minmax(500px, 1.06fr);

    min-height: 590px;

    align-items: center;

    gap: 30px;

    padding:
        30px 0
        30px 20px;
}

.hero__content {
    min-width: 0;

    padding:
        28px
        18px
        28px
        4px;
}

.hero__badge {
    display: inline-flex;

    min-height: 31px;

    align-items: center;

    gap: 7px;

    padding: 0 12px;

    border-radius: 999px;

    color:
        var(--green-800);

    background:
        var(--green-100);

    font-size: 9px;
    font-weight: 800;

    letter-spacing: 0.08em;
}

.hero h1 {
    max-width: 670px;

    margin: 25px 0 0;

    color: #161b18;

    font-size:
        clamp(
            48px,
            5vw,
            68px
        );

    font-weight: 800;

    letter-spacing:
        -0.045em;

    line-height: 1.08;
}

.hero h1 span {
    color:
        var(--green-700);
}

.hero__description {
    max-width: 590px;

    margin: 21px 0 0;

    color: #67736c;

    font-size: 14px;

    line-height: 1.75;
}

/* FEATURES */

.hero__features {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 18px;

    margin-top: 31px;
}

.hero__features article {
    display: flex;

    align-items: center;

    gap: 10px;
}

.hero__features article > span {
    display: grid;

    width: 43px;
    height: 43px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 50%;

    color:
        var(--green-700);

    background:
        var(--green-50);
}

.hero__features strong,
.hero__features small {
    display: block;
}

.hero__features strong {
    color: #26352c;

    font-size: 10px;
}

.hero__features small {
    margin-top: 4px;

    color: #8d978f;

    font-size: 8px;
}

/* ACTION */

.hero__actions {
    display: flex;

    flex-wrap: wrap;

    gap: 12px;

    margin-top: 35px;
}

.button {
    display: inline-flex;

    min-height: 48px;

    align-items: center;
    justify-content: center;

    gap: 8px;

    padding: 0 22px;

    border-radius: 7px;

    font-size: 11px;
    font-weight: 700;

    text-decoration: none;

    transition:
        transform 160ms ease,
        background-color 160ms ease;
}

.button:hover {
    transform:
        translateY(-1px);
}

.button--primary {
    color: #ffffff;

    background:
        var(--green-700);
}

.button--primary:hover {
    background:
        var(--green-800);
}

.button--secondary {
    border:
        1px solid
        #78c198;

    color:
        var(--green-700);

    background: #ffffff;
}

/* ========================================
   HERO VISUAL
======================================== */

.hero__visual {
    position: relative;

    min-height: 550px;
}

.hero-background {
    position: absolute;

    inset:
        55px
        24px
        26px
        78px;

    border-radius:
        80px
        18px
        80px
        18px;

    background:
        linear-gradient(
            145deg,
            #e2f3e8,
            #f5faf7
        );
}

/* MAIN PRODUCT */

.hero-main-product {
    position: absolute;

    top: 10px;
    right: 86px;
    bottom: 35px;
    left: 50px;

    overflow: hidden;

    border-radius: 28px;

    color: inherit;

    background: #edf5f0;

    box-shadow:
        0 28px 70px
        rgb(25 68 41 / 16%);

    text-decoration: none;
}

.hero-main-product > img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition:
        transform 320ms ease;
}

.hero-main-product:hover > img {
    transform:
        scale(1.025);
}

.hero-image-placeholder {
    display: grid;

    width: 100%;
    height: 100%;

    place-items: center;

    color: #9aada1;
}

.hero-main-product__label {
    position: absolute;

    top: 17px;
    left: 17px;

    padding: 7px 11px;

    border-radius: 999px;

    color:
        var(--green-800);

    background:
        rgb(255 255 255 / 91%);

    font-size: 9px;
    font-weight: 700;
}

.hero-main-product__info {
    position: absolute;

    right: 20px;
    bottom: 20px;
    left: 20px;

    display: grid;

    gap: 5px;

    padding: 17px;

    border-radius: 13px;

    background:
        rgb(255 255 255 / 94%);

    backdrop-filter:
        blur(12px);
}

.hero-main-product__info span {
    color:
        var(--green-700);

    font-size: 9px;
    font-weight: 700;
}

.hero-main-product__info strong {
    overflow: hidden;

    color: #243229;

    font-size: 13px;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.hero-main-product__info b {
    color:
        var(--green-700);

    font-size: 16px;
}

/* SIDE PRODUCTS */

.hero-side-products {
    position: absolute;

    right: 0;
    bottom: 28px;

    z-index: 4;

    display: grid;

    gap: 8px;

    width: 215px;
}

.hero-side-product {
    display: grid;

    grid-template-columns:
        68px
        minmax(0, 1fr);

    gap: 9px;

    align-items: center;

    padding: 7px;

    border:
        1px solid
        #dce7e0;

    border-radius: 12px;

    color: inherit;

    background: #ffffff;

    box-shadow:
        0 10px 26px
        rgb(30 66 42 / 10%);

    text-decoration: none;
}

.hero-side-product img,
.hero-side-product__empty {
    width: 68px;
    height: 68px;

    border-radius: 8px;
}

.hero-side-product img {
    object-fit: cover;
}

.hero-side-product__empty {
    display: grid;

    place-items: center;

    color: #97a69d;

    background: #edf4ef;
}

.hero-side-product > div:last-child {
    min-width: 0;
}

.hero-side-product strong {
    display: -webkit-box;

    overflow: hidden;

    color: #29372e;

    font-size: 9px;

    line-height: 1.4;

    -webkit-box-orient:
        vertical;

    -webkit-line-clamp: 2;
}

.hero-side-product span {
    display: block;

    margin-top: 5px;

    color:
        var(--green-700);

    font-size: 10px;
    font-weight: 700;
}

/* DELIVERY */

.delivery-card {
    position: absolute;

    top: 68px;
    right: 5px;

    z-index: 5;

    display: grid;

    grid-template-columns:
        auto
        minmax(0, 1fr);

    gap: 10px;

    width: 184px;

    padding: 15px;

    border-radius: 13px;

    background: #ffffff;

    box-shadow:
        0 14px 34px
        rgb(25 66 41 / 13%);
}

.delivery-card__icon {
    display: grid;

    width: 38px;
    height: 38px;

    place-items: center;

    border-radius: 8px;

    color:
        var(--green-700);

    background:
        var(--green-100);
}

.delivery-card strong,
.delivery-card span {
    display: block;
}

.delivery-card strong {
    color: #26352c;

    font-size: 10px;

    line-height: 1.4;
}

.delivery-card span {
    margin-top: 4px;

    color: #87928a;

    font-size: 8px;

    line-height: 1.45;
}

/* ========================================
   CENTER HEADING
======================================== */

.center-heading {
    text-align: center;
}

.center-heading h2 {
    margin: 0;

    color: #202923;

    font-size: 20px;

    font-weight: 700;
}

.center-heading > p {
    margin: 8px 0 0;

    color: #818b84;

    font-size: 11px;
}

.center-heading > span {
    display: block;

    width: 38px;
    height: 2px;

    margin: 12px auto 0;

    background:
        var(--green-700);
}

/* ========================================
   BENEFITS
======================================== */

.benefit-section {
    padding:
        30px
        36px
        33px;

    border-radius: 18px;

    background:
        linear-gradient(
            90deg,
            #f6fbf8,
            #edf8f1,
            #f7fbf8
        );
}

.benefit-grid {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 24px;

    margin-top: 30px;
}

.benefit-grid article {
    display: grid;

    grid-template-columns:
        auto
        minmax(0, 1fr);

    gap: 12px;
}

.benefit-grid__icon {
    display: grid;

    width: 42px;
    height: 42px;

    place-items: center;

    border-radius: 50%;

    color:
        var(--green-700);

    background: #e4f5eb;
}

.benefit-grid strong {
    color: #29372e;

    font-size: 10px;
}

.benefit-grid p {
    margin: 7px 0 0;

    color: #7f8a82;

    font-size: 9px;

    line-height: 1.55;
}

/* ========================================
   CATEGORY
======================================== */

.category-section {
    display: grid;

    gap: 29px;
}

.category-showcase {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 16px;
}

.business-card {
    overflow: hidden;

    border:
        1px solid
        #e0e8e3;

    border-radius: 11px;

    color: inherit;

    background: #ffffff;

    box-shadow:
        0 8px 24px
        rgb(41 70 51 / 6%);

    text-decoration: none;

    transition:
        transform 180ms ease,
        box-shadow 180ms ease;
}

.business-card:hover {
    transform:
        translateY(-4px);

    box-shadow:
        0 16px 36px
        rgb(34 72 47 / 10%);
}

.business-card__visual {
    display: grid;

    height: 178px;

    place-items: center;

    color: #79aa8d;

    background:
        linear-gradient(
            135deg,
            #dcefe3,
            #f1f8f4
        );
}

.business-card__content {
    position: relative;

    min-height: 105px;

    padding:
        19px
        16px
        16px;
}

.business-card__icon {
    position: absolute;

    top: -20px;
    left: 16px;

    display: grid;

    width: 38px;
    height: 38px;

    place-items: center;

    border:
        1px solid
        #dce7e0;

    border-radius: 8px;

    color:
        var(--green-700);

    background: #ffffff;
}

.business-card strong {
    display: block;

    margin-top: 7px;

    color: #26352c;

    font-size: 11px;
}

.business-card p {
    margin: 7px 0 0;

    color: #7e8982;

    font-size: 9px;

    line-height: 1.55;
}

/* ========================================
   SECTION HEADING
======================================== */

.product-section {
    display: grid;

    gap: 23px;
}

.section-heading {
    display: flex;

    align-items: flex-end;
    justify-content:
        space-between;

    gap: 20px;
}

.section-heading span {
    color:
        var(--green-700);

    font-size: 9px;
    font-weight: 800;

    letter-spacing: 0.12em;
}

.section-heading h2 {
    margin: 6px 0 0;

    color: #222d26;

    font-size: 24px;

    letter-spacing:
        -0.02em;
}

.section-heading a {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    color:
        var(--green-700);

    font-size: 10px;
    font-weight: 700;

    text-decoration: none;
}

/* ========================================
   PRODUCT
======================================== */

.product-grid,
.product-loading {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 16px;
}

.product-skeleton {
    padding: 10px;

    border:
        1px solid
        #e0e7e2;

    border-radius: 10px;

    background: #ffffff;
}

.product-skeleton__image {
    height: 245px;

    border-radius: 8px;

    background: #edf3ef;

    animation:
        skeleton-pulse
        1.3s
        ease-in-out
        infinite;
}

.product-skeleton__line {
    width: 88%;
    height: 11px;

    margin-top: 15px;

    background: #edf1ee;
}

.product-skeleton__line--short {
    width: 56%;
}

@keyframes skeleton-pulse {
    50% {
        opacity: 0.55;
    }
}

.product-empty {
    display: grid;

    min-height: 240px;

    place-items: center;
    align-content: center;

    gap: 8px;

    border:
        1px solid
        #e0e7e2;

    color: #87938b;

    background: #ffffff;
}

.product-empty strong {
    color: #39483f;

    font-size: 12px;
}

/* ========================================
   STATS
======================================== */

.stats-section {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    padding:
        0
        52px;

    background:
        linear-gradient(
            90deg,
            #034c2c,
            #07683a
        );
}

.stats-section article {
    display: grid;

    grid-template-columns:
        auto
        minmax(0, 1fr);

    align-items: center;

    gap: 12px;

    padding:
        29px
        24px;

    color: #ffffff;
}

.stats-section article > svg {
    color: #aee0bf;
}

.stats-section strong,
.stats-section span {
    display: block;
}

.stats-section strong {
    font-size: 23px;
}

.stats-section span {
    margin-top: 4px;

    color:
        rgb(255 255 255 / 74%);

    font-size: 9px;
}

/* ========================================
   REVIEWS
======================================== */

.review-section {
    display: grid;

    gap: 29px;
}

.review-grid {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 18px;
}

.review-card {
    position: relative;

    min-height: 220px;

    padding: 24px;

    border:
        1px solid
        #e3e8e5;

    border-radius: 12px;

    background: #ffffff;

    box-shadow:
        0 12px 30px
        rgb(45 73 55 / 7%);
}

.review-card__quote {
    color:
        var(--green-700);

    font-family:
        Georgia,
        serif;

    font-size: 40px;

    line-height: 1;
}

.review-card__stars {
    display: flex;

    gap: 2px;

    margin-top: 5px;

    color: #dda020;
}

.review-card > p {
    min-height: 72px;

    margin:
        13px
        0
        18px;

    color: #5d6961;

    font-size: 11px;

    line-height: 1.7;
}

.review-card footer {
    display: flex;

    align-items: center;

    gap: 10px;
}

.review-card__avatar {
    display: grid;

    width: 38px;
    height: 38px;

    flex: 0 0 auto;

    place-items: center;

    border-radius: 50%;

    color: #ffffff;

    background:
        var(--green-700);

    font-size: 12px;
    font-weight: 700;
}

.review-card footer strong,
.review-card footer span {
    display: block;
}

.review-card footer strong {
    color: #28372e;

    font-size: 10px;
}

.review-card footer span {
    margin-top: 3px;

    color: #8a948d;

    font-size: 8px;
}

.review-empty {
    display: grid;

    min-height: 200px;

    place-items: center;
    align-content: center;

    gap: 7px;

    border:
        1px solid
        #e0e7e2;

    color: #829087;
}

.review-empty strong {
    color: #45544a;

    font-size: 11px;
}

.review-empty span {
    font-size: 9px;
}

/* ========================================
   CTA
======================================== */

.bottom-cta {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        420px;

    min-height: 250px;

    overflow: hidden;

    border-radius: 16px;

    color: #ffffff;

    background:
        linear-gradient(
            115deg,
            #098f4d,
            #06723f
        );
}

.bottom-cta__content {
    padding:
        37px
        46px;
}

.bottom-cta__content > span {
    color: #b8e6c7;

    font-size: 9px;
    font-weight: 800;

    letter-spacing: 0.14em;
}

.bottom-cta h2 {
    max-width: 620px;

    margin: 10px 0 0;

    font-size: 30px;

    line-height: 1.2;
}

.bottom-cta p {
    max-width: 600px;

    margin: 12px 0 0;

    color:
        rgb(255 255 255 / 76%);

    font-size: 11px;

    line-height: 1.6;
}

.bottom-cta__actions {
    display: flex;

    gap: 10px;

    margin-top: 22px;
}

.bottom-cta__primary,
.bottom-cta__secondary {
    display: inline-flex;

    min-height: 40px;

    align-items: center;
    justify-content: center;

    padding:
        0
        16px;

    border-radius: 6px;

    font-size: 10px;
    font-weight: 700;

    text-decoration: none;
}

.bottom-cta__primary {
    color:
        var(--green-800);

    background: #ffffff;
}

.bottom-cta__secondary {
    border:
        1px solid
        rgb(255 255 255 / 55%);

    color: #ffffff;
}

.bottom-cta__visual {
    display: flex;

    align-items: flex-end;
    justify-content: center;

    gap: 18px;

    padding-bottom: 42px;

    color: #d7f0df;

    background:
        radial-gradient(
            circle
            at 50% 80%,
            rgb(255 255 255 / 17%),
            transparent 62%
        );
}

/* ========================================
   RESPONSIVE
======================================== */

@media (max-width: 1100px) {
    .hero {
        grid-template-columns:
            1fr;
    }

    .hero__visual {
        min-height: 510px;
    }

    .benefit-grid,
    .category-showcase,
    .stats-section {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .product-grid,
    .product-loading {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
    }

    .bottom-cta {
        grid-template-columns:
            1fr;
    }

    .bottom-cta__visual {
        min-height: 180px;
    }
}

@media (max-width: 820px) {
    .product-grid,
    .product-loading {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .review-grid {
        grid-template-columns:
            1fr;
    }
}

@media (max-width: 650px) {
    .home-page {
        width:
            calc(100% - 28px);

        gap: 48px;
    }

    .hero {
        padding: 10px 0;
    }

    .hero h1 {
        font-size: 42px;
    }

    .hero__features {
        grid-template-columns:
            1fr;
    }

    .hero__actions {
        width: 100%;

        flex-direction:
            column;
    }

    .button {
        width: 100%;

        box-sizing:
            border-box;
    }

    .hero__visual {
        min-height: 410px;
    }

    .hero-main-product {
        inset:
            10px
            24px
            20px;
    }

    .hero-side-products,
    .delivery-card {
        display: none;
    }

    .benefit-section {
        padding:
            26px
            20px;
    }

    .benefit-grid,
    .category-showcase,
    .product-grid,
    .product-loading,
    .stats-section {
        grid-template-columns:
            1fr;
    }

    .section-heading {
        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .stats-section {
        padding: 0;
    }

    .bottom-cta__content {
        padding:
            30px
            22px;
    }

    .bottom-cta h2 {
        font-size: 25px;
    }

    .bottom-cta__actions {
        flex-direction:
            column;
    }

    .bottom-cta__primary,
    .bottom-cta__secondary {
        width: 100%;

        box-sizing:
            border-box;
    }
}
</style>
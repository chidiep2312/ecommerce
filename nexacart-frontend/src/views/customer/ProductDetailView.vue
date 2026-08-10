<script setup>
import {
    Check,
    ChevronRight,
    Heart,
    ImageOff,
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
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import QuantitySelector
    from '@/components/customer/QuantitySelector.vue'

import {
    getProduct,
} from '@/api/products'

import {
    getPublicShopProducts,
} from '@/api/shop'
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

const product = ref(null)

const quantity = ref(1)
const selectedImageIndex = ref(0)
const activeTab = ref('description')

const isLoading = ref(false)
const isWishlistLoading = ref(false)
const isAddingToCart = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const productImages = computed(() => {
    if (!product.value) {
        return []
    }

    if (
        Array.isArray(
            product.value.images,
        ) &&
        product.value.images.length > 0
    ) {
        return product.value.images
    }

    if (product.value.main_image) {
        return [
            product.value.main_image,
        ]
    }

    return []
})

const currentImage = computed(() => {
    return (
        productImages.value[
        selectedImageIndex.value
        ] ?? null
    )
})

const isOutOfStock = computed(() => {
    return (
        !product.value ||
        Number(
            product.value.stock ?? 0,
        ) <= 0
    )
})

const isUnavailable = computed(() => {
    if (!product.value) {
        return true
    }

    return (
        product.value.status !==
        'active' ||
        product.value.is_suspended ||
        isOutOfStock.value
    )
})

const currentPrice = computed(() => {
    return Number(
        product.value
            ?.effective_price ??
        product.value
            ?.sale_price ??
        product.value
            ?.price ??
        0,
    )
})

const originalPrice = computed(() => {
    if (!product.value) {
        return null
    }

    const price = Number(
        product.value.price ?? 0,
    )

    const effectivePrice =
        currentPrice.value

    if (
        effectivePrice >= price
    ) {
        return null
    }

    return price
})

const discountPercent = computed(() => {
    if (
        !originalPrice.value ||
        originalPrice.value <= 0
    ) {
        return 0
    }

    return Math.round(
        (
            1 -
            currentPrice.value /
            originalPrice.value
        ) * 100,
    )
})

const totalPrice = computed(() => {
    return (
        currentPrice.value *
        Number(quantity.value)
    )
})

const isWishlisted = computed(() => {
    return Boolean(
        product.value
            ?.is_wishlisted,
    )
})
function formatReviewDate(value) {
    if (!value) {
        return ''
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
    ).format(
        new Date(value),
    )
}
async function fetchProduct() {
    const slug =
        route.params.slug

    if (!slug) {
        errorMessage.value =
            'Không xác định được sản phẩm.'

        return
    }

    isLoading.value = true
    errorMessage.value = ''

    try {
     
        const response =
            await getProduct(
                slug,
            )
   console.log(response)

        /*
         * Tùy response API.
         *
         * ProductResource thường:
         * response.data.data
         */
        product.value =
            response.data?.data ??
            response.data ??
            null

        quantity.value = 1
        selectedImageIndex.value = 0
    } catch (error) {
        product.value = null

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải thông tin sản phẩm.'
    } finally {
        isLoading.value = false
    }
}

function getImageUrl(image) {
    if (!image) {
        return ''
    }

    return (
        image.url ??
        image.path ??
        ''
    )
}

function selectImage(index) {
    selectedImageIndex.value =
        index
}

async function toggleWishlist() {
    if (!product.value) {
        return
    }

    /*
     * Wishlist chỉ dành cho user
     * đã đăng nhập.
     */
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

    if (isWishlistLoading.value) {
        return
    }

    isWishlistLoading.value = true

    errorMessage.value = ''
    successMessage.value = ''

    try {
        if (
            product.value
                .is_wishlisted
        ) {
            const response =
                await removeFromWishlist(
                    product.value.id,
                )

            product.value
                .is_wishlisted = false

            successMessage.value =
                response.data?.message ??
                'Đã bỏ sản phẩm khỏi danh sách yêu thích.'
        } else {
            const response =
                await addToWishlist(
                    product.value.id,
                )

            product.value
                .is_wishlisted = true

            successMessage.value =
                response.data?.message ??
                'Đã thêm sản phẩm vào danh sách yêu thích.'
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể cập nhật danh sách yêu thích.'
    } finally {
        isWishlistLoading.value = false
    }
}

async function addToCart() {
    if (
        !product.value ||
        isUnavailable.value ||
        isAddingToCart.value
    ) {
        return
    }

    isAddingToCart.value = true

    errorMessage.value = ''
    successMessage.value = ''

    try {

        await cartStore.addItem(
            product.value.id,
            quantity.value,
        )

        successMessage.value =
            'Đã thêm sản phẩm vào giỏ hàng.'
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            error.message ??
            'Không thể thêm sản phẩm vào giỏ hàng.'
    } finally {
        isAddingToCart.value = false
    }
}

async function buyNow() {
    if (
        !product.value ||
        isUnavailable.value
    ) {
        return
    }

    try {
        await addToCart()

        router.push({
            name: 'cart',
        })
    } catch (error) {
        console.error(
            'Không thể mua ngay:',
            error,
        )
    }
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

function getStatusMessage() {
    if (!product.value) {
        return ''
    }

    if (
        product.value.is_suspended
    ) {
        return 'Sản phẩm hiện đã bị tạm khóa.'
    }

    if (
        product.value.status !==
        'active'
    ) {
        return 'Sản phẩm hiện không được mở bán.'
    }

    if (isOutOfStock.value) {
        return 'Sản phẩm tạm hết hàng.'
    }

    return `Còn ${product.value.stock} sản phẩm`
}

watch(
    () => route.params.slug,
    () => {
        fetchProduct()
    },
)

onMounted(() => {
    fetchProduct()
})
</script>

<template>
    <div class="product-detail-page">
        <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>

        <div v-if="isLoading" class="state-box">
            Đang tải sản phẩm...
        </div>

        <div v-else-if="!product" class="state-box">
            Không tìm thấy sản phẩm.
        </div>

        <template v-else>
            <!-- BREADCRUMB -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <RouterLink to="/">
                    Trang chủ
                </RouterLink>

                <ChevronRight :size="14" />

                <RouterLink :to="{
                    name: 'products',
                }">
                    Sản phẩm
                </RouterLink>

                <ChevronRight :size="14" />

                <span>
                    {{ product.name }}
                </span>
            </nav>

            <!-- MAIN PRODUCT -->
            <section class="product-overview">
                <!-- GALLERY -->
                <div class="product-gallery">
                    <div v-if="
                        productImages.length >
                        1
                    " class="thumbnail-list">
                        <button v-for="(
image,
    index
                            ) in productImages" :key="image.id ??
                                index
                                " type="button" class="thumbnail-button" :class="{
                                    active:
                                        selectedImageIndex ===
                                        index,
                                }" @click="
                                    selectImage(
                                        index,
                                    )
                                    ">
                            <img :src="getImageUrl(
                                image,
                            )
                                " :alt="`${product.name} ${index + 1}`
                                    ">
                        </button>
                    </div>

                    <div class="main-image">
                        <img v-if="
                            currentImage &&
                            getImageUrl(
                                currentImage,
                            )
                        " :src="getImageUrl(
                            currentImage,
                        )
                            " :alt="product.name
                                ">

                        <div v-else class="image-empty">
                            <ImageOff :size="44" />

                            <span>
                                Chưa có ảnh
                            </span>
                        </div>

                        <span v-if="
                            discountPercent >
                            0
                        " class="discount-badge">
                            -{{
                                discountPercent
                            }}%
                        </span>
                    </div>
                </div>

                <!-- INFORMATION -->
                <div class="product-information">
                    <div class="product-meta">
                        <RouterLink v-if="
                            product.category
                        " :to="{
                            name:
                                'products',

                            query: {
                                category:
                                    product
                                        .category
                                        .slug,
                            },
                        }">
                            {{
                                product.category
                                    .name
                            }}
                        </RouterLink>

                        <span>
                            SKU:
                            {{
                                product.sku ??
                                '--'
                            }}
                        </span>
                    </div>

                    <h1>
                        {{ product.name }}
                    </h1>

                    <div class="rating-row">
                        <span class="rating-score">
                            <Star :size="16" fill="currentColor" />

                            <strong>
                                {{
                                    product
                                        .average_rating ??
                                    '0.0'
                                }}
                            </strong>
                        </span>

                        <span>
                            {{
                                product
                                    .reviews_count ??
                                0
                            }}
                            đánh giá
                        </span>
                    </div>

                    <div class="price-row">
                        <strong>
                            {{
                                formatPrice(
                                    currentPrice,
                                )
                            }}
                        </strong>

                        <span v-if="
                            originalPrice
                        " class="old-price">
                            {{
                                formatPrice(
                                    originalPrice,
                                )
                            }}
                        </span>

                        <span v-if="
                            discountPercent >
                            0
                        " class="price-discount">
                            Tiết kiệm
                            {{
                                discountPercent
                            }}%
                        </span>
                    </div>

                    <div class="stock-status" :class="{
                        available:
                            !isUnavailable,
                        unavailable:
                            isUnavailable,
                    }">
                        <Check v-if="
                            !isUnavailable
                        " :size="16" />

                        <span>
                            {{
                                getStatusMessage()
                            }}
                        </span>
                    </div>

                    <div class="purchase-panel">
                        <div class="purchase-row">
                            <span>
                                Số lượng
                            </span>

                            <QuantitySelector v-model="quantity
                                " :max="Number(
                                    product
                                        .stock ??
                                    0,
                                )
                                    " :disabled="isUnavailable
                                        " />
                        </div>

                        <div class="purchase-row">
                            <span>
                                Tạm tính
                            </span>

                            <strong>
                                {{
                                    formatPrice(
                                        totalPrice,
                                    )
                                }}
                            </strong>
                        </div>

                        <div class="purchase-actions">
                            <button type="button" class="primary-button" :disabled="isUnavailable ||
                                isAddingToCart
                                " @click="
                                    addToCart
                                ">
                                <ShoppingBag :size="18" />

                                {{
                                    isAddingToCart
                                        ? 'Đang thêm...'
                                        : 'Thêm vào giỏ'
                                }}
                            </button>

                            <button type="button" class="secondary-button" :disabled="isUnavailable
                                " @click="
                                    buyNow
                                ">
                                Mua ngay
                            </button>

                            <button type="button" class="wishlist-button" :class="{
                                active:
                                    isWishlisted,
                            }" :disabled="isWishlistLoading
                                " @click="
                                    toggleWishlist
                                ">
                                <Heart :size="20" :fill="isWishlisted
                                    ? 'currentColor'
                                    : 'none'
                                    " />
                            </button>
                        </div>
                    </div>

                    <div class="benefits">
                        <article>
                            <Truck :size="20" />

                            <div>
                                <strong>
                                    Giao hàng theo dõi được
                                </strong>

                                <span>
                                    Theo dõi trạng thái
                                    trong tài khoản.
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
                                    Thông tin đơn hàng
                                    được xử lý bảo mật.
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
                                    Áp dụng theo chính sách
                                    của NexaCart.
                                </span>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- SELLER -->
           <section class="seller-panel">
    <div class="seller-panel__info">
        <div class="seller-panel__icon">
            <Store :size="24" />
        </div>

        <div class="seller-panel__content">
            <span class="seller-panel__label">
                Được bán bởi
            </span>

            <h2>
                {{
                    product.seller
                        ?.shop
                        ?.name ??
                    product.seller
                        ?.name ??
                    'Người bán'
                }}
            </h2>

            <p
                v-if="
                    product.seller?.shop
                "
            >
                Cửa hàng chính thức trên NexaCart
            </p>
        </div>
    </div>

    <div class="seller-panel__actions">
        <RouterLink
            :to="{
                name: 'products',

                query: {
                    seller_id:
                        product.seller
                            ?.id,
                },
            }"
            class="seller-action seller-action--secondary"
        >
            <ShoppingBag :size="17" />

            <span>
                Xem sản phẩm
            </span>

            <ArrowRight :size="15" />
        </RouterLink>

        <RouterLink
            v-if="
                product.seller
                    ?.shop
                    ?.slug
            "
            :to="{
                name:
                    'seller-public-shop',

                params: {
                    slug:
                        product.seller
                            .shop
                            .slug,
                },
            }"
            class="seller-action seller-action--primary"
        >
            <Store :size="17" />

            <span>
                Xem cửa hàng
            </span>

            <ArrowRight :size="15" />
        </RouterLink>
    </div>
</section>

            <!-- DETAIL -->
            <section class="detail-panel">
                <div class="detail-tabs">
                    <button type="button" :class="{
                        active:
                            activeTab ===
                            'description',
                    }" @click="
                        activeTab =
                        'description'
                        ">
                        Mô tả sản phẩm
                    </button>

                    <button type="button" :class="{
                        active:
                            activeTab ===
                            'information',
                    }" @click="
                        activeTab =
                        'information'
                        ">
                        Thông tin sản phẩm
                    </button>

                    <button type="button" :class="{
                        active:
                            activeTab ===
                            'reviews',
                    }" @click="
                        activeTab = 'reviews'
                        ">
                        Đánh giá
                        ({{
                            product.reviews_count ??
                            0
                        }})
                    </button>

                    <button type="button" :class="{
                        active:
                            activeTab ===
                            'shipping',
                    }" @click="
                        activeTab =
                        'shipping'
                        ">
                        Vận chuyển
                    </button>
                </div>

                <div class="detail-content">
                    <div v-if="
                        activeTab ===
                        'description'
                    ">
                        <h2>
                            Mô tả sản phẩm
                        </h2>

                        <p v-if="
                            product.description
                        " class="description">
                            {{
                                product.description
                            }}
                        </p>

                        <p v-else class="empty-description">
                            Chưa có mô tả cho sản phẩm.
                        </p>
                    </div>

                    <div v-else-if="
                        activeTab ===
                        'information'
                    ">
                        <h2>
                            Thông tin sản phẩm
                        </h2>

                        <dl class="information-table">
                            <div>
                                <dt>
                                    Mã sản phẩm
                                </dt>

                                <dd>
                                    {{
                                        product.sku ??
                                        '--'
                                    }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Danh mục
                                </dt>

                                <dd>
                                    {{
                                        product
                                            .category
                                            ?.name ??
                                        '--'
                                    }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Thương hiệu
                                </dt>

                                <dd>
                                    {{
                                        product
                                            .brand
                                            ?.name ??
                                        '--'
                                    }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Người bán
                                </dt>

                                <dd>
                                    {{
                                        product
                                            .seller
                                            ?.name ??
                                        '--'
                                    }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Tồn kho
                                </dt>

                                <dd>
                                    {{
                                        product.stock
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div v-else-if="
                        activeTab ===
                        'reviews'
                    " class="reviews-panel">
                        <h2>
                            Đánh giá từ người mua
                        </h2>

                        <div class="review-summary">
                            <div class="review-summary__score">
                                <strong>
                                    {{
                                        product.average_rating ??
                                    '0.0'
                                    }}
                                </strong>

                                <span>
                                    / 5
                                </span>
                            </div>

                            <div class="review-summary__info">
                                <div class="review-summary__stars">
                                    <Star v-for="star in 5" :key="star" :size="18" :fill="star <=
                                            Math.round(
                                                Number(
                                                    product.average_rating ??
                                                    0,
                                                ),
                                            )
                                            ? 'currentColor'
                                            : 'none'
                                        " />
                                </div>

                                <span>
                                    Dựa trên
                                    {{
                                        product.reviews_count ??
                                    0
                                    }}
                                    đánh giá
                                </span>
                            </div>
                        </div>

                        <div v-if="
                            product.reviews?.length
                        " class="review-list">
                            <article v-for="review in product.reviews" :key="review.id" class="review-item">
                                <div class="review-item__header">
                                    <div class="review-user">
                                        <div class="review-user__avatar">
                                            {{
                                                review.user?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() ??
                                            'U'
                                            }}
                                        </div>

                                        <div>
                                            <strong>
                                                {{
                                                    review.user?.name ??
                                                    'Người mua'
                                                }}
                                            </strong>

                                            <span>
                                                {{
                                                    formatReviewDate(
                                                        review.created_at,
                                                )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="review-stars">
                                        <Star v-for="star in 5" :key="star" :size="15" :fill="star <=
                                                Number(
                                                    review.rating,
                                                )
                                                ? 'currentColor'
                                                : 'none'
                                            " />
                                    </div>
                                </div>

                                <p v-if="review.comment" class="review-comment">
                                    {{ review.comment }}
                                </p>

                                <p v-else class="review-comment review-comment--empty">
                                    Người mua không để lại
                                    nhận xét.
                                </p>
                            </article>
                        </div>

                        <div v-else class="reviews-empty">
                            <Star :size="28" />

                            <strong>
                                Chưa có đánh giá
                            </strong>

                            <span>
                                Sản phẩm này chưa nhận được
                                đánh giá từ người mua.
                            </span>
                        </div>
                    </div>
                    <div v-else-if="activeTab === 'shipping'">
                        <h2>
                            Vận chuyển và đổi trả
                        </h2>

                        <div class="shipping-list">
                            <article>
                                <PackageCheck :size="22" />

                                <div>
                                    <h3>
                                        Xử lý đơn hàng
                                    </h3>

                                    <p>
                                        Người bán xác nhận
                                        và chuẩn bị đơn hàng
                                        trước khi giao.
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
                                        Phí giao hàng được
                                        tính trong quá trình
                                        checkout.
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
                                        Sản phẩm được xử lý
                                        theo chính sách đổi
                                        trả của NexaCart.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.product-detail-page {
    display: flex;
    width: min(calc(100% - 40px),
            1280px);
    min-height: 500px;
    flex-direction: column;
    gap: 26px;
    margin: 0 auto;
    padding: 28px 0 60px;
    color: #1e2a22;
    font-family: Roboto, Arial, sans-serif;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    overflow: hidden;
    color: #7a877f;
    font-size: 12px;
    white-space: nowrap;
}

.breadcrumb a {
    color: #647269;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: #24734a;
}

.breadcrumb span {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* OVERVIEW */

.product-overview {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) minmax(400px, 0.85fr);
    gap: 42px;
    align-items: start;
}

.product-gallery {
    display: grid;
    grid-template-columns:
        76px minmax(0, 1fr);
    gap: 13px;
}

.thumbnail-list {
    display: grid;
    align-content: start;
    gap: 10px;
}

.thumbnail-button {
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    padding: 0;
    border: 1px solid #d9e2dc;
    background: #f5f7f6;
    cursor: pointer;
}

.thumbnail-button.active,
.thumbnail-button:hover {
    border-color: #24734a;
}

.thumbnail-button img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-image {
    position: relative;
    display: grid;
    overflow: hidden;
    aspect-ratio: 1;
    place-items: center;
    border: 1px solid #dce5df;
    background: #f5f7f6;
}

.main-image>img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.image-empty {
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 8px;
    color: #8c9991;
}

.discount-badge {
    position: absolute;
    top: 14px;
    left: 14px;
    padding: 6px 9px;
    color: #ffffff;
    background: #24734a;
    font-size: 11px;
    font-weight: 700;
}

/* INFORMATION */

.product-information {
    min-width: 0;
}

.product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
    color: #76837b;
    font-size: 11px;
}

.product-meta a {
    color: #24734a;
    font-weight: 600;
    text-decoration: none;
}

.product-information h1 {
    margin: 14px 0 0;
    color: #17211b;
    font-size: clamp(27px,
            3vw,
            38px);
    line-height: 1.2;
}

.rating-row {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-top: 15px;
    color: #718078;
    font-size: 11px;
}

.rating-score {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #9a6718;
}

.price-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 11px;
    margin-top: 24px;
    padding: 18px;
    background: #f5f8f6;
}

.price-row>strong {
    color: #24734a;
    font-size: 28px;
}

.old-price {
    color: #9ba59f;
    font-size: 14px;
    text-decoration: line-through;
}

.price-discount {
    padding: 5px 8px;
    border: 1px solid #a7cbb4;
    color: #21633f;
    background: #edf7f0;
    font-size: 10px;
    font-weight: 700;
}

.stock-status {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    gap: 7px;
    margin-top: 16px;
    padding: 0 10px;
    border: 1px solid;
    font-size: 11px;
    font-weight: 600;
}

.stock-status.available {
    border-color: #a5c9b2;
    color: #246440;
    background: #edf7f0;
}

.stock-status.unavailable {
    border-color: #dfb1b1;
    color: #983737;
    background: #fff2f2;
}

/* PURCHASE */

.purchase-panel {
    display: grid;
    gap: 17px;
    margin-top: 22px;
    padding-top: 22px;
    border-top: 1px solid #e1e7e3;
}

.purchase-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.purchase-row>span {
    color: #68766d;
    font-size: 12px;
}

.purchase-row strong {
    font-size: 16px;
}

.purchase-actions {
    display: grid;
    grid-template-columns:
        minmax(0, 1.4fr) minmax(0, 0.8fr) 44px;
    gap: 8px;
}

.primary-button,
.secondary-button,
.wishlist-button {
    min-height: 44px;
    border-radius: 0;
    font: inherit;
    cursor: pointer;
}

.primary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid #24734a;
    color: #ffffff;
    background: #24734a;
    font-size: 12px;
    font-weight: 600;
}

.primary-button:hover:not(:disabled) {
    background: #1e633f;
}

.secondary-button {
    border: 1px solid #24734a;
    color: #24734a;
    background: #ffffff;
    font-size: 12px;
    font-weight: 600;
}

.secondary-button:hover:not(:disabled) {
    background: #eef6f1;
}

.wishlist-button {
    display: grid;
    place-items: center;
    padding: 0;
    border: 1px solid #c9d4cd;
    color: #637168;
    background: #ffffff;
}

.wishlist-button:hover,
.wishlist-button.active {
    border-color: #24734a;
    color: #24734a;
    background: #edf6f0;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* BENEFITS */

.benefits {
    display: grid;
    gap: 1px;
    margin-top: 22px;
    border: 1px solid #dde5e0;
    background: #dde5e0;
}

.benefits article {
    display: grid;
    grid-template-columns:
        auto 1fr;
    gap: 11px;
    padding: 14px;
    background: #ffffff;
}

.benefits svg {
    color: #24734a;
}

.benefits strong {
    display: block;
    font-size: 12px;
}

.benefits span {
    display: block;
    margin-top: 4px;
    color: #758279;
    font-size: 10px;
}
/* =========================
   SELLER
========================= */

.seller-panel {
    display: flex;
    min-height: 92px;
    align-items: center;
    justify-content: space-between;
    gap: 28px;

    padding: 18px 20px;

    border: 1px solid #dce5df;

    background: #ffffff;
}

.seller-panel__info {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 14px;
}

.seller-panel__icon {
    display: grid;
    width: 50px;
    height: 50px;
    flex: 0 0 auto;
    place-items: center;

    color: #24734a;
    background: #edf6f0;
}

.seller-panel__content {
    min-width: 0;
}

.seller-panel__label {
    display: block;

    color: #7d8981;
    font-size: 11px;
}

.seller-panel__content h2 {
    margin: 4px 0 0;

    overflow: hidden;

    color: #26342b;
    font-size: 16px;
    font-weight: 700;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.seller-panel__content p {
    margin: 5px 0 0;

    color: #8a958e;
    font-size: 11px;
}

/* ACTION GROUP */

.seller-panel__actions {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 9px;
}

.seller-action {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    gap: 7px;

    padding: 0 14px;

    border: 1px solid;

    font-size: 12px;
    font-weight: 600;

    text-decoration: none;

    transition:
        background-color 150ms ease,
        border-color 150ms ease,
        color 150ms ease;
}

/* NÚT PHỤ */

.seller-action--secondary {
    border-color: #cbd6cf;

    color: #425047;
    background: #ffffff;
}

.seller-action--secondary:hover {
    border-color: #9fb8a8;

    color: #24734a;
    background: #f5f9f6;
}

/* NÚT CHÍNH */

.seller-action--primary {
    border-color: #24734a;

    color: #ffffff;
    background: #24734a;
}

.seller-action--primary:hover {
    border-color: #1e633f;

    background: #1e633f;
}

.seller-action svg:last-child {
    transition:
        transform 150ms ease;
}

.seller-action:hover
svg:last-child {
    transform: translateX(2px);
}

/* DETAIL */

.detail-panel {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.detail-tabs {
    display: flex;
    overflow-x: auto;
    border-bottom: 1px solid #e4eae6;
}

.detail-tabs button {
    min-height: 52px;
    padding: 0 18px;
    border: 0;
    border-bottom: 3px solid transparent;
    color: #69776e;
    background: #ffffff;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.detail-tabs button.active {
    border-bottom-color: #24734a;
    color: #24734a;
}

.detail-content {
    padding: 28px;
}

.detail-content h2 {
    margin: 0 0 18px;
    font-size: 19px;
}

.description {
    margin: 0;
    color: #4f5d54;
    font-size: 13px;
    line-height: 1.9;
    white-space: pre-line;
}

.empty-description {
    color: #89958d;
    font-size: 12px;
}

.information-table {
    max-width: 720px;
    margin: 0;
    border: 1px solid #e0e7e2;
}

.information-table>div {
    display: grid;
    grid-template-columns:
        190px 1fr;
    border-bottom: 1px solid #e5ebe7;
}

.information-table>div:last-child {
    border-bottom: 0;
}

.information-table dt,
.information-table dd {
    margin: 0;
    padding: 13px 15px;
    font-size: 12px;
}

.information-table dt {
    background: #f7f9f8;
    color: #647269;
    font-weight: 600;
}

.information-table dd {
    color: #354239;
}

.shipping-list {
    display: grid;
    gap: 12px;
    max-width: 760px;
}

.shipping-list article {
    display: grid;
    grid-template-columns:
        auto 1fr;
    gap: 13px;
    padding: 16px;
    border: 1px solid #e0e7e2;
    background: #fafcfb;
}

.shipping-list svg {
    color: #24734a;
}

.shipping-list h3 {
    margin: 0;
    font-size: 13px;
}

.shipping-list p {
    margin: 5px 0 0;
    color: #748178;
    font-size: 11px;
    line-height: 1.6;
}

/* STATES */

.alert {
    padding: 12px 15px;
    border: 1px solid;
    font-size: 12px;
}

.alert-error {
    border-color: #dfb0b0;
    color: #973737;
    background: #fff2f2;
}

.alert-success {
    border-color: #9dc5aa;
    color: #21633f;
    background: #edf7f0;
}

.state-box {
    display: grid;
    min-height: 380px;
    place-items: center;
    border: 1px solid #dce5df;
    color: #748178;
    background: #ffffff;
}

/* RESPONSIVE */

@media (max-width: 980px) {
    .product-overview {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .product-detail-page {
        width: calc(100% - 28px);
    }

    .product-gallery {
        grid-template-columns: 1fr;
    }

    .thumbnail-list {
        grid-row: 2;
        grid-template-columns:
            repeat(4,
                minmax(0, 1fr));
    }

    .purchase-actions {
        grid-template-columns:
            1fr 44px;
    }

    .secondary-button {
        grid-column: 1 / -1;
        grid-row: 2;
    }

    .seller-panel {
        align-items: flex-start;
        flex-direction: column;
    }

    .seller-link {
        width: 100%;
        justify-content: center;
    }

    .information-table>div {
        grid-template-columns: 1fr;
    }

    .information-table dd {
        border-top: 1px solid #e5ebe7;
    }

    .detail-content {
        padding: 20px 16px;
    }
}

/* REVIEWS */

.reviews-panel {
    max-width: 900px;
}

.review-summary {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 28px;
    padding: 20px;
    border: 1px solid #dce5df;
    background: #f7faf8;
}

.review-summary__score {
    display: flex;
    align-items: baseline;
    gap: 5px;
    padding-right: 24px;
    border-right: 1px solid #dce5df;
}

.review-summary__score strong {
    color: #24734a;
    font-size: 36px;
    line-height: 1;
}

.review-summary__score span {
    color: #78857c;
    font-size: 13px;
}

.review-summary__info {
    display: grid;
    gap: 7px;
}

.review-summary__stars {
    display: flex;
    gap: 3px;
    color: #9a6718;
}

.review-summary__info > span {
    color: #78857c;
    font-size: 11px;
}

/* LIST */

.review-list {
    display: grid;
}

.review-item {
    padding: 22px 0;
    border-bottom: 1px solid #e2e9e4;
}

.review-item:first-child {
    padding-top: 0;
}

.review-item:last-child {
    border-bottom: 0;
}

.review-item__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 11px;
}

.review-user__avatar {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    color: #24734a;
    background: #edf6f0;
    font-size: 13px;
    font-weight: 700;
}

.review-user > div:last-child {
    display: grid;
    gap: 4px;
}

.review-user strong {
    color: #26342b;
    font-size: 12px;
}

.review-user span {
    color: #89958d;
    font-size: 10px;
}

.review-stars {
    display: flex;
    flex: 0 0 auto;
    gap: 2px;
    color: #9a6718;
}

.review-comment {
    margin: 14px 0 0 49px;
    color: #536158;
    font-size: 12px;
    line-height: 1.7;
}

.review-comment--empty {
    color: #929d96;
    font-style: italic;
}

/* EMPTY */

.reviews-empty {
    display: grid;
    min-height: 180px;
    place-items: center;
    align-content: center;
    gap: 8px;
    border: 1px dashed #d5ded8;
    color: #8a968e;
    background: #fafcfb;
    text-align: center;
}

.reviews-empty svg {
    color: #9a6718;
}

.reviews-empty strong {
    color: #56635a;
    font-size: 13px;
}

.reviews-empty span {
    font-size: 11px;
}
@media (max-width: 640px) {
    .review-summary {
        align-items: flex-start;
        flex-direction: column;
    }

    .review-summary__score {
        padding-right: 0;
        padding-bottom: 14px;
        border-right: 0;
        border-bottom: 1px solid #dce5df;
    }

    .review-item__header {
        flex-direction: column;
        gap: 10px;
    }

    .review-comment {
        margin-left: 0;
    }
}
</style>
<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    deleteSellerProduct,
    getSellerProduct,
} from '@/api/seller/products'

const route = useRoute()
const router = useRouter()

const product = ref(null)

const loading = ref(false)
const deleting = ref(false)

const errorMessage = ref('')
const imageErrorIds = ref([])

const productId = computed(() => {
    return route.params.id
})

const productImages = computed(() => {
    if (!product.value) {
        return []
    }

    if (
        Array.isArray(
            product.value.images,
        )
    ) {
        return product.value.images
    }

    return []
})

const mainImage = computed(() => {
    if (!productImages.value.length) {
        return null
    }

    return (
        productImages.value.find(
            image =>
                Boolean(image.is_main),
        ) ??
        productImages.value[0]
    )
})

const productStatus = computed(() => {
    if (!product.value) {
        return {
            label: 'Không xác định',
            className: 'status-default',
        }
    }

    if (
        Boolean(
            product.value.is_suspended,
        )
    ) {
        return {
            label: 'Bị admin khóa',
            className: 'status-suspended',
        }
    }

    if (
        product.value.status ===
        'active'
    ) {
        return {
            label: 'Đang bán',
            className: 'status-active',
        }
    }

    return {
        label: 'Ngừng bán',
        className: 'status-inactive',
    }
})

const stockStatus = computed(() => {
    const stock = Number(
        product.value?.stock ?? 0,
    )

    if (stock <= 0) {
        return {
            label: 'Hết hàng',
            className: 'stock-empty',
        }
    }

    if (stock <= 5) {
        return {
            label: `Sắp hết hàng (${stock})`,
            className: 'stock-low',
        }
    }

    return {
        label: `Còn ${stock} sản phẩm`,
        className: 'stock-normal',
    }
})

const discountPercent = computed(() => {
    const price = Number(
        product.value?.price ?? 0,
    )

    const originalPrice = Number(
        product.value
            ?.original_price ?? 0,
    )

    if (
        originalPrice <= 0 ||
        originalPrice <= price
    ) {
        return null
    }

    return Math.round(
        (
            (
                originalPrice -
                price
            ) /
            originalPrice
        ) * 100,
    )
})

function extractProduct(response) {
    const responseData =
        response.data?.data

    if (
        responseData?.data &&
        typeof responseData.data ===
            'object'
    ) {
        return responseData.data
    }

    return responseData ?? null
}

async function fetchProduct() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getSellerProduct(
                productId.value,
            )
        console.log(response);
        product.value =
            extractProduct(response)

        if (!product.value) {
            errorMessage.value =
                'Không tìm thấy dữ liệu sản phẩm.'
        }
    } catch (error) {
        if (
            error.response?.status ===
            404
        ) {
            errorMessage.value =
                'Sản phẩm không tồn tại hoặc đã bị xóa.'

            return
        }

        if (
            error.response?.status ===
            403
        ) {
            errorMessage.value =
                'Bạn không có quyền xem sản phẩm này.'

            return
        }

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tải thông tin sản phẩm.'
    } finally {
        loading.value = false
    }
}

function goBack() {
    router.push({
        name: 'seller-products',
    })
}

function goToEdit() {
    if (
        product.value
            ?.is_suspended
    ) {
        errorMessage.value =
            'Sản phẩm đang bị admin khóa và không thể chỉnh sửa.'

        return
    }

    router.push({
        name: 'seller-products-edit',
        params: {
            id: productId.value,
        },
    })
}

async function removeProduct() {
    if (!product.value) {
        return
    }

    if (
        product.value
            .is_suspended
    ) {
        errorMessage.value =
            'Sản phẩm đang bị admin khóa và không thể xóa.'

        return
    }

    const confirmed =
        window.confirm(
            `Bạn có chắc muốn xóa sản phẩm "${product.value.name}" không?`,
        )

    if (!confirmed) {
        return
    }

    deleting.value = true
    errorMessage.value = ''

    try {
        await deleteSellerProduct(
            product.value.id,
        )

        await router.push({
            name: 'seller-products',
            query: {
                deleted: 1,
            },
        })
    } catch (error) {
        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể xóa sản phẩm.'
    } finally {
        deleting.value = false
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
        },
    ).format(
        Number(value ?? 0),
    )
}

function formatDate(value) {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return value
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        },
    ).format(date)
}

function getImageUrl(image) {
    return (
        image?.url ??
        image?.image_url ??
        image?.path_url ??
        ''
    )
}

function hasImageError(image) {
    return imageErrorIds.value.includes(
        image.id,
    )
}

function handleImageError(image) {
    if (
        !imageErrorIds.value.includes(
            image.id,
        )
    ) {
        imageErrorIds.value.push(
            image.id,
        )
    }
}

onMounted(() => {
    fetchProduct()
})
</script>

<template>
    <section class="product-detail-page">
        <header class="page-header">
            <div class="page-heading">
                <button
                    type="button"
                    class="back-button"
                    @click="goBack"
                >
                    ←
                </button>

                <div>
                    <h2 class="page-title">
                        Chi tiết sản phẩm
                    </h2>

                    <p class="page-description">
                        Xem thông tin chi tiết
                        của sản phẩm đang bán.
                    </p>
                </div>
            </div>

            <div
                v-if="product"
                class="page-actions"
            >
                <button
                    type="button"
                    class="
                        button
                        button-secondary
                    "
                    :disabled="
                        product.is_suspended
                    "
                    @click="goToEdit"
                >
                    Chỉnh sửa
                </button>

                <button
                    type="button"
                    class="
                        button
                        button-danger
                    "
                    :disabled="
                        product.is_suspended ||
                        deleting
                    "
                    @click="removeProduct"
                >
                    {{
                        deleting
                            ? 'Đang xóa...'
                            : 'Xóa sản phẩm'
                    }}
                </button>
            </div>
        </header>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="loading"
            class="loading-panel"
        >
            <span class="loader" />

            <p>
                Đang tải thông tin sản phẩm...
            </p>
        </div>

        <div
            v-else-if="product"
            class="detail-layout"
        >
            <main class="detail-main">
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Thông tin sản phẩm
                            </h3>

                            <p class="panel-description">
                                Thông tin cơ bản và
                                trạng thái kinh doanh.
                            </p>
                        </div>

                        <span
                            class="status-badge"
                            :class="
                                productStatus
                                    .className
                            "
                        >
                            {{
                                productStatus.label
                            }}
                        </span>
                    </header>

                    <div class="product-overview">
                        <div class="main-image">
                            <img
                                v-if="
                                    mainImage &&
                                    getImageUrl(
                                        mainImage,
                                    ) &&
                                    !hasImageError(
                                        mainImage,
                                    )
                                "
                                :src="
                                    getImageUrl(
                                        mainImage,
                                    )
                                "
                                :alt="product.name"
                                @error="
                                    handleImageError(
                                        mainImage,
                                    )
                                "
                            >

                            <div
                                v-else
                                class="image-placeholder"
                            >
                                SP
                            </div>

                            <span
                                v-if="
                                    mainImage
                                        ?.is_main
                                "
                                class="main-image-label"
                            >
                                Ảnh chính
                            </span>
                        </div>

                        <div class="overview-content">
                            <div>
                                <span class="product-code">
                                    SKU:
                                    {{
                                        product.sku ??
                                        'Chưa có'
                                    }}
                                </span>

                                <h1 class="product-name">
                                    {{ product.name }}
                                </h1>

                                <p class="product-slug">
                                    {{
                                        product.slug ??
                                        '—'
                                    }}
                                </p>
                            </div>

                            <div class="price-section">
                                <strong class="sale-price">
                                    {{
                                        formatCurrency(
                                            product.price,
                                        )
                                    }}
                                </strong>

                                <del
                                    v-if="
                                        product
                                            .original_price &&
                                        Number(
                                            product
                                                .original_price,
                                        ) >
                                            Number(
                                                product
                                                    .price,
                                            )
                                    "
                                    class="original-price"
                                >
                                    {{
                                        formatCurrency(
                                            product
                                                .original_price,
                                        )
                                    }}
                                </del>

                                <span
                                    v-if="
                                        discountPercent
                                    "
                                    class="discount-label"
                                >
                                    Giảm
                                    {{
                                        discountPercent
                                    }}%
                                </span>
                            </div>

                            <div class="overview-meta">
                                <div class="meta-item">
                                    <span>
                                        Danh mục
                                    </span>

                                    <strong>
                                        {{
                                            product
                                                .category
                                                ?.name ??
                                            'Chưa phân loại'
                                        }}
                                    </strong>
                                </div>

                                <div class="meta-item">
                                    <span>
                                        Thương hiệu
                                    </span>

                                    <strong>
                                        {{
                                            product
                                                .brand
                                                ?.name ??
                                            'Không thương hiệu'
                                        }}
                                    </strong>
                                </div>

                                <div class="meta-item">
                                    <span>
                                        Tồn kho
                                    </span>

                                    <strong
                                        class="stock-label"
                                        :class="
                                            stockStatus
                                                .className
                                        "
                                    >
                                        {{
                                            stockStatus
                                                .label
                                        }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Mô tả sản phẩm
                            </h3>

                            <p class="panel-description">
                                Nội dung giới thiệu
                                và thông tin chi tiết.
                            </p>
                        </div>
                    </header>

                    <div class="description-content">
                        <div
                            v-if="
                                product.description
                            "
                            class="product-description"
                        >
                            {{
                                product.description
                            }}
                        </div>

                        <p
                            v-else
                            class="empty-description"
                        >
                            Sản phẩm chưa có mô tả.
                        </p>
                    </div>
                </section>

                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Hình ảnh sản phẩm
                            </h3>

                            <p class="panel-description">
                                Có
                                {{
                                    productImages.length
                                }}
                                hình ảnh.
                            </p>
                        </div>
                    </header>

                    <div
                        v-if="
                            productImages.length
                        "
                        class="image-grid"
                    >
                        <article
                            v-for="
                                image in
                                productImages
                            "
                            :key="image.id"
                            class="image-item"
                        >
                            <div class="image-box">
                                <img
                                    v-if="
                                        getImageUrl(
                                            image,
                                        ) &&
                                        !hasImageError(
                                            image,
                                        )
                                    "
                                    :src="
                                        getImageUrl(
                                            image,
                                        )
                                    "
                                    :alt="
                                        product.name
                                    "
                                    @error="
                                        handleImageError(
                                            image,
                                        )
                                    "
                                >

                                <div
                                    v-else
                                    class="image-placeholder"
                                >
                                    SP
                                </div>
                            </div>

                            <div class="image-information">
                                <span>
                                    Thứ tự:
                                    {{
                                        image.sort_order ??
                                        0
                                    }}
                                </span>

                                <strong
                                    v-if="
                                        image.is_main
                                    "
                                >
                                    Ảnh chính
                                </strong>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="empty-images"
                    >
                        Sản phẩm chưa có hình ảnh.
                    </div>
                </section>
            </main>

            <aside class="detail-sidebar">
                <section
                    v-if="
                        product.is_suspended
                    "
                    class="
                        panel
                        suspension-panel
                    "
                >
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Sản phẩm bị khóa
                            </h3>

                            <p class="panel-description">
                                Sản phẩm đã bị admin
                                tạm ngừng hoạt động.
                            </p>
                        </div>
                    </header>

                    <div class="suspension-content">
                        <div class="suspension-icon">
                            !
                        </div>

                        <div>
                            <span>
                                Lý do khóa
                            </span>

                            <p>
                                {{
                                    product
                                        .suspension_reason ??
                                    'Admin chưa cung cấp lý do.'
                                }}
                            </p>
                        </div>
                    </div>

                    <dl class="suspension-meta">
                        <div>
                            <dt>
                                Người khóa
                            </dt>

                            <dd>
                                {{
                                    product
                                        .suspended_by
                                        ?.name ??
                                    'Admin'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Thời gian khóa
                            </dt>

                            <dd>
                                {{
                                    formatDate(
                                        product
                                            .suspended_at,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Thông tin quản lý
                            </h3>
                        </div>
                    </header>

                    <dl class="information-list">
                        <div>
                            <dt>
                                Mã sản phẩm
                            </dt>

                            <dd>
                                #{{ product.id }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Trạng thái bán
                            </dt>

                            <dd>
                                {{
                                    product.status ===
                                    'active'
                                        ? 'Đang bán'
                                        : 'Ngừng bán'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Trạng thái admin
                            </dt>

                            <dd>
                                {{
                                    product
                                        .is_suspended
                                        ? 'Bị khóa'
                                        : 'Bình thường'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Số lượng tồn
                            </dt>

                            <dd>
                                {{
                                    product.stock ??
                                    0
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Ngày tạo
                            </dt>

                            <dd>
                                {{
                                    formatDate(
                                        product
                                            .created_at,
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Cập nhật gần nhất
                            </dt>

                            <dd>
                                {{
                                    formatDate(
                                        product
                                            .updated_at,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Thao tác
                            </h3>
                        </div>
                    </header>

                    <div class="sidebar-actions">
                        <button
                            type="button"
                            class="
                                button
                                button-primary
                                full-button
                            "
                            :disabled="
                                product
                                    .is_suspended
                            "
                            @click="goToEdit"
                        >
                            Chỉnh sửa sản phẩm
                        </button>

                        <button
                            type="button"
                            class="
                                button
                                button-secondary
                                full-button
                            "
                            @click="goBack"
                        >
                            Quay lại danh sách
                        </button>

                        <button
                            type="button"
                            class="
                                button
                                button-danger-outline
                                full-button
                            "
                            :disabled="
                                product
                                    .is_suspended ||
                                deleting
                            "
                            @click="removeProduct"
                        >
                            {{
                                deleting
                                    ? 'Đang xóa...'
                                    : 'Xóa sản phẩm'
                            }}
                        </button>
                    </div>
                </section>
            </aside>
        </div>
    </section>
</template>

<style scoped>
.product-detail-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.page-heading {
    display: flex;
    align-items: center;
    gap: 13px;
}

.back-button {
    display: flex;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #374151;
    cursor: pointer;
    font-size: 20px;
}

.back-button:hover {
    border-color: #15803d;
    color: #15803d;
}

.page-title {
    margin: 0;
    color: #111827;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 7px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.button-primary {
    border-color: #15803d;
    background: #15803d;
    color: #ffffff;
}

.button-primary:hover:not(:disabled) {
    background: #166534;
}

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.button-danger {
    border-color: #dc2626;
    background: #dc2626;
    color: #ffffff;
}

.button-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.button-danger-outline {
    border-color: #fca5a5;
    background: #ffffff;
    color: #dc2626;
}

.button-danger-outline:hover:not(:disabled) {
    background: #fef2f2;
}

.full-button {
    width: 100%;
}

.alert {
    border: 1px solid;
    padding: 12px 15px;
    font-size: 13px;
}

.alert-error {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.loading-panel {
    display: flex;
    min-height: 420px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    border: 1px solid #e1e7e3;
    background: #ffffff;
    color: #6b7280;
}

.loader {
    width: 34px;
    height: 34px;
    border: 3px solid #dcfce7;
    border-top-color: #15803d;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.detail-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        320px;
    gap: 20px;
    align-items: start;
}

.detail-main,
.detail-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel {
    border: 1px solid #e1e7e3;
    background: #ffffff;
}

.panel-header {
    display: flex;
    min-height: 68px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 18px;
}

.panel-title {
    margin: 0;
    color: #111827;
    font-size: 15px;
    font-weight: 600;
}

.panel-description {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.product-overview {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
    padding: 22px;
}

.main-image {
    position: relative;
    display: flex;
    aspect-ratio: 1 / 1;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
}

.main-image img,
.image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #9ca3af;
    font-size: 22px;
    font-weight: 700;
}

.main-image-label {
    position: absolute;
    right: 8px;
    bottom: 8px;
    background: #15803d;
    padding: 5px 8px;
    color: #ffffff;
    font-size: 11px;
}

.overview-content {
    display: flex;
    min-width: 0;
    flex-direction: column;
    justify-content: space-between;
    gap: 22px;
}

.product-code {
    color: #9ca3af;
    font-size: 12px;
}

.product-name {
    margin: 8px 0 0;
    color: #111827;
    font-size: 26px;
    font-weight: 600;
    line-height: 1.35;
}

.product-slug {
    margin: 7px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.price-section {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.sale-price {
    color: #15803d;
    font-size: 25px;
}

.original-price {
    color: #9ca3af;
    font-size: 14px;
}

.discount-label {
    border: 1px solid #fca5a5;
    background: #fef2f2;
    padding: 4px 7px;
    color: #dc2626;
    font-size: 11px;
    font-weight: 500;
}

.overview-meta {
    display: grid;
    grid-template-columns:
        repeat(3, minmax(0, 1fr));
    border-top: 1px solid #e5e7eb;
    border-left: 1px solid #e5e7eb;
}

.meta-item {
    display: flex;
    min-height: 82px;
    flex-direction: column;
    gap: 7px;
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px;
}

.meta-item span {
    color: #9ca3af;
    font-size: 11px;
}

.meta-item strong {
    color: #374151;
    font-size: 13px;
}

.stock-label {
    font-weight: 500;
}

.stock-normal {
    color: #166534 !important;
}

.stock-low {
    color: #b45309 !important;
}

.stock-empty {
    color: #dc2626 !important;
}

.status-badge {
    display: inline-flex;
    border: 1px solid;
    padding: 5px 8px;
    font-size: 11px;
    white-space: nowrap;
}

.status-active {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-inactive {
    border-color: #d1d5db;
    background: #f9fafb;
    color: #4b5563;
}

.status-suspended {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.description-content {
    padding: 20px;
}

.product-description {
    color: #4b5563;
    font-size: 14px;
    line-height: 1.8;
    white-space: pre-wrap;
}

.empty-description,
.empty-images {
    margin: 0;
    color: #9ca3af;
    font-size: 13px;
}

.image-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 14px;
    padding: 18px;
}

.image-item {
    border: 1px solid #e5e7eb;
}

.image-box {
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #f9fafb;
}

.image-information {
    display: flex;
    min-height: 40px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-top: 1px solid #e5e7eb;
    padding: 0 9px;
    color: #9ca3af;
    font-size: 10px;
}

.image-information strong {
    color: #15803d;
    font-size: 10px;
}

.empty-images {
    padding: 45px 20px;
    text-align: center;
}

.suspension-panel {
    border-color: #fca5a5;
}

.suspension-panel .panel-header {
    background: #fef2f2;
}

.suspension-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 18px;
}

.suspension-icon {
    display: flex;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #dc2626;
    color: #ffffff;
    font-weight: 700;
}

.suspension-content span {
    color: #991b1b;
    font-size: 11px;
    font-weight: 600;
}

.suspension-content p {
    margin: 6px 0 0;
    color: #7f1d1d;
    font-size: 13px;
    line-height: 1.6;
}

.suspension-meta,
.information-list {
    margin: 0;
}

.suspension-meta {
    border-top: 1px solid #fecaca;
}

.suspension-meta > div,
.information-list > div {
    display: flex;
    min-height: 46px;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    border-bottom: 1px solid #f3f4f6;
    padding: 10px 18px;
}

.suspension-meta > div:last-child,
.information-list > div:last-child {
    border-bottom: 0;
}

.suspension-meta dt,
.information-list dt {
    color: #9ca3af;
    font-size: 11px;
}

.suspension-meta dd,
.information-list dd {
    margin: 0;
    color: #374151;
    font-size: 12px;
    font-weight: 500;
    text-align: right;
}

.sidebar-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 18px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1200px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }

    .detail-sidebar {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .suspension-panel {
        grid-column: span 2;
    }
}

@media (max-width: 850px) {
    .product-overview {
        grid-template-columns: 220px 1fr;
    }

    .overview-meta {
        grid-template-columns: 1fr;
    }

    .image-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 680px) {
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-actions {
        width: 100%;
    }

    .page-actions .button {
        flex: 1;
    }

    .product-overview {
        grid-template-columns: 1fr;
    }

    .main-image {
        max-width: 420px;
    }

    .product-name {
        font-size: 21px;
    }

    .detail-sidebar {
        display: flex;
    }

    .image-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 430px) {
    .page-heading {
        align-items: flex-start;
    }

    .page-actions {
        flex-direction: column;
    }

    .image-grid {
        grid-template-columns: 1fr;
    }
}
</style>
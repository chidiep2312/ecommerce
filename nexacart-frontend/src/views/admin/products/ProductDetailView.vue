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
    getAdminProduct,
    restoreAdminProduct,
    suspendAdminProduct,
} from '@/api/admin/products'

const route = useRoute()
const router = useRouter()

const product = ref(null)
const selectedImageUrl = ref('')

const loading = ref(false)
const actionLoading = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const showSuspendModal = ref(false)
const suspendReason = ref('')
const suspendErrors = ref({})

const productId = computed(() => {
    return route.params.id
})

const productImages = computed(() => {
    if (!product.value) {
        return []
    }

    if (
        Array.isArray(product.value.images) &&
        product.value.images.length > 0
    ) {
        return product.value.images
    }

    if (product.value.main_image_url) {
        return [
            {
                id: 'main',
                url: product.value.main_image_url,
                is_main: true,
            },
        ]
    }

    return []
})

const productStatusLabel = computed(() => {
   
    if (!product.value) {
        return '—'
    }

    if (product.value.is_suspended==1) {
        return 'Bị admin khóa'
    }

    const labels = {
        active: 'Đang bán',
        inactive: 'Ngừng bán',
    }

    return (
        labels[product.value.status] ??
        product.value.status ??
        '—'
    )
})

const productStatusClass = computed(() => {
    if (!product.value) {
        return ''
    }

    if (product.value.is_suspended) {
        return 'status-suspended'
    }

    if (product.value.status === 'active') {
        return 'status-active'
    }

    return 'status-inactive'
})

const stockLabel = computed(() => {
    const stock = Number(
        product.value?.stock ?? 0,
    )

    if (stock <= 0) {
        return 'Hết hàng'
    }

    if (stock <= 5) {
        return 'Sắp hết'
    }

    return 'Còn hàng'
})

const stockClass = computed(() => {
    const stock = Number(
        product.value?.stock ?? 0,
    )

    if (stock <= 0) {
        return 'stock-empty'
    }

    if (stock <= 5) {
        return 'stock-low'
    }

    return 'stock-available'
})

async function fetchProduct() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminProduct(
            productId.value,
        )

        product.value =
            response.data?.data ??
            response.data

        selectedImageUrl.value =
            getInitialImageUrl(
                product.value,
            )
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải thông tin sản phẩm.'
    } finally {
        loading.value = false
    }
}

function getInitialImageUrl(productData) {
    if (!productData) {
        return ''
    }

    const mainImage =
        productData.images?.find(
            image => image.is_main,
        )

    return (
        mainImage?.url ??
        mainImage?.image_url ??
        productData.main_image_url ??
        productData.images?.[0]?.url ??
        productData.images?.[0]?.image_url ??
        ''
    )
}

function getImageUrl(image) {
    return (
        image?.url ??
        image?.image_url ??
        ''
    )
}

function selectImage(image) {
    selectedImageUrl.value =
        getImageUrl(image)
}

function handleImageError(event) {
    event.target.style.display = 'none'

    const fallback =
        event.target.nextElementSibling

    if (fallback) {
        fallback.style.display = 'flex'
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

function goBack() {
    router.push({
        name: 'admin-products',
    })
}

function openSuspendModal() {
    suspendReason.value = ''
    suspendErrors.value = {}
    errorMessage.value = ''
    successMessage.value = ''

    showSuspendModal.value = true
}

function closeSuspendModal() {
    if (actionLoading.value) {
        return
    }

    showSuspendModal.value = false
    suspendReason.value = ''
    suspendErrors.value = {}
}

async function submitSuspendProduct() {
    if (!product.value) {
        return
    }

    actionLoading.value = true
    errorMessage.value = ''
    successMessage.value = ''
    suspendErrors.value = {}

    try {
        const response =
            await suspendAdminProduct(
                product.value.id,
                suspendReason.value,
            )

        const updatedProduct =
            response.data?.data

        product.value = {
            ...product.value,
            ...updatedProduct,
            is_suspended:
                updatedProduct
                    ?.is_suspended ??
                true,
            suspended_reason:
                updatedProduct
                    ?.suspended_reason ??
                suspendReason.value,
        }

        successMessage.value =
            response.data?.message ??
            'Khóa sản phẩm thành công.'

        showSuspendModal.value = false
        suspendReason.value = ''
    } catch (error) {
        if (error.response?.status === 422) {
            suspendErrors.value =
                error.response?.data
                    ?.errors ?? {}

            return
        }

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể khóa sản phẩm.'
    } finally {
        actionLoading.value = false
    }
}

async function restoreProduct() {
    if (!product.value) {
        return
    }

    const confirmed = window.confirm(
        `Bạn có chắc muốn mở khóa sản phẩm "${product.value.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    actionLoading.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await restoreAdminProduct(
                product.value.id,
            )

        const updatedProduct =
            response.data?.data

        product.value = {
            ...product.value,
            ...updatedProduct,
            is_suspended:
                updatedProduct
                    ?.is_suspended ??
                false,
            suspended_reason: null,
            suspended_by: null,
            suspended_at: null,
            suspended_at_formatted: null,
        }

        successMessage.value =
            response.data?.message ??
            'Mở khóa sản phẩm thành công.'
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể mở khóa sản phẩm.'
    } finally {
        actionLoading.value = false
    }
}

onMounted(() => {
    fetchProduct()
})
</script>

<template>
    <section class="product-detail-page">
        <header class="page-header">
            <div class="header-left">
                <button
                    type="button"
                    class="back-button"
                    @click="goBack"
                >
                    ← Quay lại
                </button>

                <div>
                    <h1 class="page-title">
                        Chi tiết sản phẩm
                    </h1>

                    <p class="page-description">
                        Xem thông tin và kiểm soát
                        trạng thái sản phẩm.
                    </p>
                </div>
            </div>

            <div
                v-if="product"
                class="header-actions"
            >
                <button
                    v-if="!product.is_suspended"
                    type="button"
                    class="button button-danger"
                    :disabled="actionLoading"
                    @click="openSuspendModal"
                >
                    Khóa sản phẩm
                </button>

                <button
                    v-else
                    type="button"
                    class="button button-success"
                    :disabled="actionLoading"
                    @click="restoreProduct"
                >
                    {{
                        actionLoading
                            ? 'Đang xử lý...'
                            : 'Mở khóa sản phẩm'
                    }}
                </button>
            </div>
        </header>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="loading"
            class="page-state"
        >
            Đang tải thông tin sản phẩm...
        </div>

        <div
            v-else-if="product"
            class="detail-content"
        >
            <div
                v-if="product.is_suspended"
                class="suspended-panel"
            >
                <div class="suspended-heading">
                    Sản phẩm đã bị khóa
                </div>

                <div class="suspended-grid">
                    <div class="suspended-item">
                        <span class="info-label">
                            Người khóa
                        </span>

                        <strong>
                            {{
                                product.suspended_by
                                    ?.name ??
                                '—'
                            }}
                        </strong>
                    </div>

                    <div class="suspended-item">
                        <span class="info-label">
                            Ngày khóa
                        </span>

                        <strong>
                            {{
                                product
                                    .suspended_at_formatted ??
                                formatDate(
                                    product
                                        .suspended_at,
                                )
                            }}
                        </strong>
                    </div>

                    <div class="suspended-reason">
                        <span class="info-label">
                            Lý do khóa
                        </span>

                        <p>
                            {{
                                product
                                    .suspended_reason ??
                                'Không có lý do.'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="main-grid">
                <article class="panel image-panel">
                    <div class="main-image">
                        <img
                            v-if="selectedImageUrl"
                            :src="selectedImageUrl"
                            :alt="product.name"
                            @error="handleImageError"
                        >

                        <div
                            class="main-image-fallback"
                            :style="{
                                display:
                                    selectedImageUrl
                                        ? 'none'
                                        : 'flex',
                            }"
                        >
                            Không có ảnh sản phẩm
                        </div>
                    </div>

                    <div
                        v-if="
                            productImages.length > 1
                        "
                        class="thumbnail-list"
                    >
                        <button
                            v-for="image in productImages"
                            :key="image.id"
                            type="button"
                            class="thumbnail-button"
                            :class="{
                                active:
                                    selectedImageUrl ===
                                    getImageUrl(
                                        image,
                                    ),
                            }"
                            @click="
                                selectImage(image)
                            "
                        >
                            <img
                                :src="
                                    getImageUrl(
                                        image,
                                    )
                                "
                                :alt="product.name"
                            >
                        </button>
                    </div>
                </article>

                <article class="panel product-panel">
                    <div class="panel-header">
                        <div>
                            <span class="product-id">
                                ID: {{ product.id }}
                            </span>

                            <h2 class="product-name">
                                {{ product.name }}
                            </h2>

                            <p class="product-slug">
                                {{ product.slug }}
                            </p>
                        </div>

                        <span
                            class="status-badge"
                            :class="
                                productStatusClass
                            "
                        >
                            {{
                                productStatusLabel
                            }}
                        </span>

                    </div>

                    <div class="price-section">
                        <strong class="sale-price">
                            {{
                                formatCurrency(
                                    product.price,
                                )
                            }}
                        </strong>

                        <span
                            v-if="
                                product.original_price &&
                                Number(
                                    product.original_price,
                                ) >
                                    Number(
                                        product.price,
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
                        </span>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">
                                SKU
                            </span>

                            <strong>
                                {{
                                    product.sku ??
                                    '—'
                                }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Tồn kho
                            </span>

                            <div class="stock-value">
                                <strong>
                                    {{
                                        product.stock ??
                                        0
                                    }}
                                </strong>

                                <span
                                    class="stock-label"
                                    :class="
                                        stockClass
                                    "
                                >
                                    {{ stockLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Danh mục
                            </span>

                            <strong>
                                {{
                                    product.category
                                        ?.name ??
                                    'Không có'
                                }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Thương hiệu
                            </span>

                            <strong>
                                {{
                                    product.brand
                                        ?.name ??
                                    'Không có'
                                }}
                            </strong>
                        </div>
                    </div>
                </article>
            </div>

            <div class="secondary-grid">
                <article class="panel">
                    <div class="section-header">
                        <h2 class="section-title">
                            Thông tin người bán
                        </h2>
                    </div>

                    <div class="seller-card">
                        <div class="seller-avatar">
                            {{
                                product.seller
                                    ?.name
                                    ?.charAt(0)
                                    ?.toUpperCase() ??
                                'S'
                            }}
                        </div>

                        <div>
                            <strong class="seller-name">
                                {{
                                    product.seller
                                        ?.name ??
                                    'Không xác định'
                                }}
                            </strong>

                            <p class="seller-email">
                                {{
                                    product.seller
                                        ?.email ??
                                    'Không có email'
                                }}
                            </p>
                        </div>
                    </div>
                </article>

                <article class="panel">
                    <div class="section-header">
                        <h2 class="section-title">
                            Thông tin hệ thống
                        </h2>
                    </div>

                    <div class="system-grid">
                        <div class="info-item">
                            <span class="info-label">
                                Ngày tạo
                            </span>

                            <strong>
                                {{
                                    product
                                        .created_at_formatted ??
                                    formatDate(
                                        product
                                            .created_at,
                                    )
                                }}
                            </strong>
                        </div>

                        <div class="info-item">
                            <span class="info-label">
                                Cập nhật lần cuối
                            </span>

                            <strong>
                                {{
                                    product
                                        .updated_at_formatted ??
                                    formatDate(
                                        product
                                            .updated_at,
                                    )
                                }}
                            </strong>
                        </div>
                    </div>
                </article>
            </div>

            <article class="panel description-panel">
                <div class="section-header">
                    <h2 class="section-title">
                        Mô tả sản phẩm
                    </h2>
                </div>

                <div
                    v-if="product.description"
                    class="product-description"
                    v-html="product.description"
                />

                <div
                    v-else
                    class="empty-description"
                >
                    Sản phẩm chưa có mô tả.
                </div>
            </article>

            <article
                v-if="productImages.length > 0"
                class="panel gallery-panel"
            >
                <div class="section-header">
                    <h2 class="section-title">
                        Hình ảnh sản phẩm
                    </h2>

                    <span class="image-count">
                        {{
                            productImages.length
                        }}
                        ảnh
                    </span>
                </div>

                <div class="gallery-grid">
                    <button
                        v-for="image in productImages"
                        :key="image.id"
                        type="button"
                        class="gallery-item"
                        @click="
                            selectImage(image)
                        "
                    >
                        <img
                            :src="
                                getImageUrl(
                                    image,
                                )
                            "
                            :alt="product.name"
                        >

                        <span
                            v-if="image.is_main"
                            class="main-image-label"
                        >
                            Ảnh chính
                        </span>
                    </button>
                </div>
            </article>
        </div>

        <div
            v-if="showSuspendModal"
            class="modal-overlay"
            @click.self="closeSuspendModal"
        >
            <div class="modal">
                <header class="modal-header">
                    <div>
                        <h2 class="modal-title">
                            Khóa sản phẩm
                        </h2>

                        <p class="modal-description">
                            {{
                                product?.name
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="modal-close"
                        :disabled="actionLoading"
                        @click="closeSuspendModal"
                    >
                        ×
                    </button>
                </header>

                <form
                    class="suspend-form"
                    @submit.prevent="
                        submitSuspendProduct
                    "
                >
                    <div class="form-field">
                        <label for="suspend-reason">
                            Lý do khóa
                            <span class="required">
                                *
                            </span>
                        </label>

                        <textarea
                            id="suspend-reason"
                            v-model.trim="
                                suspendReason
                            "
                            rows="5"
                            maxlength="1000"
                            placeholder="Nhập lý do khóa sản phẩm"
                        />

                        <div class="field-footer">
                            <p
                                v-if="
                                    suspendErrors
                                        .reason
                                "
                                class="field-error"
                            >
                                {{
                                    suspendErrors
                                        .reason[0]
                                }}
                            </p>

                            <span class="character-count">
                                {{
                                    suspendReason
                                        .length
                                }}/1000
                            </span>
                        </div>
                    </div>

                    <div class="warning-box">
                        Sản phẩm sẽ bị ẩn khỏi trang
                        bán hàng. Thông tin sản phẩm
                        và trạng thái của seller vẫn
                        được giữ nguyên.
                    </div>

                    <footer class="modal-actions">
                        <button
                            type="button"
                            class="
                                button
                                button-secondary
                            "
                            :disabled="actionLoading"
                            @click="
                                closeSuspendModal
                            "
                        >
                            Hủy
                        </button>

                        <button
                            type="submit"
                            class="
                                button
                                button-danger
                            "
                            :disabled="
                                actionLoading ||
                                suspendReason.length <
                                    5
                            "
                        >
                            {{
                                actionLoading
                                    ? 'Đang khóa...'
                                    : 'Khóa sản phẩm'
                            }}
                        </button>
                    </footer>
                </form>
            </div>
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

.header-left {
    display: flex;
    align-items: flex-start;
    gap: 18px;
}

.back-button {
    border: 0;
    background: transparent;
    padding: 4px 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    font-weight: 500;
}

.back-button:hover {
    text-decoration: underline;
}

.page-title {
    margin: 0;
    color: #111827;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.button {
    min-height: 40px;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-weight: 500;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.button-danger {
    border-color: #dc2626;
    background: #dc2626;
    color: #ffffff;
}

.button-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.button-success {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.button-success:hover:not(:disabled) {
    background: #15803d;
}

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.alert {
    border: 1px solid;
    padding: 12px 16px;
    font-size: 14px;
}

.alert-success {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.alert-error {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.page-state {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 60px 20px;
    color: #6b7280;
    text-align: center;
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.main-grid {
    display: grid;
    grid-template-columns:
        minmax(320px, 420px)
        minmax(0, 1fr);
    gap: 20px;
}

.image-panel {
    padding: 18px;
}

.main-image {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.main-image-fallback {
    position: absolute;
    inset: 0;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 14px;
}

.thumbnail-list {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
    margin-top: 12px;
}

.thumbnail-button {
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 0;
    background: #ffffff;
    padding: 2px;
    cursor: pointer;
}

.thumbnail-button.active {
    border-color: #16a34a;
}

.thumbnail-button img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-panel {
    padding: 24px;
}

.panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 20px;
}

.product-id {
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
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.status-badge {
    display: inline-flex;
    flex-shrink: 0;
    border: 1px solid;
    padding: 5px 9px;
    font-size: 12px;
    font-weight: 500;
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

.price-section {
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #e5e7eb;
    padding: 24px 0;
}

.sale-price {
    color: #15803d;
    font-size: 28px;
    font-weight: 700;
}

.original-price {
    color: #9ca3af;
    font-size: 15px;
    text-decoration: line-through;
}

.info-grid,
.system-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding-top: 24px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.info-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 500;
}

.info-item strong {
    color: #111827;
    font-size: 14px;
    font-weight: 500;
}

.stock-value {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stock-label {
    font-size: 12px;
}

.stock-available {
    color: #15803d;
}

.stock-low {
    color: #b45309;
}

.stock-empty {
    color: #dc2626;
}

.secondary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e5e7eb;
    padding: 16px 20px;
}

.section-title {
    margin: 0;
    color: #111827;
    font-size: 16px;
    font-weight: 600;
}

.seller-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
}

.seller-avatar {
    display: flex;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-size: 18px;
    font-weight: 700;
}

.seller-name {
    color: #111827;
}

.seller-email {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.system-grid {
    padding: 20px;
}

.description-panel {
    min-height: 180px;
}

.product-description {
    padding: 20px;
    color: #374151;
    font-size: 14px;
    line-height: 1.75;
}

.product-description :deep(img) {
    max-width: 100%;
    height: auto;
}

.product-description :deep(p) {
    margin-top: 0;
    margin-bottom: 12px;
}

.empty-description {
    padding: 36px 20px;
    color: #9ca3af;
    text-align: center;
}

.image-count {
    color: #6b7280;
    font-size: 13px;
}

.gallery-grid {
    display: grid;
    grid-template-columns:
        repeat(
            auto-fill,
            minmax(150px, 1fr)
        );
    gap: 14px;
    padding: 20px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 0;
    background: #f9fafb;
    padding: 0;
    cursor: pointer;
}

.gallery-item:hover {
    border-color: #16a34a;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-image-label {
    position: absolute;
    right: 0;
    bottom: 0;
    background: #15803d;
    padding: 4px 7px;
    color: #ffffff;
    font-size: 11px;
}

.suspended-panel {
    border: 1px solid #fca5a5;
    background: #fef2f2;
    padding: 18px 20px;
}

.suspended-heading {
    margin-bottom: 16px;
    color: #991b1b;
    font-size: 16px;
    font-weight: 600;
}

.suspended-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.suspended-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.suspended-item strong {
    color: #7f1d1d;
    font-size: 14px;
}

.suspended-reason {
    display: flex;
    grid-column: 1 / -1;
    flex-direction: column;
    gap: 6px;
}

.suspended-reason p {
    margin: 0;
    color: #7f1d1d;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.modal-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(17 24 39 / 55%);
    padding: 20px;
}

.modal {
    width: 100%;
    max-width: 520px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 18px 20px;
}

.modal-title {
    margin: 0;
    color: #111827;
    font-size: 18px;
    font-weight: 600;
}

.modal-description {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.modal-close {
    border: 0;
    background: transparent;
    padding: 0;
    color: #6b7280;
    cursor: pointer;
    font-size: 26px;
    line-height: 1;
}

.suspend-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 20px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-field label {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.form-field textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0;
    padding: 10px 12px;
    color: #111827;
    font: inherit;
    outline: none;
    resize: vertical;
}

.form-field textarea:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.required {
    color: #dc2626;
}

.field-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.field-error {
    margin: 0;
    color: #dc2626;
    font-size: 12px;
}

.character-count {
    margin-left: auto;
    color: #9ca3af;
    font-size: 12px;
}

.warning-box {
    border: 1px solid #fcd34d;
    background: #fffbeb;
    padding: 12px;
    color: #92400e;
    font-size: 13px;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e5e7eb;
    margin: 2px -20px -20px;
    padding: 16px 20px;
}

@media (max-width: 980px) {
    .main-grid,
    .secondary-grid {
        grid-template-columns: 1fr;
    }

    .image-panel {
        max-width: 560px;
    }
}

@media (max-width: 680px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .header-left {
        flex-direction: column;
        gap: 10px;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .button {
        width: 100%;
    }

    .panel-header {
        flex-direction: column;
    }

    .info-grid,
    .system-grid,
    .suspended-grid {
        grid-template-columns: 1fr;
    }

    .suspended-reason {
        grid-column: auto;
    }

    .thumbnail-list {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
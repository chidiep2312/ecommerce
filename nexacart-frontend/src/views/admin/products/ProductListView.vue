<script setup>
import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue'
import { useRouter } from 'vue-router'

import {
    getAdminProducts,
    restoreAdminProduct,
    suspendAdminProduct,
} from '@/api/admin/products'

const router = useRouter()

const products = ref([])
const brands = ref([])
const sellers = ref([])

const loading = ref(false)
const updatingProductId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const showSuspendModal = ref(false)
const selectedProduct = ref(null)
const suspendReason = ref('')
const suspendErrors = ref({})

const filters = ref({
    search: '',
    seller_id: '',
    brand_id: '',
    status: '',
    is_suspended: 0,
    stock_status: '',
    per_page: 10,
    page: 1,
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
})

let searchTimeout = null

const hasProducts = computed(() => {
    return products.value.length > 0
})

const pageNumbers = computed(() => {
    const currentPage =
        pagination.value.current_page

    const lastPage =
        pagination.value.last_page

    const startPage = Math.max(
        1,
        currentPage - 2,
    )

    const endPage = Math.min(
        lastPage,
        currentPage + 2,
    )

    const pages = []

    for (
        let page = startPage;
        page <= endPage;
        page += 1
    ) {
        pages.push(page)
    }

    return pages
})

async function fetchProducts() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminProducts({
            search:
                filters.value.search ||
                undefined,

            seller_id:
                filters.value.seller_id ||
                undefined,

            brand_id:
                filters.value.brand_id ||
                undefined,

            status:
                filters.value.status ||
                undefined,
            is_suspended:
                filters.value.is_suspended ||
                undefined,
            stock_status:
                filters.value.stock_status ||
                undefined,

            per_page:
                filters.value.per_page,

            page:
                filters.value.page,
        })

        const responseData = response.data

        products.value =
            responseData.data ?? []

        brands.value =
            responseData.filters?.brands ??
            brands.value

        sellers.value =
            responseData.filters?.sellers ??
            sellers.value

        pagination.value = {
            current_page:
                responseData.meta
                    ?.current_page ?? 1,

            last_page:
                responseData.meta
                    ?.last_page ?? 1,

            per_page:
                responseData.meta
                    ?.per_page ?? 10,

            total:
                responseData.meta
                    ?.total ?? 0,

            from:
                responseData.meta
                    ?.from ?? 0,

            to:
                responseData.meta
                    ?.to ?? 0,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách sản phẩm.'
    } finally {
        loading.value = false
    }
}

function getProductImage(product) {
    return (
        product.main_image_url ??
        product.main_image?.url ??
        product.images?.find(
            image => image.is_main,
        )?.url ??
        product.images?.[0]?.url ??
        ''
    )
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
    const amount = Number(value ?? 0)

    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
        },
    ).format(amount)
}

function getStatusLabel(product) {
    if (product.is_suspended) {
        return 'Bị admin khóa'
    }

    const labels = {
        active: 'Đang bán',
        inactive: 'Ngừng bán',
    }

    return (
        labels[product.status] ??
        product.status ??
        '—'
    )
}

function getStockLabel(stock) {
    const quantity = Number(stock ?? 0)

    if (quantity <= 0) {
        return 'Hết hàng'
    }

    if (quantity <= 5) {
        return 'Sắp hết'
    }

    return 'Còn hàng'
}

function getStockClass(stock) {
    const quantity = Number(stock ?? 0)

    if (quantity <= 0) {
        return 'stock-empty'
    }

    if (quantity <= 5) {
        return 'stock-low'
    }

    return 'stock-available'
}

function viewProduct(product) {
    router.push({
        name: 'admin-products-show',

        params: {
            id: product.id,
        },
    })
}

function openSuspendModal(product) {
    selectedProduct.value = product
    suspendReason.value = ''
    suspendErrors.value = {}

    errorMessage.value = ''
    successMessage.value = ''

    showSuspendModal.value = true
}

function closeSuspendModal() {
    if (updatingProductId.value !== null) {
        return
    }

    showSuspendModal.value = false
    selectedProduct.value = null
    suspendReason.value = ''
    suspendErrors.value = {}
}

async function submitSuspendProduct() {
    if (!selectedProduct.value) {
        return
    }

    updatingProductId.value =
        selectedProduct.value.id

    errorMessage.value = ''
    successMessage.value = ''
    suspendErrors.value = {}

    try {
        const response =
            await suspendAdminProduct(
                selectedProduct.value.id,
                suspendReason.value,
            )

        const updatedProduct =
            response.data?.data

        const productIndex =
            products.value.findIndex(
                product =>
                    product.id ===
                    selectedProduct.value.id,
            )

        if (productIndex !== -1) {
            products.value[productIndex] = {
                ...products.value[productIndex],
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
        }

        successMessage.value =
            response.data?.message ??
            'Khóa sản phẩm thành công.'

        showSuspendModal.value = false
        selectedProduct.value = null
        suspendReason.value = ''
        suspendErrors.value = {}
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
        updatingProductId.value = null
    }
}

async function restoreProduct(product) {
    const confirmed = window.confirm(
        `Bạn có chắc muốn mở khóa sản phẩm "${product.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    updatingProductId.value = product.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await restoreAdminProduct(
                product.id,
            )

        const updatedProduct =
            response.data?.data

        const productIndex =
            products.value.findIndex(
                item => item.id === product.id,
            )

        if (productIndex !== -1) {
            products.value[productIndex] = {
                ...products.value[productIndex],
                ...updatedProduct,

                is_suspended:
                    updatedProduct
                        ?.is_suspended ??
                    false,

                suspended_reason: null,
                suspended_by: null,
                suspended_at: null,
            }
        }

        successMessage.value =
            response.data?.message ??
            'Mở khóa sản phẩm thành công.'
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể mở khóa sản phẩm.'
    } finally {
        updatingProductId.value = null
    }
}

function resetFilters() {
    filters.value = {
        search: '',
        seller_id: '',
        brand_id: '',
        status: '',
        is_suspended: 0,
        stock_status: '',
        per_page: 10,
        page: 1,
    }

    fetchProducts()
}

function changePage(page) {
    if (
        page < 1 ||
        page > pagination.value.last_page ||
        page === pagination.value.current_page
    ) {
        return
    }

    filters.value.page = page

    fetchProducts()
}

watch(
    () => filters.value.search,
    () => {
        clearTimeout(searchTimeout)

        searchTimeout = setTimeout(() => {
            filters.value.page = 1

            fetchProducts()
        }, 500)
    },
)

watch(
    [
        () => filters.value.seller_id,
        () => filters.value.brand_id,
        () => filters.value.status,
        () => filters.value.stock_status,
        () => filters.value.is_suspended,
        () => filters.value.per_page,
    ],
    () => {
        filters.value.page = 1

        fetchProducts()
    },
)

onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <section class="product-page">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    Quản lý sản phẩm
                </h1>

                <p class="page-description">
                    Theo dõi và kiểm soát sản phẩm
                    của tất cả người bán trên hệ thống.
                </p>
            </div>
        </header>

        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>

        <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
        </div>

        <div class="filter-panel">
            <div class="filter-field search-field">
                <label for="product-search">
                    Tìm kiếm
                </label>

                <input id="product-search" v-model.trim="filters.search" type="search"
                    placeholder="Tên, slug hoặc mã sản phẩm">
            </div>

            <div class="filter-field">
                <label for="product-seller">
                    Người bán
                </label>

                <select id="product-seller" v-model="filters.seller_id">
                    <option value="">
                        Tất cả người bán
                    </option>

                    <option v-for="seller in sellers" :key="seller.id" :value="seller.id">
                        {{ seller.name }}
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="product-brand">
                    Thương hiệu
                </label>

                <select id="product-brand" v-model="filters.brand_id">
                    <option value="">
                        Tất cả thương hiệu
                    </option>

                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                        {{ brand.name }}
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="product-status">
                    Trạng thái
                </label>

                <select id="product-status" v-model="filters.status">
                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option value="active">
                        Đang bán
                    </option>

                    <option value="inactive">
                        Ngừng bán
                    </option>


                </select>
            </div>
            <div class="filter-field">
                <label for="product-suspended">
                    Khóa bởi Admin
                </label>

                <select id="product-suspended" v-model="filters.is_suspended">
                    <option value="">
                        Tất cả
                    </option>

                    <option :value="0">
                        Chưa khóa
                    </option>

                    <option :value="1">
                        Đã khóa
                    </option>
                </select>
            </div>
            <div class="filter-field">
                <label for="product-stock">
                    Tồn kho
                </label>

                <select id="product-stock" v-model="filters.stock_status">
                    <option value="">
                        Tất cả tồn kho
                    </option>

                    <option value="in_stock">
                        Còn hàng
                    </option>

                    <option value="low_stock">
                        Sắp hết
                    </option>

                    <option value="out_of_stock">
                        Hết hàng
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="product-per-page">
                    Số dòng
                </label>

                <select id="product-per-page" v-model.number="filters.per_page">
                    <option :value="10">
                        10
                    </option>

                    <option :value="20">
                        20
                    </option>

                    <option :value="50">
                        50
                    </option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="button" class="button button-secondary" @click="resetFilters">
                    Đặt lại
                </button>
            </div>
        </div>

        <div class="table-card">
            <div v-if="loading" class="table-state">
                Đang tải danh sách sản phẩm...
            </div>

            <div v-else-if="!hasProducts" class="table-state">
                Không tìm thấy sản phẩm phù hợp.
            </div>

            <div v-else class="table-wrapper">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Người bán</th>
                            <th>Thương hiệu</th>
                            <th>Giá bán</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>

                            <th class="action-column">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="product in products" :key="product.id">
                            <td>
                                <div class="product-info">
                                    <div class="product-image">
                                        <img v-if="
                                            getProductImage(
                                                product,
                                            )
                                        " :src="getProductImage(
                                                product,
                                            )
                                                " :alt="product.name" @error="
                                                handleImageError
                                            ">

                                        <div class="image-fallback" :style="{
                                            display:
                                                getProductImage(
                                                    product,
                                                )
                                                    ? 'none'
                                                    : 'flex',
                                        }">
                                            {{
                                                product.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase()
                                            }}
                                        </div>
                                    </div>

                                    <div class="product-content">
                                        <div class="product-name">
                                            {{ product.name }}
                                        </div>

                                        <div class="product-meta">
                                            ID:
                                            {{ product.id }}

                                            <template v-if="product.sku">
                                                · SKU:
                                                {{ product.sku }}
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="seller-cell">
                                    <span class="seller-name">
                                        {{
                                            product.seller
                                                ?.name ??
                                            '—'
                                        }}
                                    </span>

                                    <span class="seller-email">
                                        {{
                                            product.seller
                                                ?.email ??
                                            ''
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                {{
                                    product.brand?.name ??
                                    'Không có'
                                }}
                            </td>

                            <td>
                                <div class="price-cell">
                                    <strong>
                                        {{
                                            formatCurrency(
                                                product.price,
                                            )
                                        }}
                                    </strong>

                                    <span v-if="
                                        product.original_price &&
                                        Number(
                                            product.original_price,
                                        ) >
                                        Number(
                                            product.price,
                                        )
                                    " class="original-price">
                                        {{
                                            formatCurrency(
                                                product.original_price,
                                            )
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="stock-cell">
                                    <strong>
                                        {{ product.stock ?? 0 }}
                                    </strong>

                                    <span class="stock-label" :class="getStockClass(
                                        product.stock,
                                    )
                                        ">
                                        {{
                                            getStockLabel(
                                                product.stock,
                                            )
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="status-badge" :class="{
                                    'status-active':
                                        product.status ===
                                        'active' &&
                                        !product.is_suspended,

                                    'status-inactive':
                                        product.status ===
                                        'inactive' &&
                                        !product.is_suspended,

                                    'status-locked':
                                        product.is_suspended,
                                }">
                                    {{
                                        getStatusLabel(
                                            product,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                {{
                                    product
                                        .created_at_formatted ??
                                    '—'
                                }}
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" class="button-link" @click="
                                        viewProduct(
                                            product,
                                        )
                                        ">
                                        Chi tiết
                                    </button>

                                    <button v-if="
                                        !product.is_suspended
                                    " type="button" class="
                                            button-link
                                            button-danger-link
                                        " :disabled="updatingProductId ===
                                            product.id
                                            " @click="
                                            openSuspendModal(
                                                product,
                                            )
                                            ">
                                        Khóa
                                    </button>

                                    <button v-else type="button" class="
                                            button-link
                                            button-restore
                                        " :disabled="updatingProductId ===
                                            product.id
                                            " @click="
                                            restoreProduct(
                                                product,
                                            )
                                            ">
                                        {{
                                            updatingProductId ===
                                                product.id
                                                ? 'Đang xử lý...'
                                                : 'Mở khóa'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer v-if="
                !loading &&
                pagination.total > 0
            " class="pagination">
                <div class="pagination-info">
                    Hiển thị
                    {{ pagination.from }}
                    –
                    {{ pagination.to }}
                    trong
                    {{ pagination.total }}
                    sản phẩm
                </div>

                <div class="pagination-buttons">
                    <button type="button" class="page-button" :disabled="pagination.current_page === 1
                        " @click="
                            changePage(
                                pagination.current_page - 1,
                            )
                            ">
                        Trước
                    </button>

                    <button v-for="page in pageNumbers" :key="page" type="button" class="page-button" :class="{
                        active:
                            page ===
                            pagination.current_page,
                    }" @click="changePage(page)">
                        {{ page }}
                    </button>

                    <button type="button" class="page-button" :disabled="pagination.current_page ===
                        pagination.last_page
                        " @click="
                            changePage(
                                pagination.current_page + 1,
                            )
                            ">
                        Sau
                    </button>
                </div>
            </footer>
        </div>

        <div v-if="showSuspendModal" class="modal-overlay" @click.self="closeSuspendModal">
            <div class="modal">
                <header class="modal-header">
                    <div>
                        <h2 class="modal-title">
                            Khóa sản phẩm
                        </h2>

                        <p class="modal-description">
                            Sản phẩm:
                            <strong>
                                {{
                                    selectedProduct
                                        ?.name
                                }}
                            </strong>
                        </p>
                    </div>

                    <button type="button" class="modal-close" :disabled="updatingProductId !== null
                        " @click="closeSuspendModal">
                        ×
                    </button>
                </header>

                <form class="suspend-form" @submit.prevent="
                    submitSuspendProduct
                ">
                    <div class="form-field">
                        <label for="suspend-reason">
                            Lý do khóa
                            <span class="required">
                                *
                            </span>
                        </label>

                        <textarea id="suspend-reason" v-model.trim="suspendReason
                            " rows="5" maxlength="1000" placeholder="Nhập lý do khóa sản phẩm" />

                        <div class="field-footer">
                            <p v-if="
                                suspendErrors
                                    .reason
                            " class="field-error">
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
                        Sản phẩm sẽ không còn hiển thị
                        cho khách hàng. Trạng thái do
                        seller thiết lập vẫn được giữ
                        nguyên.
                    </div>

                    <footer class="modal-actions">
                        <button type="button" class="
                                button
                                button-secondary
                            " :disabled="updatingProductId !==
                                null
                                " @click="
                                closeSuspendModal
                            ">
                            Hủy
                        </button>

                        <button type="submit" class="
                                button
                                button-danger
                            " :disabled="updatingProductId !==
                                null ||
                                suspendReason.length <
                                5
                                ">
                            {{
                                updatingProductId !==
                                    null
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
.product-page {
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

.page-title {
    margin: 0;
    color: #111827;
    font-family: Roboto, sans-serif;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
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

.filter-panel {
    display: grid;
    grid-template-columns:
        minmax(260px, 1fr) 180px 180px 170px;
    gap: 16px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 16px;
}

.filter-field,
.form-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.filter-field label,
.form-field label {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.filter-field input,
.filter-field select {
    width: 100%;
    height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    padding: 0 12px;
    color: #111827;
    font: inherit;
    outline: none;
}

.filter-field input:focus,
.filter-field select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.filter-actions {
    display: flex;
    align-items: flex-end;
}

.button {
    height: 40px;
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

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover:not(:disabled) {
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

.table-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.table-wrapper {
    overflow-x: auto;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
}

.product-table th,
.product-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.product-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
}

.product-table td {
    color: #4b5563;
    font-size: 14px;
}

.product-table tbody tr:hover {
    background: #f9fafb;
}

.product-info {
    display: flex;
    min-width: 250px;
    align-items: center;
    gap: 12px;
}

.product-image {
    position: relative;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-fallback {
    position: absolute;
    inset: 0;
    align-items: center;
    justify-content: center;
    color: #15803d;
    font-size: 16px;
    font-weight: 700;
}

.product-content {
    min-width: 0;
}

.product-name {
    max-width: 230px;
    overflow: hidden;
    color: #111827;
    font-weight: 500;
    text-overflow: ellipsis;
}

.product-meta {
    margin-top: 4px;
    color: #9ca3af;
    font-size: 12px;
}

.seller-cell,
.price-cell,
.stock-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.seller-name {
    color: #111827;
    font-weight: 500;
}

.seller-email {
    color: #9ca3af;
    font-size: 12px;
}

.price-cell strong {
    color: #111827;
    font-weight: 600;
}

.original-price {
    color: #9ca3af;
    font-size: 12px;
    text-decoration: line-through;
}

.stock-cell strong {
    color: #111827;
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

.status-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    padding: 4px 8px;
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

.status-locked {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.button-link {
    border: 0;
    background: transparent;
    padding: 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button-link:hover:not(:disabled) {
    text-decoration: underline;
}

.button-link:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.button-danger-link {
    color: #dc2626;
}

.button-restore {
    color: #15803d;
}

.action-column {
    width: 150px;
}

.table-state {
    padding: 48px 20px;
    color: #6b7280;
    text-align: center;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
}

.pagination-info {
    color: #6b7280;
    font-size: 13px;
}

.pagination-buttons {
    display: flex;
    gap: 6px;
}

.page-button {
    min-width: 36px;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #374151;
    cursor: pointer;
}

.page-button:hover:not(:disabled) {
    border-color: #16a34a;
    color: #15803d;
}

.page-button.active {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.page-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
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
    align-items: flex-start;
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

@media (max-width: 1100px) {
    .filter-panel {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

@media (max-width: 760px) {
    .filter-panel {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 520px) {
    .filter-panel {
        grid-template-columns: 1fr;
    }

    .pagination {
        align-items: flex-start;
        flex-direction: column;
    }

    .modal-overlay {
        align-items: flex-start;
        overflow-y: auto;
    }
}
</style>
<script setup>
import {
    onMounted,
    ref,
    watch,
} from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    deleteSellerProduct,
    getSellerProducts,
} from '@/api/seller/products'

const route = useRoute()
const router = useRouter()

const products = ref([])
const loading = ref(false)
const deletingId = ref(null)

const successMessage = ref('')
const errorMessage = ref('')

const filters = ref({
    search: '',
    status: '',
    is_suspended: '',
    stock_status: '',
    page: 1,
    per_page: 15,
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
})

let searchTimer = null

function buildParams() {
    return {
        search:
            filters.value.search ||
            undefined,

        status:
            filters.value.status ||
            undefined,

        is_suspended:
            filters.value.is_suspended === ''
                ? undefined
                : filters.value.is_suspended,

        stock_status:
            filters.value.stock_status ||
            undefined,

        page:
            filters.value.page,

        per_page:
            filters.value.per_page,
    }
}

async function fetchProducts() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getSellerProducts(
                buildParams(),
            )

        products.value =
            response.data?.data ?? []

        pagination.value = {
            current_page:
                response.data?.meta
                    ?.current_page ?? 1,

            last_page:
                response.data?.meta
                    ?.last_page ?? 1,

            per_page:
                response.data?.meta
                    ?.per_page ?? 15,

            total:
                response.data?.meta
                    ?.total ?? 0,

            from:
                response.data?.meta
                    ?.from ?? null,

            to:
                response.data?.meta
                    ?.to ?? null,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách sản phẩm.'
    } finally {
        loading.value = false
    }
}

function applyRouteQuery() {
    filters.value.search =
        route.query.search ?? ''

    filters.value.status =
        route.query.status ?? ''

    filters.value.stock_status =
        route.query.stock_status ?? ''

    filters.value.is_suspended =
        route.query.is_suspended ===
            undefined
            ? ''
            : Number(
                route.query
                    .is_suspended,
            )

    filters.value.page = Number(
        route.query.page ?? 1,
    )

    filters.value.per_page = Number(
        route.query.per_page ?? 15,
    )
}

function updateRouteQuery() {
    router.replace({
        query: {
            search:
                filters.value.search ||
                undefined,

            status:
                filters.value.status ||
                undefined,

            is_suspended:
                filters.value
                    .is_suspended === ''
                    ? undefined
                    : filters.value
                        .is_suspended,

            stock_status:
                filters.value
                    .stock_status ||
                undefined,

            page:
                filters.value.page > 1
                    ? filters.value.page
                    : undefined,

            per_page:
                filters.value.per_page !==
                15
                    ? filters.value.per_page
                    : undefined,
        },
    })
}

function goToCreate() {
    router.push({
        name: 'seller-products-create',
    })
}

function goToDetail(productId) {
    router.push({
        name: 'seller-products-show',
        params: {
            id: productId,
        },
    })
}

function goToEdit(product) {
    if (product.is_suspended) {
        errorMessage.value =
            'Sản phẩm đang bị admin khóa và không thể chỉnh sửa.'

        return
    }

    router.push({
        name: 'seller-products-edit',
        params: {
            id: product.id,
        },
    })
}

async function removeProduct(product) {
    if (product.is_suspended) {
        errorMessage.value =
            'Sản phẩm đang bị admin khóa và không thể xóa.'

        return
    }

    const confirmed = window.confirm(
        `Bạn có chắc muốn xóa sản phẩm "${product.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    deletingId.value = product.id
    successMessage.value = ''
    errorMessage.value = ''

    try {
        const response =
            await deleteSellerProduct(
                product.id,
            )

        successMessage.value =
            response.data?.message ??
            'Xóa sản phẩm thành công.'

        if (
            products.value.length === 1 &&
            filters.value.page > 1
        ) {
            filters.value.page--
        }

        updateRouteQuery()
        await fetchProducts()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể xóa sản phẩm.'
    } finally {
        deletingId.value = null
    }
}

function resetFilters() {
    filters.value = {
        search: '',
        status: '',
        is_suspended: '',
        stock_status: '',
        page: 1,
        per_page: 15,
    }

    updateRouteQuery()
    fetchProducts()
}

function changePage(page) {
    if (
        page < 1 ||
        page >
            pagination.value.last_page ||
        page ===
            pagination.value.current_page
    ) {
        return
    }

    filters.value.page = page

    updateRouteQuery()
    fetchProducts()
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

function getProductImage(product) {
    return (
        product.main_image_url ??
        product.main_image?.url ??
        product.main_image
            ?.image_url ??
        ''
    )
}

function getProductStatus(product) {
    if (product.is_suspended) {
        return {
            label: 'Bị admin khóa',
            className:
                'status-suspended',
        }
    }

    if (
        product.status === 'active'
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
}

function getStockStatus(product) {
    const stock = Number(
        product.stock ?? 0,
    )

    if (stock <= 0) {
        return {
            label: 'Hết hàng',
            className: 'stock-empty',
        }
    }

    if (stock <= 5) {
        return {
            label: `Sắp hết (${stock})`,
            className: 'stock-low',
        }
    }

    return {
        label: `${stock} sản phẩm`,
        className: 'stock-normal',
    }
}

watch(
    () => filters.value.search,
    () => {
        clearTimeout(searchTimer)

        searchTimer = setTimeout(
            () => {
                filters.value.page = 1

                updateRouteQuery()
                fetchProducts()
            },
            500,
        )
    },
)

watch(
    [
        () => filters.value.status,
        () =>
            filters.value
                .is_suspended,
        () =>
            filters.value
                .stock_status,
        () =>
            filters.value.per_page,
    ],
    () => {
        filters.value.page = 1

        updateRouteQuery()
        fetchProducts()
    },
)

onMounted(() => {
    applyRouteQuery()
    fetchProducts()
})
</script>

<template>
    <section class="product-list-page">
        <header class="page-header">
            <div>
                <h2 class="page-title">
                    Sản phẩm của tôi
                </h2>

                <p class="page-description">
                    Quản lý thông tin, giá bán,
                    tồn kho và trạng thái sản phẩm.
                </p>
            </div>

            <button
                type="button"
                class="button button-primary"
                @click="goToCreate"
            >
                <span class="button-icon">
                    +
                </span>

                Thêm sản phẩm
            </button>
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

        <section class="filter-panel">
            <div class="filter-grid">
                <div
                    class="
                        form-group
                        search-group
                    "
                >
                    <label for="search">
                        Tìm kiếm
                    </label>

                    <div class="search-input">
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="m21 20-5.2-5.2a7 7 0 1 0-1 1L20 21l1-1ZM5 10a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z"
                            />
                        </svg>

                        <input
                            id="search"
                            v-model.trim="
                                filters.search
                            "
                            type="text"
                            placeholder="Tên sản phẩm, SKU..."
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">
                        Trạng thái bán
                    </label>

                    <select
                        id="status"
                        v-model="
                            filters.status
                        "
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option value="active">
                            Đang bán
                        </option>

                        <option value="inactive">
                            Ngừng bán
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="admin-status">
                        Trạng thái admin
                    </label>

                    <select
                        id="admin-status"
                        v-model="
                            filters
                                .is_suspended
                        "
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option :value="0">
                            Không bị khóa
                        </option>

                        <option :value="1">
                            Bị admin khóa
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stock-status">
                        Tồn kho
                    </label>

                    <select
                        id="stock-status"
                        v-model="
                            filters
                                .stock_status
                        "
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option value="in_stock">
                            Còn hàng
                        </option>

                        <option value="low_stock">
                            Sắp hết hàng
                        </option>

                        <option value="out_of_stock">
                            Hết hàng
                        </option>
                    </select>
                </div>

                <div class="filter-action">
                    <button
                        type="button"
                        class="
                            button
                            button-secondary
                        "
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>
            </div>
        </section>

        <section class="table-panel">
            <header class="table-header">
                <div>
                    <h3>
                        Danh sách sản phẩm
                    </h3>

                    <p>
                        Có
                        <strong>
                            {{
                                pagination.total
                            }}
                        </strong>
                        sản phẩm
                    </p>
                </div>

                <div class="per-page">
                    <label for="per-page">
                        Hiển thị
                    </label>

                    <select
                        id="per-page"
                        v-model.number="
                            filters.per_page
                        "
                    >
                        <option :value="10">
                            10
                        </option>

                        <option :value="15">
                            15
                        </option>

                        <option :value="25">
                            25
                        </option>

                        <option :value="50">
                            50
                        </option>
                    </select>
                </div>
            </header>

            <div
                v-if="loading"
                class="table-state"
            >
                <span class="loader" />

                <p>
                    Đang tải danh sách sản phẩm...
                </p>
            </div>

            <div
                v-else-if="
                    products.length === 0
                "
                class="table-state"
            >
                <div class="empty-icon">
                    SP
                </div>

                <h3>
                    Không tìm thấy sản phẩm
                </h3>

                <p>
                    Thử thay đổi bộ lọc hoặc thêm
                    sản phẩm mới.
                </p>

                <button
                    type="button"
                    class="button button-primary"
                    @click="goToCreate"
                >
                    Thêm sản phẩm
                </button>
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá bán</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th class="action-column">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="
                                product in products
                            "
                            :key="product.id"
                        >
                            <td>
                                <div class="product-cell">
                                    <div class="product-image">
                                        <img
                                            v-if="
                                                getProductImage(
                                                    product,
                                                )
                                            "
                                            :src="
                                                getProductImage(
                                                    product,
                                                )
                                            "
                                            :alt="
                                                product.name
                                            "
                                        >

                                        <span v-else>
                                            SP
                                        </span>
                                    </div>

                                    <div class="product-information">
                                        <button
                                            type="button"
                                            class="product-name"
                                            @click="
                                                goToDetail(
                                                    product.id,
                                                )
                                            "
                                        >
                                            {{
                                                product.name
                                            }}
                                        </button>

                                        <span class="product-sku">
                                            SKU:
                                            {{
                                                product.sku ??
                                                'Chưa có'
                                            }}
                                        </span>

                                        <span
                                            v-if="
                                                product
                                                    .is_suspended &&
                                                product
                                                    .suspension_reason
                                            "
                                            class="suspension-reason"
                                        >
                                            Lý do:
                                            {{
                                                product
                                                    .suspension_reason
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="category-cell">
                                    <strong>
                                        {{
                                            product
                                                .category
                                                ?.name ??
                                            'Chưa phân loại'
                                        }}
                                    </strong>

                                    <span>
                                        {{
                                            product
                                                .brand
                                                ?.name ??
                                            'Không thương hiệu'
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="price-cell">
                                    <strong>
                                        {{
                                            formatCurrency(
                                                product
                                                    .price,
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
                                    >
                                        {{
                                            formatCurrency(
                                                product
                                                    .original_price,
                                            )
                                        }}
                                    </del>
                                </div>
                            </td>

                            <td>
                                <span
                                    class="stock-label"
                                    :class="
                                        getStockStatus(
                                            product,
                                        )
                                            .className
                                    "
                                >
                                    {{
                                        getStockStatus(
                                            product,
                                        ).label
                                    }}
                                </span>
                            </td>

                            <td>
                                <span
                                    class="status-badge"
                                    :class="
                                        getProductStatus(
                                            product,
                                        )
                                            .className
                                    "
                                >
                                    {{
                                        getProductStatus(
                                            product,
                                        ).label
                                    }}
                                </span>
                            </td>

                            <td>
                                <div class="action-menu">
                                    <button
                                        type="button"
                                        class="
                                            action-button
                                            action-view
                                        "
                                        title="Xem chi tiết"
                                        @click="
                                            goToDetail(
                                                product.id,
                                            )
                                        "
                                    >
                                        Xem
                                    </button>

                                    <button
                                        type="button"
                                        class="
                                            action-button
                                            action-edit
                                        "
                                        :disabled="
                                            product
                                                .is_suspended
                                        "
                                        title="Chỉnh sửa"
                                        @click="
                                            goToEdit(
                                                product,
                                            )
                                        "
                                    >
                                        Sửa
                                    </button>

                                    <button
                                        type="button"
                                        class="
                                            action-button
                                            action-delete
                                        "
                                        :disabled="
                                            product
                                                .is_suspended ||
                                            deletingId ===
                                                product.id
                                        "
                                        title="Xóa sản phẩm"
                                        @click="
                                            removeProduct(
                                                product,
                                            )
                                        "
                                    >
                                        {{
                                            deletingId ===
                                            product.id
                                                ? 'Đang xóa'
                                                : 'Xóa'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer
                v-if="
                    !loading &&
                    pagination.total > 0
                "
                class="pagination"
            >
                <p class="pagination-information">
                    Hiển thị
                    <strong>
                        {{ pagination.from }}
                    </strong>
                    -
                    <strong>
                        {{ pagination.to }}
                    </strong>
                    trong
                    <strong>
                        {{
                            pagination.total
                        }}
                    </strong>
                    sản phẩm
                </p>

                <div class="pagination-actions">
                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination
                                .current_page <= 1
                        "
                        @click="
                            changePage(
                                pagination
                                    .current_page -
                                    1,
                            )
                        "
                    >
                        Trước
                    </button>

                    <span class="page-indicator">
                        Trang
                        <strong>
                            {{
                                pagination
                                    .current_page
                            }}
                        </strong>
                        /
                        {{
                            pagination.last_page
                        }}
                    </span>

                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination
                                .current_page >=
                            pagination
                                .last_page
                        "
                        @click="
                            changePage(
                                pagination
                                    .current_page +
                                    1,
                            )
                        "
                    >
                        Sau
                    </button>
                </div>
            </footer>
        </section>
    </section>
</template>

<style scoped>
.product-list-page {
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
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 7px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button-primary {
    border-color: #15803d;
    background: #15803d;
    color: #ffffff;
}

.button-primary:hover {
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

.button-icon {
    font-size: 19px;
    font-weight: 400;
    line-height: 1;
}

.alert {
    border: 1px solid;
    padding: 12px 15px;
    font-size: 13px;
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

.filter-panel,
.table-panel {
    border: 1px solid #e1e7e3;
    background: #ffffff;
}

.filter-panel {
    padding: 18px;
}

.filter-grid {
    display: grid;
    grid-template-columns:
        minmax(260px, 2fr)
        repeat(
            3,
            minmax(150px, 1fr)
        )
        auto;
    gap: 14px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label,
.per-page label {
    color: #4b5563;
    font-size: 12px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.per-page select {
    width: 100%;
    min-height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    padding: 0 11px;
    color: #111827;
    font: inherit;
    font-size: 13px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus,
.per-page select:focus {
    border-color: #16a34a;
}

.search-input {
    position: relative;
}

.search-input svg {
    position: absolute;
    top: 50%;
    left: 11px;
    width: 17px;
    height: 17px;
    fill: #9ca3af;
    transform: translateY(-50%);
}

.search-input input {
    padding-left: 38px;
}

.table-header {
    display: flex;
    min-height: 68px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 18px;
}

.table-header h3 {
    margin: 0;
    color: #111827;
    font-size: 15px;
    font-weight: 600;
}

.table-header p {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.per-page {
    display: flex;
    align-items: center;
    gap: 9px;
}

.per-page select {
    width: 72px;
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
    border-bottom: 1px solid #eef0ef;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
}

.product-table th {
    background: #fafbfa;
    color: #6b7280;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2px;
    white-space: nowrap;
}

.product-table td {
    color: #374151;
    font-size: 13px;
}

.product-cell {
    display: flex;
    min-width: 290px;
    align-items: center;
    gap: 12px;
}

.product-image {
    display: flex;
    width: 58px;
    height: 58px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #f3f4f6;
    color: #9ca3af;
    font-size: 11px;
    font-weight: 600;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-information {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 5px;
}

.product-name {
    max-width: 260px;
    overflow: hidden;
    border: 0;
    background: transparent;
    padding: 0;
    color: #111827;
    cursor: pointer;
    font: inherit;
    font-weight: 600;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-name:hover {
    color: #15803d;
}

.product-sku {
    color: #9ca3af;
    font-size: 11px;
}

.suspension-reason {
    max-width: 280px;
    overflow: hidden;
    color: #dc2626;
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.category-cell,
.price-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.category-cell {
    min-width: 130px;
}

.category-cell strong {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.category-cell span {
    color: #9ca3af;
    font-size: 11px;
}

.price-cell {
    min-width: 120px;
}

.price-cell strong {
    color: #15803d;
    font-size: 14px;
    white-space: nowrap;
}

.price-cell del {
    color: #9ca3af;
    font-size: 11px;
    white-space: nowrap;
}

.stock-label,
.status-badge {
    display: inline-flex;
    border: 1px solid;
    padding: 5px 8px;
    font-size: 11px;
    white-space: nowrap;
}

.stock-normal {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.stock-low {
    border-color: #fcd34d;
    background: #fffbeb;
    color: #92400e;
}

.stock-empty {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
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

.action-column {
    width: 1%;
}

.action-menu {
    display: flex;
    min-width: 120px;
    gap: 10px;
    white-space: nowrap;
}

.action-button {
    border: 0;
    background: transparent;
    padding: 3px 0;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
    font-weight: 500;
}

.action-button:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.action-view {
    color: #374151;
}

.action-edit {
    color: #15803d;
}

.action-delete {
    color: #dc2626;
}

.table-state {
    display: flex;
    min-height: 320px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 40px 20px;
    color: #6b7280;
    text-align: center;
}

.table-state h3 {
    margin: 14px 0 0;
    color: #374151;
    font-size: 16px;
}

.table-state p {
    margin: 7px 0 18px;
    font-size: 13px;
}

.empty-icon {
    display: flex;
    width: 55px;
    height: 55px;
    align-items: center;
    justify-content: center;
    background: #f0fdf4;
    color: #15803d;
    font-size: 13px;
    font-weight: 700;
}

.loader {
    width: 32px;
    height: 32px;
    border: 3px solid #dcfce7;
    border-top-color: #15803d;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.pagination {
    display: flex;
    min-height: 66px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 0 18px;
}

.pagination-information {
    margin: 0;
    color: #6b7280;
    font-size: 12px;
}

.pagination-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-button {
    min-height: 34px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    padding: 0 12px;
    color: #374151;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
}

.page-button:hover:not(:disabled) {
    border-color: #16a34a;
    color: #15803d;
}

.page-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.page-indicator {
    color: #6b7280;
    font-size: 12px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .search-group {
        grid-column: span 2;
    }

    .filter-action {
        display: flex;
        justify-content: flex-end;
    }
}

@media (max-width: 680px) {
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-header .button-primary {
        width: 100%;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .search-group {
        grid-column: auto;
    }

    .filter-action,
    .filter-action .button {
        width: 100%;
    }

    .table-header {
        align-items: flex-start;
        flex-direction: column;
        padding-top: 15px;
        padding-bottom: 15px;
    }

    .pagination {
        align-items: flex-start;
        flex-direction: column;
        padding-top: 16px;
        padding-bottom: 16px;
    }

    .pagination-actions {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
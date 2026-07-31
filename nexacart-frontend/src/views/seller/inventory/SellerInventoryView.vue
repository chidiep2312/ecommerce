<script setup>
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    getSellerInventory,
    updateProductStock,
} from '@/api/seller/inventory'

const products = ref([])
const pagination = ref(null)

const isLoading = ref(false)
const isSubmitting = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const search = ref('')
const selectedStockStatus = ref('')
const currentPage = ref(1)

const selectedProduct = ref(null)
const isModalOpen = ref(false)

const form = reactive({
    type: 'import',
    quantity: 1,
})

let searchTimeout = null

const statistics = computed(() => {
    return {
        totalProducts:
            pagination.value?.total ??
            products.value.length,

        totalStock:
            products.value.reduce(
                (total, product) =>
                    total +
                    Number(
                        product.stock ?? 0,
                    ),
                0,
            ),

        lowStock:
            products.value.filter(
                product =>
                    Number(product.stock) >
                        0 &&
                    Number(product.stock) <=
                        5,
            ).length,

        outOfStock:
            products.value.filter(
                product =>
                    Number(product.stock) ===
                    0,
            ).length,
    }
})

async function fetchInventory(page = 1) {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getSellerInventory({
                page,
                search:
                    search.value.trim() ||
                    undefined,

                stock_status:
                    selectedStockStatus.value ||
                    undefined,

                per_page: 15,
            })

        products.value = Array.isArray(
            response.data?.data,
        )
            ? response.data.data
            : []

        pagination.value =
            response.data?.meta ?? null

        currentPage.value =
            pagination.value?.current_page ??
            page
    } catch (error) {
        console.error(
            'Không thể tải tồn kho:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách tồn kho.'
    } finally {
        isLoading.value = false
    }
}

function handleSearch() {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        fetchInventory(1)
    }, 400)
}

function handleStatusChange() {
    fetchInventory(1)
}

function openStockModal(product) {
    selectedProduct.value = product

    form.type = 'import'
    form.quantity = 1

    errorMessage.value = ''
    successMessage.value = ''

    isModalOpen.value = true
}

function closeStockModal() {
    if (isSubmitting.value) {
        return
    }

    isModalOpen.value = false
    selectedProduct.value = null
}

function handleTypeChange() {
    if (form.type === 'import') {
        form.quantity = 1
        return
    }

    form.quantity = Number(
        selectedProduct.value?.stock ?? 0,
    )
}

async function submitStockUpdate() {
    if (
        !selectedProduct.value ||
        isSubmitting.value
    ) {
        return
    }

    const quantity = Number(
        form.quantity,
    )

    if (
        !Number.isInteger(quantity) ||
        quantity < 0
    ) {
        errorMessage.value =
            'Số lượng phải là số nguyên không âm.'

        return
    }

    if (
        form.type === 'import' &&
        quantity <= 0
    ) {
        errorMessage.value =
            'Số lượng nhập thêm phải lớn hơn 0.'

        return
    }

    const actionText =
        form.type === 'import'
            ? `nhập thêm ${quantity} sản phẩm`
            : `điều chỉnh tồn kho thành ${quantity}`

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${actionText}?`,
    )

    if (!confirmed) {
        return
    }

    isSubmitting.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await updateProductStock(
                selectedProduct.value.id,
                {
                    type: form.type,
                    quantity,
                },
            )

        const updatedProduct =
            response.data?.data?.data ??
            response.data?.data

        const productIndex =
            products.value.findIndex(
                product =>
                    product.id ===
                    selectedProduct.value.id,
            )

        if (
            productIndex !== -1 &&
            updatedProduct
        ) {
            products.value[
                productIndex
            ] = updatedProduct
        }

        successMessage.value =
            response.data?.message ??
            'Cập nhật tồn kho thành công.'

        isModalOpen.value = false
        selectedProduct.value = null
    } catch (error) {
        console.error(
            'Không thể cập nhật tồn kho:',
            error,
        )

        const validationErrors =
            Object.values(
                error.response?.data
                    ?.errors ?? {},
            )
                .flat()
                .join(' ')

        errorMessage.value =
            validationErrors ||
            error.response?.data?.message ||
            'Không thể cập nhật tồn kho.'
    } finally {
        isSubmitting.value = false
    }
}

function changePage(page) {
    const lastPage =
        pagination.value?.last_page ?? 1

    if (
        page < 1 ||
        page > lastPage ||
        isLoading.value
    ) {
        return
    }

    fetchInventory(page)
}

function getStockStatus(product) {
    const stock = Number(
        product.stock ?? 0,
    )

    if (stock === 0) {
        return {
            label: 'Hết hàng',
            className: 'stock-out',
        }
    }

    if (stock <= 5) {
        return {
            label: 'Sắp hết',
            className: 'stock-low',
        }
    }

    return {
        label: 'Còn hàng',
        className: 'stock-available',
    }
}

function getProductImage(product) {
    return (
        product.main_image?.url ??
        product.image_url ??
        ''
    )
}

function formatCurrency(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
        },
    ).format(Number(value ?? 0))
}

onMounted(() => {
    fetchInventory()
})
</script>

<template>
    <div class="inventory-page">
        <header class="page-header">
            <div>
                <p class="page-eyebrow">
                    QUẢN LÝ BÁN HÀNG
                </p>

                <h1>Quản lý tồn kho</h1>

                <p class="page-description">
                    Theo dõi và cập nhật số lượng
                    sản phẩm hiện có trong kho.
                </p>
            </div>

            <button
                type="button"
                class="refresh-button"
                :disabled="isLoading"
                @click="
                    fetchInventory(
                        currentPage,
                    )
                "
            >
                Làm mới
            </button>
        </header>

        <section class="statistics-grid">
            <article class="stat-card">
                <span>Tổng sản phẩm</span>

                <strong>
                    {{
                        statistics.totalProducts
                    }}
                </strong>
            </article>

            <article class="stat-card">
                <span>
                    Tổng tồn trên trang
                </span>

                <strong>
                    {{ statistics.totalStock }}
                </strong>
            </article>

            <article class="stat-card">
                <span>Sản phẩm sắp hết</span>

                <strong>
                    {{ statistics.lowStock }}
                </strong>
            </article>

            <article class="stat-card">
                <span>Sản phẩm hết hàng</span>

                <strong>
                    {{ statistics.outOfStock }}
                </strong>
            </article>
        </section>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="
                errorMessage &&
                !isModalOpen
            "
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <section class="inventory-panel">
            <div class="toolbar">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Tìm theo tên hoặc SKU"
                    @input="handleSearch"
                >

                <select
                    v-model="
                        selectedStockStatus
                    "
                    @change="
                        handleStatusChange
                    "
                >
                    <option value="">
                        Tất cả tồn kho
                    </option>

                    <option value="in_stock">
                        Còn hàng
                    </option>

                    <option value="low_stock">
                        Sắp hết hàng
                    </option>

                    <option
                        value="out_of_stock"
                    >
                        Hết hàng
                    </option>
                </select>
            </div>

            <div
                v-if="isLoading"
                class="state-box"
            >
                Đang tải dữ liệu tồn kho...
            </div>

            <div
                v-else-if="
                    products.length === 0
                "
                class="state-box"
            >
                Không có sản phẩm phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>SKU</th>
                            <th>Danh mục</th>
                            <th>Giá bán</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="
                                product in
                                products
                            "
                            :key="product.id"
                        >
                            <td>
                                <div
                                    class="product-cell"
                                >
                                    <div
                                        class="product-image"
                                    >
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

                                    <div>
                                        <strong>
                                            {{
                                                product.name
                                            }}
                                        </strong>

                                        <span>
                                            {{
                                                product.brand
                                                    ?.name ??
                                                'Không có thương hiệu'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{
                                    product.sku ??
                                    '--'
                                }}
                            </td>

                            <td>
                                {{
                                    product.category
                                        ?.name ??
                                    '--'
                                }}
                            </td>

                            <td class="price-cell">
                                {{
                                    formatCurrency(
                                        product.sale_price ??
                                        product.price,
                                    )
                                }}
                            </td>

                            <td>
                                <strong
                                    class="stock-number"
                                >
                                    {{ product.stock }}
                                </strong>
                            </td>

                            <td>
                                <span
                                    class="stock-badge"
                                    :class="
                                        getStockStatus(
                                            product,
                                        ).className
                                    "
                                >
                                    {{
                                        getStockStatus(
                                            product,
                                        ).label
                                    }}
                                </span>
                            </td>

                            <td class="action-cell">
                                <button
                                    type="button"
                                    class="update-button"
                                    @click="
                                        openStockModal(
                                            product,
                                        )
                                    "
                                >
                                    Cập nhật kho
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="
                    pagination &&
                    pagination.last_page > 1
                "
                class="pagination"
            >
                <button
                    type="button"
                    :disabled="
                        currentPage <= 1
                    "
                    @click="
                        changePage(
                            currentPage - 1,
                        )
                    "
                >
                    Trước
                </button>

                <span>
                    Trang
                    {{ currentPage }}
                    /
                    {{
                        pagination.last_page
                    }}
                </span>

                <button
                    type="button"
                    :disabled="
                        currentPage >=
                        pagination.last_page
                    "
                    @click="
                        changePage(
                            currentPage + 1,
                        )
                    "
                >
                    Sau
                </button>
            </div>
        </section>

        <div
            v-if="isModalOpen"
            class="modal-backdrop"
            @click.self="closeStockModal"
        >
            <section class="stock-modal">
                <header class="modal-header">
                    <div>
                        <p>CẬP NHẬT TỒN KHO</p>

                        <h2>
                            {{
                                selectedProduct
                                    ?.name
                            }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="close-button"
                        @click="
                            closeStockModal
                        "
                    >
                        ×
                    </button>
                </header>

                <div class="current-stock">
                    <span>Tồn kho hiện tại</span>

                    <strong>
                        {{
                            selectedProduct
                                ?.stock ?? 0
                        }}
                    </strong>
                </div>

                <div
                    v-if="
                        errorMessage &&
                        isModalOpen
                    "
                    class="alert alert-error"
                >
                    {{ errorMessage }}
                </div>

                <form
                    @submit.prevent="
                        submitStockUpdate
                    "
                >
                    <div class="form-group">
                        <label>
                            Loại cập nhật
                        </label>

                        <select
                            v-model="form.type"
                            :disabled="
                                isSubmitting
                            "
                            @change="
                                handleTypeChange
                            "
                        >
                            <option
                                value="import"
                            >
                                Nhập thêm hàng
                            </option>

                            <option
                                value="adjustment"
                            >
                                Điều chỉnh tồn kho
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">
                            {{
                                form.type ===
                                'import'
                                    ? 'Số lượng nhập thêm'
                                    : 'Số lượng tồn mới'
                            }}
                        </label>

                        <input
                            id="quantity"
                            v-model.number="
                                form.quantity
                            "
                            type="number"
                            min="0"
                            step="1"
                            :disabled="
                                isSubmitting
                            "
                        >

                        <small>
                            <template
                                v-if="
                                    form.type ===
                                    'import'
                                "
                            >
                                Hệ thống sẽ cộng số
                                lượng này vào tồn kho
                                hiện tại.
                            </template>

                            <template v-else>
                                Hệ thống sẽ thay thế
                                tồn kho hiện tại bằng
                                số lượng mới.
                            </template>
                        </small>
                    </div>

                    <div
                        class="stock-preview"
                    >
                        <span>
                            Tồn kho sau cập nhật
                        </span>

                        <strong>
                            {{
                                form.type ===
                                'import'
                                    ? Number(
                                        selectedProduct
                                            ?.stock ??
                                            0,
                                    ) +
                                      Number(
                                          form.quantity ||
                                          0,
                                      )
                                    : Number(
                                        form.quantity ||
                                        0,
                                    )
                            }}
                        </strong>
                    </div>

                    <footer
                        class="modal-actions"
                    >
                        <button
                            type="button"
                            class="cancel-button"
                            :disabled="
                                isSubmitting
                            "
                            @click="
                                closeStockModal
                            "
                        >
                            Đóng
                        </button>

                        <button
                            type="submit"
                            class="submit-button"
                            :disabled="
                                isSubmitting
                            "
                        >
                            {{
                                isSubmitting
                                    ? 'Đang cập nhật...'
                                    : 'Xác nhận cập nhật'
                            }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </div>
</template>

<style scoped>
.inventory-page {
    min-height: 100%;
    padding: 28px;
    background: #f5f7f6;
    color: #172019;
    font-family: Roboto, Arial, sans-serif;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 24px;
}

.page-eyebrow {
    margin: 0 0 8px;
    color: #267149;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h1 {
    margin: 0;
    font-size: 30px;
}

.page-description {
    margin: 8px 0 0;
    color: #68756d;
    font-size: 14px;
}

.refresh-button,
.update-button,
.pagination button,
.cancel-button,
.submit-button {
    min-height: 40px;
    border: 1px solid #cdd7d0;
    padding: 0 16px;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.refresh-button,
.update-button,
.pagination button,
.cancel-button {
    background: #ffffff;
    color: #1f633f;
}

.submit-button {
    border-color: #1f6844;
    background: #1f6844;
    color: #ffffff;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.statistics-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    border: 1px solid #dce4df;
    background: #ffffff;
    padding: 20px;
}

.stat-card span {
    display: block;
    margin-bottom: 12px;
    color: #6c7971;
    font-size: 13px;
}

.stat-card strong {
    color: #17432d;
    font-size: 28px;
}

.inventory-panel {
    border: 1px solid #dce4df;
    background: #ffffff;
}

.toolbar {
    display: grid;
    grid-template-columns:
        minmax(280px, 1fr)
        220px;
    gap: 12px;
    padding: 18px;
    border-bottom: 1px solid #e3e9e5;
}

.toolbar input,
.toolbar select,
.form-group input,
.form-group select {
    width: 100%;
    height: 44px;
    box-sizing: border-box;
    border: 1px solid #cdd7d0;
    background: #ffffff;
    padding: 0 13px;
    color: #172019;
    font: inherit;
    outline: none;
}

.toolbar input:focus,
.toolbar select:focus,
.form-group input:focus,
.form-group select:focus {
    border-color: #267149;
}

.table-wrapper {
    overflow-x: auto;
}

.inventory-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

.inventory-table th,
.inventory-table td {
    padding: 15px 18px;
    border-bottom: 1px solid #e7ece8;
    text-align: left;
    vertical-align: middle;
}

.inventory-table th {
    background: #f7f9f8;
    color: #69766e;
    font-size: 12px;
    text-transform: uppercase;
}

.product-cell {
    display: flex;
    align-items: center;
    gap: 13px;
}

.product-cell > div:last-child {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.product-cell span {
    color: #718078;
    font-size: 12px;
}

.product-image {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 54px;
    height: 54px;
    border: 1px solid #dce4df;
    background: #f2f5f3;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.price-cell,
.stock-number {
    white-space: nowrap;
}

.stock-number {
    font-size: 17px;
}

.stock-badge {
    display: inline-flex;
    min-height: 28px;
    align-items: center;
    border: 1px solid;
    padding: 0 10px;
    font-size: 12px;
    font-weight: 700;
}

.stock-available {
    border-color: #9cc8aa;
    background: #edf8f1;
    color: #246640;
}

.stock-low {
    border-color: #e2c774;
    background: #fff8dd;
    color: #755700;
}

.stock-out {
    border-color: #dfb0b0;
    background: #fff0f0;
    color: #922929;
}

.action-cell {
    text-align: right;
}

.state-box {
    padding: 65px 20px;
    color: #69766e;
    text-align: center;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    padding: 18px;
}

.alert {
    margin-bottom: 18px;
    border: 1px solid;
    padding: 14px 16px;
}

.alert-success {
    border-color: #9bc6a9;
    background: #edf8f1;
    color: #21643f;
}

.alert-error {
    border-color: #dfb0b0;
    background: #fff1f1;
    color: #942828;
}

.modal-backdrop {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgb(12 20 15 / 55%);
}

.stock-modal {
    width: min(100%, 520px);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    border: 1px solid #ccd7cf;
    background: #ffffff;
    padding: 24px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e3e9e5;
}

.modal-header p {
    margin: 0 0 6px;
    color: #267149;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.close-button {
    width: 36px;
    height: 36px;
    border: 1px solid #d4ddd7;
    background: #ffffff;
    color: #516057;
    font-size: 24px;
    cursor: pointer;
}

.current-stock,
.stock-preview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 18px 0;
    background: #f3f7f4;
    padding: 16px;
}

.current-stock span,
.stock-preview span {
    color: #637168;
    font-size: 14px;
}

.current-stock strong,
.stock-preview strong {
    color: #1b6540;
    font-size: 24px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
}

.form-group small {
    display: block;
    margin-top: 7px;
    color: #718078;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 18px;
    border-top: 1px solid #e3e9e5;
}

@media (max-width: 1000px) {
    .statistics-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .inventory-page {
        padding: 18px;
    }

    .page-header {
        flex-direction: column;
    }

    .refresh-button {
        width: 100%;
    }

    .statistics-grid,
    .toolbar {
        grid-template-columns: 1fr;
    }

    .modal-actions {
        flex-direction: column-reverse;
    }

    .modal-actions button {
        width: 100%;
    }
}
</style>
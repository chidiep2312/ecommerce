<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue'
import { useRouter } from 'vue-router'

import {
    getSellerVoucherSummary,
    getSellerVoucherUsages,
} from '@/api/seller/voucher'

const router = useRouter()

const usages = ref([])
const pagination = ref(null)

const isLoading = ref(false)
const isSummaryLoading = ref(false)
const errorMessage = ref('')

const filters = reactive({
    search: '',
    order_status: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 15,
})

const summary = reactive({
    usage_count: 0,
    total_discount: 0,
    unique_vouchers: 0,
    unique_customers: 0,
})

let searchTimer = null

const hasUsages = computed(() => {
    return usages.value.length > 0
})

const currentPage = computed(() => {
    return Number(
        pagination.value?.current_page ?? 1,
    )
})

const lastPage = computed(() => {
    return Number(
        pagination.value?.last_page ?? 1,
    )
})

const resultFrom = computed(() => {
    return Number(
        pagination.value?.from ?? 0,
    )
})

const resultTo = computed(() => {
    return Number(
        pagination.value?.to ?? 0,
    )
})

const totalResults = computed(() => {
    return Number(
        pagination.value?.total ?? 0,
    )
})

async function fetchUsages() {
    isLoading.value = true
    errorMessage.value = ''

    try {
      
        const response =
            await getSellerVoucherUsages({
                page: filters.page,
                per_page: filters.per_page,

                search:
                    filters.search.trim() ||
                    undefined,

                order_status:
                    filters.order_status ||
                    undefined,

                date_from:
                    filters.date_from ||
                    undefined,

                date_to:
                    filters.date_to ||
                    undefined,
            })

        usages.value =
            response.data?.data ?? []

        pagination.value =
            response.data?.meta ?? null
            
    } catch (error) {
          
        usages.value = []
        pagination.value = null

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách voucher.'
    } finally {
        isLoading.value = false
    }
}

async function fetchSummary() {
    isSummaryLoading.value = true

    try {
        const response =
            await getSellerVoucherSummary({
                search:
                    filters.search.trim() ||
                    undefined,

                order_status:
                    filters.order_status ||
                    undefined,

                date_from:
                    filters.date_from ||
                    undefined,

                date_to:
                    filters.date_to ||
                    undefined,
            })

        Object.assign(
            summary,
            response.data?.data ?? {
                usage_count: 0,
                total_discount: 0,
                unique_vouchers: 0,
                unique_customers: 0,
            },
        )
    } catch (error) {
        console.error(
            'Không thể tải thống kê voucher:',
            error,
        )
    } finally {
        isSummaryLoading.value = false
    }
}

async function fetchPageData() {
    await Promise.all([
        fetchUsages(),
        fetchSummary(),
    ])
}

function applyFilters() {
    if (
        filters.date_from &&
        filters.date_to &&
        filters.date_from > filters.date_to
    ) {
        errorMessage.value =
            'Ngày bắt đầu không được lớn hơn ngày kết thúc.'

        return
    }

    filters.page = 1
    fetchPageData()
}

function handleSearch() {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(() => {
        filters.page = 1
        fetchPageData()
    }, 400)
}

function resetFilters() {
    filters.search = ''
    filters.order_status = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.page = 1
    filters.per_page = 15

    fetchPageData()
}

function changePage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return
    }

    filters.page = page
    fetchUsages()
}

function changePerPage() {
    filters.page = 1
    fetchUsages()
}

function openOrder(orderId) {
    if (!orderId) {
        return
    }

    router.push({
        name: 'seller-orders-detail',
        params: {
            id: orderId,
        },
    })
}


function formatCurrency(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(Number(value ?? 0))
}

function formatNumber(value) {
    return new Intl.NumberFormat(
        'vi-VN',
    ).format(Number(value ?? 0))
}

function formatDate(value) {
    if (!value) {
        return '--'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return '--'
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

function getOrderStatusLabel(status) {
    const labels = {
        pending: 'Chờ xác nhận',
        confirmed: 'Đã xác nhận',
        processing: 'Đang xử lý',
        shipping: 'Đang giao',
        delivered: 'Đã giao',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy',
        refunded: 'Đã hoàn tiền',
    }

    return labels[status] ?? status ?? '--'
}

function getOrderStatusClass(status) {
    return [
        'status-badge',
        `status-badge--${status ?? 'unknown'}`,
    ]
}

function getVoucherTypeLabel(voucher) {
    if (!voucher) {
        return '--'
    }

    if (voucher.type === 'percentage') {
        return `Giảm ${formatNumber(voucher.value)}%`
    }

    if (voucher.type === 'fixed') {
        return `Giảm ${formatCurrency(voucher.value)}`
    }

    return '--'
}

onMounted(() => {
    fetchPageData()
})

onBeforeUnmount(() => {
    clearTimeout(searchTimer)
})
</script>

<template>
    <div class="seller-voucher-page">
        <header class="page-header">
            <div>
                <p class="page-eyebrow">
                    QUẢN LÝ BÁN HÀNG
                </p>

                <h1>Đơn hàng sử dụng voucher</h1>

                <p class="page-description">
                    Theo dõi các voucher toàn hệ thống
                    đã được áp dụng vào đơn hàng thuộc
                    cửa hàng của bạn.
                </p>
            </div>

            <button
                class="refresh-button"
                type="button"
                :disabled="
                    isLoading ||
                    isSummaryLoading
                "
                @click="fetchPageData"
            >
                Làm mới dữ liệu
            </button>
        </header>

        <section class="summary-grid">
            <article class="summary-card">
                <span class="summary-label">
                    Lượt sử dụng
                </span>

                <strong class="summary-value">
                    {{
                        isSummaryLoading
                            ? '...'
                            : formatNumber(
                                summary.usage_count,
                            )
                    }}
                </strong>

                <span class="summary-note">
                    Tổng số đơn dùng voucher
                </span>
            </article>

            <article class="summary-card">
                <span class="summary-label">
                    Tổng tiền giảm
                </span>

                <strong class="summary-value">
                    {{
                        isSummaryLoading
                            ? '...'
                            : formatCurrency(
                                summary.total_discount,
                            )
                    }}
                </strong>

                <span class="summary-note">
                    Tổng ưu đãi trên đơn hàng
                </span>
            </article>

            <article class="summary-card">
                <span class="summary-label">
                    Voucher khác nhau
                </span>

                <strong class="summary-value">
                    {{
                        isSummaryLoading
                            ? '...'
                            : formatNumber(
                                summary.unique_vouchers,
                            )
                    }}
                </strong>

                <span class="summary-note">
                    Số mã voucher đã xuất hiện
                </span>
            </article>

            <article class="summary-card">
                <span class="summary-label">
                    Khách hàng sử dụng
                </span>

                <strong class="summary-value">
                    {{
                        isSummaryLoading
                            ? '...'
                            : formatNumber(
                                summary.unique_customers,
                            )
                    }}
                </strong>

                <span class="summary-note">
                    Số khách hàng khác nhau
                </span>
            </article>
        </section>

        <section class="voucher-panel">
            <div class="panel-header">
                <div>
                    <h2>Lịch sử sử dụng</h2>

                    <p>
                        Tìm kiếm theo mã voucher,
                        đơn hàng hoặc khách hàng.
                    </p>
                </div>
            </div>

            <div class="toolbar">
                <div class="field field--search">
                    <label for="voucher-search">
                        Tìm kiếm
                    </label>

                    <input
                        id="voucher-search"
                        v-model="filters.search"
                        type="search"
                        placeholder="Mã voucher, mã đơn, khách hàng..."
                        @input="handleSearch"
                    >
                </div>

                <div class="field">
                    <label for="order-status">
                        Trạng thái đơn
                    </label>

                    <select
                        id="order-status"
                        v-model="filters.order_status"
                        @change="applyFilters"
                    >
                        <option value="">
                            Tất cả trạng thái
                        </option>

                        <option value="pending">
                            Chờ xác nhận
                        </option>

                        <option value="confirmed">
                            Đã xác nhận
                        </option>

                        <option value="processing">
                            Đang xử lý
                        </option>

                        <option value="shipping">
                            Đang giao
                        </option>

                        <option value="completed">
                            Hoàn thành
                        </option>

                        <option value="cancelled">
                            Đã hủy
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="date-from">
                        Từ ngày
                    </label>

                    <input
                        id="date-from"
                        v-model="filters.date_from"
                        type="date"
                    >
                </div>

                <div class="field">
                    <label for="date-to">
                        Đến ngày
                    </label>

                    <input
                        id="date-to"
                        v-model="filters.date_to"
                        type="date"
                    >
                </div>

                <div class="toolbar-actions">
                    <button
                        class="primary-button"
                        type="button"
                        :disabled="isLoading"
                        @click="applyFilters"
                    >
                        Áp dụng
                    </button>

                    <button
                        class="secondary-button"
                        type="button"
                        :disabled="isLoading"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="alert alert--error"
            >
                <span>
                    {{ errorMessage }}
                </span>

                <button
                    type="button"
                    @click="fetchPageData"
                >
                    Thử lại
                </button>
            </div>

            <div
                v-if="isLoading"
                class="state-box"
            >
                <div class="loading-spinner"></div>

                <p>Đang tải dữ liệu voucher...</p>
            </div>

            <div
                v-else-if="!hasUsages"
                class="state-box"
            >
                <div class="empty-icon">
                    %
                </div>

                <h3>Chưa có dữ liệu voucher</h3>

                <p>
                    Chưa có đơn hàng nào của bạn sử
                    dụng voucher phù hợp với bộ lọc.
                </p>
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table>
                    <thead>
                        <tr>
                            <th>Voucher</th>
                            <th>Đơn hàng</th>
                            <th>Khách hàng</th>
                            <th>Tạm tính</th>
                            <th>Giảm giá</th>
                            <th>Thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Thời gian dùng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="usage in usages"
                            :key="usage.id"
                        >
                            <td>
                                <div class="voucher-cell">
                                    <strong class="voucher-code">
                                        {{
                                            usage.voucher
                                                ?.code ??
                                            '--'
                                        }}
                                    </strong>

                                    <span class="voucher-type">
                                        {{
                                            getVoucherTypeLabel(
                                                usage.voucher,
                                            )
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <button
                                    class="order-code-button"
                                    type="button"
                                    :disabled="
                                        !usage.order?.id
                                    "
                                    @click="
                                        openOrder(
                                            usage.order?.id,
                                        )
                                    "
                                >
                                    {{
                                        usage.order
                                            ?.order_code ??
                                        (
                                            usage.order?.id
                                                ? `#${usage.order.id}`
                                                : '--'
                                        )
                                    }}
                                </button>
                            </td>

                            <td>
                                <div class="customer-cell">
                                    <strong>
                                        {{
                                            usage.customer
                                                ?.name ??
                                            '--'
                                        }}
                                    </strong>

                                    <span>
                                        {{
                                            usage.customer
                                                ?.email ??
                                            '--'
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td class="money-cell">
                                {{
                                    formatCurrency(
                                        usage.order
                                            ?.subtotal,
                                    )
                                }}
                            </td>

                            <td class="discount-cell">
                                -
                                {{
                                    formatCurrency(
                                        usage
                                            .discount_amount,
                                    )
                                }}
                            </td>

                            <td class="money-cell money-cell--strong">
                                {{
                                    formatCurrency(
                                        usage.order
                                            ?.total_amount,
                                    )
                                }}
                            </td>

                            <td>
                                <span
                                    :class="
                                        getOrderStatusClass(
                                            usage.order
                                                ?.status,
                                        )
                                    "
                                >
                                    {{
                                        getOrderStatusLabel(
                                            usage.order
                                                ?.status,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                <span class="date-cell">
                                    {{
                                        formatDate(
                                            usage.used_at,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                <button
                                    class="view-button"
                                    type="button"
                                    :disabled="
                                        !usage.order?.id
                                    "
                                    @click="
                                        openOrder(
                                            usage.order?.id,
                                        )
                                    "
                                >
                                    Xem đơn
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer
                v-if="hasUsages"
                class="panel-footer"
            >
                <div class="result-summary">
                    Hiển thị
                    <strong>{{ resultFrom }}</strong>
                    –
                    <strong>{{ resultTo }}</strong>
                    trong
                    <strong>
                        {{ formatNumber(totalResults) }}
                    </strong>
                    kết quả
                </div>

                <div class="pagination-controls">
                    <select
                        v-model.number="filters.per_page"
                        aria-label="Số dòng mỗi trang"
                        @change="changePerPage"
                    >
                        <option :value="10">
                            10 / trang
                        </option>

                        <option :value="15">
                            15 / trang
                        </option>

                        <option :value="25">
                            25 / trang
                        </option>

                        <option :value="50">
                            50 / trang
                        </option>
                    </select>

                    <button
                        type="button"
                        :disabled="
                            currentPage <= 1 ||
                            isLoading
                        "
                        @click="
                            changePage(
                                currentPage - 1,
                            )
                        "
                    >
                        Trước
                    </button>

                    <span class="page-indicator">
                        Trang
                        <strong>
                            {{ currentPage }}
                        </strong>
                        /
                        <strong>
                            {{ lastPage }}
                        </strong>
                    </span>

                    <button
                        type="button"
                        :disabled="
                            currentPage >= lastPage ||
                            isLoading
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
            </footer>
        </section>
    </div>
</template>

<style scoped>
.seller-voucher-page {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding: 24px;
    font-family: Roboto, Arial, sans-serif;
    color: #1f2933;
    background: #f4f7f5;
    min-height: 100%;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
}

.page-eyebrow {
    margin: 0 0 8px;
    color: #24734a;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h1 {
    margin: 0;
    color: #17211b;
    font-size: 28px;
    font-weight: 700;
}

.page-description {
    max-width: 680px;
    margin: 10px 0 0;
    color: #66736b;
    font-size: 14px;
    line-height: 1.6;
}

.refresh-button,
.primary-button,
.secondary-button,
.view-button,
.pagination-controls button {
    min-height: 40px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 0;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.refresh-button,
.secondary-button {
    border-color: #cbd5cf;
    color: #33443a;
    background: #ffffff;
}

.primary-button {
    color: #ffffff;
    background: #24734a;
}

.view-button {
    min-height: 34px;
    padding: 0 12px;
    border-color: #b8c9bf;
    color: #245c3d;
    background: #ffffff;
    white-space: nowrap;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.refresh-button:hover:not(:disabled),
.secondary-button:hover:not(:disabled),
.view-button:hover:not(:disabled),
.pagination-controls button:hover:not(:disabled) {
    background: #edf4ef;
}

.primary-button:hover:not(:disabled) {
    background: #1e633f;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.summary-card {
    display: flex;
    flex-direction: column;
    min-height: 138px;
    padding: 20px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.summary-label {
    color: #5f6f65;
    font-size: 13px;
    font-weight: 500;
}

.summary-value {
    margin-top: 16px;
    color: #17211b;
    font-size: 25px;
    line-height: 1.2;
}

.summary-note {
    margin-top: auto;
    padding-top: 14px;
    color: #839087;
    font-size: 12px;
}

.voucher-panel {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    padding: 20px 22px;
    border-bottom: 1px solid #e5ebe7;
}

.panel-header h2 {
    margin: 0;
    color: #1d2921;
    font-size: 18px;
}

.panel-header p {
    margin: 6px 0 0;
    color: #7a887f;
    font-size: 13px;
}

.toolbar {
    display: grid;
    grid-template-columns:
        minmax(240px, 2fr)
        minmax(170px, 1fr)
        minmax(150px, 1fr)
        minmax(150px, 1fr)
        auto;
    gap: 14px;
    align-items: end;
    padding: 18px 22px;
    border-bottom: 1px solid #e5ebe7;
    background: #fafcfb;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.field label {
    color: #536259;
    font-size: 12px;
    font-weight: 600;
}

.field input,
.field select,
.pagination-controls select {
    height: 40px;
    padding: 0 12px;
    border: 1px solid #cfd9d3;
    border-radius: 0;
    outline: none;
    color: #27352d;
    background: #ffffff;
    font: inherit;
    font-size: 14px;
}

.field input:focus,
.field select:focus,
.pagination-controls select:focus {
    border-color: #24734a;
    box-shadow: 0 0 0 2px rgb(36 115 74 / 10%);
}

.toolbar-actions {
    display: flex;
    gap: 8px;
}

.alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 18px 22px 0;
    padding: 12px 14px;
    font-size: 14px;
}

.alert--error {
    border: 1px solid #e8b5b5;
    color: #982f2f;
    background: #fff5f5;
}

.alert button {
    padding: 0;
    border: 0;
    color: inherit;
    background: transparent;
    font-weight: 700;
    cursor: pointer;
}

.state-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 320px;
    padding: 40px 20px;
    text-align: center;
}

.state-box h3 {
    margin: 16px 0 8px;
    font-size: 18px;
}

.state-box p {
    max-width: 430px;
    margin: 0;
    color: #718078;
    font-size: 14px;
    line-height: 1.6;
}

.empty-icon {
    display: grid;
    width: 58px;
    height: 58px;
    place-items: center;
    border: 1px solid #b9c9bf;
    color: #24734a;
    background: #eef5f0;
    font-size: 24px;
    font-weight: 700;
}

.loading-spinner {
    width: 34px;
    height: 34px;
    border: 3px solid #dce8e0;
    border-top-color: #24734a;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
}

th,
td {
    padding: 14px 16px;
    border-bottom: 1px solid #e8edea;
    text-align: left;
    vertical-align: middle;
}

th {
    color: #5b6960;
    background: #f7faf8;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

td {
    color: #39473f;
    font-size: 13px;
}

tbody tr:hover {
    background: #fbfdfc;
}

.voucher-cell,
.customer-cell {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.voucher-code {
    color: #1d6943;
    font-size: 14px;
}

.voucher-type,
.customer-cell span,
.date-cell {
    color: #78867d;
    font-size: 12px;
}

.order-code-button {
    padding: 0;
    border: 0;
    color: #256944;
    background: transparent;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.order-code-button:hover:not(:disabled) {
    text-decoration: underline;
}

.money-cell {
    white-space: nowrap;
}

.money-cell--strong {
    color: #202c24;
    font-weight: 700;
}

.discount-cell {
    color: #b6473c;
    font-weight: 700;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 0 9px;
    border: 1px solid #d4ddd7;
    color: #56645b;
    background: #f5f7f6;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge--pending {
    border-color: #dfc98d;
    color: #806414;
    background: #fff9e9;
}

.status-badge--confirmed,
.status-badge--processing {
    border-color: #a9c8b5;
    color: #256044;
    background: #edf7f0;
}

.status-badge--shipping {
    border-color: #b8c6d8;
    color: #435e7a;
    background: #f0f4f8;
}

.status-badge--completed,
.status-badge--delivered {
    border-color: #9bc6a9;
    color: #1f6840;
    background: #eaf7ee;
}

.status-badge--cancelled,
.status-badge--refunded {
    border-color: #deb2b2;
    color: #983c3c;
    background: #fff1f1;
}

.panel-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 22px;
    border-top: 1px solid #e5ebe7;
}

.result-summary {
    color: #6c7a71;
    font-size: 13px;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-controls select {
    height: 36px;
}

.pagination-controls button {
    min-height: 36px;
    padding: 0 13px;
    border-color: #ccd7d0;
    color: #33463a;
    background: #ffffff;
}

.page-indicator {
    padding: 0 8px;
    color: #647269;
    font-size: 13px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .toolbar {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .field--search {
        grid-column: 1 / -1;
    }

    .toolbar-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 700px) {
    .seller-voucher-page {
        padding: 16px;
    }

    .page-header {
        flex-direction: column;
    }

    .refresh-button {
        width: 100%;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .toolbar {
        grid-template-columns: 1fr;
    }

    .field--search,
    .toolbar-actions {
        grid-column: auto;
    }

    .toolbar-actions {
        flex-direction: column;
    }

    .toolbar-actions button {
        width: 100%;
    }

    .panel-footer {
        align-items: stretch;
        flex-direction: column;
    }

    .pagination-controls {
        flex-wrap: wrap;
        justify-content: space-between;
    }
}
</style>
<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'
import { useRouter } from 'vue-router'

import { getSellerOrders } from '@/api/seller/orders'

const router = useRouter()

const orders = ref([])
const pagination = ref(null)

const isLoading = ref(false)
const errorMessage = ref('')

const search = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)

const statusOptions = [
    {
        value: 'all',
        label: 'Tất cả trạng thái',
    },
    {
        value: 'pending',
        label: 'Chờ xác nhận',
    },
    {
        value: 'confirmed',
        label: 'Đã xác nhận',
    },
    {
        value: 'processing',
        label: 'Đang xử lý',
    },
    {
        value: 'shipping',
        label: 'Đang giao hàng',
    },
    {
        value: 'completed',
        label: 'Hoàn thành',
    },
    {
        value: 'cancelled',
        label: 'Đã hủy',
    },
]

const filteredOrders = computed(() => {
    let result = orders.value

    if (selectedStatus.value !== 'all') {
        result = result.filter(
            order =>
                order.status ===
                selectedStatus.value,
        )
    }

    const keyword = search.value
        .trim()
        .toLowerCase()

    if (keyword) {
        result = result.filter(order => {
            const code = String(
                order.code ??
                order.order_code ??
                order.id ??
                '',
            ).toLowerCase()

            const customerName = String(
                order.customer?.name ??
                order.user?.name ??
                '',
            ).toLowerCase()

            const customerPhone = String(
                order.phone ??
                order.shipping_phone ??
                order.customer?.phone ??
                '',
            ).toLowerCase()

            return (
                code.includes(keyword) ||
                customerName.includes(keyword) ||
                customerPhone.includes(keyword)
            )
        })
    }

    return result
})

const statistics = computed(() => {
    return {
        total: orders.value.length,

        pending: orders.value.filter(
            order =>
                order.status === 'pending',
        ).length,

        processing: orders.value.filter(
            order =>
                [
                    'confirmed',
                    'processing',
                    'shipping',
                ].includes(order.status),
        ).length,

        completed: orders.value.filter(
            order =>
                order.status ===
                'completed',
        ).length,
    }
})

async function fetchOrders(page = 1) {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getSellerOrders({
                page,
            })

        const responseData =
            response.data?.data

        /*
         * Trường hợp Laravel Resource Collection:
         *
         * response.data.data = [...]
         * response.data.meta = {...}
         */
        orders.value = Array.isArray(
            responseData,
        )
            ? responseData
            : []

        pagination.value =
            response.data?.meta ?? null

        currentPage.value =
            pagination.value?.current_page ??
            page
    } catch (error) {
        console.error(
            'Không thể tải đơn hàng:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách đơn hàng.'
    } finally {
        isLoading.value = false
    }
}

function viewOrder(order) {
    router.push({
        name: 'seller-orders-detail',
        params: {
            id: order.id,
        },
    })
}

function changePage(page) {
    if (
        page < 1 ||
        page >
            (pagination.value?.last_page ??
                1)
    ) {
        return
    }

    fetchOrders(page)
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

function formatDate(value) {
    if (!value) {
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
    ).format(new Date(value))
}

function getOrderCode(order) {
    return (
        order.code ??
        order.order_code ??
        `#${order.id}`
    )
}

function getCustomerName(order) {
    return (
        order.customer?.name ??
        order.user?.name ??
        order.customer_name ??
        'Khách hàng'
    )
}

function getCustomerPhone(order) {
    return (
        order.shipping_phone ??
        order.phone ??
        order.customer?.phone ??
        '--'
    )
}

function getOrderTotal(order) {
    return (
        order.total_amount ??
        order.grand_total ??
        order.total ??
        0
    )
}

function getItemCount(order) {
    if (
        typeof order.items_count ===
        'number'
    ) {
        return order.items_count
    }

    if (Array.isArray(order.items)) {
        return order.items.reduce(
            (total, item) =>
                total +
                Number(
                    item.quantity ?? 0,
                ),
            0,
        )
    }

    return 0
}

function getStatusLabel(status) {
    const labels = {
        pending: 'Chờ xác nhận',
        confirmed: 'Đã xác nhận',
        processing: 'Đang xử lý',
        shipping: 'Đang giao hàng',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy',
    }

    return labels[status] ?? status
}

function getStatusClass(status) {
    return `status-${status}`
}

onMounted(() => {
    fetchOrders()
})
</script>

<template>
    <div class="seller-order-page">
        <header class="page-header">
            <div>
                <p class="page-eyebrow">
                    QUẢN LÝ BÁN HÀNG
                </p>

                <h1>Đơn hàng</h1>

                <p class="page-description">
                    Theo dõi, xác nhận và cập nhật
                    trạng thái các đơn hàng của bạn.
                </p>
            </div>

            <button
                type="button"
                class="refresh-button"
                :disabled="isLoading"
                @click="
                    fetchOrders(currentPage)
                "
            >
                Làm mới
            </button>
        </header>

        <section class="statistics-grid">
            <article class="stat-card">
                <span class="stat-label">
                    Tổng đơn hàng
                </span>

                <strong>
                    {{ statistics.total }}
                </strong>
            </article>

            <article class="stat-card">
                <span class="stat-label">
                    Chờ xác nhận
                </span>

                <strong>
                    {{ statistics.pending }}
                </strong>
            </article>

            <article class="stat-card">
                <span class="stat-label">
                    Đang xử lý
                </span>

                <strong>
                    {{ statistics.processing }}
                </strong>
            </article>

            <article class="stat-card">
                <span class="stat-label">
                    Hoàn thành
                </span>

                <strong>
                    {{ statistics.completed }}
                </strong>
            </article>
        </section>

        <section class="order-panel">
            <div class="toolbar">
                <div class="search-field">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Tìm mã đơn, khách hàng, số điện thoại"
                    >
                </div>

                <select
                    v-model="selectedStatus"
                    class="status-filter"
                >
                    <option
                        v-for="
                            option in
                            statusOptions
                        "
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div
                v-if="errorMessage"
                class="alert alert-error"
            >
                {{ errorMessage }}
            </div>

            <div
                v-if="isLoading"
                class="state-box"
            >
                Đang tải danh sách đơn hàng...
            </div>

            <div
                v-else-if="
                    filteredOrders.length === 0
                "
                class="state-box"
            >
                Không có đơn hàng phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="
                                order in
                                filteredOrders
                            "
                            :key="order.id"
                        >
                            <td>
                                <button
                                    type="button"
                                    class="order-code"
                                    @click="
                                        viewOrder(
                                            order,
                                        )
                                    "
                                >
                                    {{
                                        getOrderCode(
                                            order,
                                        )
                                    }}
                                </button>
                            </td>

                            <td>
                                <div
                                    class="customer-info"
                                >
                                    <strong>
                                        {{
                                            getCustomerName(
                                                order,
                                            )
                                        }}
                                    </strong>

                                    <span>
                                        {{
                                            getCustomerPhone(
                                                order,
                                            )
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                {{
                                    getItemCount(
                                        order,
                                    )
                                }}
                                sản phẩm
                            </td>

                            <td class="money-cell">
                                {{
                                    formatCurrency(
                                        getOrderTotal(
                                            order,
                                        ),
                                    )
                                }}
                            </td>

                            <td>
                                <span
                                    class="status-badge"
                                    :class="
                                        getStatusClass(
                                            order.status,
                                        )
                                    "
                                >
                                    {{
                                        getStatusLabel(
                                            order.status,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                {{
                                    formatDate(
                                        order.created_at,
                                    )
                                }}
                            </td>

                            <td class="action-cell">
                                <button
                                    type="button"
                                    class="view-button"
                                    @click="
                                        viewOrder(
                                            order,
                                        )
                                    "
                                >
                                    Xem chi tiết
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
                            pagination.last_page ||
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
        </section>
    </div>
</template>

<style scoped>
.seller-order-page {
    min-height: 100%;
    padding: 28px;
    background: #f5f7f6;
    color: #162019;
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
    line-height: 1.2;
}

.page-description {
    margin: 8px 0 0;
    color: #66736a;
    font-size: 14px;
}

.refresh-button,
.view-button,
.pagination button {
    min-height: 40px;
    border: 1px solid #cdd7d0;
    background: #ffffff;
    color: #1f5f3f;
    padding: 0 16px;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.refresh-button:hover,
.view-button:hover,
.pagination button:hover:not(:disabled) {
    border-color: #267149;
    background: #edf7f0;
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
    border: 1px solid #dde4df;
    background: #ffffff;
    padding: 20px;
}

.stat-label {
    display: block;
    margin-bottom: 12px;
    color: #68756d;
    font-size: 13px;
}

.stat-card strong {
    color: #173f2b;
    font-size: 28px;
}

.order-panel {
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
    border-bottom: 1px solid #e1e7e3;
}

.search-field input,
.status-filter {
    width: 100%;
    height: 44px;
    border: 1px solid #cfd9d2;
    background: #ffffff;
    padding: 0 14px;
    color: #1d2921;
    font: inherit;
    outline: none;
}

.search-field input:focus,
.status-filter:focus {
    border-color: #267149;
}

.alert {
    margin: 18px;
    border: 1px solid;
    padding: 14px 16px;
}

.alert-error {
    border-color: #e2b7b7;
    background: #fff2f2;
    color: #9b2424;
}

.state-box {
    padding: 64px 20px;
    color: #68756d;
    text-align: center;
}

.table-wrapper {
    overflow-x: auto;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.order-table th,
.order-table td {
    padding: 16px 18px;
    border-bottom: 1px solid #e7ece8;
    text-align: left;
    vertical-align: middle;
}

.order-table th {
    background: #f7f9f8;
    color: #68756d;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.order-table tbody tr:hover {
    background: #fafcfb;
}

.order-code {
    border: 0;
    background: transparent;
    color: #1f6844;
    padding: 0;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.customer-info strong {
    font-size: 14px;
}

.customer-info span {
    color: #718078;
    font-size: 13px;
}

.money-cell {
    font-weight: 700;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    border: 1px solid;
    padding: 0 10px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.status-pending {
    border-color: #e7ce87;
    background: #fff8df;
    color: #7c5b00;
}

.status-confirmed {
    border-color: #a9c9b5;
    background: #edf7f0;
    color: #246442;
}

.status-processing {
    border-color: #a9c7d1;
    background: #eef7fa;
    color: #225d70;
}

.status-shipping {
    border-color: #b9b9dc;
    background: #f2f2fc;
    color: #4b4b8b;
}

.status-completed {
    border-color: #97cbaa;
    background: #e9f7ee;
    color: #1d6a3d;
}

.status-cancelled {
    border-color: #e0b3b3;
    background: #fff0f0;
    color: #9a2929;
}

.action-cell {
    text-align: right;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    padding: 18px;
}

.pagination span {
    color: #66736a;
    font-size: 14px;
}

@media (max-width: 1024px) {
    .statistics-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .seller-order-page {
        padding: 18px;
    }

    .page-header {
        flex-direction: column;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
    }

    .toolbar {
        grid-template-columns: 1fr;
    }

    .refresh-button {
        width: 100%;
    }
}
</style>
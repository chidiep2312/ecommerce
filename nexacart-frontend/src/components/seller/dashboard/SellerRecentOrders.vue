<script setup>
defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits([
    'view-all',
])

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

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        },
    ).format(
        new Date(value),
    )
}

function getStatusLabel(status) {
    const labels = {
        pending: 'Chờ xác nhận',
        confirmed: 'Đã xác nhận',
        processing: 'Đang xử lý',
        shipping: 'Đang giao',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy',
    }

    return labels[status] ?? status
}
</script>

<template>
    <article class="panel">
        <header class="panel-header">
            <div>
                <h2 class="panel-title">
                    Đơn hàng gần đây
                </h2>

                <p class="panel-description">
                    Các đơn hàng mới nhất
                    cần theo dõi
                </p>
            </div>

            <button
                type="button"
                class="text-button"
                @click="emit('view-all')"
            >
                Xem tất cả
            </button>
        </header>

        <div
            v-if="orders.length === 0"
            class="empty-state"
        >
            Chưa có đơn hàng nào.
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
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="order in orders"
                        :key="order.id"
                    >
                        <td>
                            <strong class="order-code">
                                {{ order.code }}
                            </strong>
                        </td>

                        <td>
                            {{
                                order.customer_name
                            }}
                        </td>

                        <td>
                            <strong>
                                {{
                                    formatCurrency(
                                        order.total_amount,
                                    )
                                }}
                            </strong>
                        </td>

                        <td>
                            <span
                                class="status-badge"
                                :class="
                                    `status-${order.status}`
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
                    </tr>
                </tbody>
            </table>
        </div>
    </article>
</template>

<style scoped>
.panel {
    border: 1px solid #e1e7e3;
    background: #ffffff;
}

.panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 17px 19px;
}

.panel-title {
    margin: 0;
    color: #111827;
    font-size: 16px;
    font-weight: 600;
}

.panel-description {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.text-button {
    border: 0;
    background: transparent;
    padding: 3px 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.table-wrapper {
    overflow-x: auto;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
}

.order-table th,
.order-table td {
    border-bottom: 1px solid #f0f1f2;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
}

.order-table th {
    background: #fafbfa;
    color: #6b7280;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.order-table td {
    color: #374151;
    font-size: 13px;
    white-space: nowrap;
}

.order-code {
    color: #15803d;
}

.status-badge {
    display: inline-flex;
    border: 1px solid;
    padding: 4px 7px;
    font-size: 11px;
}

.status-pending {
    border-color: #fcd34d;
    background: #fffbeb;
    color: #92400e;
}

.status-confirmed,
.status-processing {
    border-color: #a7f3d0;
    background: #ecfdf5;
    color: #047857;
}

.status-shipping,
.status-completed {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-cancelled {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.empty-state {
    padding: 60px 20px;
    color: #9ca3af;
    text-align: center;
}
</style>
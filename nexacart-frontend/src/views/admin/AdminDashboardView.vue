<script setup>
import {
    CircleDollarSign,
    Package,
    ShoppingCart,
    Store,
    Users,
} from '@lucide/vue'
import { computed, onMounted,ref } from 'vue'

import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import AdminStatCard from '@/components/admin/AdminStatCard.vue'


import {
    getAdminDashboard,
} from '@/api/admin/dashboard'

// const statistics = ref({
//     users: 0,
//     sellers: 0,
//     products: 0,
//     orders: 0,
//     revenue: 0,
// })

// const recentOrders = ref([])
// const isLoading = ref(false)

// async function loadDashboard() {
//     isLoading.value = true

//     try {
//         const response =
//             await getAdminDashboard()

//         statistics.value =
//             response.data.data.statistics

//         recentOrders.value =
//             response.data.data.recent_orders
//     } catch (error) {
//         console.error(
//             'Không thể tải dashboard:',
//             error,
//         )
//     } finally {
//         isLoading.value = false
//     }
// }

// onMounted(loadDashboard)

const statistics = ref({
    users: 12540,
    sellers: 428,
    products: 8934,
    orders: 2756,
    revenue: 486750000,
})

const recentOrders = ref([
    {
        id: 1,
        code: 'NC240701',
        customer: 'Nguyễn Văn An',
        seller: 'Tech Store',
        total: 2450000,
        status: 'PENDING',
    },
    {
        id: 2,
        code: 'NC240702',
        customer: 'Trần Minh Anh',
        seller: 'Home Market',
        total: 835000,
        status: 'CONFIRMED',
    },
    {
        id: 3,
        code: 'NC240703',
        customer: 'Lê Thảo Vy',
        seller: 'Fashion Hub',
        total: 1290000,
        status: 'SHIPPING',
    },
    {
        id: 4,
        code: 'NC240704',
        customer: 'Phạm Quốc Huy',
        seller: 'Digital World',
        total: 5240000,
        status: 'COMPLETED',
    },
])

const formattedRevenue = computed(() => {
    return `${statistics.value.revenue.toLocaleString(
        'vi-VN',
    )} ₫`
})

function statusLabel(status) {
    const labels = {
        PENDING: 'Chờ xác nhận',
        CONFIRMED: 'Đã xác nhận',
        SHIPPING: 'Đang giao',
        COMPLETED: 'Hoàn thành',
        CANCELLED: 'Đã hủy',
    }

    return labels[status] ?? status
}
</script>

<template>
    <section>
        <AdminPageHeader
            title="Tổng quan hệ thống"
            description="Theo dõi hoạt động kinh doanh và vận hành của NexaCart."
        >
            <template #actions>
                <button
                    type="button"
                    class="admin-button admin-button--secondary"
                >
                    Xuất báo cáo
                </button>

                <button
                    type="button"
                    class="admin-button admin-button--primary"
                >
                    Xem báo cáo chi tiết
                </button>
            </template>
        </AdminPageHeader>

        <div class="dashboard-stat-grid">
            <AdminStatCard
                title="Tổng người dùng"
                :value="
                    statistics.users.toLocaleString(
                        'vi-VN',
                    )
                "
                trend="+8.4%"
                description="so với tháng trước"
                :icon="Users"
            />

            <AdminStatCard
                title="Người bán"
                :value="
                    statistics.sellers.toLocaleString(
                        'vi-VN',
                    )
                "
                trend="+3.2%"
                description="so với tháng trước"
                :icon="Store"
            />

            <AdminStatCard
                title="Sản phẩm"
                :value="
                    statistics.products.toLocaleString(
                        'vi-VN',
                    )
                "
                trend="+126"
                description="sản phẩm mới"
                :icon="Package"
            />

            <AdminStatCard
                title="Đơn hàng"
                :value="
                    statistics.orders.toLocaleString(
                        'vi-VN',
                    )
                "
                trend="+12.5%"
                description="so với tháng trước"
                :icon="ShoppingCart"
            />

            <AdminStatCard
                title="Doanh thu"
                :value="formattedRevenue"
                trend="+15.8%"
                description="so với tháng trước"
                :icon="CircleDollarSign"
            />
        </div>

        <div class="dashboard-grid">
            <section class="dashboard-panel">
                <div class="dashboard-panel__header">
                    <div>
                        <h2>Hoạt động kinh doanh</h2>
                        <p>
                            Tổng quan doanh thu trong
                            7 ngày gần nhất
                        </p>
                    </div>

                    <select class="dashboard-select">
                        <option>7 ngày gần nhất</option>
                        <option>30 ngày gần nhất</option>
                        <option>12 tháng</option>
                    </select>
                </div>

                <div class="dashboard-chart-placeholder">
                    <span>
                        Khu vực biểu đồ doanh thu
                    </span>
                    <small>
                        Có thể tích hợp Chart.js sau
                    </small>
                </div>
            </section>

            <section class="dashboard-panel">
                <div class="dashboard-panel__header">
                    <div>
                        <h2>Trạng thái hệ thống</h2>
                        <p>
                            Các chỉ số cần chú ý
                        </p>
                    </div>
                </div>

                <div class="dashboard-system-list">
                    <div>
                        <span>
                            Đơn hàng chờ xác nhận
                        </span>
                        <strong>184</strong>
                    </div>

                    <div>
                        <span>
                            Seller chờ duyệt
                        </span>
                        <strong>23</strong>
                    </div>

                    <div>
                        <span>
                            Sản phẩm chờ kiểm duyệt
                        </span>
                        <strong>61</strong>
                    </div>

                    <div>
                        <span>
                            Khiếu nại chưa xử lý
                        </span>
                        <strong>12</strong>
                    </div>

                    <div>
                        <span>
                            Sản phẩm sắp hết hàng
                        </span>
                        <strong>95</strong>
                    </div>
                </div>
            </section>
        </div>

        <section class="dashboard-panel">
            <div class="dashboard-panel__header">
                <div>
                    <h2>Đơn hàng gần đây</h2>
                    <p>
                        Các đơn hàng mới phát sinh
                        trên hệ thống
                    </p>
                </div>

                <RouterLink
                    to="/admin/orders"
                    class="admin-button admin-button--secondary"
                >
                    Xem tất cả
                </RouterLink>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Người bán</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="order in recentOrders"
                            :key="order.id"
                        >
                            <td>
                                <strong>
                                    {{ order.code }}
                                </strong>
                            </td>

                            <td>
                                {{ order.customer }}
                            </td>

                            <td>
                                {{ order.seller }}
                            </td>

                            <td>
                                {{
                                    order.total.toLocaleString(
                                        'vi-VN',
                                    )
                                }}
                                ₫
                            </td>

                            <td>
                                <span
                                    class="dashboard-status"
                                    :class="
                                        `dashboard-status--${order.status.toLowerCase()}`
                                    "
                                >
                                    {{
                                        statusLabel(
                                            order.status,
                                        )
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>

<style scoped>
.dashboard-stat-grid {
    display: grid;
    grid-template-columns:
        repeat(5, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns:
        minmax(0, 2fr)
        minmax(300px, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.dashboard-panel {
    margin-bottom: 20px;
    background: #ffffff;
    border: 1px solid
        var(--admin-border-light);
}

.dashboard-panel__header {
    min-height: 76px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid
        var(--admin-border-light);
}

.dashboard-panel__header h2 {
    margin: 0 0 4px;
    color: var(--admin-text-primary);
    font-size: 17px;
}

.dashboard-panel__header p {
    margin: 0;
    color: var(--admin-text-secondary);
    font-size: 13px;
}

.dashboard-select {
    min-height: 38px;
    padding: 0 10px;
    border: 1px solid var(--admin-border);
    background: #ffffff;
    outline: none;
}

.dashboard-chart-placeholder {
    min-height: 310px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 12px solid #ffffff;
    background:
        repeating-linear-gradient(
            0deg,
            #f9fafb,
            #f9fafb 39px,
            #e5e7eb 40px
        );
    color: var(--admin-text-secondary);
}

.dashboard-chart-placeholder span {
    font-size: 16px;
    font-weight: 600;
}

.dashboard-chart-placeholder small {
    margin-top: 5px;
}

.dashboard-system-list {
    padding: 4px 20px;
}

.dashboard-system-list div {
    min-height: 55px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid
        var(--admin-border-light);
}

.dashboard-system-list div:last-child {
    border-bottom: 0;
}

.dashboard-system-list span {
    color: var(--admin-text-secondary);
}

.dashboard-system-list strong {
    min-width: 42px;
    padding: 4px 8px;
    background: var(--admin-primary-50);
    color: var(--admin-primary-800);
    text-align: center;
}

.dashboard-status {
    display: inline-block;
    min-width: 105px;
    padding: 5px 9px;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
}

.dashboard-status--pending {
    background: #fef3c7;
    color: #92400e;
}

.dashboard-status--confirmed {
    background: #e0f2fe;
    color: #075985;
}

.dashboard-status--shipping {
    background: #ede9fe;
    color: #5b21b6;
}

.dashboard-status--completed {
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
}

.dashboard-status--cancelled {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 1350px) {
    .dashboard-stat-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 1000px) {
    .dashboard-stat-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .dashboard-stat-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-panel__header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
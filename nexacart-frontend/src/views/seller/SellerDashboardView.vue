<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'
import {
    useRouter,
} from 'vue-router'

import SellerQuickActions from '@/components/seller/dashboard/SellerQuickActions.vue'
import SellerRecentOrders from '@/components/seller/dashboard/SellerRecentOrders.vue'
import SellerSummaryCard from '@/components/seller/dashboard/SellerSummaryCard.vue'

const router = useRouter()

const loading = ref(false)
const errorMessage = ref('')

const dashboard = ref({
    revenue_today: 0,
    orders_today: 0,
    pending_orders: 0,
    total_products: 0,
    low_stock_products: 0,
    suspended_products: 0,
    recent_orders: [],
})

const summaryCards = computed(() => {
    return [
        {
            label: 'Doanh thu hôm nay',
            value: formatCurrency(
                dashboard.value
                    .revenue_today,
            ),
            description:
                'Tổng doanh thu từ đơn hàng',
            type: 'revenue',
            code: '₫',
        },
        {
            label: 'Đơn hàng hôm nay',
            value:
                dashboard.value
                    .orders_today,
            description:
                'Đơn hàng mới trong ngày',
            type: 'orders',
            code: 'ĐH',
        },
        {
            label: 'Chờ xác nhận',
            value:
                dashboard.value
                    .pending_orders,
            description:
                'Đơn hàng cần xử lý',
            type: 'pending',
            code: 'CX',
        },
        {
            label: 'Tổng sản phẩm',
            value:
                dashboard.value
                    .total_products,
            description:
                'Sản phẩm đang quản lý',
            type: 'products',
            code: 'SP',
        },
    ]
})

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

function goToProducts(query = {}) {
    router.push({
        name: 'seller-products',
        query,
    })
}

function goToOrders() {
    router.push({
        name: 'seller-orders',
    })
}

function goToCreateProduct() {
    router.push({
        name: 'seller-products-create',
    })
}

async function fetchDashboard() {
    loading.value = true
    errorMessage.value = ''

    try {
        // const response =
        //     await getSellerDashboard()
        //
        // dashboard.value =
        //     response.data?.data

        dashboard.value = {
            revenue_today: 4250000,
            orders_today: 18,
            pending_orders: 7,
            total_products: 46,
            low_stock_products: 5,
            suspended_products: 2,
            recent_orders: [
                {
                    id: 152,
                    code: 'NXA000152',
                    customer_name:
                        'Nguyễn Văn Nam',
                    total_amount: 820000,
                    status: 'pending',
                    created_at:
                        '2026-07-28T08:30:00',
                },
                {
                    id: 151,
                    code: 'NXA000151',
                    customer_name:
                        'Trần Minh Anh',
                    total_amount: 1250000,
                    status: 'confirmed',
                    created_at:
                        '2026-07-28T07:45:00',
                },
            ],
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải dữ liệu tổng quan.'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchDashboard()
})
</script>

<template>
    <section class="seller-dashboard">
        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="loading"
            class="dashboard-loading"
        >
            Đang tải dữ liệu tổng quan...
        </div>

        <template v-else>
            <section class="summary-grid">
                <SellerSummaryCard
                    v-for="card in summaryCards"
                    :key="card.label"
                    :label="card.label"
                    :value="card.value"
                    :description="
                        card.description
                    "
                    :type="card.type"
                    :code="card.code"
                />
            </section>

            <section class="dashboard-grid">
                <SellerRecentOrders
                    :orders="
                        dashboard.recent_orders
                    "
                    @view-all="goToOrders"
                />

                <aside class="dashboard-sidebar">
                    <SellerQuickActions
                        @create-product="
                            goToCreateProduct
                        "
                        @view-orders="goToOrders"
                        @view-products="
                            goToProducts
                        "
                    />

                    <SellerWarnings
                        :low-stock-count="
                            dashboard
                                .low_stock_products
                        "
                        :suspended-count="
                            dashboard
                                .suspended_products
                        "
                        @view-low-stock="
                            goToProducts({
                                stock_status:
                                    'low_stock',
                            })
                        "
                        @view-suspended="
                            goToProducts({
                                is_suspended: 1,
                            })
                        "
                    />
                </aside>
            </section>
        </template>
    </section>
</template>

<style scoped>
.seller-dashboard {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.alert {
    border: 1px solid;
    padding: 12px 16px;
    font-size: 14px;
}

.alert-error {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.dashboard-loading {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 70px 20px;
    color: #6b7280;
    text-align: center;
}

.summary-grid {
    display: grid;
    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );
    gap: 18px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns:
        minmax(0, 2fr)
        minmax(280px, 0.8fr);
    gap: 20px;
}

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-sidebar {
        display: grid;
        grid-template-columns:
            1fr 1fr;
    }
}

@media (max-width: 680px) {
    .summary-grid,
    .dashboard-sidebar {
        grid-template-columns: 1fr;
    }
}
</style>
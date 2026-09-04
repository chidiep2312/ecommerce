<script setup>
import {
    CircleDollarSign,
    Package,
    ShoppingCart,
    Store,
    Users,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    ref,
} from 'vue'

import {
    Line,
} from 'vue-chartjs'

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Filler,
} from 'chart.js'

import AdminPageHeader
    from '@/components/admin/AdminPageHeader.vue'

import AdminStatCard
    from '@/components/admin/AdminStatCard.vue'

import {
    getAdminDashboard,
} from '@/api/admin/dashboard'

/*
|--------------------------------------------------------------------------
| Chart.js
|--------------------------------------------------------------------------
*/

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Filler,
)

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const isLoading = ref(false)

const errorMessage = ref('')

const chartTimeframe = ref('7d')

const dashboard = ref({
    summary: {
        total_users: 0,
        total_customers: 0,
        total_sellers: 0,
        total_products: 0,
        active_products: 0,
        total_orders: 0,
        completed_orders: 0,
    },

    order_statuses: {
        pending: 0,
        confirmed: 0,
        shipping: 0,
        completed: 0,
        cancelled: 0,
    },

    top_sellers: [],

    top_products: [],

    platform_fee: {
        total: 0,
        current_month: 0,
    },

    daily_platform_fee: [],
})

/*
|--------------------------------------------------------------------------
| Computed Data
|--------------------------------------------------------------------------
*/

const summary = computed(() => {
    return dashboard.value.summary
})

const orderStatuses = computed(() => {
    return dashboard.value.order_statuses
})

const topSellers = computed(() => {
    return dashboard.value.top_sellers ?? []
})

const topProducts = computed(() => {
    return dashboard.value.top_products ?? []
})

const platformFee = computed(() => {
    return dashboard.value.platform_fee ?? {
        total: 0,
        current_month: 0,
    }
})

/*
|--------------------------------------------------------------------------
| Order Status
|--------------------------------------------------------------------------
*/

const orderStatusItems = computed(() => [
    {
        key: 'pending',
        label: 'Chờ xác nhận',
        value:
            orderStatuses.value.pending ?? 0,
        class: 'pending',
    },

    {
        key: 'confirmed',
        label: 'Đã xác nhận',
        value:
            orderStatuses.value.confirmed ?? 0,
        class: 'confirmed',
    },

    {
        key: 'shipping',
        label: 'Đang giao',
        value:
            orderStatuses.value.shipping ?? 0,
        class: 'shipping',
    },

    {
        key: 'completed',
        label: 'Hoàn thành',
        value:
            orderStatuses.value.completed ?? 0,
        class: 'completed',
    },

    {
        key: 'cancelled',
        label: 'Đã hủy',
        value:
            orderStatuses.value.cancelled ?? 0,
        class: 'cancelled',
    },
])

/*
|--------------------------------------------------------------------------
| Platform Fee Chart
|--------------------------------------------------------------------------
*/

const feeChartRows = computed(() => {
    const rows =
        dashboard.value.daily_platform_fee ?? []

    if (
        chartTimeframe.value === '7d'
    ) {
        return rows.slice(-7)
    }

    return rows.slice(-30)
})

const feeChartData = computed(() => ({
    labels:
        feeChartRows.value.map(
            row =>
                formatShortDate(
                    row.date
                )
        ),

    datasets: [
        {
            label:
                'Phí nền tảng',

            data:
                feeChartRows.value.map(
                    row =>
                        Number(
                            row.fee ?? 0
                        )
                ),

            borderColor:
                '#15803d',

            backgroundColor:
                'rgba(21, 128, 61, 0.10)',

            borderWidth: 2,

            fill: true,

            tension: 0.35,

            pointRadius: 3,

            pointHoverRadius: 5,

            pointBackgroundColor:
                '#15803d',

            pointBorderColor:
                '#ffffff',

            pointBorderWidth: 2,
        },
    ],
}))

const feeChartOptions = computed(() => ({
    responsive: true,

    maintainAspectRatio: false,

    interaction: {
        mode: 'index',
        intersect: false,
    },

    plugins: {
        legend: {
            display: false,
        },

        tooltip: {
            callbacks: {
                title(items) {
                    if (
                        !items.length
                    ) {
                        return ''
                    }

                    const row =
                        feeChartRows
                            .value[
                                items[0]
                                    .dataIndex
                            ]

                    return formatFullDate(
                        row?.date
                    )
                },

                label(context) {
                    return (
                        'Phí nền tảng: ' +
                        formatCurrency(
                            context
                                .parsed
                                .y
                        )
                    )
                },

                afterLabel(context) {
                    const row =
                        feeChartRows
                            .value[
                                context
                                    .dataIndex
                            ]

                    return (
                        `${
                            row
                                ?.orders_count ??
                            0
                        } đơn tính phí`
                    )
                },
            },
        },
    },

    scales: {
        x: {
            grid: {
                display: false,
            },

            border: {
                display: false,
            },

            ticks: {
                color:
                    '#6b7280',

                maxRotation: 0,

                autoSkip: true,

                maxTicksLimit:
                    chartTimeframe
                        .value ===
                    '7d'
                        ? 7
                        : 10,
            },
        },

        y: {
            beginAtZero: true,

            border: {
                display: false,
            },

            grid: {
                color:
                    '#f1f5f9',
            },

            ticks: {
                color:
                    '#6b7280',

                callback(value) {
                    return formatCompactCurrency(
                        value
                    )
                },
            },
        },
    },
}))

/*
|--------------------------------------------------------------------------
| Format Helpers
|--------------------------------------------------------------------------
*/

function formatNumber(value) {
    return Number(
        value ?? 0
    ).toLocaleString(
        'vi-VN'
    )
}

function formatCurrency(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
        }
    ).format(
        Number(value ?? 0)
    )
}

function formatCompactCurrency(value) {
    const number =
        Number(value ?? 0)

    if (
        number >=
        1_000_000_000
    ) {
        return (
            (
                number /
                1_000_000_000
            ).toFixed(1) +
            ' tỷ'
        )
    }

    if (
        number >=
        1_000_000
    ) {
        return (
            (
                number /
                1_000_000
            ).toFixed(1) +
            ' tr'
        )
    }

    if (
        number >=
        1_000
    ) {
        return (
            Math.round(
                number / 1_000
            ) + 'k'
        )
    }

    return number.toString()
}

function parseDate(date) {
    if (!date) {
        return null
    }

    return new Date(
        `${date}T00:00:00`
    )
}

function formatShortDate(date) {
    const parsed =
        parseDate(date)

    if (!parsed) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
        }
    ).format(parsed)
}

function formatFullDate(date) {
    const parsed =
        parseDate(date)

    if (!parsed) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(parsed)
}

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

async function loadDashboard() {
    isLoading.value = true

    errorMessage.value = ''

    try {
        const response =
            await getAdminDashboard()

        dashboard.value =
            response.data.data
    } catch (error) {
        console.error(
            'Không thể tải dashboard:',
            error
        )

        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể tải dữ liệu dashboard.'
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    loadDashboard()
})
</script>

<template>
    <section class="admin-dashboard">

        <!-- ================= HEADER ================= -->

        <AdminPageHeader
            title="Tổng quan hệ thống"
            description="Theo dõi hoạt động vận hành, giao dịch và phí nền tảng của NexaCart."
        />

        <!-- ================= ERROR ================= -->

        <div
            v-if="errorMessage"
            class="dashboard-error"
        >
            <span>
                {{ errorMessage }}
            </span>

            <button
                type="button"
                @click="loadDashboard"
            >
                Thử lại
            </button>
        </div>

        <!-- ================= LOADING ================= -->

        <div
            v-if="isLoading"
            class="dashboard-loading"
        >
            Đang tải dữ liệu dashboard...
        </div>

        <!-- ================= CONTENT ================= -->

        <template v-else>

            <!-- ================= STAT CARDS ================= -->

            <div class="dashboard-stat-grid">

                <AdminStatCard
                    title="Tổng người dùng"
                    :value="
                        formatNumber(
                            summary.total_users
                        )
                    "
                    :trend="
                        `${formatNumber(
                            summary.total_customers
                        )} khách hàng`
                    "
                    description="tài khoản trên hệ thống"
                    :icon="Users"
                />

                <AdminStatCard
                    title="Người bán"
                    :value="
                        formatNumber(
                            summary.total_sellers
                        )
                    "
                    trend="Seller"
                    description="người bán trên hệ thống"
                    :icon="Store"
                />

                <AdminStatCard
                    title="Sản phẩm"
                    :value="
                        formatNumber(
                            summary.total_products
                        )
                    "
                    :trend="
                        `${formatNumber(
                            summary.active_products
                        )} hoạt động`
                    "
                    description="sản phẩm toàn hệ thống"
                    :icon="Package"
                />

                <AdminStatCard
                    title="Đơn hàng"
                    :value="
                        formatNumber(
                            summary.total_orders
                        )
                    "
                    :trend="
                        `${formatNumber(
                            summary.completed_orders
                        )} hoàn thành`
                    "
                    description="tổng đơn hàng"
                    :icon="ShoppingCart"
                />

                <AdminStatCard
                    title="Tổng phí nền tảng"
                    :value="
                        formatCurrency(
                            platformFee.total
                        )
                    "
                    trend="5%"
                    description="từ các đơn đủ điều kiện"
                    :icon="
                        CircleDollarSign
                    "
                />

                <AdminStatCard
                    title="Phí nền tảng tháng này"
                    :value="
                        formatCurrency(
                            platformFee.current_month
                        )
                    "
                    trend="Tháng này"
                    description="phí nền tảng đã phát sinh"
                    :icon="
                        CircleDollarSign
                    "
                />

            </div>

            <!-- ================= CHART + STATUS ================= -->

            <div class="dashboard-grid">

                <!-- ================= PLATFORM FEE CHART ================= -->

                <section class="dashboard-panel">

                    <div
                        class="
                            dashboard-panel__header
                        "
                    >
                        <div>
                            <h2>
                                Phí nền tảng
                            </h2>

                            <p>
                                Phí thu từ các đơn hàng
                                hoàn thành đủ điều kiện
                            </p>
                        </div>

                        <select
                            v-model="
                                chartTimeframe
                            "
                            class="
                                dashboard-select
                            "
                        >
                            <option
                                value="7d"
                            >
                                7 ngày gần nhất
                            </option>

                            <option
                                value="30d"
                            >
                                30 ngày gần nhất
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="
                            feeChartRows.length
                        "
                        class="
                            dashboard-chart
                        "
                    >
                        <Line
                            :data="
                                feeChartData
                            "
                            :options="
                                feeChartOptions
                            "
                        />
                    </div>

                    <div
                        v-else
                        class="
                            dashboard-empty
                        "
                    >
                        Chưa có dữ liệu phí
                        nền tảng.
                    </div>

                </section>

                <!-- ================= ORDER STATUS ================= -->

                <section class="dashboard-panel">

                    <div
                        class="
                            dashboard-panel__header
                        "
                    >
                        <div>
                            <h2>
                                Trạng thái đơn hàng
                            </h2>

                            <p>
                                Phân bố đơn hàng
                                toàn hệ thống
                            </p>
                        </div>
                    </div>

                    <div
                        class="
                            dashboard-system-list
                        "
                    >
                        <div
                            v-for="
                                item in
                                orderStatusItems
                            "
                            :key="
                                item.key
                            "
                        >
                            <span
                                class="
                                    status-name
                                "
                            >
                                <i
                                    class="
                                        status-dot
                                    "
                                    :class="
                                        `status-dot--${item.class}`
                                    "
                                ></i>

                                {{
                                    item.label
                                }}
                            </span>

                            <strong>
                                {{
                                    formatNumber(
                                        item.value
                                    )
                                }}
                            </strong>
                        </div>
                    </div>

                </section>

            </div>

            <!-- ================= TOP SELLERS ================= -->

            <section class="dashboard-panel">

                <div
                    class="
                        dashboard-panel__header
                    "
                >
                    <div>
                        <h2>
                            Top người bán
                        </h2>

                        <p>
                            5 seller có doanh số
                            đơn hoàn thành cao nhất
                        </p>
                    </div>
                </div>

                <div
                    class="
                        admin-table-wrapper
                    "
                >
                    <table
                        v-if="
                            topSellers.length
                        "
                        class="admin-table"
                    >
                        <thead>
                            <tr>
                                <th>
                                    #
                                </th>

                                <th>
                                    Người bán
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Đơn hoàn thành
                                </th>

                                <th>
                                    Doanh số
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(
                                    seller,
                                    index
                                ) in
                                    topSellers"
                                :key="
                                    seller.seller_id
                                "
                            >
                                <td
                                    class="
                                        dashboard-rank
                                    "
                                >
                                    {{
                                        index + 1
                                    }}
                                </td>

                                <td>
                                    <strong>
                                        {{
                                            seller
                                                .seller_name
                                        }}
                                    </strong>
                                </td>

                                <td
                                    class="
                                        dashboard-muted
                                    "
                                >
                                    {{
                                        seller
                                            .seller_email
                                    }}
                                </td>

                                <td>
                                    {{
                                        formatNumber(
                                            seller
                                                .completed_orders
                                        )
                                    }}
                                </td>

                                <td
                                    class="
                                        dashboard-money
                                    "
                                >
                                    {{
                                        formatCurrency(
                                            seller
                                                .total_sales
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-else
                        class="
                            dashboard-empty
                        "
                    >
                        Chưa có dữ liệu
                        người bán.
                    </div>
                </div>

            </section>

            <!-- ================= TOP PRODUCTS ================= -->

            <section class="dashboard-panel">

                <div
                    class="
                        dashboard-panel__header
                    "
                >
                    <div>
                        <h2>
                            Sản phẩm bán chạy
                        </h2>

                        <p>
                            Top 10 sản phẩm theo
                            số lượng đã bán
                        </p>
                    </div>
                </div>

                <div
                    class="
                        admin-table-wrapper
                    "
                >
                    <table
                        v-if="
                            topProducts.length
                        "
                        class="admin-table"
                    >
                        <thead>
                            <tr>
                                <th>
                                    #
                                </th>

                                <th>
                                    Sản phẩm
                                </th>

                                <th>
                                    SKU
                                </th>

                                <th>
                                    Đã bán
                                </th>

                                <th>
                                    Doanh số
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(
                                    product,
                                    index
                                ) in
                                    topProducts"
                                :key="
                                    product.product_id
                                "
                            >
                                <td
                                    class="
                                        dashboard-rank
                                    "
                                >
                                    {{
                                        index + 1
                                    }}
                                </td>

                                <td>
                                    <strong>
                                        {{
                                            product
                                                .product_name
                                        }}
                                    </strong>
                                </td>

                                <td
                                    class="
                                        dashboard-sku
                                    "
                                >
                                    {{
                                        product
                                            .product_sku
                                    }}
                                </td>

                                <td>
                                    {{
                                        formatNumber(
                                            product
                                                .total_quantity
                                        )
                                    }}
                                </td>

                                <td
                                    class="
                                        dashboard-money
                                    "
                                >
                                    {{
                                        formatCurrency(
                                            product
                                                .total_sales
                                        )
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-else
                        class="
                            dashboard-empty
                        "
                    >
                        Chưa có dữ liệu
                        sản phẩm.
                    </div>
                </div>

            </section>

        </template>

    </section>
</template>

<style scoped>
.admin-dashboard {
    width: 100%;
}

/* =========================================================
   ERROR
   ========================================================= */

.dashboard-error {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 16px;

    margin-bottom: 18px;

    padding: 12px 16px;

    border: 1px solid #fecaca;

    background: #fef2f2;

    color: #991b1b;

    font-size: 13px;
}

.dashboard-error button {
    border: 0;

    background: transparent;

    color: #991b1b;

    font-weight: 600;

    text-decoration: underline;

    cursor: pointer;
}

/* =========================================================
   LOADING
   ========================================================= */

.dashboard-loading {
    padding: 70px 20px;

    border:
        1px solid
        var(--admin-border-light);

    background: #ffffff;

    color:
        var(--admin-text-secondary);

    font-size: 13px;

    text-align: center;
}

/* =========================================================
   STAT CARDS
   ========================================================= */

.dashboard-stat-grid {
    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 16px;

    margin-bottom: 22px;
}

/* =========================================================
   MAIN GRID
   ========================================================= */

.dashboard-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 2fr)
        minmax(300px, 1fr);

    gap: 20px;

    margin-bottom: 20px;
}

/* =========================================================
   PANEL
   ========================================================= */

.dashboard-panel {
    margin-bottom: 20px;

    background: #ffffff;

    border:
        1px solid
        var(--admin-border-light);
}

.dashboard-panel__header {
    min-height: 76px;

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 16px;

    padding: 16px 20px;

    border-bottom:
        1px solid
        var(--admin-border-light);
}

.dashboard-panel__header h2 {
    margin: 0 0 4px;

    color:
        var(--admin-text-primary);

    font-size: 17px;

    font-weight: 700;
}

.dashboard-panel__header p {
    margin: 0;

    color:
        var(--admin-text-secondary);

    font-size: 13px;
}

/* =========================================================
   SELECT
   ========================================================= */

.dashboard-select {
    min-height: 38px;

    padding: 0 10px;

    border:
        1px solid
        var(--admin-border);

    border-radius: 0;

    background: #ffffff;

    color:
        var(--admin-text-primary);

    font-size: 13px;

    outline: none;

    cursor: pointer;
}

.dashboard-select:focus {
    border-color:
        var(--admin-primary-600);
}

/* =========================================================
   CHART
   ========================================================= */

.dashboard-chart {
    position: relative;

    height: 330px;

    padding: 20px;
}

/* =========================================================
   ORDER STATUS
   ========================================================= */

.dashboard-system-list {
    padding: 4px 20px;
}

.dashboard-system-list > div {
    min-height: 57px;

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 12px;

    border-bottom:
        1px solid
        var(--admin-border-light);
}

.dashboard-system-list > div:last-child {
    border-bottom: 0;
}

.status-name {
    display: flex;

    align-items: center;

    gap: 9px;

    color:
        var(--admin-text-secondary);

    font-size: 13px;
}

.status-dot {
    width: 8px;
    height: 8px;

    display: inline-block;

    flex-shrink: 0;

    background: #9ca3af;
}

.status-dot--pending {
    background: #d97706;
}

.status-dot--confirmed {
    background: #0284c7;
}

.status-dot--shipping {
    background: #7c3aed;
}

.status-dot--completed {
    background: #15803d;
}

.status-dot--cancelled {
    background: #dc2626;
}

.dashboard-system-list strong {
    min-width: 46px;

    padding: 4px 8px;

    background:
        var(--admin-primary-50);

    color:
        var(--admin-primary-800);

    font-size: 13px;

    text-align: center;

    font-variant-numeric:
        tabular-nums;
}

/* =========================================================
   TABLE
   ========================================================= */

.dashboard-rank {
    width: 40px;

    color:
        var(--admin-text-secondary);

    font-weight: 700;
}

.dashboard-muted {
    color:
        var(--admin-text-secondary);
}

.dashboard-money {
    color:
        var(--admin-text-primary);

    font-weight: 600;

    white-space: nowrap;

    font-variant-numeric:
        tabular-nums;
}

.dashboard-sku {
    color:
        var(--admin-primary-700);

    font-family: monospace;

    font-weight: 600;
}

/* =========================================================
   EMPTY
   ========================================================= */

.dashboard-empty {
    padding: 55px 20px;

    color:
        var(--admin-text-secondary);

    font-size: 13px;

    text-align: center;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (
    max-width: 1350px
) {
    .dashboard-stat-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }
}

@media (
    max-width: 1000px
) {
    .dashboard-grid {
        grid-template-columns:
            1fr;
    }
}

@media (
    max-width: 600px
) {
    .dashboard-stat-grid {
        grid-template-columns:
            1fr;
    }

    .dashboard-panel__header {
        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .dashboard-select {
        width: 100%;
    }

    .dashboard-chart {
        height: 270px;

        padding: 12px;
    }
}
</style>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getDashboard } from '@/api/seller/dashboard'

import { Line } from 'vue-chartjs'

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Filler,
} from 'chart.js'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Filler
)

const router = useRouter()

const loading = ref(false)
const errorMessage = ref('')
const chartTimeframe = ref('7d')

const dashboard = ref({
    summary: {
        total_products: 0,
        active_products: 0,
        low_stock_products: 0,
        out_of_stock_products: 0,
        total_orders: 0,
        completed_revenue: 0,
        current_month_revenue: 0,
    },

    order_statuses: {
        pending: 0,
        confirmed: 0,
        shipping: 0,
        completed: 0,
        cancelled: 0,
    },

    top_products: [],

    daily_revenue: [],
})

/*
|--------------------------------------------------------------------------
| Dashboard Data
|--------------------------------------------------------------------------
*/

const summary = computed(() => {
    return dashboard.value.summary
})

const orderStatuses = computed(() => {
    return dashboard.value.order_statuses
})

const topProducts = computed(() => {
    return dashboard.value.top_products ?? []
})

/*
|--------------------------------------------------------------------------
| Summary Cards
|--------------------------------------------------------------------------
*/

const summaryCards = computed(() => [
    {
        label: 'Doanh thu tháng này',

        value: formatCurrency(
            summary.value.current_month_revenue
        ),

        subtext:
            'Từ các đơn hàng đã hoàn thành',
    },

    {
        label: 'Tổng đơn hàng',

        value:
            `${summary.value.total_orders} đơn`,

        subtext:
            `${orderStatuses.value.pending ?? 0} đơn đang chờ xác nhận`,

        warning:
            (orderStatuses.value.pending ?? 0) > 0,
    },

    {
        label: 'Sản phẩm đang bán',

        value:
            `${summary.value.active_products} SP`,

        subtext:
            `${summary.value.total_products} sản phẩm trong gian hàng`,
    },

    {
        label: 'Tổng doanh thu',

        value: formatCurrency(
            summary.value.completed_revenue
        ),

        subtext:
            'Từ toàn bộ đơn đã hoàn thành',
    },
])

/*
|--------------------------------------------------------------------------
| Order Status
|--------------------------------------------------------------------------
*/

const orderStatusItems = computed(() => [
    {
        key: 'pending',
        label: 'Chờ xác nhận',
        count:
            orderStatuses.value.pending ?? 0,
        class: 'status-pending',
    },

    {
        key: 'confirmed',
        label: 'Đã xác nhận',
        count:
            orderStatuses.value.confirmed ?? 0,
        class: 'status-confirmed',
    },

    {
        key: 'shipping',
        label: 'Đang vận chuyển',
        count:
            orderStatuses.value.shipping ?? 0,
        class: 'status-shipping',
    },

    {
        key: 'completed',
        label: 'Hoàn thành',
        count:
            orderStatuses.value.completed ?? 0,
        class: 'status-completed',
    },

    {
        key: 'cancelled',
        label: 'Đã hủy',
        count:
            orderStatuses.value.cancelled ?? 0,
        class: 'status-cancelled',
    },
])

/*
|--------------------------------------------------------------------------
| Revenue Chart
|--------------------------------------------------------------------------
*/

const revenueChartData = computed(() => {
    const data =
        dashboard.value.daily_revenue ?? []

    if (chartTimeframe.value === '7d') {
        return data.slice(-7)
    }

    return data.slice(-30)
})

const revenueChart = computed(() => ({
    labels: revenueChartData.value.map(
        item => formatChartDate(item.date)
    ),

    datasets: [
        {
            label: 'Doanh thu',

            data: revenueChartData.value.map(
                item => Number(item.revenue)
            ),

            borderColor: '#15803d',

            backgroundColor:
                'rgba(21, 128, 61, 0.12)',

            borderWidth: 2,

            fill: true,

            tension: 0.35,

            pointRadius: 4,

            pointHoverRadius: 6,

            pointBackgroundColor:
                '#15803d',

            pointBorderColor:
                '#ffffff',

            pointBorderWidth: 2,
        },
    ],
}))

const revenueChartOptions = computed(() => ({
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
                    if (!items.length) {
                        return ''
                    }

                    const dataIndex =
                        items[0].dataIndex

                    const item =
                        revenueChartData
                            .value[dataIndex]

                    return formatFullDate(
                        item?.date
                    )
                },

                label(context) {
                    return (
                        'Doanh thu: ' +
                        formatCurrency(
                            context.parsed.y
                        )
                    )
                },

                afterLabel(context) {
                    const item =
                        revenueChartData
                            .value[
                                context.dataIndex
                            ]

                    return (
                        `${item?.orders_count ?? 0}` +
                        ' đơn hoàn thành'
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
                color: '#6b7280',

                maxRotation: 0,

                autoSkip: true,

                maxTicksLimit:
                    chartTimeframe.value ===
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
                color: '#f1f5f9',
            },

            ticks: {
                color: '#6b7280',

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
| Current Date
|--------------------------------------------------------------------------
*/

const currentDateFormatted = computed(() => {
    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            weekday: 'long',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(new Date())
})

/*
|--------------------------------------------------------------------------
| Format Helpers
|--------------------------------------------------------------------------
*/

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

    if (number >= 1_000_000_000) {
        return (
            `${(
                number /
                1_000_000_000
            ).toFixed(1)} tỷ`
        )
    }

    if (number >= 1_000_000) {
        return (
            `${(
                number /
                1_000_000
            ).toFixed(1)} tr`
        )
    }

    if (number >= 1_000) {
        return (
            `${(
                number /
                1_000
            ).toFixed(0)}k`
        )
    }

    return number.toString()
}

function parseDashboardDate(date) {
    if (!date) {
        return null
    }

    return new Date(
        `${date}T00:00:00`
    )
}

function formatChartDate(date) {
    const parsedDate =
        parseDashboardDate(date)

    if (!parsedDate) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
        }
    ).format(parsedDate)
}

function formatFullDate(date) {
    const parsedDate =
        parseDashboardDate(date)

    if (!parsedDate) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(parsedDate)
}

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function goToProducts(query = {}) {
    router.push({
        name: 'seller-products',
        query,
    })
}

function goToOrders(query = {}) {
    router.push({
        name: 'seller-orders',
        query,
    })
}

function goToCreateProduct() {
    router.push({
        name: 'seller-products-create',
    })
}

/*
|--------------------------------------------------------------------------
| Fetch Dashboard
|--------------------------------------------------------------------------
*/

async function fetchDashboard() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getDashboard()

        dashboard.value =
            response.data.data
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải dữ liệu dashboard.'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchDashboard()
})
</script>

<template>
    <div class="seller-commerce-dashboard">

        <!-- ================= HEADER ================= -->

        <header class="commerce-header">

            <div class="header-left">

                <h1 class="header-title">
                    Hiệu Quả Hoạt Động Cửa Hàng
                </h1>

                <p class="header-subtitle">
                    Dữ liệu kinh doanh tính đến hôm nay,
                    {{ currentDateFormatted }}
                </p>

            </div>

            <div class="header-right">

                <button
                    class="btn btn-refresh"
                    :disabled="loading"
                    @click="fetchDashboard"
                >
                    <svg
                        class="icon-refresh"
                        :class="{
                            'is-loading': loading
                        }"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <polyline
                            points="23 4 23 10 17 10"
                        />

                        <polyline
                            points="1 20 1 14 7 14"
                        />

                        <path
                            d="
                                M3.51 9
                                a9 9 0 0 1 14.85-3.36
                                L23 10

                                M1 14
                                l4.64 4.36
                                A9 9 0 0 0 20.49 15
                            "
                        />
                    </svg>

                    <span>
                        Tải lại
                    </span>

                </button>

            </div>

        </header>

        <!-- ================= ERROR ================= -->

        <div
            v-if="errorMessage"
            class="error-notice"
        >
            <span>
                {{ errorMessage }}
            </span>

            <button
                class="btn-text-retry"
                @click="fetchDashboard"
            >
                Thử lại
            </button>
        </div>

        <!-- ================= LOADING ================= -->

        <div
            v-if="loading"
            class="loading-panel"
        >
            <div class="loading-spinner"></div>

            <span>
                Đang tải dữ liệu cửa hàng...
            </span>
        </div>

        <!-- ================= CONTENT ================= -->

        <template v-else>

            <!-- ================= SUMMARY ================= -->

            <section class="metrics-grid">

                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="metric-card"
                    :class="{
                        'is-alert-metric':
                            card.warning
                    }"
                >
                    <div class="metric-top">

                        <span class="metric-title">
                            {{ card.label }}
                        </span>

                    </div>

                    <div class="metric-main-value">
                        {{ card.value }}
                    </div>

                    <div class="metric-footer">

                        <span class="metric-subtext">
                            {{ card.subtext }}
                        </span>

                    </div>

                </div>

            </section>

            <!-- ================= MAIN GRID ================= -->

            <div class="dashboard-body-grid">

                <!-- ================= LEFT ================= -->

                <div class="primary-column">

                    <!-- Revenue Chart -->

                    <section class="content-card chart-card">

                        <div class="card-header">

                            <div class="header-text-group">

                                <h2 class="card-title">
                                    Phân Tích Doanh Thu
                                </h2>

                                <p class="card-desc">
                                    Doanh thu từ các đơn hàng
                                    đã hoàn thành theo ngày
                                </p>

                            </div>

                            <div class="timeframe-buttons">

                                <button
                                    class="tf-btn"
                                    :class="{
                                        active:
                                            chartTimeframe ===
                                            '7d'
                                    }"
                                    @click="
                                        chartTimeframe =
                                            '7d'
                                    "
                                >
                                    7 ngày
                                </button>

                                <button
                                    class="tf-btn"
                                    :class="{
                                        active:
                                            chartTimeframe ===
                                            '30d'
                                    }"
                                    @click="
                                        chartTimeframe =
                                            '30d'
                                    "
                                >
                                    30 ngày
                                </button>

                            </div>

                        </div>

                        <div
                            v-if="
                                revenueChartData.length
                            "
                            class="chart-wrapper"
                        >
                            <Line
                                :data="
                                    revenueChart
                                "
                                :options="
                                    revenueChartOptions
                                "
                            />
                        </div>

                        <div
                            v-else
                            class="empty-chart-view"
                        >
                            Chưa có doanh thu trong
                            khoảng thời gian này.
                        </div>

                    </section>

                    <!-- ================= TOP PRODUCTS ================= -->

                    <section
                        class="
                            content-card
                            products-card
                        "
                    >

                        <div class="card-header">

                            <div>

                                <h2 class="card-title">
                                    Sản Phẩm Bán Chạy
                                </h2>

                                <p class="card-desc">
                                    Top 5 sản phẩm theo số
                                    lượng bán từ các đơn
                                    đã hoàn thành
                                </p>

                            </div>

                            <button
                                class="btn-link-action"
                                @click="goToProducts()"
                            >
                                Quản lý sản phẩm
                                &rarr;
                            </button>

                        </div>

                        <div class="table-container">

                            <table
                                v-if="
                                    topProducts.length
                                "
                                class="ecom-table"
                            >
                                <thead>
                                    <tr>
                                        <th>
                                            #
                                        </th>

                                        <th>
                                            SẢN PHẨM
                                        </th>

                                        <th>
                                            SKU
                                        </th>

                                        <th>
                                            ĐÃ BÁN
                                        </th>

                                        <th>
                                            DOANH THU
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
                                        class="
                                            product-row-item
                                        "
                                        @click="
                                            goToProducts()
                                        "
                                    >
                                        <td
                                            class="
                                                rank-cell
                                            "
                                        >
                                            {{
                                                index +
                                                1
                                            }}
                                        </td>

                                        <td
                                            class="
                                                product-name-cell
                                            "
                                        >
                                            {{
                                                product.product_name
                                            }}
                                        </td>

                                        <td
                                            class="
                                                product-sku-cell
                                            "
                                        >
                                            {{
                                                product.product_sku
                                            }}
                                        </td>

                                        <td
                                            class="
                                                quantity-cell
                                            "
                                        >
                                            {{
                                                product.total_quantity
                                            }}
                                        </td>

                                        <td
                                            class="
                                                amount-cell
                                            "
                                        >
                                            {{
                                                formatCurrency(
                                                    product.total_revenue
                                                )
                                            }}
                                        </td>
                                    </tr>

                                </tbody>
                            </table>

                            <div
                                v-else
                                class="
                                    empty-table-view
                                "
                            >
                                Chưa có sản phẩm phát
                                sinh doanh số.
                            </div>

                        </div>

                    </section>

                </div>

                <!-- ================= SIDEBAR ================= -->

                <aside class="sidebar-column">

                    <!-- Quick Actions -->

                    <div
                        class="
                            content-card
                            action-panel
                        "
                    >

                        <div class="card-header">

                            <h2 class="card-title">
                                Tác Vụ Nhanh
                            </h2>

                        </div>

                        <div class="action-btn-group">

                            <button
                                class="
                                    action-btn
                                    action-btn-primary
                                "
                                @click="
                                    goToCreateProduct
                                "
                            >
                                <span
                                    class="
                                        btn-icon-box
                                    "
                                >
                                    +
                                </span>

                                <div class="btn-meta">

                                    <span
                                        class="
                                            meta-title
                                        "
                                    >
                                        Thêm sản phẩm mới
                                    </span>

                                    <span
                                        class="
                                            meta-desc
                                        "
                                    >
                                        Đăng sản phẩm lên
                                        gian hàng
                                    </span>

                                </div>

                            </button>

                            <button
                                class="action-btn"
                                @click="
                                    goToOrders()
                                "
                            >
                                <span
                                    class="
                                        btn-icon-box
                                    "
                                >
                                    ↓
                                </span>

                                <div class="btn-meta">

                                    <span
                                        class="
                                            meta-title
                                        "
                                    >
                                        Xử lý đơn hàng
                                    </span>

                                    <span
                                        class="
                                            meta-desc
                                        "
                                    >
                                        Xác nhận và quản lý
                                        đơn hàng
                                    </span>

                                </div>

                            </button>

                            <button
                                class="action-btn"
                                @click="
                                    goToProducts()
                                "
                            >
                                <span
                                    class="
                                        btn-icon-box
                                    "
                                >
                                    ≡
                                </span>

                                <div class="btn-meta">

                                    <span
                                        class="
                                            meta-title
                                        "
                                    >
                                        Quản lý kho hàng
                                    </span>

                                    <span
                                        class="
                                            meta-desc
                                        "
                                    >
                                        Kiểm soát tồn kho
                                        và sản phẩm
                                    </span>

                                </div>

                            </button>

                        </div>

                    </div>

                    <!-- ================= ORDER STATUS ================= -->

                    <div
                        class="
                            content-card
                            status-panel
                        "
                    >

                        <div class="card-header">

                            <h2 class="card-title">
                                Trạng Thái Đơn Hàng
                            </h2>

                        </div>

                        <div class="order-status-list">

                            <div
                                v-for="
                                    item in
                                    orderStatusItems
                                "
                                :key="item.key"
                                class="
                                    order-status-row
                                "
                                @click="
                                    goToOrders({
                                        status:
                                            item.key
                                    })
                                "
                            >
                                <span
                                    class="
                                        status-badge
                                    "
                                    :class="
                                        item.class
                                    "
                                >
                                    {{
                                        item.label
                                    }}
                                </span>

                                <strong
                                    class="
                                        status-count
                                    "
                                >
                                    {{
                                        item.count
                                    }}
                                </strong>

                            </div>

                        </div>

                    </div>

                    <!-- ================= STOCK ALERTS ================= -->

                    <div
                        class="
                            content-card
                            alert-panel
                        "
                    >

                        <div class="card-header">

                            <h2 class="card-title">
                                Cần Chú Ý
                            </h2>

                        </div>

                        <div class="alert-list">

                            <!-- Low Stock -->

                            <div
                                class="alert-row"
                                :class="{
                                    'has-warning':
                                        summary.low_stock_products >
                                        0
                                }"
                                @click="
                                    goToProducts({
                                        stock_status:
                                            'low_stock'
                                    })
                                "
                            >

                                <div class="alert-info">

                                    <strong
                                        class="
                                            alert-title
                                        "
                                    >
                                        Sắp hết hàng
                                    </strong>

                                    <span
                                        class="
                                            alert-sub
                                        "
                                    >
                                        Sản phẩm tồn kho
                                        từ 1 đến 5
                                    </span>

                                </div>

                                <span
                                    class="
                                        alert-count-tag
                                        warn-amber
                                    "
                                >
                                    {{
                                        summary.low_stock_products
                                    }}
                                </span>

                            </div>

                            <!-- Out Of Stock -->

                            <div
                                class="alert-row"
                                :class="{
                                    'has-warning':
                                        summary.out_of_stock_products >
                                        0
                                }"
                                @click="
                                    goToProducts({
                                        stock_status:
                                            'out_of_stock'
                                    })
                                "
                            >

                                <div class="alert-info">

                                    <strong
                                        class="
                                            alert-title
                                        "
                                    >
                                        Hết hàng
                                    </strong>

                                    <span
                                        class="
                                            alert-sub
                                        "
                                    >
                                        Sản phẩm có tồn
                                        kho bằng 0
                                    </span>

                                </div>

                                <span
                                    class="
                                        alert-count-tag
                                        warn-rose
                                    "
                                >
                                    {{
                                        summary.out_of_stock_products
                                    }}
                                </span>

                            </div>

                        </div>

                    </div>

                </aside>

            </div>

        </template>

    </div>
</template>

<style scoped>
/* =========================================================
   THEME
   ========================================================= */

.seller-commerce-dashboard {
    --brand-green: #15803d;
    --brand-green-hover: #166534;
    --brand-green-light: #f0fdf4;
    --brand-green-border: #bbf7d0;

    --text-primary: #111827;
    --text-secondary: #4b5563;
    --text-muted: #6b7280;

    --border-color: #e5e7eb;

    --bg-card: #ffffff;
    --bg-subtle: #f9fafb;

    display: flex;
    flex-direction: column;

    gap: 20px;

    width: 100%;

    color: var(--text-primary);

    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Helvetica,
        Arial,
        sans-serif;

    box-sizing: border-box;
}

/* =========================================================
   HEADER
   ========================================================= */

.commerce-header {
    display: flex;

    justify-content: space-between;
    align-items: flex-end;

    flex-wrap: wrap;

    gap: 12px;

    padding-bottom: 16px;

    border-bottom:
        1px solid var(--border-color);
}

.header-title {
    margin: 0 0 4px;

    font-size: 1.25rem;
    font-weight: 700;

    color: var(--text-primary);
}

.header-subtitle {
    margin: 0;

    font-size: 0.8125rem;

    color: var(--text-muted);
}

/* =========================================================
   BUTTONS
   ========================================================= */

.btn {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 14px;

    border:
        1px solid var(--border-color);

    border-radius: 0;

    background: #ffffff;

    color: var(--text-primary);

    font-size: 0.8125rem;
    font-weight: 600;

    cursor: pointer;

    transition: all 0.15s ease;
}

.btn-refresh:hover:not(:disabled) {
    background:
        var(--bg-subtle);

    border-color:
        #cbd5e1;

    color:
        var(--brand-green);
}

.btn:disabled {
    opacity: 0.6;

    cursor: not-allowed;
}

.icon-refresh {
    width: 14px;
    height: 14px;
}

.is-loading {
    animation:
        spin 0.8s linear infinite;
}

@keyframes spin {
    100% {
        transform:
            rotate(360deg);
    }
}

/* =========================================================
   ERROR
   ========================================================= */

.error-notice {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 10px 16px;

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #991b1b;

    font-size: 0.8125rem;
}

.btn-text-retry {
    border: none;

    background: transparent;

    color: #991b1b;

    font-weight: 700;

    text-decoration: underline;

    cursor: pointer;
}

/* =========================================================
   SUMMARY CARDS
   ========================================================= */

.metrics-grid {
    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0, 1fr)
        );

    gap: 14px;
}

.metric-card {
    display: flex;

    flex-direction: column;

    gap: 8px;

    padding: 16px;

    background:
        var(--bg-card);

    border:
        1px solid
        var(--border-color);

    transition:
        border-color 0.15s ease;
}

.metric-card:hover {
    border-color:
        #cbd5e1;
}

.metric-card.is-alert-metric {
    border-left:
        3px solid #d97706;
}

.metric-top {
    display: flex;

    justify-content:
        space-between;

    align-items: center;
}

.metric-title {
    font-size: 0.8125rem;

    font-weight: 600;

    color:
        var(--text-secondary);
}

.metric-main-value {
    font-size: 1.5rem;

    font-weight: 700;

    color:
        var(--text-primary);

    letter-spacing:
        -0.02em;

    font-variant-numeric:
        tabular-nums;
}

.metric-footer {
    display: flex;

    align-items: center;

    gap: 6px;

    color:
        var(--text-muted);

    font-size: 0.75rem;
}

/* =========================================================
   MAIN LAYOUT
   ========================================================= */

.dashboard-body-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        310px;

    align-items: start;

    gap: 16px;
}

.primary-column {
    display: flex;

    flex-direction: column;

    min-width: 0;

    gap: 16px;
}

.sidebar-column {
    display: flex;

    flex-direction: column;

    gap: 16px;
}

.content-card {
    background:
        var(--bg-card);

    border:
        1px solid
        var(--border-color);
}

/* =========================================================
   CARD HEADER
   ========================================================= */

.card-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 12px;

    padding: 14px 18px;

    background:
        var(--bg-subtle);

    border-bottom:
        1px solid
        var(--border-color);
}

.card-title {
    margin: 0;

    color:
        var(--text-primary);

    font-size: 0.875rem;

    font-weight: 700;
}

.card-desc {
    margin: 2px 0 0;

    color:
        var(--text-muted);

    font-size: 0.75rem;
}

/* =========================================================
   CHART
   ========================================================= */

.timeframe-buttons {
    display: flex;

    flex-shrink: 0;

    gap: 2px;
}

.tf-btn {
    padding:
        5px 11px;

    border:
        1px solid
        var(--border-color);

    border-radius: 0;

    background: #ffffff;

    color:
        var(--text-secondary);

    font-size: 0.75rem;

    font-weight: 600;

    cursor: pointer;
}

.tf-btn:hover {
    background:
        var(--bg-subtle);
}

.tf-btn.active {
    background:
        var(--brand-green);

    border-color:
        var(--brand-green);

    color: #ffffff;
}

.chart-wrapper {
    position: relative;

    height: 320px;

    padding: 20px;
}

.empty-chart-view {
    padding: 80px 20px;

    color:
        var(--text-muted);

    font-size: 0.8125rem;

    text-align: center;
}

/* =========================================================
   TABLE
   ========================================================= */

.table-container {
    overflow-x: auto;
}

.ecom-table {
    width: 100%;

    border-collapse:
        collapse;

    font-size: 0.8125rem;

    text-align: left;
}

.ecom-table th {
    padding: 10px 18px;

    background: #ffffff;

    border-bottom:
        1px solid
        var(--border-color);

    color:
        var(--text-muted);

    font-size: 0.6875rem;

    font-weight: 700;

    letter-spacing:
        0.04em;

    white-space: nowrap;
}

.ecom-table td {
    padding: 12px 18px;

    border-bottom:
        1px solid
        var(--border-color);

    vertical-align:
        middle;
}

.product-row-item {
    cursor: pointer;

    transition:
        background-color
        0.1s ease;
}

.product-row-item:hover {
    background:
        var(--bg-subtle);
}

.rank-cell {
    width: 35px;

    color:
        var(--text-muted);

    font-weight: 700;
}

.product-name-cell {
    color:
        var(--text-primary);

    font-weight: 600;
}

.product-sku-cell {
    color:
        var(--brand-green);

    font-family:
        monospace;

    font-weight: 600;
}

.quantity-cell {
    font-weight: 600;

    font-variant-numeric:
        tabular-nums;
}

.amount-cell {
    font-weight: 600;

    font-variant-numeric:
        tabular-nums;

    white-space: nowrap;
}

.empty-table-view {
    padding: 36px 16px;

    color:
        var(--text-muted);

    font-size: 0.8125rem;

    text-align: center;
}

/* =========================================================
   LINK ACTION
   ========================================================= */

.btn-link-action {
    flex-shrink: 0;

    border: none;

    background:
        transparent;

    color:
        var(--brand-green);

    font-size:
        0.8125rem;

    font-weight: 600;

    cursor: pointer;
}

.btn-link-action:hover {
    text-decoration:
        underline;
}

/* =========================================================
   QUICK ACTIONS
   ========================================================= */

.action-btn-group {
    display: flex;

    flex-direction: column;

    gap: 8px;

    padding: 12px;
}

.action-btn {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px 12px;

    background: #ffffff;

    border:
        1px solid
        var(--border-color);

    border-radius: 0;

    cursor: pointer;

    text-align: left;

    transition:
        all 0.15s ease;
}

.action-btn:hover {
    background:
        var(--bg-subtle);

    border-color:
        #cbd5e1;
}

.action-btn-primary {
    background:
        var(--brand-green-light);

    border-color:
        var(--brand-green-border);
}

.action-btn-primary:hover {
    background:
        #dcfce7;
}

.btn-icon-box {
    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    width: 28px;
    height: 28px;

    background: #ffffff;

    border:
        1px solid
        var(--border-color);

    color:
        var(--brand-green);

    font-weight: 700;
}

.btn-meta {
    display: flex;

    flex-direction: column;
}

.meta-title {
    color:
        var(--text-primary);

    font-size: 0.8125rem;

    font-weight: 600;
}

.meta-desc {
    color:
        var(--text-muted);

    font-size: 0.6875rem;
}

/* =========================================================
   ORDER STATUS
   ========================================================= */

.order-status-list {
    display: flex;

    flex-direction: column;
}

.order-status-row {
    display: flex;

    align-items: center;
    justify-content:
        space-between;

    gap: 12px;

    padding: 12px 16px;

    border-bottom:
        1px solid
        var(--border-color);

    cursor: pointer;

    transition:
        background-color
        0.1s ease;
}

.order-status-row:last-child {
    border-bottom: none;
}

.order-status-row:hover {
    background:
        var(--bg-subtle);
}

.status-count {
    color:
        var(--text-primary);

    font-size: 0.875rem;

    font-variant-numeric:
        tabular-nums;
}

/* =========================================================
   STATUS BADGES
   ========================================================= */

.status-badge {
    display:
        inline-block;

    padding: 3px 8px;

    border:
        1px solid
        transparent;

    font-size:
        0.6875rem;

    font-weight: 600;
}

.status-pending {
    background: #fffbeb;

    border-color: #fde68a;

    color: #b45309;
}

.status-confirmed {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #1d4ed8;
}

.status-shipping {
    background: #f5f3ff;

    border-color: #ddd6fe;

    color: #6d28d9;
}

.status-completed {
    background: #f0fdf4;

    border-color: #bbf7d0;

    color: #15803d;
}

.status-cancelled {
    background: #fef2f2;

    border-color: #fecaca;

    color: #b91c1c;
}

/* =========================================================
   ALERTS
   ========================================================= */

.alert-list {
    display: flex;

    flex-direction: column;
}

.alert-row {
    display: flex;

    align-items: center;
    justify-content:
        space-between;

    gap: 12px;

    padding: 12px 16px;

    border-bottom:
        1px solid
        var(--border-color);

    cursor: pointer;
}

.alert-row:last-child {
    border-bottom: none;
}

.alert-row:hover {
    background:
        var(--bg-subtle);
}

.alert-info {
    display: flex;

    flex-direction: column;

    min-width: 0;
}

.alert-title {
    color:
        var(--text-primary);

    font-size:
        0.8125rem;

    font-weight: 600;
}

.alert-sub {
    color:
        var(--text-muted);

    font-size:
        0.6875rem;
}

.alert-count-tag {
    flex-shrink: 0;

    padding: 2px 8px;

    border:
        1px solid
        transparent;

    font-family:
        monospace;

    font-size:
        0.75rem;

    font-weight: 700;
}

.warn-amber {
    background: #fef3c7;

    border-color: #fde68a;

    color: #92400e;
}

.warn-rose {
    background: #fee2e2;

    border-color: #fecaca;

    color: #991b1b;
}

/* =========================================================
   LOADING
   ========================================================= */

.loading-panel {
    display: flex;

    align-items: center;
    justify-content: center;

    gap: 12px;

    padding: 60px 20px;

    background: #ffffff;

    border:
        1px solid
        var(--border-color);

    color:
        var(--text-muted);

    font-size:
        0.8125rem;
}

.loading-spinner {
    width: 16px;
    height: 16px;

    border:
        2px solid #e5e7eb;

    border-top-color:
        var(--brand-green);

    animation:
        spin 0.6s linear
        infinite;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {
    .metrics-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .dashboard-body-grid {
        grid-template-columns:
            1fr;
    }
}

@media (max-width: 640px) {
    .metrics-grid {
        grid-template-columns:
            1fr;
    }

    .commerce-header {
        flex-direction:
            column;

        align-items:
            stretch;
    }

    .card-header {
        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .timeframe-buttons {
        width: 100%;
    }

    .tf-btn {
        flex: 1;
    }

    .chart-wrapper {
        height: 260px;

        padding: 14px;
    }
}
</style>
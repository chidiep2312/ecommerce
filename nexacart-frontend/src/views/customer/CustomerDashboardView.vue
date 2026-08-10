<script setup>
import {
    CheckCircle2,
    Clock3,
    Heart,
    KeyRound,
    LayoutDashboard,
    LogOut,
    MapPin,
    Package,
    ShoppingBag,
    Star,
    UserRound,
} from '@lucide/vue'
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import {
    getCustomerDashboard,
} from '@/api/dashboard'
const router = useRouter()

const orders = ref([])

const isLoading = ref(false)
const errorMessage = ref('')
const user = ref(null)

const statistics = ref({
    total_orders: 0,
    pending_orders: 0,
    confirmed_orders: 0,
    shipping_orders: 0,
    completed_orders: 0,
    cancelled_orders: 0,
})

const recentOrders = ref([])

const userInitial = computed(() => {
    const name =
        user.value?.name?.trim()

    if (!name) {
        return 'U'
    }

    return name
        .charAt(0)
        .toUpperCase()
})

async function fetchDashboard() {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getCustomerDashboard()

        const data =
            response.data?.data

        user.value =
            data?.customer ?? null

        statistics.value = {
            total_orders:
                data?.statistics
                    ?.total_orders ?? 0,

            pending_orders:
                data?.statistics
                    ?.pending_orders ?? 0,

            confirmed_orders:
                data?.statistics
                    ?.confirmed_orders ?? 0,

            shipping_orders:
                data?.statistics
                    ?.shipping_orders ?? 0,

            completed_orders:
                data?.statistics
                    ?.completed_orders ?? 0,

            cancelled_orders:
                data?.statistics
                    ?.cancelled_orders ?? 0,
        }

        recentOrders.value =
            Array.isArray(
                data?.recent_orders
            )
                ? data.recent_orders
                : []
    } catch (error) {
        console.error(
            'Không thể tải dashboard:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải thông tin tài khoản.'
    } finally {
        isLoading.value = false
    }
}

function openOrders() {
    router.push({
        name: 'customer-orders',
    })
}

function openOrder(order) {
    router.push({
        name: 'customer-order-detail',

        params: {
            id: order.id,
        },
    })
}

function openProfile() {
    router.push({
        name: 'customer-profile',
    })
}

function openAddresses() {
    router.push({
        name: 'customer-addresses',
    })
}

function getOrderCode(order) {
    return (
        order.order_code ??
        `#${order.id}`
    )
}

function getSellerName(order) {
    return (
        order.seller?.name ??
        'Người bán'
    )
}

function getOrderTotal(order) {
    return (
        order.total_amount ??
        order.total ??
        0
    )
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

function formatDate(value) {
    if (!value) {
        return '--'
    }

    const date = new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
    ).format(date)
}

onMounted(() => {
    fetchDashboard()
})
</script>
<template>
    <div class="account-page">
        <div class="account-container">

                <!-- CONTENT -->
                <main class="account-content">
                    <section class="welcome-section">
                        <div>
                            <p class="welcome-label">
                                TỔNG QUAN TÀI KHOẢN
                            </p>

                            <h2>
                                Xin chào,
                                {{
                                    user?.name ??
                                    'Khách hàng'
                                }}
                            </h2>

                            <p>
                                Theo dõi đơn hàng và
                                quản lý thông tin tài
                                khoản của bạn.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="edit-profile-button"
                            @click="openProfile"
                        >
                            <UserRound
                                :size="17"
                            />

                            Chỉnh sửa hồ sơ
                        </button>
                    </section>

                    <!-- STATISTICS -->
                    <section class="statistics-grid">
                        <article class="stat-card">
                            <div class="stat-icon">
                                <ShoppingBag
                                    :size="21"
                                />
                            </div>

                            <div>
                                <span>
                                    Tổng đơn hàng
                                </span>

                                <strong>
                                    {{
                                        statistics
                                            .total_orders
                                    }}
                                </strong>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">
                                <Clock3
                                    :size="21"
                                />
                            </div>

                            <div>
                                <span>
                                    Chờ xác nhận
                                </span>

                                <strong>
                                    {{
                                        statistics
                                            .pending_orders
                                    }}
                                </strong>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">
                                <Package
                                    :size="21"
                                />
                            </div>

                            <div>
                                <span>
                                    Đang giao
                                </span>

                                <strong>
                                    {{
                                        statistics
                                            .shipping_orders
                                    }}
                                </strong>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">
                                <CheckCircle2
                                    :size="21"
                                />
                            </div>

                            <div>
                                <span>
                                    Hoàn thành
                                </span>

                                <strong>
                                    {{
                                        statistics
                                            .completed_orders
                                    }}
                                </strong>
                            </div>
                        </article>
                    </section>

                    <!-- MAIN AREA -->
                    <div class="dashboard-content-grid">
                        <section class="panel orders-panel">
                            <header class="panel-header">
                                <div>
                                    <h3>
                                        Đơn hàng gần đây
                                    </h3>

                                    <p>
                                        5 đơn hàng mới nhất
                                        của bạn.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="link-button"
                                    @click="openOrders"
                                >
                                    Xem tất cả
                                </button>
                            </header>

                            <div
                                v-if="
                                    recentOrders.length ===
                                    0
                                "
                                class="empty-state"
                            >
                                <ShoppingBag
                                    :size="38"
                                />

                                <strong>
                                    Chưa có đơn hàng
                                </strong>

                                <span>
                                    Hãy bắt đầu mua sắm
                                    trên NexaCart.
                                </span>

                                <RouterLink
                                    to="/products"
                                    class="shop-button"
                                >
                                    Khám phá sản phẩm
                                </RouterLink>
                            </div>

                            <div
                                v-else
                                class="table-wrapper"
                            >
                                <table>
                                    <thead>
                                        <tr>
                                            <th>
                                                Mã đơn
                                            </th>

                                            <th>
                                                Người bán
                                            </th>

                                            <th>
                                                Ngày đặt
                                            </th>

                                            <th>
                                                Tổng tiền
                                            </th>

                                            <th>
                                                Trạng thái
                                            </th>

                                            <th></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="
                                                order in
                                                recentOrders
                                            "
                                            :key="
                                                order.id
                                            "
                                        >
                                            <td>
                                                <button
                                                    type="button"
                                                    class="order-code"
                                                    @click="
                                                        openOrder(
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
                                                {{
                                                    getSellerName(
                                                        order,
                                                    )
                                                }}
                                            </td>

                                            <td>
                                                {{
                                                    formatDate(
                                                        order
                                                            .created_at,
                                                    )
                                                }}
                                            </td>

                                            <td
                                                class="money"
                                            >
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
                                                            order
                                                                .status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        getStatusLabel(
                                                            order
                                                                .status,
                                                        )
                                                    }}
                                                </span>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    class="detail-button"
                                                    @click="
                                                        openOrder(
                                                            order,
                                                        )
                                                    "
                                                >
                                                    Xem
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- PROFILE SUMMARY -->
                        <aside class="profile-summary">
                            <section class="panel">
                                <header class="panel-header">
                                    <div>
                                        <h3>
                                            Thông tin cá nhân
                                        </h3>

                                        <p>
                                            Thông tin tài khoản
                                            hiện tại.
                                        </p>
                                    </div>
                                </header>

                                <dl class="info-list">
                                    <div>
                                        <dt>
                                            Họ và tên
                                        </dt>

                                        <dd>
                                            {{
                                                user?.name ??
                                                '--'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Email
                                        </dt>

                                        <dd>
                                            {{
                                                user?.email ??
                                                '--'
                                            }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Vai trò
                                        </dt>

                                        <dd>
                                            Khách hàng
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Trạng thái
                                        </dt>

                                        <dd
                                            class="active-account"
                                        >
                                            Đang hoạt động
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>
                                            Ngày tham gia
                                        </dt>

                                        <dd>
                                            {{
                                                formatDate(
                                                    user?.created_at,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>

                                <button
                                    type="button"
                                    class="profile-button"
                                    @click="openProfile"
                                >
                                    Chỉnh sửa thông tin
                                </button>
                            </section>
                        </aside>
                    </div>
                </main>
            </div>
        </div>
    
</template>
<style scoped>
.account-page {
    min-height: calc(100vh - 70px);
    background: #f5f7f6;
    color: #18221c;
    font-family: Roboto, Arial, sans-serif;
}

.account-container {
    width: min(
        calc(100% - 40px),
        1320px
    );
    margin: 0 auto;
    padding: 34px 0 60px;
}

.account-heading {
    margin-bottom: 22px;
}

.account-heading p,
.welcome-label {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.13em;
}

.account-heading h1 {
    margin: 0;
    font-size: 28px;
}

.account-layout {
    display: grid;
    grid-template-columns:
        245px minmax(0, 1fr);
    gap: 22px;
    align-items: start;
}

/* SIDEBAR */

.account-sidebar {
    position: sticky;
    top: 90px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.sidebar-user {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 20px 16px;
    border-bottom: 1px solid #e5ebe7;
}

.sidebar-avatar {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    background: #24734a;
    color: #ffffff;
    font-size: 18px;
    font-weight: 700;
}

.sidebar-user-info {
    min-width: 0;
}

.sidebar-user-info strong {
    display: block;
    overflow: hidden;
    color: #202c24;
    font-size: 14px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-user-info span {
    display: block;
    overflow: hidden;
    margin-top: 4px;
    color: #78857c;
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-menu {
    padding: 8px 0;
}

.menu-item,
.logout-button {
    display: flex;
    width: 100%;
    min-height: 46px;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    border: 0;
    border-left: 3px solid transparent;
    background: #ffffff;
    color: #526158;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
}

.menu-item svg {
    color: #74837a;
}

.menu-item:hover {
    background: #f5f9f6;
    color: #1f5f3e;
}

.menu-item.active {
    border-left-color: #24734a;
    background: #edf6f0;
    color: #1f633f;
    font-weight: 700;
}

.menu-item.active svg {
    color: #24734a;
}

.sidebar-footer {
    padding: 8px 0;
    border-top: 1px solid #e5ebe7;
}

.logout-button {
    color: #a44343;
}

.logout-button:hover {
    background: #fff4f4;
}

/* CONTENT */

.account-content {
    min-width: 0;
}

.welcome-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 18px;
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.welcome-section h2 {
    margin: 0;
    font-size: 23px;
}

.welcome-section p:last-child {
    margin: 7px 0 0;
    color: #6e7c73;
    font-size: 13px;
}

.edit-profile-button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    gap: 8px;
    padding: 0 15px;
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.edit-profile-button:hover {
    background: #1e633f;
}

/* STATISTICS */

.statistics-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card {
    display: flex;
    min-height: 96px;
    align-items: center;
    gap: 13px;
    padding: 17px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.stat-icon {
    display: grid;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    place-items: center;
    background: #edf6f0;
    color: #24734a;
}

.stat-card span {
    display: block;
    color: #738077;
    font-size: 11px;
}

.stat-card strong {
    display: block;
    margin-top: 5px;
    color: #1d2921;
    font-size: 22px;
}

/* DASHBOARD */

.dashboard-content-grid {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        285px;
    gap: 18px;
    align-items: start;
}

.panel {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 17px 19px;
    border-bottom: 1px solid #e6ece8;
}

.panel-header h3 {
    margin: 0;
    font-size: 16px;
}

.panel-header p {
    margin: 4px 0 0;
    color: #7c887f;
    font-size: 11px;
}

.link-button {
    border: 0;
    background: transparent;
    color: #24734a;
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.link-button:hover {
    text-decoration: underline;
}

/* TABLE */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 740px;
    border-collapse: collapse;
}

th,
td {
    padding: 13px 14px;
    border-bottom: 1px solid #e9eeeb;
    text-align: left;
}

th {
    background: #f8faf9;
    color: #66746b;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

td {
    color: #3c4941;
    font-size: 12px;
}

tbody tr:hover {
    background: #fafcfb;
}

.order-code {
    border: 0;
    padding: 0;
    background: transparent;
    color: #216441;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.money {
    font-weight: 700;
    white-space: nowrap;
}

.detail-button {
    border: 1px solid #c6d2ca;
    padding: 6px 10px;
    background: #ffffff;
    color: #256342;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.detail-button:hover {
    border-color: #24734a;
    background: #edf6f0;
}

/* STATUS */

.status-badge {
    display: inline-flex;
    min-height: 25px;
    align-items: center;
    padding: 0 8px;
    border: 1px solid;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-pending {
    border-color: #e0ca89;
    background: #fff8df;
    color: #7c600b;
}

.status-confirmed,
.status-processing {
    border-color: #accab7;
    background: #edf7f0;
    color: #236241;
}

.status-shipping {
    border-color: #b8c8d7;
    background: #f0f5f8;
    color: #456079;
}

.status-completed {
    border-color: #9bc6a9;
    background: #eaf7ee;
    color: #1e673f;
}

.status-cancelled {
    border-color: #deb0b0;
    background: #fff1f1;
    color: #983535;
}

/* PROFILE */

.profile-summary {
    min-width: 0;
}

.info-list {
    margin: 0;
}

.info-list > div {
    padding: 13px 17px;
    border-bottom: 1px solid #e9eeeb;
}

.info-list dt {
    margin-bottom: 5px;
    color: #7d8981;
    font-size: 10px;
    text-transform: uppercase;
}

.info-list dd {
    margin: 0;
    overflow-wrap: anywhere;
    color: #35433a;
    font-size: 12px;
    font-weight: 500;
}

.active-account {
    color: #24734a !important;
}

.profile-button {
    width: calc(100% - 34px);
    min-height: 39px;
    margin: 16px 17px;
    border: 1px solid #c2d0c6;
    background: #ffffff;
    color: #245f3f;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.profile-button:hover {
    border-color: #24734a;
    background: #edf6f0;
}

/* EMPTY */

.empty-state {
    display: flex;
    min-height: 300px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 30px;
    color: #718078;
    text-align: center;
}

.empty-state svg {
    color: #24734a;
}

.empty-state strong {
    margin-top: 14px;
    color: #28352d;
    font-size: 15px;
}

.empty-state span {
    margin-top: 5px;
    font-size: 12px;
}

.shop-button {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    margin-top: 16px;
    padding: 0 14px;
    background: #24734a;
    color: #ffffff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

/* GENERAL */

.alert {
    margin-bottom: 18px;
    padding: 12px 15px;
    border: 1px solid;
}

.alert-error {
    border-color: #deb0b0;
    background: #fff2f2;
    color: #983636;
}

.loading-box {
    display: grid;
    min-height: 400px;
    place-items: center;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #718078;
}

/* RESPONSIVE */

@media (max-width: 1080px) {
    .statistics-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .dashboard-content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 820px) {
    .account-layout {
        grid-template-columns: 1fr;
    }

    .account-sidebar {
        position: static;
    }

    .account-menu {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .menu-item {
        border-left: 0;
        border-bottom: 2px solid transparent;
    }

    .menu-item.active {
        border-bottom-color: #24734a;
    }
}

@media (max-width: 600px) {
    .account-container {
        width: calc(100% - 28px);
        padding-top: 22px;
    }

    .welcome-section {
        align-items: flex-start;
        flex-direction: column;
    }

    .edit-profile-button {
        width: 100%;
        justify-content: center;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
    }

    .account-menu {
        grid-template-columns: 1fr;
    }
}
</style>
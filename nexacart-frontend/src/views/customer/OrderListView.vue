<script setup>
import {
    ChevronLeft,
    ChevronRight,
    Clock3,
    Eye,
    Package,
    Search,
    ShoppingBag,
    Store,
    XCircle,
} from '@lucide/vue'

import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    cancelCustomerOrder,
    getCustomerOrders,
} from '@/api/orders'

const router = useRouter()

const orders = ref([])
const pagination = ref(null)

const isLoading = ref(false)
const cancellingOrderId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const filters = reactive({
    search: '',
    status: '',
    page: 1,
    per_page: 10,
})

let searchTimer = null

const currentPage = computed(() => {
    return Number(
        pagination.value?.current_page ??
        1,
    )
})

const lastPage = computed(() => {
    return Number(
        pagination.value?.last_page ??
        1,
    )
})

const totalOrders = computed(() => {
    return Number(
        pagination.value?.total ??
        orders.value.length,
    )
})

const paginationFrom = computed(() => {
    return Number(
        pagination.value?.from ??
        0,
    )
})

const paginationTo = computed(() => {
    return Number(
        pagination.value?.to ??
        0,
    )
})

const statusTabs = [
    {
        value: '',
        label: 'Tất cả',
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
        value: 'shipping',
        label: 'Đang giao',
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

async function fetchOrders() {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getCustomerOrders({
                page:
                    filters.page,

                per_page:
                    filters.per_page,

                search:
                    filters.search.trim() ||
                    undefined,

                status:
                    filters.status ||
                    undefined,
            })

        orders.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []

        pagination.value =
            response.data?.meta ??
            null
    } catch (error) {
        console.error(
            'Không thể tải đơn hàng:',
            error,
        )

        orders.value = []
        pagination.value = null

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách đơn hàng.'
    } finally {
        isLoading.value = false
    }
}

function changeStatus(status) {
    if (
        filters.status === status
    ) {
        return
    }

    filters.status = status
    filters.page = 1

    fetchOrders()
}

function handleSearch() {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(
        () => {
            filters.page = 1
            fetchOrders()
        },
        400,
    )
}

function resetSearch() {
    filters.search = ''
    filters.page = 1

    fetchOrders()
}

function changePerPage() {
    filters.page = 1
    fetchOrders()
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

    fetchOrders()

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

function openOrder(order) {
    router.push({
        name:
            'customer-order-detail',

        params: {
            id: order.id,
        },
    })
}

async function cancelOrder(order) {
    if (
        order.status !== 'pending' ||
        cancellingOrderId.value
    ) {
        return
    }

    const confirmed =
        window.confirm(
            `Bạn có chắc muốn hủy đơn hàng "${getOrderCode(order)}" không?`,
        )

    if (!confirmed) {
        return
    }

    cancellingOrderId.value =
        order.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await cancelCustomerOrder(
                order.id,
            )

        successMessage.value =
            response.data?.message ??
            'Hủy đơn hàng thành công.'

        /*
         * Có thể cập nhật trực tiếp order,
         * nhưng fetch lại giúp đồng bộ luôn
         * stock/status từ backend.
         */
        await fetchOrders()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            getFirstValidationError(
                error.response?.data
                    ?.errors,
            ) ??
            'Không thể hủy đơn hàng.'
    } finally {
        cancellingOrderId.value =
            null
    }
}

function getFirstValidationError(
    errors,
) {
    if (
        !errors ||
        typeof errors !== 'object'
    ) {
        return null
    }

    return (
        Object.values(errors)
            .flat()[0] ??
        null
    )
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
    return Number(
        order.total_amount ??
        order.total ??
        0,
    )
}

function getItemsCount(order) {
    return Number(
        order.items_count ??
        order.items?.length ??
        0,
    )
}

function getStatusLabel(status) {
    const labels = {
        pending:
            'Chờ xác nhận',

        confirmed:
            'Đã xác nhận',

        shipping:
            'Đang giao hàng',

        completed:
            'Hoàn thành',

        cancelled:
            'Đã hủy',
    }

    return (
        labels[status] ??
        status ??
        '--'
    )
}

function getStatusClass(status) {
    return [
        'status-badge',
        `status-${status ?? 'unknown'}`,
    ]
}

function formatCurrency(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(
        Number(value ?? 0),
    )
}

function formatDate(value) {
    if (!value) {
        return '--'
    }

    const date =
        new Date(value)

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
            hour: '2-digit',
            minute: '2-digit',
        },
    ).format(date)
}

onMounted(() => {
    fetchOrders()
})

onBeforeUnmount(() => {
    clearTimeout(searchTimer)
})
</script>

<template>
    <div class="order-list-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">
                    ĐƠN HÀNG
                </p>

                <h2>
                    Đơn hàng của tôi
                </h2>

                <p>
                    Theo dõi trạng thái và
                    quản lý các đơn hàng
                    đã đặt trên NexaCart.
                </p>
            </div>
        </header>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <!-- STATUS TABS -->
        <section class="status-tabs">
            <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                :class="{
                    active:
                        filters.status ===
                        tab.value,
                }"
                @click="
                    changeStatus(
                        tab.value,
                    )
                "
            >
                {{ tab.label }}
            </button>
        </section>

        <!-- SEARCH -->
        <section class="toolbar">
            <div class="search-box">
                <Search :size="18" />

                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Tìm theo mã đơn hàng..."
                    @input="handleSearch"
                >

                <button
                    v-if="
                        filters.search
                    "
                    type="button"
                    aria-label="Xóa tìm kiếm"
                    @click="resetSearch"
                >
                    ×
                </button>
            </div>

            <div class="toolbar-right">
                <span>
                    {{
                        totalOrders
                    }}
                    đơn hàng
                </span>

                <select
                    v-model.number="
                        filters.per_page
                    "
                    @change="
                        changePerPage
                    "
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
                </select>
            </div>
        </section>

        <div
            v-if="isLoading"
            class="state-box"
        >
            Đang tải đơn hàng...
        </div>

        <div
            v-else-if="
                orders.length === 0
            "
            class="empty-state"
        >
            <ShoppingBag :size="42" />

            <h3>
                Chưa có đơn hàng
            </h3>

            <p>
                Không tìm thấy đơn hàng
                phù hợp với điều kiện hiện tại.
            </p>

            <RouterLink
                to="/products"
                class="shopping-button"
            >
                Tiếp tục mua sắm
            </RouterLink>
        </div>

        <section
            v-else
            class="order-list"
        >
            <article
                v-for="order in orders"
                :key="order.id"
                class="order-card"
            >
                <!-- ORDER HEADER -->
                <header class="order-card-header">
                    <div class="order-meta">
                        <strong>
                            {{
                                getOrderCode(
                                    order,
                                )
                            }}
                        </strong>

                        <span>
                            {{
                                formatDate(
                                    order.created_at,
                                )
                            }}
                        </span>
                    </div>

                    <span
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
                </header>

                <!-- SELLER -->
                <div class="seller-row">
                    <Store :size="17" />

                    <span>
                        Người bán:
                    </span>

                    <strong>
                        {{
                            getSellerName(
                                order,
                            )
                        }}
                    </strong>
                </div>

                <!-- ORDER CONTENT -->
                <div class="order-body">
                    <div class="order-summary">
                        <div class="summary-icon">
                            <Package
                                :size="22"
                            />
                        </div>

                        <div>
                            <strong>
                                {{
                                    getItemsCount(
                                        order,
                                    )
                                }}
                                sản phẩm
                            </strong>

                            <span>
                                Đơn hàng
                                {{
                                    getOrderCode(
                                        order,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <dl class="price-summary">
                        <div>
                            <dt>
                                Tạm tính
                            </dt>

                            <dd>
                                {{
                                    formatCurrency(
                                        order.subtotal,
                                    )
                                }}
                            </dd>
                        </div>

                        <div
                            v-if="
                                Number(
                                    order
                                        .discount_amount,
                                ) > 0
                            "
                        >
                            <dt>
                                Giảm giá
                            </dt>

                            <dd
                                class="discount"
                            >
                                -
                                {{
                                    formatCurrency(
                                        order
                                            .discount_amount,
                                    )
                                }}
                            </dd>
                        </div>

                        <div
                            v-if="
                                Number(
                                    order
                                        .shipping_fee,
                                ) > 0
                            "
                        >
                            <dt>
                                Phí vận chuyển
                            </dt>

                            <dd>
                                {{
                                    formatCurrency(
                                        order
                                            .shipping_fee,
                                    )
                                }}
                            </dd>
                        </div>

                        <div class="total-row">
                            <dt>
                                Tổng thanh toán
                            </dt>

                            <dd>
                                {{
                                    formatCurrency(
                                        getOrderTotal(
                                            order,
                                        ),
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- ORDER FOOTER -->
                <footer class="order-footer">
                    <div class="status-note">
                        <Clock3
                            v-if="
                                order.status ===
                                'pending'
                            "
                            :size="15"
                        />

                        <Package
                            v-else-if="
                                order.status ===
                                'shipping'
                            "
                            :size="15"
                        />

                        <span>
                            <template
                                v-if="
                                    order.status ===
                                    'pending'
                                "
                            >
                                Đơn hàng đang chờ
                                người bán xác nhận.
                            </template>

                            <template
                                v-else-if="
                                    order.status ===
                                    'confirmed'
                                "
                            >
                                Người bán đã xác nhận
                                đơn hàng.
                            </template>

                            <template
                                v-else-if="
                                    order.status ===
                                    'shipping'
                                "
                            >
                                Đơn hàng đang được
                                giao đến bạn.
                            </template>

                            <template
                                v-else-if="
                                    order.status ===
                                    'completed'
                                "
                            >
                                Đơn hàng đã hoàn thành.
                            </template>

                            <template
                                v-else-if="
                                    order.status ===
                                    'cancelled'
                                "
                            >
                                Đơn hàng đã bị hủy.
                            </template>
                        </span>
                    </div>

                    <div class="order-actions">
                        <button
                            v-if="
                                order.status ===
                                'pending'
                            "
                            type="button"
                            class="cancel-button"
                            :disabled="
                                cancellingOrderId ===
                                order.id
                            "
                            @click="
                                cancelOrder(
                                    order,
                                )
                            "
                        >
                            <XCircle
                                :size="16"
                            />

                            {{
                                cancellingOrderId ===
                                order.id
                                    ? 'Đang hủy...'
                                    : 'Hủy đơn'
                            }}
                        </button>

                        <button
                            type="button"
                            class="detail-button"
                            @click="
                                openOrder(
                                    order,
                                )
                            "
                        >
                            <Eye :size="16" />

                            Xem chi tiết
                        </button>
                    </div>
                </footer>
            </article>
        </section>

        <!-- PAGINATION -->
        <footer
            v-if="
                !isLoading &&
                orders.length > 0
            "
            class="pagination"
        >
            <div>
                Hiển thị
                <strong>
                    {{ paginationFrom }}
                </strong>

                –

                <strong>
                    {{ paginationTo }}
                </strong>

                trong

                <strong>
                    {{ totalOrders }}
                </strong>

                đơn hàng
            </div>

            <div class="pagination-controls">
                <button
                    type="button"
                    :disabled="
                        currentPage <= 1
                    "
                    @click="
                        changePage(
                            currentPage -
                            1,
                        )
                    "
                >
                    <ChevronLeft
                        :size="16"
                    />

                    Trước
                </button>

                <span>
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
                        currentPage >=
                        lastPage
                    "
                    @click="
                        changePage(
                            currentPage +
                            1,
                        )
                    "
                >
                    Sau

                    <ChevronRight
                        :size="16"
                    />
                </button>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.order-list-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1d2921;
    font-family: Roboto, Arial, sans-serif;
}

.page-header {
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h2 {
    margin: 0;
    font-size: 22px;
}

.page-header > div > p:last-child {
    margin: 7px 0 0;
    color: #6f7c73;
    font-size: 13px;
}

.status-tabs {
    display: flex;
    overflow-x: auto;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.status-tabs button {
    min-width: 120px;
    min-height: 48px;
    padding: 0 16px;
    border: 0;
    border-right: 1px solid #e4ebe6;
    border-bottom: 3px solid transparent;
    color: #647269;
    background: #ffffff;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.status-tabs button:hover {
    background: #f6f9f7;
}

.status-tabs button.active {
    border-bottom-color: #24734a;
    color: #24734a;
    background: #f1f7f3;
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 16px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.search-box {
    display: flex;
    width: min(100%, 430px);
    height: 40px;
    align-items: center;
    border: 1px solid #cad5ce;
    background: #ffffff;
}

.search-box:focus-within {
    border-color: #24734a;
}

.search-box svg {
    flex: 0 0 auto;
    margin-left: 12px;
    color: #7b887f;
}

.search-box input {
    width: 100%;
    height: 100%;
    padding: 0 10px;
    border: 0;
    outline: none;
    background: transparent;
    color: #2a372f;
    font: inherit;
    font-size: 12px;
}

.search-box > button {
    width: 38px;
    height: 38px;
    border: 0;
    color: #78857c;
    background: transparent;
    font-size: 20px;
    cursor: pointer;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toolbar-right span {
    color: #758279;
    font-size: 11px;
}

.toolbar-right select {
    height: 40px;
    padding: 0 10px;
    border: 1px solid #cad5ce;
    outline: none;
    color: #435148;
    background: #ffffff;
    font: inherit;
    font-size: 11px;
}

.order-list {
    display: grid;
    gap: 14px;
}

.order-card {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.order-card-header {
    display: flex;
    min-height: 58px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 0 18px;
    border-bottom: 1px solid #e8edea;
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.order-meta strong {
    color: #285f40;
    font-size: 13px;
}

.order-meta span {
    padding-left: 12px;
    border-left: 1px solid #d5ddd8;
    color: #7a877f;
    font-size: 11px;
}

.status-badge {
    display: inline-flex;
    min-height: 26px;
    align-items: center;
    padding: 0 9px;
    border: 1px solid;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-pending {
    border-color: #dfc98b;
    color: #7c610f;
    background: #fff8e3;
}

.status-confirmed {
    border-color: #a9c9b5;
    color: #246242;
    background: #edf7f0;
}

.status-shipping {
    border-color: #b8c8d7;
    color: #455f79;
    background: #f0f5f8;
}

.status-completed {
    border-color: #99c6a8;
    color: #20663f;
    background: #eaf7ee;
}

.status-cancelled {
    border-color: #dfb0b0;
    color: #963535;
    background: #fff1f1;
}

.seller-row {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 12px 18px;
    border-bottom: 1px solid #edf1ee;
    color: #68766d;
    font-size: 11px;
}

.seller-row svg {
    color: #24734a;
}

.seller-row strong {
    color: #35433a;
}

.order-body {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        280px;
    gap: 30px;
    padding: 20px 18px;
}

.order-summary {
    display: flex;
    align-items: center;
    gap: 13px;
}

.summary-icon {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    color: #24734a;
    background: #edf6f0;
}

.order-summary strong,
.order-summary span {
    display: block;
}

.order-summary strong {
    color: #2b382f;
    font-size: 13px;
}

.order-summary span {
    margin-top: 5px;
    color: #78857c;
    font-size: 11px;
}

.price-summary {
    display: grid;
    gap: 8px;
    margin: 0;
}

.price-summary > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.price-summary dt {
    color: #77847b;
    font-size: 11px;
}

.price-summary dd {
    margin: 0;
    color: #3c4941;
    font-size: 11px;
    font-weight: 600;
}

.price-summary dd.discount {
    color: #ac443d;
}

.total-row {
    margin-top: 3px;
    padding-top: 9px;
    border-top: 1px solid #e6ebe8;
}

.total-row dt {
    color: #344239;
    font-weight: 700;
}

.total-row dd {
    color: #24734a;
    font-size: 15px;
    font-weight: 700;
}

.order-footer {
    display: flex;
    min-height: 66px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 10px 18px;
    border-top: 1px solid #e8edea;
    background: #fafcfb;
}

.status-note {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #718078;
    font-size: 11px;
}

.status-note svg {
    color: #24734a;
}

.order-actions {
    display: flex;
    gap: 8px;
}

.cancel-button,
.detail-button {
    display: inline-flex;
    min-height: 36px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 12px;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.cancel-button {
    border: 1px solid #d9aaaa;
    color: #9a3939;
    background: #ffffff;
}

.detail-button {
    border: 1px solid #24734a;
    color: #ffffff;
    background: #24734a;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.state-box,
.empty-state {
    display: flex;
    min-height: 330px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #718078;
    text-align: center;
}

.empty-state svg {
    color: #24734a;
}

.empty-state h3 {
    margin: 14px 0 5px;
    color: #2a372f;
}

.empty-state p {
    margin: 0;
    font-size: 12px;
}

.shopping-button {
    display: inline-flex;
    min-height: 39px;
    align-items: center;
    margin-top: 17px;
    padding: 0 14px;
    color: #ffffff;
    background: #24734a;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 16px;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #738078;
    font-size: 11px;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-controls button {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    gap: 5px;
    padding: 0 10px;
    border: 1px solid #c8d3cc;
    color: #46544b;
    background: #ffffff;
    font: inherit;
    font-size: 11px;
    cursor: pointer;
}

.alert {
    padding: 12px 15px;
    border: 1px solid;
    font-size: 12px;
}

.alert-error {
    border-color: #dfb1b1;
    color: #973737;
    background: #fff3f3;
}

.alert-success {
    border-color: #9fc6ab;
    color: #21633f;
    background: #edf7f0;
}

@media (max-width: 760px) {
    .toolbar,
    .order-footer,
    .pagination {
        align-items: stretch;
        flex-direction: column;
    }

    .search-box {
        width: 100%;
    }

    .toolbar-right {
        justify-content: space-between;
    }

    .order-body {
        grid-template-columns: 1fr;
    }

    .order-actions {
        width: 100%;
    }

    .order-actions button {
        flex: 1;
    }

    .pagination-controls {
        justify-content: space-between;
    }
}

@media (max-width: 520px) {
    .order-card-header {
        align-items: flex-start;
        flex-direction: column;
        padding-block: 14px;
    }

    .order-meta {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

    .order-meta span {
        padding-left: 0;
        border-left: 0;
    }
}
</style>
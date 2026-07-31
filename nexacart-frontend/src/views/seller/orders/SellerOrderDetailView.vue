<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getSellerOrder,
    updateSellerOrderStatus,
} from '@/api/seller/orders'

const route = useRoute()
const router = useRouter()

const order = ref(null)

const isLoading = ref(false)
const isUpdating = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const selectedStatus = ref('')

const orderId = computed(() => {
    return route.params.id
})

const availableStatuses = computed(() => {
    if (!order.value) {
        return []
    }

    const transitions = {
        pending: [
            {
                value: 'confirmed',
                label: 'Xác nhận đơn hàng',
            },
            {
                value: 'cancelled',
                label: 'Hủy đơn hàng',
            },
        ],

        confirmed: [
            {
                value: 'processing',
                label: 'Chuyển sang xử lý',
            },
            {
                value: 'cancelled',
                label: 'Hủy đơn hàng',
            },
        ],

        processing: [
            {
                value: 'shipping',
                label: 'Bắt đầu giao hàng',
            },
            {
                value: 'cancelled',
                label: 'Hủy đơn hàng',
            },
        ],

        shipping: [
            {
                value: 'completed',
                label: 'Xác nhận hoàn thành',
            },
        ],

        completed: [],
        cancelled: [],
    }

    return (
        transitions[
            order.value.status
        ] ?? []
    )
})

const canUpdateStatus = computed(() => {
    return (
        availableStatuses.value.length >
        0
    )
})

const orderItems = computed(() => {
    return Array.isArray(
        order.value?.items,
    )
        ? order.value.items
        : []
})

async function fetchOrder() {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getSellerOrder(
                orderId.value,
            )

        order.value =
            response.data?.data?.data ??
            response.data?.data ??
            null

        selectedStatus.value = ''
    } catch (error) {
        console.error(
            'Không thể tải đơn hàng:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải chi tiết đơn hàng.'
    } finally {
        isLoading.value = false
    }
}

async function submitStatus() {
    if (
        !selectedStatus.value ||
        isUpdating.value
    ) {
        return
    }

    const selectedOption =
        availableStatuses.value.find(
            item =>
                item.value ===
                selectedStatus.value,
        )

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${
            selectedOption?.label
                ?.toLowerCase() ??
            'cập nhật trạng thái'
        }?`,
    )

    if (!confirmed) {
        return
    }

    isUpdating.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await updateSellerOrderStatus(
                orderId.value,
                selectedStatus.value,
            )

        order.value =
            response.data?.data?.data ??
            response.data?.data ??
            order.value

        selectedStatus.value = ''

        successMessage.value =
            response.data?.message ??
            'Cập nhật trạng thái thành công.'
    } catch (error) {
        console.error(
            'Không thể cập nhật trạng thái:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            Object.values(
                error.response?.data
                    ?.errors ?? {},
            )
                .flat()
                .join(' ') ??
            'Không thể cập nhật trạng thái.'
    } finally {
        isUpdating.value = false
    }
}

function goBack() {
    router.push({
        name: 'seller-orders',
    })
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

function getOrderCode() {
    return (
        order.value?.code ??
        order.value?.order_code ??
        `#${order.value?.id ?? ''}`
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

function getCustomerName() {
    return (
        order.value?.customer?.name ??
        order.value?.user?.name ??
        order.value?.customer_name ??
        'Khách hàng'
    )
}

function getCustomerEmail() {
    return (
        order.value?.customer?.email ??
        order.value?.user?.email ??
        order.value?.email ??
        '--'
    )
}

function getCustomerPhone() {
    return (
        order.value?.shipping_phone ??
        order.value?.phone ??
        order.value?.customer?.phone ??
        '--'
    )
}

function getShippingAddress() {
    return (
        order.value?.shipping_address ??
        order.value?.address ??
        '--'
    )
}

function getProductName(item) {
    return (
        item.product?.name ??
        item.product_name ??
        item.name ??
        'Sản phẩm'
    )
}

function getProductImage(item) {
    return (
        item.product?.main_image?.url ??
        item.product?.image_url ??
        item.image_url ??
        ''
    )
}

function getProductSku(item) {
    return (
        item.product?.sku ??
        item.sku ??
        '--'
    )
}

function getItemPrice(item) {
    return (
        item.unit_price ??
        item.price ??
        0
    )
}

function getItemSubtotal(item) {
    return (
        item.subtotal ??
        Number(getItemPrice(item)) *
            Number(item.quantity ?? 0)
    )
}

function getSubtotal() {
    return (
        order.value?.subtotal ??
        orderItems.value.reduce(
            (total, item) =>
                total +
                Number(
                    getItemSubtotal(item),
                ),
            0,
        )
    )
}

function getShippingFee() {
    return (
        order.value?.shipping_fee ?? 0
    )
}

function getDiscount() {
    return (
        order.value?.discount_amount ??
        order.value?.discount ??
        0
    )
}

function getTotal() {
    return (
        order.value?.total_amount ??
        order.value?.grand_total ??
        order.value?.total ??
        getSubtotal() +
            Number(getShippingFee()) -
            Number(getDiscount())
    )
}

onMounted(() => {
    fetchOrder()
})
</script>

<template>
    <div class="order-detail-page">
        <button
            type="button"
            class="back-button"
            @click="goBack"
        >
            ← Quay lại danh sách
        </button>

        <div
            v-if="isLoading"
            class="state-box"
        >
            Đang tải chi tiết đơn hàng...
        </div>

        <div
            v-else-if="
                errorMessage && !order
            "
            class="state-box error-state"
        >
            <p>{{ errorMessage }}</p>

            <button
                type="button"
                @click="fetchOrder"
            >
                Thử lại
            </button>
        </div>

        <template v-else-if="order">
            <header class="detail-header">
                <div>
                    <p class="page-eyebrow">
                        CHI TIẾT ĐƠN HÀNG
                    </p>

                    <div class="title-row">
                        <h1>
                            {{ getOrderCode() }}
                        </h1>

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
                    </div>

                    <p class="created-time">
                        Đặt lúc
                        {{
                            formatDate(
                                order.created_at,
                            )
                        }}
                    </p>
                </div>
            </header>

            <div
                v-if="successMessage"
                class="alert alert-success"
            >
                {{ successMessage }}
            </div>

            <div
                v-if="
                    errorMessage && order
                "
                class="alert alert-error"
            >
                {{ errorMessage }}
            </div>

            <main class="detail-layout">
                <section class="main-column">
                    <article class="panel">
                        <div class="panel-header">
                            <h2>
                                Sản phẩm trong đơn
                            </h2>

                            <span>
                                {{
                                    orderItems.length
                                }}
                                loại sản phẩm
                            </span>
                        </div>

                        <div
                            v-if="
                                orderItems.length ===
                                0
                            "
                            class="empty-items"
                        >
                            Đơn hàng chưa có dữ
                            liệu sản phẩm.
                        </div>

                        <div
                            v-else
                            class="item-list"
                        >
                            <div
                                v-for="
                                    item in
                                    orderItems
                                "
                                :key="item.id"
                                class="order-item"
                            >
                                <div
                                    class="product-image"
                                >
                                    <img
                                        v-if="
                                            getProductImage(
                                                item,
                                            )
                                        "
                                        :src="
                                            getProductImage(
                                                item,
                                            )
                                        "
                                        :alt="
                                            getProductName(
                                                item,
                                            )
                                        "
                                    >

                                    <span v-else>
                                        SP
                                    </span>
                                </div>

                                <div
                                    class="product-info"
                                >
                                    <strong>
                                        {{
                                            getProductName(
                                                item,
                                            )
                                        }}
                                    </strong>

                                    <span>
                                        SKU:
                                        {{
                                            getProductSku(
                                                item,
                                            )
                                        }}
                                    </span>

                                    <span>
                                        {{
                                            formatCurrency(
                                                getItemPrice(
                                                    item,
                                                ),
                                            )
                                        }}
                                        ×
                                        {{
                                            item.quantity
                                        }}
                                    </span>
                                </div>

                                <strong
                                    class="item-subtotal"
                                >
                                    {{
                                        formatCurrency(
                                            getItemSubtotal(
                                                item,
                                            ),
                                        )
                                    }}
                                </strong>
                            </div>
                        </div>
                    </article>

                    <article class="panel">
                        <div class="panel-header">
                            <h2>
                                Thông tin giao hàng
                            </h2>
                        </div>

                        <div
                            class="information-grid"
                        >
                            <div>
                                <span>
                                    Người nhận
                                </span>

                                <strong>
                                    {{
                                        getCustomerName()
                                    }}
                                </strong>
                            </div>

                            <div>
                                <span>
                                    Số điện thoại
                                </span>

                                <strong>
                                    {{
                                        getCustomerPhone()
                                    }}
                                </strong>
                            </div>

                            <div>
                                <span>Email</span>

                                <strong>
                                    {{
                                        getCustomerEmail()
                                    }}
                                </strong>
                            </div>

                            <div
                                class="full-width"
                            >
                                <span>
                                    Địa chỉ
                                </span>

                                <strong>
                                    {{
                                        getShippingAddress()
                                    }}
                                </strong>
                            </div>

                            <div
                                v-if="order.note"
                                class="full-width"
                            >
                                <span>
                                    Ghi chú
                                </span>

                                <strong>
                                    {{ order.note }}
                                </strong>
                            </div>
                        </div>
                    </article>
                </section>

                <aside class="sidebar-column">
                    <article
                        v-if="canUpdateStatus"
                        class="panel status-panel"
                    >
                        <h2>
                            Cập nhật trạng thái
                        </h2>

                        <p>
                            Chọn bước xử lý tiếp
                            theo cho đơn hàng này.
                        </p>

                        <select
                            v-model="
                                selectedStatus
                            "
                            :disabled="
                                isUpdating
                            "
                        >
                            <option value="">
                                Chọn trạng thái
                            </option>

                            <option
                                v-for="
                                    status in
                                    availableStatuses
                                "
                                :key="
                                    status.value
                                "
                                :value="
                                    status.value
                                "
                            >
                                {{ status.label }}
                            </option>
                        </select>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="
                                !selectedStatus ||
                                isUpdating
                            "
                            @click="
                                submitStatus
                            "
                        >
                            {{
                                isUpdating
                                    ? 'Đang cập nhật...'
                                    : 'Cập nhật trạng thái'
                            }}
                        </button>
                    </article>

                    <article
                        v-else
                        class="panel final-status-panel"
                    >
                        <h2>
                            Trạng thái cuối
                        </h2>

                        <p>
                            Đơn hàng đã
                            {{
                                order.status ===
                                'completed'
                                    ? 'hoàn thành'
                                    : 'bị hủy'
                            }}
                            và không thể cập nhật
                            thêm.
                        </p>
                    </article>

                    <article class="panel summary-panel">
                        <h2>
                            Tổng thanh toán
                        </h2>

                        <div class="summary-row">
                            <span>Tạm tính</span>

                            <strong>
                                {{
                                    formatCurrency(
                                        getSubtotal(),
                                    )
                                }}
                            </strong>
                        </div>

                        <div class="summary-row">
                            <span>
                                Phí giao hàng
                            </span>

                            <strong>
                                {{
                                    formatCurrency(
                                        getShippingFee(),
                                    )
                                }}
                            </strong>
                        </div>

                        <div class="summary-row">
                            <span>Giảm giá</span>

                            <strong>
                                -
                                {{
                                    formatCurrency(
                                        getDiscount(),
                                    )
                                }}
                            </strong>
                        </div>

                        <div
                            class="summary-row total-row"
                        >
                            <span>Tổng cộng</span>

                            <strong>
                                {{
                                    formatCurrency(
                                        getTotal(),
                                    )
                                }}
                            </strong>
                        </div>

                        <div class="payment-info">
                            <span>
                                Phương thức thanh toán
                            </span>

                            <strong>
                                {{
                                    order.payment_method ??
                                    'COD'
                                }}
                            </strong>
                        </div>
                    </article>
                </aside>
            </main>
        </template>
    </div>
</template>

<style scoped>
.order-detail-page {
    min-height: 100%;
    padding: 28px;
    background: #f5f7f6;
    color: #172019;
    font-family: Roboto, Arial, sans-serif;
}

.back-button {
    border: 0;
    background: transparent;
    color: #21633f;
    padding: 0;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 20px;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.page-eyebrow {
    margin: 0 0 8px;
    color: #267149;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.title-row {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.title-row h1 {
    margin: 0;
    font-size: 30px;
}

.created-time {
    margin: 8px 0 0;
    color: #6d7870;
    font-size: 14px;
}

.detail-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        360px;
    gap: 20px;
    align-items: start;
}

.main-column,
.sidebar-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel {
    border: 1px solid #dce4df;
    background: #ffffff;
    padding: 22px;
}

.panel h2 {
    margin: 0;
    font-size: 18px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e5eae7;
}

.panel-header span {
    color: #748078;
    font-size: 13px;
}

.item-list {
    display: flex;
    flex-direction: column;
}

.order-item {
    display: grid;
    grid-template-columns:
        76px minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    padding: 18px 0;
    border-bottom: 1px solid #e9edea;
}

.order-item:last-child {
    border-bottom: 0;
}

.product-image {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 76px;
    height: 76px;
    border: 1px solid #dce4df;
    background: #f3f6f4;
    color: #698074;
    font-weight: 700;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.product-info strong {
    font-size: 15px;
}

.product-info span {
    color: #6d7971;
    font-size: 13px;
}

.item-subtotal {
    white-space: nowrap;
}

.empty-items {
    padding: 40px 0;
    color: #6d7971;
    text-align: center;
}

.information-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 24px;
    padding-top: 20px;
}

.information-grid > div {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.information-grid span {
    color: #77827b;
    font-size: 13px;
}

.information-grid strong {
    font-size: 14px;
    line-height: 1.6;
}

.full-width {
    grid-column: 1 / -1;
}

.status-panel p,
.final-status-panel p {
    margin: 10px 0 18px;
    color: #6d7971;
    font-size: 14px;
    line-height: 1.6;
}

.status-panel select {
    width: 100%;
    height: 44px;
    border: 1px solid #cdd7d0;
    background: #ffffff;
    padding: 0 12px;
    font: inherit;
    outline: none;
}

.status-panel select:focus {
    border-color: #267149;
}

.primary-button {
    width: 100%;
    min-height: 44px;
    margin-top: 12px;
    border: 1px solid #1f6844;
    background: #1f6844;
    color: #ffffff;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.primary-button:hover:not(:disabled) {
    background: #185537;
}

.primary-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.summary-panel h2 {
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 11px 0;
    color: #5f6d64;
    font-size: 14px;
}

.summary-row strong {
    color: #1b271f;
}

.total-row {
    margin-top: 8px;
    padding-top: 18px;
    border-top: 1px solid #dce4df;
    font-size: 17px;
}

.total-row strong {
    color: #17623c;
}

.payment-info {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e4e9e6;
    font-size: 13px;
}

.payment-info span {
    color: #718078;
}

.alert {
    margin-bottom: 18px;
    border: 1px solid;
    padding: 14px 16px;
}

.alert-success {
    border-color: #9bc7aa;
    background: #edf8f1;
    color: #20633f;
}

.alert-error {
    border-color: #deb0b0;
    background: #fff1f1;
    color: #952828;
}

.state-box {
    border: 1px solid #dce4df;
    background: #ffffff;
    padding: 70px 20px;
    text-align: center;
    color: #69766e;
}

.error-state {
    color: #922c2c;
}

.error-state button {
    min-height: 40px;
    border: 1px solid #267149;
    background: #267149;
    color: #ffffff;
    padding: 0 18px;
    font: inherit;
    cursor: pointer;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    border: 1px solid;
    padding: 0 11px;
    font-size: 12px;
    font-weight: 700;
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

@media (max-width: 1050px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }

    .sidebar-column {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .summary-panel {
        grid-column: 1 / -1;
    }
}

@media (max-width: 700px) {
    .order-detail-page {
        padding: 18px;
    }

    .sidebar-column {
        display: flex;
    }

    .information-grid {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }

    .order-item {
        grid-template-columns:
            64px minmax(0, 1fr);
    }

    .product-image {
        width: 64px;
        height: 64px;
    }

    .item-subtotal {
        grid-column: 2;
    }
}
</style>
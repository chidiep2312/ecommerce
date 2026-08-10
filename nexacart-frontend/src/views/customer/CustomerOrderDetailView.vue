<script setup>
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock3,
    MapPin,
    Package,
    Phone,
    ReceiptText,
    Star,
    Store,
    Truck,
    UserRound,
    XCircle,
} from '@lucide/vue'

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
    cancelCustomerOrder,
    getCustomerOrder,
} from '@/api/orders'

const route = useRoute()
const router = useRouter()

const order = ref(null)

const isLoading = ref(false)
const isCancelling = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const canCancel = computed(() => {
    return (
        order.value?.status ===
        'pending'
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
    const orderId =
        route.params.id

    if (!orderId) {
        errorMessage.value =
            'Không xác định được đơn hàng.'

        return
    }

    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getCustomerOrder(
                orderId,
            )
       
        order.value =
            response.data?.data ??
            null
        console.log(order)
    } catch (error) {
        order.value = null

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tải thông tin đơn hàng.'
    } finally {
        isLoading.value = false
    }
}

async function cancelOrder() {
    if (
        !order.value ||
        !canCancel.value ||
        isCancelling.value
    ) {
        return
    }

    const confirmed =
        window.confirm(
            `Bạn có chắc muốn hủy đơn hàng "${order.value.order_code}" không?`,
        )

    if (!confirmed) {
        return
    }

    isCancelling.value = true

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await cancelCustomerOrder(
                order.value.id,
            )

        order.value =
            response.data?.data ??
            order.value

        successMessage.value =
            response.data?.message ??
            'Hủy đơn hàng thành công.'
    } catch (error) {
        errorMessage.value =
            error.response?.data
                ?.message ??
            getFirstValidationError(
                error.response?.data
                    ?.errors,
            ) ??
            'Không thể hủy đơn hàng.'
    } finally {
        isCancelling.value = false
    }
}

function openReview(item) {
    router.push({
        name:
            'customer-product-review',

        params: {
            orderId:
                order.value.id,

            itemId:
                item.id,
        },
    })
}

function goBack() {
    router.push({
        name:
            'customer-orders',
    })
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

function getStatusDescription(
    status,
) {
    const descriptions = {
        pending:
            'Đơn hàng đang chờ người bán xác nhận.',

        confirmed:
            'Người bán đã xác nhận đơn hàng.',

        shipping:
            'Đơn hàng đang được giao đến bạn.',

        completed:
            'Đơn hàng đã hoàn thành.',

        cancelled:
            'Đơn hàng đã bị hủy.',
    }

    return (
        descriptions[status] ??
        ''
    )
}

function getStatusIcon(status) {
    const icons = {
        pending:
            Clock3,

        confirmed:
            CheckCircle2,

        shipping:
            Truck,

        completed:
            CheckCircle2,

        cancelled:
            XCircle,
    }

    return (
        icons[status] ??
        Package
    )
}

function getItemImage(item) {
    return (
        item.product
            ?.main_image?.url ??
        item.product_image ??
        ''
    )
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
    fetchOrder()
})
</script>

<template>
    <div class="order-detail-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">
                    CHI TIẾT ĐƠN HÀNG
                </p>

                <h1>
                    {{
                        order?.order_code ??
                        'Đơn hàng'
                    }}
                </h1>

                <p>
                    Xem sản phẩm,
                    thông tin giao hàng
                    và trạng thái đơn hàng.
                </p>
            </div>

            <button
                type="button"
                class="back-button"
                @click="goBack"
            >
                <ArrowLeft
                    :size="16"
                />

                Quay lại
            </button>
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

        <div
            v-if="isLoading"
            class="state-box"
        >
            Đang tải đơn hàng...
        </div>

        <template
            v-else-if="order"
        >
            <!-- STATUS -->
            <section class="status-card">
                <component
                    :is="
                        getStatusIcon(
                            order.status,
                        )
                    "
                    :size="25"
                />

                <div>
                    <strong>
                        {{
                            getStatusLabel(
                                order.status,
                            )
                        }}
                    </strong>

                    <span>
                        {{
                            getStatusDescription(
                                order.status,
                            )
                        }}
                    </span>
                </div>

                <span class="order-date">
                    <CalendarDays
                        :size="14"
                    />

                    {{
                        formatDate(
                            order.created_at,
                        )
                    }}
                </span>
            </section>

            <!-- PRODUCTS -->
            <section class="order-card">
                <header class="card-header">
                    <div>
                        <Package
                            :size="19"
                        />

                        <div>
                            <h2>
                                Sản phẩm
                            </h2>

                            <p>
                                {{
                                    orderItems
                                        .length
                                }}
                                sản phẩm trong
                                đơn hàng
                            </p>
                        </div>
                    </div>
                </header>

                <div class="order-items">
                    <article
                        v-for="
                            item in
                            orderItems
                        "
                        :key="
                            item.id
                        "
                        class="order-item"
                    >
                        <div class="item-image">
                            <img
                                v-if="
                                    getItemImage(
                                        item,
                                    )
                                "
                                :src="
                                    getItemImage(
                                        item,
                                    )
                                "
                                :alt="
                                    item.product_name
                                "
                            >

                            <Package
                                v-else
                                :size="28"
                            />
                        </div>

                        <div class="item-information">
                            <h3>
                                {{
                                    item
                                        .product_name
                                }}
                            </h3>

                            <span>
                                SKU:
                                {{
                                    item
                                        .product_sku ??
                                    '--'
                                }}
                            </span>

                            <span>
                                Số lượng:
                                {{
                                    item
                                        .quantity
                                }}
                            </span>
                        </div>

                        <div class="item-price">
                            <span>
                                {{
                                    formatCurrency(
                                        item
                                            .unit_price,
                                    )
                                }}
                                ×
                                {{
                                    item
                                        .quantity
                                }}
                            </span>

                            <strong>
                                {{
                                    formatCurrency(
                                        item
                                            .line_total,
                                    )
                                }}
                            </strong>

                            <button
                                v-if="
                                    order.status ===
                                        'completed' &&
                                    !item
                                        .is_reviewed
                                "
                                type="button"
                                class="review-button"
                                @click="
                                    openReview(
                                        item,
                                    )
                                "
                            >
                                <Star
                                    :size="15"
                                />

                                Đánh giá
                            </button>

                            <span
                                v-else-if="
                                    item
                                        .is_reviewed
                                "
                                class="reviewed-label"
                            >
                                <CheckCircle2
                                    :size="14"
                                />

                                Đã đánh giá
                            </span>
                        </div>
                    </article>
                </div>
            </section>

            <div class="detail-layout">
                <!-- SHIPPING -->
                <section class="order-card">
                    <header class="card-header">
                        <div>
                            <MapPin
                                :size="19"
                            />

                            <div>
                                <h2>
                                    Thông tin nhận hàng
                                </h2>

                                <p>
                                    Địa chỉ giao hàng
                                    của đơn.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="shipping-information">
                        <div>
                            <UserRound
                                :size="16"
                            />

                            <span>
                                Người nhận
                            </span>

                            <strong>
                                {{
                                    order.shipping.name
                                }}
                            </strong>
                        </div>

                        <div>
                            <Phone
                                :size="16"
                            />

                            <span>
                                Điện thoại
                            </span>

                            <strong>
                                {{
                                    order
                                        .shipping.phone
                                }}
                            </strong>
                        </div>

                        <div>
                            <MapPin
                                :size="16"
                            />

                            <span>
                                Địa chỉ
                            </span>

                            <strong>
                                {{
                                    order
                                        .shipping.address
                                }}
                            </strong>
                        </div>
                    </div>
                </section>

                <!-- SELLER -->
                <section class="order-card">
                    <header class="card-header">
                        <div>
                            <Store
                                :size="19"
                            />

                            <div>
                                <h2>
                                    Người bán
                                </h2>

                                <p>
                                    Cửa hàng xử lý
                                    đơn hàng này.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="seller-information">
                        <Store
                            :size="24"
                        />

                        <div>
                            <strong>
                                {{
                                    order
                                        .seller
                                        ?.shop
                                        ?.name ??
                                    order
                                        .seller
                                        ?.name ??
                                    'Người bán'
                                }}
                            </strong>

                            <span>
                                {{
                                    order
                                        .seller
                                        ?.email ??
                                    ''
                                }}
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- PAYMENT SUMMARY -->
            <section class="order-card">
                <header class="card-header">
                    <div>
                        <ReceiptText
                            :size="19"
                        />

                        <div>
                            <h2>
                                Thanh toán
                            </h2>

                            <p>
                                Chi tiết giá trị
                                đơn hàng.
                            </p>
                        </div>
                    </div>
                </header>

                <dl class="payment-summary">
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
                            Voucher
                        </dt>

                        <dd class="discount">
                            -
                            {{
                                formatCurrency(
                                    order
                                        .discount_amount,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Phí vận chuyển
                        </dt>

                        <dd>
                            {{
                                Number(
                                    order
                                        .shipping_fee,
                                ) > 0
                                    ? formatCurrency(
                                        order
                                            .shipping_fee,
                                    )
                                    : 'Miễn phí'
                            }}
                        </dd>
                    </div>

                    <div
                        v-if="
                            Number(
                                order
                                    .platform_fee,
                            ) > 0
                        "
                    >
                        <dt>
                            Phí nền tảng
                        </dt>

                        <dd>
                            {{
                                formatCurrency(
                                    order
                                        .platform_fee,
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
                                    order.total,
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- NOTE -->
            <section
                v-if="
                    order.customer_note
                "
                class="order-card"
            >
                <header class="card-header">
                    <div>
                        <ReceiptText
                            :size="19"
                        />

                        <div>
                            <h2>
                                Ghi chú
                            </h2>
                        </div>
                    </div>
                </header>

                <p class="customer-note">
                    {{
                        order.customer_note
                    }}
                </p>
            </section>

            <footer
                v-if="canCancel"
                class="order-actions"
            >
                <button
                    type="button"
                    class="cancel-order-button"
                    :disabled="
                        isCancelling
                    "
                    @click="
                        cancelOrder
                    "
                >
                    <XCircle
                        :size="16"
                    />

                    {{
                        isCancelling
                            ? 'Đang hủy...'
                            : 'Hủy đơn hàng'
                    }}
                </button>
            </footer>
        </template>
    </div>
</template>

<style scoped>
.order-detail-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1f2c24;
    font-family: Roboto, Arial, sans-serif;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h1 {
    margin: 0;
    font-size: 22px;
}

.page-header p:last-child {
    margin: 7px 0 0;
    color: #748178;
    font-size: 12px;
}

.back-button {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    color: #46544b;
    background: #ffffff;
    border: 1px solid #cad5ce;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

/* STATUS */

.status-card {
    display: grid;
    grid-template-columns:
        auto minmax(0, 1fr) auto;
    gap: 13px;
    align-items: center;
    padding: 17px 20px;
    color: #246440;
    background: #edf7f0;
    border: 1px solid #a4c8b0;
}

.status-card > svg {
    flex-shrink: 0;
}

.status-card strong,
.status-card span {
    display: block;
}

.status-card strong {
    font-size: 13px;
}

.status-card div span {
    margin-top: 3px;
    color: #66766c;
    font-size: 10px;
}

.order-date {
    display: inline-flex !important;
    align-items: center;
    gap: 5px;
    color: #68766d;
    font-size: 10px;
}

/* CARDS */

.order-card {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.card-header {
    padding: 16px 19px;
    border-bottom: 1px solid #e5ebe7;
}

.card-header > div {
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header svg {
    color: #24734a;
}

.card-header h2 {
    margin: 0;
    font-size: 14px;
}

.card-header p {
    margin: 4px 0 0;
    color: #7a877f;
    font-size: 9px;
}

/* ITEMS */

.order-item {
    display: grid;
    grid-template-columns:
        82px
        minmax(0, 1fr)
        auto;
    gap: 14px;
    align-items: center;
    padding: 16px 19px;
    border-bottom: 1px solid #edf1ee;
}

.order-item:last-child {
    border-bottom: 0;
}

.item-image {
    display: grid;
    width: 82px;
    height: 82px;
    place-items: center;
    overflow: hidden;
    color: #8b978f;
    background: #f5f7f6;
    border: 1px solid #e0e6e2;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-information {
    display: grid;
    min-width: 0;
    gap: 4px;
}

.item-information h3 {
    margin: 0;
    font-size: 12px;
}

.item-information span {
    color: #7a877f;
    font-size: 9px;
}

.item-price {
    display: grid;
    justify-items: end;
    gap: 6px;
    min-width: 130px;
}

.item-price > span {
    color: #7a877f;
    font-size: 9px;
}

.item-price > strong {
    color: #24734a;
    font-size: 12px;
}

.review-button {
    display: inline-flex;
    min-height: 32px;
    align-items: center;
    gap: 5px;
    padding: 0 9px;
    color: #24734a;
    background: #ffffff;
    border: 1px solid #24734a;
    font: inherit;
    font-size: 9px;
    font-weight: 600;
    cursor: pointer;
}

.reviewed-label {
    display: inline-flex !important;
    align-items: center;
    gap: 5px;
    color: #246440 !important;
    font-size: 9px !important;
}

/* TWO COLUMN */

.detail-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1.4fr)
        minmax(250px, 0.6fr);
    gap: 18px;
}

.shipping-information {
    display: grid;
}

.shipping-information > div {
    display: grid;
    grid-template-columns:
        18px
        100px
        minmax(0, 1fr);
    gap: 9px;
    align-items: start;
    padding: 12px 18px;
    border-bottom: 1px solid #edf1ee;
}

.shipping-information > div:last-child {
    border-bottom: 0;
}

.shipping-information svg {
    color: #24734a;
}

.shipping-information span {
    color: #7a877f;
    font-size: 10px;
}

.shipping-information strong {
    color: #344139;
    font-size: 10px;
    line-height: 1.55;
}

.seller-information {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 18px;
}

.seller-information > svg {
    color: #24734a;
}

.seller-information strong,
.seller-information span {
    display: block;
}

.seller-information strong {
    font-size: 12px;
}

.seller-information span {
    margin-top: 4px;
    color: #7a877f;
    font-size: 9px;
}

/* PAYMENT */

.payment-summary {
    width: min(
        100%,
        480px
    );
    margin: 0 0 0 auto;
    padding: 18px;
}

.payment-summary > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 7px 0;
}

.payment-summary dt {
    color: #748178;
    font-size: 10px;
}

.payment-summary dd {
    margin: 0;
    color: #354239;
    font-size: 10px;
    font-weight: 600;
}

.payment-summary .discount {
    color: #24734a;
}

.payment-summary .total-row {
    margin-top: 7px;
    padding-top: 13px;
    border-top: 1px solid #dde5e0;
}

.total-row dt {
    color: #2f3d34;
    font-weight: 700;
}

.total-row dd {
    color: #24734a;
    font-size: 16px;
}

.customer-note {
    margin: 0;
    padding: 17px 19px;
    color: #59675e;
    font-size: 11px;
    line-height: 1.7;
}

/* ACTION */

.order-actions {
    display: flex;
    justify-content: flex-end;
}

.cancel-order-button {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    color: #993c3c;
    background: #ffffff;
    border: 1px solid #daa8a8;
    font: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

/* STATE */

.alert {
    padding: 11px 14px;
    border: 1px solid;
    font-size: 11px;
}

.alert-error {
    color: #963737;
    background: #fff3f3;
    border-color: #dfb1b1;
}

.alert-success {
    color: #21633f;
    background: #edf7f0;
    border-color: #9dc5aa;
}

.state-box {
    display: grid;
    min-height: 350px;
    place-items: center;
    color: #748178;
    background: #ffffff;
    border: 1px solid #dce5df;
    font-size: 12px;
}

@media (max-width: 760px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .back-button {
        width: 100%;
        justify-content: center;
    }

    .status-card {
        grid-template-columns:
            auto minmax(0, 1fr);
    }

    .order-date {
        grid-column: 2;
    }

    .detail-layout {
        grid-template-columns: 1fr;
    }

    .order-item {
        grid-template-columns:
            70px minmax(0, 1fr);
    }

    .item-image {
        width: 70px;
        height: 70px;
    }

    .item-price {
        grid-column: 2;
        justify-items: start;
    }
}
</style>
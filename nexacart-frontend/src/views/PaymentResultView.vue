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
    getPaymentStatus,
    createVnpayPayment,
} from '@/api/payment'

const route = useRoute()
const router = useRouter()

const payment = ref(null)

const isLoading = ref(true)
const isRetrying = ref(false)

const errorMessage = ref('')

const transactionRef = computed(() => {
    const value = route.query.txn_ref

    return typeof value === 'string'
        ? value
        : ''
})

const paymentStatus = computed(() => {
    return payment.value?.status ?? null
})

const isPaid = computed(() => {
    return paymentStatus.value === 'paid'
})

const isFailed = computed(() => {
    return paymentStatus.value === 'failed'
})

const isCancelled = computed(() => {
    return paymentStatus.value === 'cancelled'
})

const isPending = computed(() => {
    return paymentStatus.value === 'pending'
})

const canRetry = computed(() => {
    return (
        isFailed.value ||
        isCancelled.value
    )
})

function formatMoney(value) {
    const numberValue =
        Number(value ?? 0)

    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
        },
    ).format(numberValue)
}

function formatDateTime(value) {
    if (!value) {
        return '-'
    }

    const date = new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return value
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            dateStyle: 'medium',
            timeStyle: 'medium',
        },
    ).format(date)
}

async function loadPayment() {
    if (!transactionRef.value) {
        errorMessage.value =
            'Không tìm thấy mã tham chiếu giao dịch.'

        isLoading.value = false

        return
    }

    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getPaymentStatus(
                transactionRef.value,
            )

        payment.value =
            response.data?.data
            ?? null

        if (!payment.value) {
            throw new Error(
                'Payment data is empty.',
            )
        }
    } catch (error) {
        console.error(
            'Load payment status failed:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message
            ?? 'Không thể kiểm tra trạng thái thanh toán.'
    } finally {
        isLoading.value = false
    }
}

async function retryPayment() {
    const orderId =
        payment.value?.order_id

    if (!orderId) {
        errorMessage.value =
            'Không xác định được đơn hàng.'

        return
    }

    isRetrying.value = true
    errorMessage.value = ''

    try {
        const response =
            await createVnpayPayment(
                orderId,
            )

        const paymentUrl =
            response.data
                ?.data
                ?.payment_url

        if (!paymentUrl) {
            throw new Error(
                'Missing payment URL.',
            )
        }

        window.location.href =
            paymentUrl
    } catch (error) {
        console.error(
            'Retry payment failed:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message
            ?? 'Không thể tạo lại giao dịch thanh toán.'
    } finally {
        isRetrying.value = false
    }
}

function goToOrder() {
    const orderId =
        payment.value?.order_id

    if (!orderId) {
        return
    }

    router.push({
        name: 'order-detail',
        params: {
            id: orderId,
        },
    })
}

function goToOrders() {
    router.push({
        name: 'orders',
    })
}

function goHome() {
    router.push({
        name: 'home',
    })
}

onMounted(() => {
    loadPayment()
})
</script>

<template>
    <main class="payment-result-page">
        <div class="payment-result-container">

            <!-- Loading -->
            <section
                v-if="isLoading"
                class="result-card"
            >
                <div class="status-icon status-icon--loading">
                    <span class="spinner"></span>
                </div>

                <h1>
                    Đang kiểm tra thanh toán
                </h1>

                <p class="result-description">
                    NexaCart đang xác nhận trạng thái giao dịch
                    với hệ thống thanh toán.
                </p>
            </section>

            <!-- Error -->
            <section
                v-else-if="errorMessage && !payment"
                class="result-card"
            >
                <div class="status-icon status-icon--error">
                    !
                </div>

                <h1>
                    Không thể kiểm tra thanh toán
                </h1>

                <p class="result-description">
                    {{ errorMessage }}
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="button button--primary"
                        @click="loadPayment"
                    >
                        Thử lại
                    </button>

                    <button
                        type="button"
                        class="button button--secondary"
                        @click="goHome"
                    >
                        Về trang chủ
                    </button>
                </div>
            </section>

            <!-- Paid -->
            <section
                v-else-if="isPaid"
                class="result-card"
            >
                <div class="status-icon status-icon--success">
                    ✓
                </div>

                <p class="status-label">
                    Thanh toán thành công
                </p>

                <h1>
                    Đơn hàng đã được thanh toán
                </h1>

                <p class="result-description">
                    Giao dịch đã được NexaCart xác nhận thành công.
                </p>

                <div class="payment-info">
                    <div class="info-row">
                        <span>
                            Mã đơn hàng
                        </span>

                        <strong>
                            {{ payment.order_code ?? `#${payment.order_id}` }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Số tiền
                        </span>

                        <strong>
                            {{ formatMoney(payment.amount) }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Cổng thanh toán
                        </span>

                        <strong>
                            {{ payment.provider ?? 'VNPay' }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Mã tham chiếu
                        </span>

                        <strong class="transaction-value">
                            {{ payment.provider_order_id }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Mã giao dịch VNPay
                        </span>

                        <strong class="transaction-value">
                            {{ payment.transaction_id ?? '-' }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Thời gian thanh toán
                        </span>

                        <strong>
                            {{ formatDateTime(payment.paid_at) }}
                        </strong>
                    </div>
                </div>

                <div class="actions">
                    <button
                        type="button"
                        class="button button--primary"
                        @click="goToOrder"
                    >
                        Xem đơn hàng
                    </button>

                    <button
                        type="button"
                        class="button button--secondary"
                        @click="goHome"
                    >
                        Tiếp tục mua sắm
                    </button>
                </div>
            </section>

            <!-- Failed / Cancelled -->
            <section
                v-else-if="isFailed || isCancelled"
                class="result-card"
            >
                <div class="status-icon status-icon--error">
                    ×
                </div>

                <p class="status-label status-label--error">
                    {{
                        isCancelled
                            ? 'Đã hủy thanh toán'
                            : 'Thanh toán thất bại'
                    }}
                </p>

                <h1>
                    {{
                        isCancelled
                            ? 'Giao dịch đã được hủy'
                            : 'Giao dịch chưa hoàn tất'
                    }}
                </h1>

                <p class="result-description">
                    Đơn hàng của bạn vẫn được giữ lại.
                    Bạn có thể thực hiện thanh toán lại.
                </p>

                <div class="payment-info">
                    <div class="info-row">
                        <span>
                            Mã đơn hàng
                        </span>

                        <strong>
                            {{ payment.order_code ?? `#${payment.order_id}` }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Số tiền
                        </span>

                        <strong>
                            {{ formatMoney(payment.amount) }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Mã tham chiếu
                        </span>

                        <strong class="transaction-value">
                            {{ payment.provider_order_id }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Mã phản hồi
                        </span>

                        <strong>
                            {{ payment.provider_response_code ?? '-' }}
                        </strong>
                    </div>
                </div>

                <p
                    v-if="errorMessage"
                    class="inline-error"
                >
                    {{ errorMessage }}
                </p>

                <div class="actions">
                    <button
                        v-if="canRetry"
                        type="button"
                        class="button button--primary"
                        :disabled="isRetrying"
                        @click="retryPayment"
                    >
                        {{
                            isRetrying
                                ? 'Đang tạo giao dịch...'
                                : 'Thanh toán lại'
                        }}
                    </button>

                    <button
                        type="button"
                        class="button button--secondary"
                        @click="goToOrder"
                    >
                        Xem đơn hàng
                    </button>
                </div>
            </section>

            <!-- Pending -->
            <section
                v-else-if="isPending"
                class="result-card"
            >
                <div class="status-icon status-icon--pending">
                    …
                </div>

                <p class="status-label status-label--pending">
                    Đang chờ xác nhận
                </p>

                <h1>
                    Giao dịch đang được xử lý
                </h1>

                <p class="result-description">
                    NexaCart chưa nhận được kết quả cuối cùng
                    của giao dịch này.
                </p>

                <div class="payment-info">
                    <div class="info-row">
                        <span>
                            Mã tham chiếu
                        </span>

                        <strong class="transaction-value">
                            {{ payment.provider_order_id }}
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>
                            Số tiền
                        </span>

                        <strong>
                            {{ formatMoney(payment.amount) }}
                        </strong>
                    </div>
                </div>

                <p
                    v-if="errorMessage"
                    class="inline-error"
                >
                    {{ errorMessage }}
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="button button--primary"
                        @click="loadPayment"
                    >
                        Kiểm tra lại
                    </button>

                    <button
                        type="button"
                        class="button button--secondary"
                        @click="goToOrders"
                    >
                        Danh sách đơn hàng
                    </button>
                </div>
            </section>

            <!-- Unknown status -->
            <section
                v-else
                class="result-card"
            >
                <div class="status-icon status-icon--error">
                    !
                </div>

                <h1>
                    Trạng thái không xác định
                </h1>

                <p class="result-description">
                    Không thể xác định trạng thái hiện tại của giao dịch.
                </p>

                <div class="actions">
                    <button
                        type="button"
                        class="button button--primary"
                        @click="loadPayment"
                    >
                        Kiểm tra lại
                    </button>
                </div>
            </section>
        </div>
    </main>
</template>

<style scoped>
.payment-result-page {
    min-height: calc(100vh - 80px);
    background: #f5f7f5;
    padding: 48px 20px;
    font-family: Roboto, Arial, sans-serif;
    color: #1f2937;
}

.payment-result-container {
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
}

.result-card {
    background: #ffffff;
    border: 1px solid #dfe5df;
    padding: 42px;
    text-align: center;
}

.status-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid;
    border-radius: 50%;
    font-size: 30px;
    font-weight: 700;
}

.status-icon--success {
    color: #16794b;
    border-color: #16794b;
}

.status-icon--error {
    color: #b42318;
    border-color: #b42318;
}

.status-icon--pending,
.status-icon--loading {
    color: #7a6512;
    border-color: #7a6512;
}

.status-label {
    margin: 0 0 8px;
    color: #16794b;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.status-label--error {
    color: #b42318;
}

.status-label--pending {
    color: #7a6512;
}

h1 {
    margin: 0;
    color: #18241d;
    font-size: 28px;
    line-height: 1.3;
}

.result-description {
    max-width: 520px;
    margin: 14px auto 28px;
    color: #66736a;
    font-size: 15px;
    line-height: 1.7;
}

.payment-info {
    margin-top: 30px;
    border-top: 1px solid #e5e9e5;
    border-bottom: 1px solid #e5e9e5;
    text-align: left;
}

.info-row {
    min-height: 54px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    border-bottom: 1px solid #eef1ee;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row span {
    color: #718078;
    font-size: 14px;
}

.info-row strong {
    color: #253329;
    font-size: 14px;
    text-align: right;
}

.transaction-value {
    max-width: 340px;
    overflow-wrap: anywhere;
    font-family: Consolas, monospace;
}

.actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 30px;
}

.button {
    min-height: 44px;
    padding: 0 22px;
    border: 1px solid transparent;
    border-radius: 0;
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    transition:
        background 0.2s ease,
        border-color 0.2s ease;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.button--primary {
    background: #16794b;
    border-color: #16794b;
    color: #ffffff;
}

.button--primary:hover:not(:disabled) {
    background: #11653e;
    border-color: #11653e;
}

.button--secondary {
    background: #ffffff;
    border-color: #cfd7d1;
    color: #314238;
}

.button--secondary:hover {
    background: #f4f6f4;
}

.inline-error {
    margin: 20px 0 0;
    color: #b42318;
    font-size: 14px;
}

.spinner {
    width: 22px;
    height: 22px;
    border: 3px solid #ded7b7;
    border-top-color: #7a6512;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 640px) {
    .payment-result-page {
        padding: 24px 12px;
    }

    .result-card {
        padding: 30px 20px;
    }

    h1 {
        font-size: 23px;
    }

    .info-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 6px;
        padding: 14px 0;
    }

    .info-row strong {
        text-align: left;
    }

    .actions {
        flex-direction: column;
    }

    .button {
        width: 100%;
    }
}
</style>
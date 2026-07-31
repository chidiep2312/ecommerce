<script setup>
import {
    ArrowLeft,
    Banknote,
    Check,
    ChevronRight,
    CircleAlert,
    CreditCard,
    MapPin,
    PackageCheck,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Truck,
} from '@lucide/vue'

import {
    computed,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import AddressCard from '@/components/customer/AddressCard.vue'

import { createOrder } from '@/api/orders'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()

const isSubmitting = ref(false)
const submitError = ref('')
const orderNote = ref('')
const paymentMethod = ref('cod')

const addresses = ref([
    {
        id: 1,
        recipientName: 'Diệp Kim Chi',
        phone: '0901234567',
        province: 'Thành phố Hồ Chí Minh',
        district: 'Quận 1',
        ward: 'Phường Bến Nghé',
        addressLine:
            '123 đường Nguyễn Huệ',
        fullAddress:
            '123 đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, Thành phố Hồ Chí Minh',
        isDefault: true,
    },
    {
        id: 2,
        recipientName: 'Diệp Kim Chi',
        phone: '0901234567',
        province: 'Đồng Nai',
        district: 'Thành phố Biên Hòa',
        ward: 'Phường Tân Mai',
        addressLine:
            '45 đường Đồng Khởi',
        fullAddress:
            '45 đường Đồng Khởi, Phường Tân Mai, Thành phố Biên Hòa, Đồng Nai',
        isDefault: false,
    },
])

const selectedAddressId = ref(
    addresses.value.find(
        (address) => address.isDefault,
    )?.id ?? null,
)

const selectedAddress = computed(() => {
    return addresses.value.find((address) => {
        return (
            address.id ===
            selectedAddressId.value
        )
    })
})

const shippingFee = computed(() => {
    if (cartStore.isEmpty) {
        return 0
    }

    if (cartStore.subtotal >= 500000) {
        return 0
    }

    return 30000
})

const grandTotal = computed(() => {
    return (
        cartStore.subtotal +
        shippingFee.value
    )
})

const canSubmit = computed(() => {
    return (
        !cartStore.isEmpty &&
        selectedAddressId.value !== null &&
        paymentMethod.value === 'cod' &&
        !isSubmitting.value
    )
})

function formatPrice(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value)
}

function selectAddress(address) {
    selectedAddressId.value = address.id
}

function editAddress(address) {
    console.log(
        'Edit address:',
        address,
    )
}

function addAddress() {
    console.log('Open address form')
}

function buildOrderPayload() {
    return {
        address_id:
            selectedAddressId.value,

        payment_method:
            paymentMethod.value,

        note:
            orderNote.value.trim() ||
            null,

        items: cartStore.items.map(
            (item) => ({
                product_id: item.id,
                quantity: item.quantity,
            }),
        ),
    }
}

async function submitOrder() {
    if (!canSubmit.value) {
        return
    }

    submitError.value = ''
    isSubmitting.value = true

    try {
        const payload =
            buildOrderPayload()

        // const response =
        //     await createOrder(payload)

        // const createdOrder =
        //     response.data.data

        await new Promise((resolve) => {
            setTimeout(resolve, 800)
        })

        const createdOrder = {
            id: Date.now(),
            code: `NC${Date.now()
                .toString()
                .slice(-8)}`,
        }
        cartStore.clearCart()

        router.replace({
            name: 'order-success',
            params: {
                orderCode:
                    createdOrder.code,
            },
        })
    } catch (error) {
        const status =
            error.response?.status

        if (status === 422) {
            submitError.value =
                error.response?.data?.message ??
                'Thông tin đặt hàng chưa hợp lệ.'
        } else if (status === 409) {
            submitError.value =
                error.response?.data?.message ??
                'Tồn kho của một số sản phẩm đã thay đổi.'
        } else {
            submitError.value =
                'Không thể tạo đơn hàng. Vui lòng thử lại.'
        }
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="checkout-page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <RouterLink to="/">
                Trang chủ
            </RouterLink>

            <ChevronRight :size="14" />

            <RouterLink to="/cart">
                Giỏ hàng
            </RouterLink>

            <ChevronRight :size="14" />

            <span aria-current="page">
                Thanh toán
            </span>
        </nav>

        <header class="checkout-header">
            <div>
                <p class="checkout-header__eyebrow">
                    Hoàn tất đơn hàng
                </p>

                <h1>Thanh toán</h1>

                <p>
                    Kiểm tra địa chỉ nhận hàng,
                    sản phẩm và phương thức thanh
                    toán trước khi đặt hàng.
                </p>
            </div>

            <RouterLink to="/cart" class="checkout-header__back">
                <ArrowLeft :size="17" />

                Quay lại giỏ hàng
            </RouterLink>
        </header>

        <section v-if="cartStore.isEmpty" class="empty-checkout">
            <div class="empty-checkout__icon">
                <ShoppingBag :size="30" />
            </div>

            <h2>
                Không có sản phẩm để thanh toán
            </h2>

            <p>
                Giỏ hàng của bạn đang trống.
                Hãy thêm sản phẩm trước khi tiến
                hành thanh toán.
            </p>

            <RouterLink to="/products" class="empty-checkout__button">
                Khám phá sản phẩm
            </RouterLink>
        </section>

        <div v-else class="checkout-layout">
            <div class="checkout-content">
                <section class="checkout-card">
                    <header class="checkout-card__header">
                        <div>
                            <MapPin :size="20" />

                            <div>
                                <h2>
                                    Địa chỉ nhận hàng
                                </h2>

                                <p>
                                    Chọn địa chỉ giao
                                    hàng cho đơn hàng.
                                </p>
                            </div>
                        </div>

                        <button type="button" class="checkout-card__action" @click="addAddress">
                            <Plus :size="16" />

                            Thêm địa chỉ
                        </button>
                    </header>

                    <div class="address-list">
                        <AddressCard v-for="address in addresses" :key="address.id" :address="address" :selected="selectedAddressId ===
                            address.id
                            " @select="selectAddress" @edit="editAddress" />
                    </div>
                </section>

                <section class="checkout-card">
                    <header class="checkout-card__header">
                        <div>
                            <PackageCheck :size="20" />

                            <div>
                                <h2>
                                    Sản phẩm đặt mua
                                </h2>

                                <p>
                                    {{
                                        cartStore.itemCount
                                    }}
                                    sản phẩm trong đơn hàng.
                                </p>
                            </div>
                        </div>

                        <RouterLink to="/cart" class="checkout-card__link">
                            Chỉnh sửa
                        </RouterLink>
                    </header>

                    <div class="checkout-products">
                        <article v-for="item in cartStore.items" :key="item.id" class="checkout-product">
                            <RouterLink :to="{
                                name:
                                    'product-detail',
                                params: {
                                    slug:
                                        item.slug,
                                },
                            }" class="checkout-product__image">
                                <img :src="item.image" :alt="item.name" />
                            </RouterLink>

                            <div class="checkout-product__information">
                                <p v-if="item.seller" class="checkout-product__seller">
                                    {{ item.seller }}
                                </p>

                                <RouterLink :to="{
                                    name:
                                        'product-detail',
                                    params: {
                                        slug:
                                            item.slug,
                                    },
                                }" class="checkout-product__name">
                                    {{ item.name }}
                                </RouterLink>

                                <span>
                                    Số lượng:
                                    {{ item.quantity }}
                                </span>
                            </div>

                            <div class="checkout-product__price">
                                <span>
                                    {{
                                        formatPrice(
                                            item.price,
                                        )
                                    }}
                                </span>

                                <strong>
                                    {{
                                        formatPrice(
                                            item.price *
                                            item.quantity,
                                        )
                                    }}
                                </strong>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="checkout-card">
                    <header class="checkout-card__header">
                        <div>
                            <CreditCard :size="20" />

                            <div>
                                <h2>
                                    Phương thức thanh toán
                                </h2>

                                <p>
                                    Chọn cách thanh toán
                                    cho đơn hàng.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="payment-methods">
                        <label class="payment-method" :class="{
                            'payment-method--selected':
                                paymentMethod ===
                                'cod',
                        }">
                            <input v-model="paymentMethod
                                " type="radio" value="cod" name="payment-method" />

                            <span class="payment-method__control">
                                <Check v-if="
                                    paymentMethod ===
                                    'cod'
                                " :size="13" />
                            </span>

                            <span class="payment-method__icon">
                                <Banknote :size="22" />
                            </span>

                            <span class="payment-method__content">
                                <strong>
                                    Thanh toán khi nhận hàng
                                </strong>

                                <span>
                                    Thanh toán tiền mặt cho
                                    nhân viên giao hàng.
                                </span>
                            </span>
                        </label>

                        <div class="payment-method payment-method--disabled">
                            <span class="payment-method__control" />

                            <span class="payment-method__icon">
                                <CreditCard :size="22" />
                            </span>

                            <span class="payment-method__content">
                                <strong>
                                    Thanh toán trực tuyến
                                </strong>

                                <span>
                                    VNPay, MoMo và thẻ sẽ
                                    được bổ sung sau.
                                </span>
                            </span>

                            <span class="payment-method__coming">
                                Sắp có
                            </span>
                        </div>
                    </div>
                </section>

                <section class="checkout-card">
                    <header class="checkout-card__header">
                        <div>
                            <ShoppingBag :size="20" />

                            <div>
                                <h2>
                                    Ghi chú đơn hàng
                                </h2>

                                <p>
                                    Thông tin thêm dành
                                    cho nhà bán hàng.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="order-note">
                        <textarea v-model="orderNote" maxlength="500"
                            placeholder="Ví dụ: Giao hàng trong giờ hành chính" />

                        <span>
                            {{ orderNote.length }}/500
                        </span>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary">
                <div class="checkout-summary__header">
                    <ShoppingBag :size="20" />

                    <h2>
                        Tóm tắt thanh toán
                    </h2>
                </div>

                <div v-if="selectedAddress" class="checkout-summary__address">
                    <span>
                        Giao đến
                    </span>

                    <strong>
                        {{
                            selectedAddress.recipientName
                        }}
                    </strong>

                    <p>
                        {{
                            selectedAddress.fullAddress
                        }}
                    </p>
                </div>

                <dl class="checkout-summary__details">
                    <div>
                        <dt>Tạm tính</dt>

                        <dd>
                            {{
                                formatPrice(
                                    cartStore.subtotal,
                                )
                            }}
                        </dd>
                    </div>

                    <div v-if="
                        cartStore.discountAmount >
                        0
                    ">
                        <dt>Tiết kiệm</dt>

                        <dd class="checkout-summary__discount">
                            −{{
                                formatPrice(
                                    cartStore
                                        .discountAmount,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Phí vận chuyển</dt>

                        <dd>
                            <span v-if="
                                shippingFee === 0
                            " class="checkout-summary__free">
                                Miễn phí
                            </span>

                            <template v-else>
                                {{
                                    formatPrice(
                                        shippingFee,
                                    )
                                }}
                            </template>
                        </dd>
                    </div>
                </dl>

                <div class="checkout-summary__total">
                    <span>
                        Tổng thanh toán
                    </span>

                    <strong>
                        {{
                            formatPrice(
                                grandTotal,
                            )
                        }}
                    </strong>
                </div>

                <div v-if="submitError" class="checkout-error" role="alert">
                    <CircleAlert :size="18" />

                    <span>
                        {{ submitError }}
                    </span>
                </div>

                <button type="button" class="place-order-button" :disabled="!canSubmit" @click="submitOrder">
                    <span v-if="isSubmitting" class="place-order-button__spinner" />

                    <template v-else>
                        Đặt hàng
                    </template>
                </button>

                <p class="checkout-summary__agreement">
                    Bằng việc đặt hàng, bạn đồng ý
                    với điều khoản sử dụng và chính
                    sách mua hàng của NexaCart.
                </p>

                <div class="checkout-benefits">
                    <article>
                        <ShieldCheck :size="18" />

                        <span>
                            Thông tin đơn hàng được
                            bảo vệ.
                        </span>
                    </article>

                    <article>
                        <Truck :size="18" />

                        <span>
                            Có thể theo dõi trạng thái
                            giao hàng.
                        </span>
                    </article>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.checkout-page {
    display: grid;
    gap: 28px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    color: var(--color-text-muted);
    font-size: 12px;
}

.breadcrumb a:hover {
    color: var(--color-primary-700);
}

.checkout-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 28px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-header__eyebrow {
    margin-bottom: 9px;
    color: var(--color-primary-700);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.checkout-header h1 {
    font-size: 36px;
}

.checkout-header p {
    max-width: 650px;
    margin-top: 10px;
    font-size: 14px;
}

.checkout-header__back {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding-inline: 14px;
    color: var(--color-text-secondary);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12px;
    font-weight: 600;
}

.checkout-header__back:hover {
    color: var(--color-primary-700);
    border-color: var(--color-primary-200);
}

.checkout-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) 360px;
    gap: 28px;
    align-items: start;
}

.checkout-content {
    display: grid;
    gap: 20px;
}

.checkout-card {
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.checkout-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    min-height: 74px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-card__header>div {
    display: flex;
    align-items: center;
    gap: 12px;
}

.checkout-card__header>div>svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.checkout-card__header h2 {
    font-size: 16px;
}

.checkout-card__header p {
    margin-top: 4px;
    font-size: 11px;
}

.checkout-card__action,
.checkout-card__link {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 7px;
    color: var(--color-primary-700);
    background: transparent;
    border: 0;
    font-size: 12px;
    font-weight: 600;
}

.address-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding: 20px;
}

.checkout-products {
    display: grid;
}

.checkout-product {
    display: grid;
    grid-template-columns:
        82px minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    padding: 18px 20px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-product:last-child {
    border-bottom: 0;
}

.checkout-product__image {
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: var(--color-gray-100);
    border-radius: var(--radius-md);
}

.checkout-product__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.checkout-product__information {
    display: grid;
    gap: 5px;
    min-width: 0;
}

.checkout-product__seller {
    color: var(--color-text-muted);
    font-size: 10px;
}

.checkout-product__name {
    color: var(--color-text-primary);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
}

.checkout-product__name:hover {
    color: var(--color-primary-700);
}

.checkout-product__information>span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.checkout-product__price {
    display: grid;
    gap: 5px;
    min-width: 120px;
    text-align: right;
}

.checkout-product__price span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.checkout-product__price strong {
    color: var(--color-text-primary);
    font-size: 13px;
}

.payment-methods {
    display: grid;
    gap: 12px;
    padding: 20px;
}

.payment-method {
    display: grid;
    grid-template-columns:
        auto auto minmax(0, 1fr) auto;
    gap: 13px;
    align-items: center;
    min-height: 78px;
    padding: 15px;
    cursor: pointer;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    transition:
        border-color var(--transition-fast),
        background-color var(--transition-fast);
}

.payment-method>input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.payment-method--selected {
    background: var(--color-primary-50);
    border-color: var(--color-primary-500);
}

.payment-method--disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.payment-method__control {
    display: grid;
    place-items: center;
    width: 19px;
    height: 19px;
    color: var(--color-white);
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: 50%;
}

.payment-method--selected .payment-method__control {
    background: var(--color-primary-700);
    border-color: var(--color-primary-700);
}

.payment-method__icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    color: var(--color-primary-700);
    background: var(--color-primary-100);
    border-radius: var(--radius-md);
}

.payment-method__content {
    display: grid;
    gap: 5px;
}

.payment-method__content strong {
    font-size: 13px;
}

.payment-method__content span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.payment-method__coming {
    padding: 5px 8px;
    color: var(--color-text-muted);
    background: var(--color-gray-100);
    border-radius: var(--radius-pill);
    font-size: 10px;
    font-weight: 600;
}

.order-note {
    position: relative;
    padding: 20px;
}

.order-note textarea {
    width: 100%;
    min-height: 118px;
    padding: 14px;
    resize: vertical;
    color: var(--color-text-primary);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font: inherit;
    font-size: 13px;
    line-height: 1.6;
    outline: none;
}

.order-note textarea:focus {
    background: var(--color-white);
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 4px rgb(113 56 214 / 10%);
}

.order-note>span {
    position: absolute;
    right: 34px;
    bottom: 31px;
    color: var(--color-text-muted);
    font-size: 10px;
}

.checkout-summary {
    position: sticky;
    top: 20px;
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.checkout-summary__header {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 64px;
    padding-inline: 20px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-summary__header svg {
    color: var(--color-primary-700);
}

.checkout-summary__header h2 {
    font-size: 16px;
}

.checkout-summary__address {
    display: grid;
    gap: 5px;
    margin: 20px;
    padding: 14px;
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.checkout-summary__address>span {
    color: var(--color-text-muted);
    font-size: 10px;
}

.checkout-summary__address strong {
    font-size: 12px;
}

.checkout-summary__address p {
    font-size: 11px;
    line-height: 1.55;
}

.checkout-summary__details {
    display: grid;
    gap: 16px;
    margin: 0;
    padding: 2px 20px 20px;
}

.checkout-summary__details>div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.checkout-summary__details dt {
    color: var(--color-text-muted);
    font-size: 12px;
}

.checkout-summary__details dd {
    margin: 0;
    color: var(--color-text-primary);
    font-size: 12px;
    font-weight: 600;
}

.checkout-summary__discount,
.checkout-summary__free {
    color: var(--color-success) !important;
}

.checkout-summary__total {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    padding: 20px;
    border-top: 1px solid var(--color-border);
}

.checkout-summary__total span {
    font-size: 13px;
    font-weight: 600;
}

.checkout-summary__total strong {
    color: var(--color-primary-700);
    font-size: 22px;
}

.checkout-error {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 9px;
    margin: 0 20px 16px;
    padding: 12px;
    color: var(--color-danger);
    background: #fff1f3;
    border: 1px solid #ffd6dc;
    border-radius: var(--radius-md);
    font-size: 11px;
    line-height: 1.5;
}

.place-order-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: calc(100% - 40px);
    min-height: 48px;
    margin: 0 20px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 0;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 700;
}

.place-order-button:hover:not(:disabled) {
    background: var(--color-primary-800);
}

.place-order-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.place-order-button__spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgb(255 255 255 / 35%);
    border-top-color: var(--color-white);
    border-radius: 50%;
    animation: checkout-spin 700ms linear infinite;
}

.checkout-summary__agreement {
    margin: 14px 20px 0;
    color: var(--color-text-muted);
    font-size: 10px;
    line-height: 1.55;
    text-align: center;
}

.checkout-benefits {
    display: grid;
    gap: 10px;
    padding: 20px;
}

.checkout-benefits article {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--color-text-muted);
    font-size: 11px;
}

.checkout-benefits svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.empty-checkout {
    display: grid;
    place-items: center;
    min-height: 450px;
    padding: 40px;
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.empty-checkout__icon {
    display: grid;
    place-items: center;
    width: 68px;
    height: 68px;
    margin-bottom: 20px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 20px;
}

.empty-checkout h2 {
    font-size: 22px;
}

.empty-checkout p {
    max-width: 460px;
    margin-top: 10px;
    line-height: 1.7;
}

.empty-checkout__button {
    min-height: 44px;
    margin-top: 22px;
    padding: 12px 18px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

@keyframes checkout-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1080px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        position: static;
    }
}

@media (max-width: 720px) {
    .checkout-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .address-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .checkout-header h1 {
        font-size: 31px;
    }

    .checkout-card__header {
        align-items: flex-start;
        flex-direction: column;
    }

    .checkout-product {
        grid-template-columns:
            72px minmax(0, 1fr);
    }

    .checkout-product__price {
        grid-column: 2;
        min-width: 0;
        text-align: left;
    }

    .payment-method {
        grid-template-columns:
            auto auto minmax(0, 1fr);
    }

    .payment-method__coming {
        grid-column: 3;
        width: fit-content;
    }

    .checkout-summary__total {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
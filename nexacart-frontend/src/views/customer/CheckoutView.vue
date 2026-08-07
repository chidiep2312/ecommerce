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

import { useRouter } from 'vue-router'

import AddressCard from '@/components/customer/AddressCard.vue'

import { createOrder } from '@/api/orders'
import { useCartStore } from '@/stores/cart'

const CHECKOUT_CONTEXT_KEY =
    'nexacart_checkout_context'

const router = useRouter()
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
        addressLine: '123 đường Nguyễn Huệ',
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
        addressLine: '45 đường Đồng Khởi',
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
    return addresses.value.find(
        (address) => {
            return (
                address.id ===
                selectedAddressId.value
            )
        },
    )
})

function resolveSellerId(item) {
    const sellerId = Number(
        item.seller_id ??
        item.seller?.id,
    )

    if (
        !Number.isInteger(sellerId) ||
        sellerId <= 0
    ) {
        return null
    }

    return sellerId
}

function readCheckoutContext() {
    try {
        const storedValue =
            sessionStorage.getItem(
                CHECKOUT_CONTEXT_KEY,
            )

        if (!storedValue) {
            return null
        }

        const parsedValue =
            JSON.parse(storedValue)

        const sellerId = Number(
            parsedValue.seller_id,
        )

        const cartItemIds = Array.isArray(
            parsedValue.cart_item_ids,
        )
            ? parsedValue.cart_item_ids.map(
                (itemId) => Number(itemId),
            )
            : []

        const uniqueCartItemIds = [
            ...new Set(cartItemIds),
        ]

        const isSellerValid =
            Number.isInteger(sellerId) &&
            sellerId > 0

        const areCartItemsValid =
            uniqueCartItemIds.length > 0 &&
            uniqueCartItemIds.every(
                (itemId) => {
                    return (
                        Number.isInteger(
                            itemId,
                        ) &&
                        itemId > 0
                    )
                },
            )

        const isIdempotencyKeyValid =
            typeof parsedValue
                .idempotency_key ===
                'string' &&
            parsedValue.idempotency_key
                .trim() !== ''

        if (
            !isSellerValid ||
            !areCartItemsValid ||
            !isIdempotencyKeyValid
        ) {
            sessionStorage.removeItem(
                CHECKOUT_CONTEXT_KEY,
            )

            return null
        }

        return {
            seller_id: sellerId,
            cart_item_ids:
                uniqueCartItemIds,
            idempotency_key:
                parsedValue.idempotency_key,
        }
    } catch (error) {
        console.error(
            'Không thể đọc thông tin checkout:',
            error,
        )

        sessionStorage.removeItem(
            CHECKOUT_CONTEXT_KEY,
        )

        return null
    }
}

const checkoutContext = ref(
    readCheckoutContext(),
)

const selectedCartItemIdSet =
    computed(() => {
        return new Set(
            checkoutContext.value
                ?.cart_item_ids ?? [],
        )
    })

const selectedSellerId = computed(() => {
    return (
        checkoutContext.value
            ?.seller_id ?? null
    )
})

const checkoutItems = computed(() => {
    if (!checkoutContext.value) {
        return []
    }

    return cartStore.items.filter(
        (item) => {
            const itemId = Number(
                item.id,
            )

            return (
                selectedCartItemIdSet.value
                    .has(itemId) &&
                resolveSellerId(item) ===
                    selectedSellerId.value
            )
        },
    )
})

const hasMissingCheckoutItems =
    computed(() => {
        if (!checkoutContext.value) {
            return true
        }

        return (
            checkoutItems.value.length !==
            checkoutContext.value
                .cart_item_ids.length
        )
    })

const checkoutItemCount = computed(() => {
    return checkoutItems.value.reduce(
        (total, item) => {
            return (
                total +
                Number(item.quantity)
            )
        },
        0,
    )
})

const checkoutSubtotal = computed(() => {
    return checkoutItems.value.reduce(
        (total, item) => {
            return (
                total +
                Number(item.price) *
                    Number(item.quantity)
            )
        },
        0,
    )
})

/*
 * Backend hiện đặt shipping_fee = 0.
 * Voucher và tổng tiền cuối cùng vẫn được backend
 * kiểm tra, tính lại từ database.
 */
const shippingFee = computed(() => {
    return 0
})

const grandTotal = computed(() => {
    return (
        checkoutSubtotal.value +
        shippingFee.value
    )
})

const checkoutUnavailableMessage =
    computed(() => {
        if (!checkoutContext.value) {
            return 'Thông tin thanh toán không còn hợp lệ. Vui lòng chọn lại sản phẩm trong giỏ hàng.'
        }

        if (cartStore.isEmpty) {
            return 'Giỏ hàng của bạn đang trống.'
        }

        if (hasMissingCheckoutItems.value) {
            return 'Một hoặc nhiều sản phẩm đã chọn không còn trong giỏ hàng hoặc không thuộc người bán đã chọn.'
        }

        if (checkoutItems.value.length === 0) {
            return 'Không tìm thấy sản phẩm phù hợp để thanh toán.'
        }

        return ''
    })

const canSubmit = computed(() => {
    return (
        Boolean(checkoutContext.value) &&
        checkoutItems.value.length > 0 &&
        !hasMissingCheckoutItems.value &&
        selectedSellerId.value !== null &&
        Boolean(selectedAddress.value) &&
        paymentMethod.value === 'cod' &&
        !isSubmitting.value
    )
})

function formatPrice(value) {
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
    console.log(
        'Open address form',
    )
}

function buildOrderPayload() {
    const context =
        checkoutContext.value

    const address =
        selectedAddress.value

    if (!context) {
        throw new Error(
            'Thông tin checkout không còn hợp lệ.',
        )
    }

    if (!address) {
        throw new Error(
            'Vui lòng chọn địa chỉ nhận hàng.',
        )
    }

    return {
        seller_id:
            context.seller_id,

        cart_item_ids:
            context.cart_item_ids,

        idempotency_key:
            context.idempotency_key,

        voucher_code:
            cartStore.voucherCode ||
            null,

        shipping_name:
            address.recipientName,

        shipping_phone:
            address.phone,

        shipping_address:
            address.fullAddress,

        customer_note:
            orderNote.value.trim() ||
            null,
    }
}

function getFirstValidationError(
    validationErrors,
) {
    if (
        !validationErrors ||
        typeof validationErrors !==
            'object'
    ) {
        return null
    }

    const messages = Object.values(
        validationErrors,
    ).flat()

    return messages[0] ?? null
}

function handleCheckoutError(error) {
    const status =
        error.response?.status

    const responseData =
        error.response?.data

    const backendMessage =
        responseData?.message

    if (status === 401) {
        submitError.value =
            'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'

        return
    }

    if (status === 403) {
        submitError.value =
            backendMessage ??
            'Tài khoản không được phép thực hiện checkout.'

        return
    }

    if (status === 422) {
        const validationMessage =
            getFirstValidationError(
                responseData?.errors,
            )

        submitError.value =
            validationMessage ??
            backendMessage ??
            'Thông tin đặt hàng chưa hợp lệ.'

        return
    }

    if (status === 409) {
        submitError.value =
            backendMessage ??
            'Tồn kho hoặc dữ liệu đơn hàng đã thay đổi.'

        return
    }

    if (status === 429) {
        submitError.value =
            'Bạn thao tác quá nhanh. Vui lòng thử lại sau.'

        return
    }

    const isNetworkError =
        error.code === 'ECONNABORTED' ||
        Boolean(
            error.request &&
            !error.response,
        )

    if (isNetworkError) {
        /*
         * Không xóa checkout context.
         * Khi retry, frontend gửi lại cùng idempotency key.
         */
        submitError.value =
            'Không nhận được phản hồi từ máy chủ. Bạn có thể thử lại mà không bị tạo trùng đơn.'

        return
    }

    submitError.value =
        backendMessage ??
        error.message ??
        'Không thể tạo đơn hàng. Vui lòng thử lại.'
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

        const response =
            await createOrder(payload)

        const responseData =
            response.data?.data ??
            response.data

        const orderCode =
            responseData?.order_code ??
            responseData?.code

        if (!orderCode) {
          
            throw new Error(
                'Không nhận được mã đơn hàng từ máy chủ.',
            )
        }
        await cartStore.fetchCart()
        const checkedOutCartItemIds = [
            ...checkoutContext.value
                .cart_item_ids,
        ]

        sessionStorage.removeItem(
            CHECKOUT_CONTEXT_KEY,
        )

        removeCheckedOutItems(
            checkedOutCartItemIds,
        )

        checkoutContext.value = null

        await router.replace({
            name: 'order-success',
            params: {
                orderCode,
            },
        })
    } catch (error) {
        handleCheckoutError(error)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="checkout-page">
        <nav
            class="breadcrumb"
            aria-label="Breadcrumb"
        >
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
                <p
                    class="checkout-header__eyebrow"
                >
                    Hoàn tất đơn hàng
                </p>

                <h1>Thanh toán</h1>

                <p>
                    Kiểm tra địa chỉ nhận hàng,
                    sản phẩm và phương thức thanh
                    toán trước khi đặt hàng.
                </p>
            </div>

            <RouterLink
                to="/cart"
                class="checkout-header__back"
            >
                <ArrowLeft :size="17" />

                Quay lại giỏ hàng
            </RouterLink>
        </header>

        <section
            v-if="
                checkoutItems.length === 0 ||
                hasMissingCheckoutItems
            "
            class="empty-checkout"
        >
            <div
                class="empty-checkout__icon"
            >
                <ShoppingBag :size="30" />
            </div>

            <h2>
                Không có sản phẩm để thanh toán
            </h2>

            <p>
                {{ checkoutUnavailableMessage }}
            </p>

            <RouterLink
                to="/cart"
                class="empty-checkout__button"
            >
                Quay lại giỏ hàng
            </RouterLink>
        </section>

        <div
            v-else
            class="checkout-layout"
        >
            <div class="checkout-content">
                <section class="checkout-card">
                    <header
                        class="checkout-card__header"
                    >
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

                        <button
                            type="button"
                            class="checkout-card__action"
                            @click="addAddress"
                        >
                            <Plus :size="16" />

                            Thêm địa chỉ
                        </button>
                    </header>

                    <div class="address-list">
                        <AddressCard
                            v-for="address in addresses"
                            :key="address.id"
                            :address="address"
                            :selected="
                                selectedAddressId ===
                                address.id
                            "
                            @select="selectAddress"
                            @edit="editAddress"
                        />
                    </div>
                </section>

                <section class="checkout-card">
                    <header
                        class="checkout-card__header"
                    >
                        <div>
                            <PackageCheck :size="20" />

                            <div>
                                <h2>
                                    Sản phẩm đặt mua
                                </h2>

                                <p>
                                    {{ checkoutItemCount }}
                                    sản phẩm trong đơn hàng.
                                </p>
                            </div>
                        </div>

                        <RouterLink
                            to="/cart"
                            class="checkout-card__link"
                        >
                            Chỉnh sửa
                        </RouterLink>
                    </header>

                    <div class="checkout-products">
                        <article
                            v-for="item in checkoutItems"
                            :key="item.id"
                            class="checkout-product"
                        >
                            <RouterLink
                                :to="{
                                    name:
                                        'product-detail',
                                    params: {
                                        slug:
                                            item.slug,
                                    },
                                }"
                                class="checkout-product__image"
                            >
                                <img
                                    :src="item.image"
                                    :alt="item.name"
                                />
                            </RouterLink>

                            <div
                                class="checkout-product__information"
                            >
                                <p
                                    v-if="item.seller"
                                    class="checkout-product__seller"
                                >
                                    {{
                                        typeof item.seller ===
                                        'string'
                                            ? item.seller
                                            : item.seller.name
                                    }}
                                </p>

                                <RouterLink
                                    :to="{
                                        name:
                                            'product-detail',
                                        params: {
                                            slug:
                                                item.slug,
                                        },
                                    }"
                                    class="checkout-product__name"
                                >
                                    {{ item.name }}
                                </RouterLink>

                                <span>
                                    Số lượng:
                                    {{ item.quantity }}
                                </span>
                            </div>

                            <div
                                class="checkout-product__price"
                            >
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
                                            Number(
                                                item.price,
                                            ) *
                                                Number(
                                                    item.quantity,
                                                ),
                                        )
                                    }}
                                </strong>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="checkout-card">
                    <header
                        class="checkout-card__header"
                    >
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
                        <label
                            class="payment-method"
                            :class="{
                                'payment-method--selected':
                                    paymentMethod ===
                                    'cod',
                            }"
                        >
                            <input
                                v-model="paymentMethod"
                                type="radio"
                                value="cod"
                                name="payment-method"
                            />

                            <span
                                class="payment-method__control"
                            >
                                <Check
                                    v-if="
                                        paymentMethod ===
                                        'cod'
                                    "
                                    :size="13"
                                />
                            </span>

                            <span
                                class="payment-method__icon"
                            >
                                <Banknote :size="22" />
                            </span>

                            <span
                                class="payment-method__content"
                            >
                                <strong>
                                    Thanh toán khi nhận hàng
                                </strong>

                                <span>
                                    Thanh toán tiền mặt cho
                                    nhân viên giao hàng.
                                </span>
                            </span>
                        </label>

                        <div
                            class="payment-method payment-method--disabled"
                        >
                            <span
                                class="payment-method__control"
                            />

                            <span
                                class="payment-method__icon"
                            >
                                <CreditCard :size="22" />
                            </span>

                            <span
                                class="payment-method__content"
                            >
                                <strong>
                                    Thanh toán trực tuyến
                                </strong>

                                <span>
                                    VNPay, MoMo và thẻ sẽ
                                    được bổ sung sau.
                                </span>
                            </span>

                            <span
                                class="payment-method__coming"
                            >
                                Sắp có
                            </span>
                        </div>
                    </div>
                </section>

                <section class="checkout-card">
                    <header
                        class="checkout-card__header"
                    >
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
                        <textarea
                            v-model="orderNote"
                            maxlength="500"
                            placeholder="Ví dụ: Giao hàng trong giờ hành chính"
                        />

                        <span>
                            {{ orderNote.length }}/500
                        </span>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary">
                <div
                    class="checkout-summary__header"
                >
                    <ShoppingBag :size="20" />

                    <h2>
                        Tóm tắt thanh toán
                    </h2>
                </div>

                <div
                    v-if="selectedAddress"
                    class="checkout-summary__address"
                >
                    <span>
                        Giao đến
                    </span>

                    <strong>
                        {{
                            selectedAddress
                                .recipientName
                        }}
                    </strong>

                    <p>
                        {{
                            selectedAddress
                                .fullAddress
                        }}
                    </p>
                </div>

                <dl
                    class="checkout-summary__details"
                >
                    <div>
                        <dt>Sản phẩm đã chọn</dt>

                        <dd>
                            {{ checkoutItemCount }}
                        </dd>
                    </div>

                    <div>
                        <dt>Tạm tính</dt>

                        <dd>
                            {{
                                formatPrice(
                                    checkoutSubtotal,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Phí vận chuyển</dt>

                        <dd>
                            <span
                                v-if="shippingFee === 0"
                                class="checkout-summary__free"
                            >
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

                <p class="checkout-summary__notice">
                    Giá, voucher và tổng tiền cuối cùng
                    sẽ được backend kiểm tra lại từ database.
                </p>

                <div
                    class="checkout-summary__total"
                >
                    <span>
                        Tổng tạm tính
                    </span>

                    <strong>
                        {{
                            formatPrice(
                                grandTotal,
                            )
                        }}
                    </strong>
                </div>

                <div
                    v-if="submitError"
                    class="checkout-error"
                    role="alert"
                >
                    <CircleAlert :size="18" />

                    <span>
                        {{ submitError }}
                    </span>
                </div>

                <button
                    type="button"
                    class="place-order-button"
                    :disabled="!canSubmit"
                    @click="submitOrder"
                >
                    <span
                        v-if="isSubmitting"
                        class="place-order-button__spinner"
                    />

                    <template v-else>
                        Đặt hàng
                    </template>
                </button>

                <p
                    class="checkout-summary__agreement"
                >
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

.checkout-card__header > div {
    display: flex;
    align-items: center;
    gap: 12px;
}

.checkout-card__header > div > svg {
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

.checkout-product__information > span {
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

.payment-method > input {
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

.order-note > span {
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

.checkout-summary__address > span {
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

.checkout-summary__details > div {
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

.checkout-summary__free {
    color: var(--color-success) !important;
}

.checkout-summary__notice {
    margin: 0 20px 16px;
    padding: 11px 12px;
    color: var(--color-text-muted);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 10px;
    line-height: 1.55;
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
    animation:
        checkout-spin 700ms linear infinite;
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

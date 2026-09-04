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
    TicketPercent,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    getCustomerAddresses,
} from '@/api/address'
import {
    createVnpayPayment,
} from '@/api/payment'
import AddressCard
    from '@/components/customer/AddressCard.vue'

import {
    createOrder,
} from '@/api/orders'

import {
    getShippingOptions,
} from '@/api/shipping'

import {
    useCartStore,
} from '@/stores/cart'
import {
    validateVoucher,
} from '@/api/voucher'

const CHECKOUT_CONTEXT_KEY =
    'nexacart_checkout_context'

const router = useRouter()
const cartStore = useCartStore()

const isSubmitting = ref(false)
const submitError = ref('')

const orderNote = ref('')
const paymentMethod = ref('cod')

const addresses = ref([])
const isAddressLoading = ref(false)

const selectedAddressId = ref(null)

const shippingOptions = ref([])

const selectedShippingServiceId =
    ref(null)

const isShippingLoading = ref(false)

const shippingError = ref('')

let shippingRequestVersion = 0

const voucherCode = ref('')

const appliedVoucher = ref(null)

const voucherDiscount = ref(0)

const voucherError = ref('')

const voucherSuccess = ref('')

const isVoucherLoading = ref(false)

const selectedShippingOption =
    computed(() => {
        return (
            shippingOptions.value.find(
                option => {
                    return (
                        Number(
                            option.service_id,
                        ) ===
                        Number(
                            selectedShippingServiceId
                                .value,
                        )
                    )
                },
            ) ?? null
        )
    })

async function applyVoucher() {
    if (isVoucherLoading.value) {
        return
    }
    const code =
        voucherCode.value.trim().toUpperCase()

    if (!code) {
        voucherError.value = 'Vui lòng nhập mã voucher.'
        return
    }

    if (
        checkoutSubtotal.value <= 0
    ) {
        voucherError.value = 'Không có sản phẩm để áp dụng voucher.'
        return
    }

    isVoucherLoading.value = true

    voucherError.value = ''

    voucherSuccess.value = ''

    try {
        const response =
            await validateVoucher({
                code,

                subtotal:
                    checkoutSubtotal.value,
            })

        const data = response.data?.data

        appliedVoucher.value = data?.voucher ?? null

        voucherDiscount.value =
            Number(
                data?.discount_amount ?? 0,
            )

        voucherCode.value =
            data?.voucher?.code ??
            code

        voucherSuccess.value =
            response.data?.message ??
            'Áp dụng voucher thành công.'
    } catch (error) {
       
        appliedVoucher.value = null

        voucherDiscount.value = 0

        voucherError.value =
            error.response?.data
                ?.message ??
            getFirstValidationError(
                error.response?.data
                    ?.errors,
            ) ??
            'Voucher không hợp lệ.'
    } finally {
        isVoucherLoading.value = false
    }
}

function removeVoucher() {
    voucherCode.value = ''

    appliedVoucher.value = null

    voucherDiscount.value = 0

    voucherError.value = ''

    voucherSuccess.value = ''
}

const selectedAddress = computed(() => {
    return (
        addresses.value.find(
            address => {
                return (
                    Number(address.id) ===
                    Number(
                        selectedAddressId.value,
                    )
                )
            },
        ) ?? null
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


async function fetchAddresses() {
    isAddressLoading.value = true

    try {
        const response =
            await getCustomerAddresses()

        addresses.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []

        const defaultAddress =
            addresses.value.find(
                address => {
                    return Boolean(
                        address.is_default,
                    )
                },
            )

        selectedAddressId.value =
            defaultAddress?.id ??
            addresses.value[0]?.id ??
            null
    } catch (error) {
        console.error(
            'Không thể tải địa chỉ:',
            error,
        )

        submitError.value =
            error.response?.data?.message ??
            'Không thể tải địa chỉ giao hàng.'
    } finally {
        isAddressLoading.value = false
    }
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

        const cartItemIds =
            Array.isArray(
                parsedValue.cart_item_ids,
            )
                ? parsedValue
                    .cart_item_ids
                    .map(itemId => {
                        return Number(itemId)
                    })
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
                itemId => {
                    return (
                        Number.isInteger(
                            itemId,
                        ) &&
                        itemId > 0
                    )
                },
            )

        const idempotencyKey =
            parsedValue.idempotency_key

        const isIdempotencyKeyValid =
            typeof idempotencyKey ===
            'string' &&
            idempotencyKey.trim() !== ''

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
                idempotencyKey,
        }
    } catch (error) {
        console.error(
            'Không thể đọc checkout context:',
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
        item => {
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

const checkoutItemCount =
    computed(() => {
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


const checkoutSubtotal =
    computed(() => {
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


const shippingFee =
    computed(() => {
        return Number(
            selectedShippingOption
                .value
                ?.shipping_fee ?? 0,
        )
    })

const grandTotal = computed(() => {
    return Math.max(
        0,
        checkoutSubtotal.value -
        voucherDiscount.value +
        shippingFee.value,
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

        if (
            hasMissingCheckoutItems.value
        ) {
            return 'Một hoặc nhiều sản phẩm đã chọn không còn trong giỏ hàng hoặc không thuộc người bán đã chọn.'
        }

        if (
            checkoutItems.value.length ===
            0
        ) {
            return 'Không tìm thấy sản phẩm phù hợp để thanh toán.'
        }

        return ''
    })
async function loadShippingOptions() {

    const requestVersion =
        ++shippingRequestVersion

    shippingError.value = ''

    shippingOptions.value = []

    selectedShippingServiceId.value =
        null

    const context =
        checkoutContext.value

    if (
        !context ||
        !selectedAddressId.value ||
        !selectedSellerId.value ||
        hasMissingCheckoutItems.value
    ) {
        return
    }

    isShippingLoading.value = true

    try {
        const response =
            await getShippingOptions({
                seller_id:
                    selectedSellerId.value,

                address_id:
                    selectedAddressId.value,

                cart_item_ids:
                    context.cart_item_ids,
            })

        if (
            requestVersion !==
            shippingRequestVersion
        ) {
            return
        }

        const options =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []

        shippingOptions.value =
            options

        selectedShippingServiceId.value =
            options[0]?.service_id ??
            null

        if (options.length === 0) {
            shippingError.value =
                'Không có phương thức vận chuyển phù hợp.'
        }
    } catch (error) {
        if (
            requestVersion !==
            shippingRequestVersion
        ) {
            return
        }

        shippingOptions.value = []

        selectedShippingServiceId.value =
            null

        shippingError.value =
            error.response?.data
                ?.message ??
            getFirstValidationError(
                error.response?.data
                    ?.errors,
            ) ??
            'Không thể tính phí vận chuyển.'

        console.error(
            'Không thể tải phương thức vận chuyển:',
            error,
        )
    } finally {
        if (
            requestVersion ===
            shippingRequestVersion
        ) {
            isShippingLoading.value =
                false
        }
    }
}
const canSubmit =
    computed(() => {
        const isPaymentMethodValid =
            [
                'cod',
                'vnpay',
            ].includes(
                paymentMethod.value,
            )

        return (
            Boolean(
                checkoutContext.value,
            ) &&

            checkoutItems.value.length >
            0 &&

            !hasMissingCheckoutItems.value &&

            selectedSellerId.value !==
            null &&

            Boolean(
                selectedAddress.value,
            ) &&

            Boolean(
                selectedShippingOption
                    .value,
            ) &&

            isPaymentMethodValid &&

            !isAddressLoading.value &&

            !isShippingLoading.value &&

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
    selectedAddressId.value =
        address.id
}


function editAddress() {
    router.push({
        name: 'customer-addresses',
    })
}

function addAddress() {
    router.push({
        name: 'customer-addresses',
    })
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
    const shippingOption =
        selectedShippingOption.value


    if (!shippingOption) {
        throw new Error(
            'Vui lòng chọn phương thức vận chuyển.',
        )
    }
    return {
        seller_id:
            context.seller_id,

        cart_item_ids:
            context.cart_item_ids,

        address_id:
            address.id,

        shipping_provider:
            shippingOption.provider,

        shipping_service_id:
            shippingOption.service_id,


        idempotency_key:
            context.idempotency_key,

        voucher_code:
            appliedVoucher.value
                ?.code ??
            null,
        payment_method:
            paymentMethod.value,

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

    const messages =
        Object.values(
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
        error.code ===
        'ECONNABORTED' ||
        Boolean(
            error.request &&
            !error.response,
        )

    if (isNetworkError) {

        submitError.value =
            'Không nhận được phản hồi từ máy chủ. Bạn có thể thử lại mà không bị tạo trùng đơn.'

        return
    }

    submitError.value =
        backendMessage ??
        error.message ??
        'Không thể tạo đơn hàng. Vui lòng thử lại.'
}
watch(
    () => {
        return [
            selectedAddressId.value,

            selectedSellerId.value,

            checkoutContext.value
                ?.cart_item_ids
                ?.join('|') ?? '',
        ]
    },
    () => {
        loadShippingOptions()
    },
)
async function submitOrder() {
    if (!canSubmit.value) {
        return
    }

    submitError.value = ''
    isSubmitting.value = true

    try {
        const payload =
            buildOrderPayload()

        /*
         * Bước 1:
         * Tạo Order trước.
         */
        const response =
            await createOrder(
                payload,
            )

        const order =
            response.data?.data?.data ??
            response.data?.data ??
            response.data

        const orderId =
            order?.id

        const orderCode =
            order?.order_code ??
            order?.code

        if (!orderId) {
            throw new Error(
                'Không nhận được ID đơn hàng từ máy chủ.',
            )
        }

        if (!orderCode) {
            throw new Error(
                'Không nhận được mã đơn hàng từ máy chủ.',
            )
        }

        /*
         * Bước 2:
         * Order đã được tạo nên có thể
         * refresh cart.
         */
        await cartStore.fetchCart()

        sessionStorage.removeItem(
            CHECKOUT_CONTEXT_KEY,
        )

        checkoutContext.value = null

        /*
         * Bước 3:
         * Nếu VNPay:
         * tạo Payment rồi redirect sang
         * VNPay Fake.
         */
        if (
            paymentMethod.value ===
            'vnpay'
        ) {
            const paymentResponse =
                await createVnpayPayment(
                    orderId,
                )

            const paymentUrl =
                paymentResponse
                    .data
                    ?.data
                    ?.payment_url

            if (!paymentUrl) {
                throw new Error(
                    'Không nhận được đường dẫn thanh toán VNPay.',
                )
            }

            window.location.href =
                paymentUrl

            return
        }

        /*
         * COD:
         * Không cần payment gateway.
         */
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
onMounted(async () => {

    if (
        cartStore.items.length === 0
    ) {
        try {
            await cartStore.fetchCart()
        } catch (error) {
            console.error(
                'Không thể tải giỏ hàng:',
                error,
            )
        }
    }

    await fetchAddresses()
})
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

        <section v-if="
            checkoutItems.length === 0 ||
            hasMissingCheckoutItems
        " class="empty-checkout">
            <div class="empty-checkout__icon">
                <ShoppingBag :size="30" />
            </div>

            <h2>
                Không có sản phẩm để thanh toán
            </h2>

            <p>
                {{ checkoutUnavailableMessage }}
            </p>

            <RouterLink to="/cart" class="empty-checkout__button">
                Quay lại giỏ hàng
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
                                    {{ checkoutItemCount }}
                                    sản phẩm trong đơn hàng.
                                </p>
                            </div>
                        </div>

                        <RouterLink to="/cart" class="checkout-card__link">
                            Chỉnh sửa
                        </RouterLink>
                    </header>

                    <div class="checkout-products">
                        <article v-for="item in checkoutItems" :key="item.id" class="checkout-product">
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
                                    {{
                                        typeof item.seller ===
                                            'string'
                                            ? item.seller
                                            : item.seller.name
                                    }}
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
                    <header class="checkout-card__header">
                        <div>
                            <Truck :size="20" />

                            <div>
                                <h2>
                                    Phương thức vận chuyển
                                </h2>

                                <p>
                                    Chọn dịch vụ giao hàng
                                    cho đơn hàng.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="shipping-options">
                        <div v-if="isShippingLoading" class="shipping-state">
                            Đang tính phí vận chuyển...
                        </div>

                        <div v-else-if="shippingError" class="shipping-state shipping-state--error">
                            {{ shippingError }}
                        </div>

                        <div v-else-if="
                            shippingOptions.length ===
                            0
                        " class="shipping-state">
                            Chọn địa chỉ nhận hàng để
                            xem phương thức vận chuyển.
                        </div>

                        <label v-for="
option in shippingOptions
            " v-else :key="`${option.provider}-${option.service_id}`
                " class="shipping-option" :class="{
                    'shipping-option--selected':
                        Number(
                            selectedShippingServiceId,
                        ) ===
                        Number(
                            option.service_id,
                        ),
                }">
                            <input v-model="selectedShippingServiceId
                                " type="radio" name="shipping-service" :value="option.service_id
                                    ">

                            <span class="shipping-option__control">
                                <Check v-if="
                                    Number(
                                        selectedShippingServiceId,
                                    ) ===
                                    Number(
                                        option.service_id,
                                    )
                                " :size="13" />
                            </span>

                            <span class="shipping-option__icon">
                                <Truck :size="21" />
                            </span>

                            <span class="shipping-option__content">
                                <strong>
                                    {{
                                        option.service_name
                                    }}
                                </strong>

                                <span>
                                    Giao hàng qua GHN
                                </span>
                            </span>

                            <strong class="shipping-option__price">
                                {{
                                    formatPrice(
                                        option.shipping_fee,
                                    )
                                }}
                            </strong>
                        </label>
                    </div>
                </section>
                <section class="checkout-card">
                    <header class="checkout-card__header">
                        <div>
                            <TicketPercent :size="20" />

                            <div>
                                <h2>
                                    Mã giảm giá
                                </h2>

                                <p>
                                    Nhập voucher của NexaCart
                                    để nhận ưu đãi.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="voucher-box">
                        <div class="voucher-input-row">
                            <input v-model="voucherCode" type="text" maxlength="50" placeholder="Nhập mã voucher"
                                :disabled="Boolean(
                                    appliedVoucher
                                ) ||
                                    isVoucherLoading
                                    " @input="
                                        voucherCode =
                                        voucherCode
                                            .toUpperCase()
                                        " @keyup.enter="
                                            applyVoucher
                                        ">

                            <button v-if="
                                !appliedVoucher
                            " type="button" :disabled="isVoucherLoading ||
                                !voucherCode.trim()
                                " @click="applyVoucher">
                                {{
                                    isVoucherLoading
                                        ? 'Đang kiểm tra...'
                                        : 'Áp dụng'
                                }}
                            </button>

                            <button v-else type="button" class="voucher-remove-button" @click="
                                removeVoucher
                            ">
                                Bỏ mã
                            </button>
                        </div>

                        <p v-if="voucherError" class="voucher-message voucher-message--error">
                            {{ voucherError }}
                        </p>

                        <div v-if="appliedVoucher" class="voucher-applied">
                            <div>
                                <Check :size="16" />

                                <div>
                                    <strong>
                                        {{
                                            appliedVoucher.code
                                        }}
                                    </strong>

                                    <span>
                                        Đã áp dụng voucher
                                    </span>
                                </div>
                            </div>

                            <strong>
                                -
                                {{
                                    formatPrice(
                                        voucherDiscount,
                                    )
                                }}
                            </strong>
                        </div>
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
                            <input v-model="paymentMethod" type="radio" value="cod" name="payment-method" />

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

                        <label class="payment-method" :class="{
                            'payment-method--selected':
                                paymentMethod === 'vnpay',
                        }">
                            <input v-model="paymentMethod" type="radio" value="vnpay" name="payment-method">

                            <span class="payment-method__control">
                                <Check v-if="
                                    paymentMethod ===
                                    'vnpay'
                                " :size="13" />
                            </span>

                            <span class="payment-method__icon">
                                <CreditCard :size="22" />
                            </span>

                            <span class="payment-method__content">
                                <strong>
                                    Thanh toán qua VNPay
                                </strong>

                                <span>
                                    Thanh toán trực tuyến qua
                                    cổng VNPay.
                                </span>
                            </span>
                        </label>
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
                            selectedAddress
                                .recipient_name
                        }}
                    </strong>

                    <p>
                        {{
                            selectedAddress
                                .full_address
                        }}
                    </p>
                </div>

                <dl class="checkout-summary__details">
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
                    <div v-if="
                        appliedVoucher &&
                        voucherDiscount > 0
                    ">
                        <dt>
                            Voucher
                            <span class="voucher-code-inline">
                                {{
                                    appliedVoucher.code
                                }}
                            </span>
                        </dt>

                        <dd class="checkout-summary__discount">
                            -
                            {{
                                formatPrice(
                                    voucherDiscount,
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>Phí vận chuyển</dt>

                        <dd>
                            <span v-if="isShippingLoading">
                                Đang tính...
                            </span>

                            <span v-else-if="
                                !selectedShippingOption
                            ">
                                Chưa xác định
                            </span>

                            <span v-else-if="
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

                <p class="checkout-summary__notice">
                    Giá, voucher và tổng tiền cuối cùng
                    sẽ được backend kiểm tra lại từ database.
                </p>

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

/* =========================
   BREADCRUMB
========================= */

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: 12px;
    white-space: nowrap;
}

.breadcrumb a {
    color: var(--color-text-secondary);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.breadcrumb a:hover {
    color: var(--color-primary-700);
}

.breadcrumb>span {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* =========================
   HEADER
========================= */

.checkout-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 28px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-header__eyebrow {
    margin: 0 0 9px;
    color: var(--color-primary-700);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.checkout-header h1 {
    margin: 0;
    color: var(--color-text-primary);
    font-size: 36px;
    line-height: 1.15;
}

.checkout-header p {
    max-width: 650px;
    margin: 10px 0 0;
    color: var(--color-text-muted);
    font-size: 14px;
    line-height: 1.6;
}

.checkout-header__back {
    display: inline-flex;
    min-height: 42px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 14px;
    color: var(--color-text-secondary);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition:
        color var(--transition-fast),
        border-color var(--transition-fast),
        background-color var(--transition-fast);
}

.checkout-header__back:hover {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-color: var(--color-primary-200);
}

/* =========================
   MAIN LAYOUT
========================= */

.checkout-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) 360px;
    gap: 28px;
    align-items: start;
}

.checkout-content {
    display: grid;
    min-width: 0;
    gap: 20px;
}

/* =========================
   COMMON CARD
========================= */

.checkout-card {
    overflow: hidden;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.checkout-card__header {
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-card__header>div {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 12px;
}

.checkout-card__header>div>svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.checkout-card__header h2 {
    margin: 0;
    color: var(--color-text-primary);
    font-size: 16px;
    line-height: 1.3;
}

.checkout-card__header p {
    margin: 4px 0 0;
    color: var(--color-text-muted);
    font-size: 11px;
    line-height: 1.5;
}

.checkout-card__action,
.checkout-card__link {
    display: inline-flex;
    min-height: 34px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 8px;
    color: var(--color-primary-700);
    background: transparent;
    border: 0;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.checkout-card__action:hover,
.checkout-card__link:hover {
    color: var(--color-primary-800);
    background: var(--color-primary-50);
}

/* =========================
   ADDRESS
========================= */

.address-list {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding: 20px;
}

/* =========================
   PRODUCTS
========================= */

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
    display: block;
    overflow: hidden;
    width: 82px;
    aspect-ratio: 1 / 1;
    background: var(--color-gray-100);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.checkout-product__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-fast);
}

.checkout-product__image:hover img {
    transform: scale(1.035);
}

.checkout-product__information {
    display: grid;
    min-width: 0;
    gap: 5px;
}

.checkout-product__seller {
    margin: 0;
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.checkout-product__name {
    display: -webkit-box;
    overflow: hidden;
    color: var(--color-text-primary);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    text-decoration: none;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
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
    min-width: 120px;
    gap: 5px;
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

/* =========================
   VOUCHER
========================= */

.voucher-box {
    padding: 20px;
}

.voucher-input-row {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) 110px;
    gap: 10px;
}

.voucher-input-row input {
    width: 100%;
    height: 42px;
    box-sizing: border-box;
    padding: 0 13px;
    color: var(--color-text-primary);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    outline: none;
    font: inherit;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    transition:
        border-color var(--transition-fast),
        background-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.voucher-input-row input::placeholder {
    color: var(--color-text-muted);
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
}

.voucher-input-row input:focus {
    border-color: var(--color-primary-500);
    background: var(--color-white);
    box-shadow:
        0 0 0 3px rgb(36 115 74 / 10%);
}

.voucher-input-row input:disabled {
    cursor: not-allowed;
    color: var(--color-text-muted);
    background: var(--color-gray-50);
}

.voucher-input-row>button {
    display: inline-flex;
    min-width: 110px;
    height: 42px;
    align-items: center;
    justify-content: center;
    padding: 0 15px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 1px solid var(--color-primary-700);
    border-radius: var(--radius-md);
    font: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition:
        background-color var(--transition-fast),
        border-color var(--transition-fast),
        color var(--transition-fast);
}

.voucher-input-row>button:hover:not(:disabled) {
    background: var(--color-primary-800);
    border-color: var(--color-primary-800);
}

.voucher-input-row>button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.voucher-input-row>.voucher-remove-button {
    color: var(--color-danger);
    background: var(--color-white);
    border-color: #e2b9b9;
}

.voucher-input-row>.voucher-remove-button:hover:not(:disabled) {
    color: #8d3030;
    background: #fff4f4;
    border-color: #d49a9a;
}

.voucher-message {
    margin: 8px 0 0;
    font-size: 11px;
    line-height: 1.5;
}

.voucher-message--error {
    color: var(--color-danger);
}

.voucher-applied {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-top: 12px;
    padding: 13px 14px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-200);
    border-radius: var(--radius-md);
}

.voucher-applied>div {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
}

.voucher-applied>div>svg {
    flex: 0 0 auto;
}

.voucher-applied>div>div {
    display: grid;
    min-width: 0;
    gap: 3px;
}

.voucher-applied>div>div>strong {
    overflow: hidden;
    color: var(--color-primary-700);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.voucher-applied>div>div>span {
    color: var(--color-text-muted);
    font-size: 10px;
}

.voucher-applied>strong {
    flex: 0 0 auto;
    color: var(--color-success);
    font-size: 13px;
    font-weight: 700;
}

/* =========================
   PAYMENT METHOD
========================= */

.payment-methods {
    display: grid;
    gap: 12px;
    padding: 20px;
}

.payment-method {
    position: relative;
    display: grid;
    grid-template-columns:
        auto auto minmax(0, 1fr) auto;
    gap: 13px;
    min-height: 78px;
    align-items: center;
    padding: 15px;
    cursor: pointer;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    transition:
        border-color var(--transition-fast),
        background-color var(--transition-fast);
}

.payment-method:hover:not(.payment-method--disabled) {
    border-color: var(--color-primary-200);
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
    width: 19px;
    height: 19px;
    place-items: center;
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
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    place-items: center;
    color: var(--color-primary-700);
    background: var(--color-primary-100);
    border-radius: var(--radius-md);
}

.payment-method__content {
    display: grid;
    min-width: 0;
    gap: 5px;
}

.payment-method__content strong {
    color: var(--color-text-primary);
    font-size: 13px;
}

.payment-method__content span {
    color: var(--color-text-muted);
    font-size: 11px;
    line-height: 1.5;
}

.payment-method__coming {
    padding: 5px 8px;
    color: var(--color-text-muted);
    background: var(--color-gray-100);
    border-radius: var(--radius-pill);
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

/* =========================
   ORDER NOTE
========================= */

.order-note {
    position: relative;
    padding: 20px;
}

.order-note textarea {
    width: 100%;
    min-height: 118px;
    box-sizing: border-box;
    padding: 14px 14px 30px;
    resize: vertical;
    color: var(--color-text-primary);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    outline: none;
    font: inherit;
    font-size: 13px;
    line-height: 1.6;
    transition:
        border-color var(--transition-fast),
        background-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.order-note textarea::placeholder {
    color: var(--color-text-muted);
}

.order-note textarea:focus {
    background: var(--color-white);
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 4px rgb(36 115 74 / 10%);
}

.order-note>span {
    position: absolute;
    right: 34px;
    bottom: 31px;
    color: var(--color-text-muted);
    font-size: 10px;
}

/* =========================
   CHECKOUT SUMMARY
========================= */

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
    min-height: 64px;
    align-items: center;
    gap: 9px;
    padding: 0 20px;
    border-bottom: 1px solid var(--color-border);
}

.checkout-summary__header svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

.checkout-summary__header h2 {
    margin: 0;
    color: var(--color-text-primary);
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
    text-transform: uppercase;
}

.checkout-summary__address strong {
    color: var(--color-text-primary);
    font-size: 12px;
}

.checkout-summary__address p {
    margin: 0;
    color: var(--color-text-secondary);
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
    text-align: right;
}

.checkout-summary__free {
    color: var(--color-success) !important;
}

.checkout-summary__discount {
    color: var(--color-success) !important;
}

.voucher-code-inline {
    display: inline-block;
    margin-left: 5px;
    padding: 2px 5px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border: 1px solid var(--color-primary-200);
    border-radius: 3px;
    font-size: 9px;
    font-weight: 700;
    line-height: 1.3;
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
    color: var(--color-text-primary);
    font-size: 13px;
    font-weight: 600;
}

.checkout-summary__total strong {
    color: var(--color-primary-700);
    font-size: 22px;
    line-height: 1;
    text-align: right;
}

/* =========================
   CHECKOUT ERROR
========================= */

.checkout-error {
    display: grid;
    grid-template-columns:
        auto minmax(0, 1fr);
    gap: 9px;
    margin: 0 20px 16px;
    padding: 12px;
    color: var(--color-danger);
    background: #fff4f4;
    border: 1px solid #f0c5c5;
    border-radius: var(--radius-md);
    font-size: 11px;
    line-height: 1.5;
}

.checkout-error svg {
    flex-shrink: 0;
}

/* =========================
   PLACE ORDER BUTTON
========================= */

.place-order-button {
    display: inline-flex;
    width: calc(100% - 40px);
    min-height: 48px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0 20px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border: 1px solid var(--color-primary-700);
    border-radius: var(--radius-md);
    font: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition:
        background-color var(--transition-fast),
        border-color var(--transition-fast),
        opacity var(--transition-fast);
}

.place-order-button:hover:not(:disabled) {
    background: var(--color-primary-800);
    border-color: var(--color-primary-800);
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

/* =========================
   CHECKOUT BENEFITS
========================= */

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
    line-height: 1.45;
}

.checkout-benefits svg {
    flex-shrink: 0;
    color: var(--color-primary-700);
}

/* =========================
   EMPTY CHECKOUT
========================= */

.empty-checkout {
    display: grid;
    min-height: 450px;
    place-items: center;
    padding: 40px;
    text-align: center;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}

.empty-checkout__icon {
    display: grid;
    width: 68px;
    height: 68px;
    place-items: center;
    margin-bottom: 20px;
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-radius: 20px;
}

.empty-checkout h2 {
    margin: 0;
    color: var(--color-text-primary);
    font-size: 22px;
}

.empty-checkout p {
    max-width: 460px;
    margin: 10px 0 0;
    color: var(--color-text-muted);
    line-height: 1.7;
}

.empty-checkout__button {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    margin-top: 22px;
    padding: 0 18px;
    color: var(--color-white);
    background: var(--color-primary-700);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition:
        background-color var(--transition-fast);
}

.empty-checkout__button:hover {
    background: var(--color-primary-800);
}

/* =========================
   ANIMATION
========================= */

@keyframes checkout-spin {
    to {
        transform: rotate(360deg);
    }
}

/* =========================
   TABLET
========================= */

@media (max-width: 1080px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        position: static;
    }
}

/* =========================
   SMALL TABLET
========================= */

@media (max-width: 720px) {
    .checkout-page {
        gap: 22px;
    }

    .checkout-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 18px;
        padding-bottom: 22px;
    }

    .checkout-header__back {
        align-self: flex-start;
    }

    .address-list {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        width: 100%;
    }
}

/* =========================
   MOBILE
========================= */

@media (max-width: 560px) {
    .checkout-page {
        gap: 18px;
    }

    .breadcrumb {
        font-size: 11px;
    }

    .checkout-header h1 {
        font-size: 30px;
    }

    .checkout-header p {
        font-size: 12px;
    }

    .checkout-card__header {
        min-height: auto;
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
        padding: 15px;
    }

    .checkout-card__header>div {
        align-items: flex-start;
    }

    .checkout-card__action,
    .checkout-card__link {
        padding-left: 0;
    }

    .address-list {
        gap: 10px;
        padding: 15px;
    }

    .checkout-product {
        grid-template-columns:
            72px minmax(0, 1fr);
        gap: 12px;
        padding: 15px;
    }

    .checkout-product__image {
        width: 72px;
    }

    .checkout-product__price {
        grid-column: 2;
        min-width: 0;
        text-align: left;
    }

    .voucher-box {
        padding: 15px;
    }

    .voucher-input-row {
        grid-template-columns: 1fr;
    }

    .voucher-input-row>button {
        width: 100%;
    }

    .voucher-applied {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }

    .voucher-applied>strong {
        padding-left: 26px;
    }

    .payment-methods {
        padding: 15px;
    }

    .payment-method {
        grid-template-columns:
            auto auto minmax(0, 1fr);
        padding: 13px;
    }

    .payment-method__coming {
        grid-column: 3;
        width: fit-content;
    }

    .order-note {
        padding: 15px;
    }

    .order-note>span {
        right: 28px;
        bottom: 26px;
    }

    .checkout-summary__address {
        margin: 15px;
    }

    .checkout-summary__details {
        padding:
            2px 15px 15px;
    }

    .checkout-summary__notice {
        margin:
            0 15px 15px;
    }

    .checkout-summary__total {
        align-items: flex-start;
        flex-direction: column;
        padding: 15px;
    }

    .checkout-summary__total strong {
        font-size: 21px;
        text-align: left;
    }

    .checkout-error {
        margin:
            0 15px 15px;
    }

    .place-order-button {
        width: calc(100% - 30px);
        margin:
            0 15px;
    }

    .checkout-summary__agreement {
        margin:
            14px 15px 0;
    }

    .checkout-benefits {
        padding: 15px;
    }

    .empty-checkout {
        min-height: 360px;
        padding: 30px 18px;
    }

    .empty-checkout h2 {
        font-size: 19px;
    }
}

.shipping-options {
    display: grid;
    gap: 12px;
    padding: 20px;
}

.shipping-state {
    padding: 16px;
    color: var(--color-text-muted);
    background: var(--color-gray-50);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 12px;
}

.shipping-state--error {
    color: var(--color-danger);
    background: #fff4f4;
    border-color: #e2b9b9;
}

.shipping-option {
    position: relative;
    display: grid;
    grid-template-columns:
        auto auto minmax(0, 1fr) auto;
    gap: 13px;
    min-height: 74px;
    align-items: center;
    padding: 14px;
    cursor: pointer;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.shipping-option--selected {
    background: var(--color-primary-50);
    border-color: var(--color-primary-500);
}

.shipping-option>input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.shipping-option__control {
    display: grid;
    width: 19px;
    height: 19px;
    place-items: center;
    color: var(--color-white);
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: 50%;
}

.shipping-option--selected .shipping-option__control {
    background: var(--color-primary-700);
    border-color: var(--color-primary-700);
}

.shipping-option__icon {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    color: var(--color-primary-700);
    background: var(--color-primary-100);
    border-radius: var(--radius-md);
}

.shipping-option__content {
    display: grid;
    gap: 5px;
}

.shipping-option__content strong {
    color: var(--color-text-primary);
    font-size: 13px;
}

.shipping-option__content span {
    color: var(--color-text-muted);
    font-size: 11px;
}

.shipping-option__price {
    color: var(--color-primary-700);
    font-size: 13px;
    white-space: nowrap;
}
</style>

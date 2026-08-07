import {
    computed,
    ref,
} from 'vue'

import { defineStore } from 'pinia'

import {
    clearCustomerCart,
    createCartItem,
    deleteCartItem,
    getCart,
    updateCartItem,
} from '@/api/cart'


function normalizeCartItem(rawItem) {
    const product =
        rawItem.product?.data ??
        rawItem.product ??
        {}

    const seller =
        product.seller?.data ??
        product.seller ??
        rawItem.seller ??
        {}

    const sellerId = Number(
        rawItem.seller_id ??
        product.seller_id ??
        seller.id,
    )

    if (
        !Number.isInteger(sellerId) ||
        sellerId <= 0
    ) {
        console.error(
            'Cart item thiếu seller_id:',
            {
                rawItem,
                product,
                seller,
            },
        )
    }

    return {
        id: Number(rawItem.id),

        product_id: Number(
            rawItem.product_id ??
            product.id,
        ),

        seller_id: sellerId,

        slug:
            product.slug ??
            rawItem.slug ??
            '',

        name:
            product.name ??
            rawItem.name ??
            'Sản phẩm',

        image:
            product.image ??
            product.main_image ??
            product.mainImage?.url ??
            product.mainImage?.path ??
            '',

        price: Number(
            product.effective_price ??
            product.sale_price ??
            product.price ??
            0,
        ),

        originalPrice:
            product.sale_price
                ? Number(product.price)
                : null,

        stock: Number(
            product.stock ?? 0,
        ),

        seller: {
            id: sellerId,

            name:
                seller.name ??
                rawItem.seller_name ??
                `Người bán #${sellerId}`,
        },

        quantity: Number(
            rawItem.quantity ?? 1,
        ),
    }
}

export const useCartStore = defineStore(
    'cart',
    () => {
        const items = ref([])
        const isLoading = ref(false)
        const cartError = ref('')

        const itemCount = computed(() => {
            return items.value.reduce(
                (total, item) => {
                    return (
                        total +
                        Number(item.quantity)
                    )
                },
                0,
            )
        })

        const uniqueItemCount =
            computed(() => {
                return items.value.length
            })

        const subtotal = computed(() => {
            return items.value.reduce(
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

        const originalSubtotal =
            computed(() => {
                return items.value.reduce(
                    (total, item) => {
                        const price =
                            item.originalPrice ??
                            item.price

                        return (
                            total +
                            Number(price) *
                            Number(item.quantity)
                        )
                    },
                    0,
                )
            })

        const discountAmount =
            computed(() => {
                return Math.max(
                    0,
                    originalSubtotal.value -
                    subtotal.value,
                )
            })

        const isEmpty = computed(() => {
            return items.value.length === 0
        })

        function extractCartItems(response) {
            const responseData =
                response.data?.data ??
                response.data

            if (Array.isArray(responseData)) {
                return responseData
            }

            if (
                Array.isArray(
                    responseData?.items,
                )
            ) {
                return responseData.items
            }

            return []
        }
        function syncCartFromResponse(response) {
            const cartItems =
                extractCartItems(response)

            items.value = cartItems.map(
                normalizeCartItem,
            )

            return items.value
        }

        async function fetchCart() {
            isLoading.value = true
            cartError.value = ''

            try {
                const response =
                    await getCart()

                syncCartFromResponse(response)
            } catch (error) {
                cartError.value =
                    error.response?.data?.message ??
                    'Không thể tải giỏ hàng.'

                throw error
            } finally {
                isLoading.value = false
            }
        }

        function findItem(cartItemId) {
            return items.value.find(
                (item) => {
                    return (
                        item.id ===
                        Number(cartItemId)
                    )
                },
            )
        }

        function findItemByProductId(
            productId,
        ) {
            return items.value.find(
                (item) => {
                    return (
                        item.product_id ===
                        Number(productId)
                    )
                },
            )
        }

        async function addItem(
            productId,
            quantity = 1,
        ) {
            cartError.value = ''

            const normalizedProductId =
                Number(productId)

            const normalizedQuantity =
                Number(quantity)

            if (
                !Number.isInteger(
                    normalizedProductId,
                ) ||
                normalizedProductId <= 0
            ) {
                throw new Error(
                    'Product ID không hợp lệ.',
                )
            }

            if (
                !Number.isInteger(
                    normalizedQuantity,
                ) ||
                normalizedQuantity <= 0
            ) {
                throw new Error(
                    'Số lượng không hợp lệ.',
                )
            }

            try {
                const response =
                    await createCartItem({
                        product_id:
                            normalizedProductId,

                        quantity:
                            normalizedQuantity,
                    })

                /*
                 * Backend trả về toàn bộ Cart,
                 * nên phải thay toàn bộ items
                 * bằng cart.items từ response.
                 */
                syncCartFromResponse(
                    response,
                )

                return findItemByProductId(
                    normalizedProductId,
                )
            } catch (error) {
                cartError.value =
                    error.response?.data?.message ??
                    'Không thể thêm sản phẩm vào giỏ hàng.'

                throw error
            }
        }

        async function setQuantity(
            cartItemId,
            quantity,
        ) {
            const normalizedCartItemId =
                Number(cartItemId)

            const normalizedQuantity =
                Number(quantity)

            if (
                !Number.isInteger(
                    normalizedCartItemId,
                ) ||
                normalizedCartItemId <= 0 ||
                !Number.isInteger(
                    normalizedQuantity,
                ) ||
                normalizedQuantity <= 0
            ) {
                return
            }

            try {
                const response =
                    await updateCartItem(
                        normalizedCartItemId,
                        {
                            quantity:
                                normalizedQuantity,
                        },
                    )

                /*
                 * Backend trả toàn bộ Cart.
                 */
                syncCartFromResponse(
                    response,
                )
            } catch (error) {
                cartError.value =
                    error.response?.data?.message ??
                    'Không thể cập nhật số lượng.'

                throw error
            }
        }

        async function increaseQuantity(
            cartItemId,
        ) {
            const item =
                findItem(cartItemId)

            if (!item) {
                return
            }

            if (
                item.stock !== null &&
                item.quantity >= item.stock
            ) {
                return
            }

            await setQuantity(
                cartItemId,
                item.quantity + 1,
            )
        }

        async function decreaseQuantity(
            cartItemId,
        ) {
            const item =
                findItem(cartItemId)

            if (
                !item ||
                item.quantity <= 1
            ) {
                return
            }

            await setQuantity(
                cartItemId,
                item.quantity - 1,
            )
        }
        async function removeItem(
            cartItemId,
        ) {
            const normalizedCartItemId =
                Number(cartItemId)

            if (
                !Number.isInteger(
                    normalizedCartItemId,
                ) ||
                normalizedCartItemId <= 0
            ) {
                return
            }

            try {
                const response =
                    await deleteCartItem(
                        normalizedCartItemId,
                    )

                syncCartFromResponse(
                    response,
                )
            } catch (error) {
                cartError.value =
                    error.response?.data?.message ??
                    'Không thể xóa sản phẩm khỏi giỏ hàng.'

                throw error
            }
        }
        /*
         * Dùng sau khi checkout thành công.
         * Backend đã xóa CartItem trong database.
         */
        function removeItemsByIds(
            cartItemIds,
        ) {
            const selectedIds =
                new Set(
                    cartItemIds.map(
                        (itemId) =>
                            Number(itemId),
                    ),
                )

            items.value =
                items.value.filter(
                    (item) => {
                        return (
                            !selectedIds.has(
                                item.id,
                            )
                        )
                    },
                )
        }

        async function clearCart() {
            try {
                await clearCustomerCart()

                items.value = []
            } catch (error) {
                cartError.value =
                    error.response?.data?.message ??
                    'Không thể xóa giỏ hàng.'

                throw error
            }
        }

        function hasProduct(productId) {
            return Boolean(
                findItemByProductId(
                    productId,
                ),
            )
        }

        return {
            items,
            isLoading,
            cartError,

            itemCount,
            uniqueItemCount,
            subtotal,
            originalSubtotal,
            discountAmount,
            isEmpty,

            fetchCart,
            addItem,
            setQuantity,
            increaseQuantity,
            decreaseQuantity,
            removeItem,
            removeItemsByIds,
            clearCart,
            findItem,
            findItemByProductId,
            hasProduct,
        }
    },
)
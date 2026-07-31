import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { STORAGE_KEYS } from '@/constants/storage'

function readStoredCart() {
    try {
        const storedValue = localStorage.getItem(
            STORAGE_KEYS.CART_ITEMS,
        )

        if (!storedValue) {
            return []
        }

        const parsedValue = JSON.parse(storedValue)

        return Array.isArray(parsedValue)
            ? parsedValue
            : []
    } catch (error) {
        console.error(
            'Không thể đọc dữ liệu giỏ hàng:',
            error,
        )

        return []
    }
}

export const useCartStore = defineStore(
    'cart',
    () => {
        const items = ref(readStoredCart())

        const itemCount = computed(() => {
            return items.value.reduce(
                (total, item) => {
                    return total + item.quantity
                },
                0,
            )
        })

        const uniqueItemCount = computed(() => {
            return items.value.length
        })

        const subtotal = computed(() => {
            return items.value.reduce(
                (total, item) => {
                    return (
                        total +
                        item.price * item.quantity
                    )
                },
                0,
            )
        })

        const originalSubtotal = computed(() => {
            return items.value.reduce(
                (total, item) => {
                    const originalPrice =
                        item.originalPrice ??
                        item.price

                    return (
                        total +
                        originalPrice *
                            item.quantity
                    )
                },
                0,
            )
        })

        const discountAmount = computed(() => {
            return Math.max(
                0,
                originalSubtotal.value -
                    subtotal.value,
            )
        })

        const isEmpty = computed(() => {
            return items.value.length === 0
        })

        function persistCart() {
            try {
                localStorage.setItem(
                    STORAGE_KEYS.CART_ITEMS,
                    JSON.stringify(items.value),
                )
            } catch (error) {
                console.error(
                    'Không thể lưu giỏ hàng:',
                    error,
                )
            }
        }

        function findItem(productId) {
            return items.value.find((item) => {
                return item.id === productId
            })
        }

        function addItem(product, quantity = 1) {
            const safeQuantity = Math.max(
                1,
                Number(quantity) || 1,
            )

            const existingItem = findItem(
                product.id,
            )

            if (existingItem) {
                const nextQuantity =
                    existingItem.quantity +
                    safeQuantity

                existingItem.quantity =
                    product.stock !== undefined
                        ? Math.min(
                              nextQuantity,
                              product.stock,
                          )
                        : nextQuantity

                persistCart()

                return
            }

            items.value.push({
                id: product.id,
                slug: product.slug,
                name: product.name,
                image:
                    product.image ??
                    product.images?.[0] ??
                    '',
                price: Number(product.price),
                originalPrice:
                    product.originalPrice
                        ? Number(
                              product.originalPrice,
                          )
                        : null,
                stock:
                    product.stock !== undefined
                        ? Number(product.stock)
                        : null,
                seller:
                    product.seller?.name ??
                    product.seller ??
                    null,
                quantity:
                    product.stock !== undefined
                        ? Math.min(
                              safeQuantity,
                              product.stock,
                          )
                        : safeQuantity,
            })

            persistCart()
        }

        function updateQuantity(
            productId,
            quantity,
        ) {
            const item = findItem(productId)

            if (!item) {
                return
            }

            const numericQuantity =
                Number(quantity)

            if (
                Number.isNaN(numericQuantity) ||
                numericQuantity <= 0
            ) {
                removeItem(productId)
                return
            }

            const maximum =
                item.stock ?? Number.MAX_SAFE_INTEGER

            item.quantity = Math.min(
                Math.max(1, numericQuantity),
                maximum,
            )

            persistCart()
        }

        function increaseQuantity(productId) {
            const item = findItem(productId)

            if (!item) {
                return
            }

            updateQuantity(
                productId,
                item.quantity + 1,
            )
        }

        function decreaseQuantity(productId) {
            const item = findItem(productId)

            if (!item) {
                return
            }

            if (item.quantity <= 1) {
                return
            }

            updateQuantity(
                productId,
                item.quantity - 1,
            )
        }

        function removeItem(productId) {
            items.value = items.value.filter(
                (item) => {
                    return item.id !== productId
                },
            )

            persistCart()
        }

        function clearCart() {
            items.value = []
            persistCart()
        }

        function hasItem(productId) {
            return Boolean(findItem(productId))
        }

        return {
            items,

            itemCount,
            uniqueItemCount,
            subtotal,
            originalSubtotal,
            discountAmount,
            isEmpty,

            addItem,
            updateQuantity,
            increaseQuantity,
            decreaseQuantity,
            removeItem,
            clearCart,
            hasItem,
        }
    },
)
import http from '@/api/http'

export function getCart() {
    return http.get(
        '/customer/cart',
    )
}

export function createCartItem(payload) {
    return http.post(
        '/customer/cart/items',
        payload,
    )
}

export function updateCartItem(
    cartItemId,
    payload,
) {
    return http.patch(
        `/customer/cart/items/${cartItemId}`,
        payload,
    )
}

export function deleteCartItem(
    cartItemId,
) {
    return http.delete(
        `/customer/cart/items/${cartItemId}`,
    )
}

export function clearCustomerCart() {
    return http.delete(
        '/customer/cart',
    )
}
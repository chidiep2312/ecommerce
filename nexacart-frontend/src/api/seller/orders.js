import http from '@/api/http'

export function getSellerOrders(params = {}) {
    return http.get('/seller/orders', {
        params,
    })
}

export function getSellerOrder(orderId) {
    return http.get(
        `/seller/orders/${orderId}`,
    )
}

export function updateSellerOrderStatus(
    orderId,
    status,
) {
    return http.patch(
        `/seller/orders/${orderId}/status`,
        {
            status,
        },
    )
}
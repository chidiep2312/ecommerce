import http from '@/api/http'

export function createOrder(payload) {
    return http.post(
        '/customer/checkout',
        payload,
    )
}
export function getCustomerOrders(params = {}) {
    return http.get('/customer/orders', {
        params,
    })
}

export function getCustomerOrder(orderId) {
    return http.get(`/customer/orders/${orderId}`)
}
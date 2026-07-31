import http from '@/api/http'

export function createOrder(payload) {
    return http.post('/orders', payload)
}

export function getCustomerOrders(params = {}) {
    return http.get('/orders', {
        params,
    })
}

export function getCustomerOrder(orderId) {
    return http.get(`/orders/${orderId}`)
}
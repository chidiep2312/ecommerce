import http from '@/api/http'

export function createVnpayPayment(order_id) {
    return http.post(
        `customer/orders/${order_id}/payments/vnpay`,
       
    )
}
export function getPaymentStatus(
    providerOrderId,
) {
    return  http.get(
        `/customer/payments/${providerOrderId}/status`,
    )
}
import http from '@/api/http'


export function getShippingOptions(
    payload,
) {
    return http.post(
        '/customer/shipping/options',
        payload,
    )
}
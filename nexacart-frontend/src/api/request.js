import http from '@/api/http'

export function submitSellerRequest(
    payload,
) {
    return http.post(
        '/seller-requests',
        payload,
    )
}
import http from '@/api/http'

export function validateVoucher(
    payload,
) {
    return http.post(
        '/customer/vouchers/validate',
        payload,
    )
}
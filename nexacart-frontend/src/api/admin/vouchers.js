import http from '@/api/http'

export function getAdminVouchers(params = {}) {
    return http.get('/admin/vouchers', {
        params,
    })
}

export function getAdminVoucher(voucherId) {
    return http.get(
        `/admin/vouchers/${voucherId}`,
    )
}

export function createAdminVoucher(payload) {
    return http.post(
        '/admin/vouchers',
        payload,
    )
}

export function updateAdminVoucher(
    voucherId,
    payload,
) {
    return http.patch(
        `/admin/vouchers/${voucherId}`,
        payload,
    )
}

export function deleteAdminVoucher(voucherId) {
    return http.delete(
        `/admin/vouchers/${voucherId}`,
    )
}
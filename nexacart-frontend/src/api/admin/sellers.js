import http from '@/api/http'

export function getAdminSellers(
    params = {},
) {
    return http.get(
        '/admin/sellers',
        {
            params,
        },
    )
}

export function getAdminSeller(
    sellerId,
) {
    return http.get(
        `/admin/sellers/${sellerId}`,
    )
}

export function updateAdminSellerStatus(
    sellerId,
    status,
) {
    return http.patch(
        `/admin/sellers/${sellerId}/status`,
        {
            status,
        },
    )
}
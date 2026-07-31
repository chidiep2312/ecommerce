import http from '@/api/http'

export function getAdminProducts(
    params = {},
) {
    return http.get(
        '/admin/products',
        {
            params,
        },
    )
}

export function getAdminProduct(
    productId,
) {
    return http.get(
        `/admin/products/${productId}`,
    )
}
export function suspendAdminProduct(
    productId,
    reason,
) {
    return http.patch(
        `/admin/products/${productId}/suspend`,
        {
            reason,
        },
    )
}

export function restoreAdminProduct(
    productId,
) {
    return http.patch(
        `/admin/products/${productId}/restore`,
    )
}
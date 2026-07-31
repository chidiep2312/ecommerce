import http from '@/api/http'

export function getSellerInventory(
    params = {},
) {
    return http.get('/seller/inventory', {
        params,
    })
}

export function updateProductStock(
    productId,
    payload,
) {
    return http.patch(
        `/seller/inventory/${productId}`,
        payload,
    )
}
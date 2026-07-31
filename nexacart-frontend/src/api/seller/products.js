import http from '@/api/http'

export function getSellerProducts(
    params = {},
) {
    return http.get(
        '/seller/products',
        {
            params,
        },
    )
}

export function deleteSellerProduct(
    productId,
) {
    return http.delete(
        `/seller/products/${productId}`,
    )
}

export function getSellerProduct(
    productId,
) {
    return http.get(
        `/seller/products/${productId}`,
    )
}

export function updateSellerProduct(
    productId,
    payload,
) {
    return http.post(
        `/seller/products/${productId}`,
        payload,
        {
            headers: {
                'Content-Type':
                    'multipart/form-data',
            },
        },
    )
}
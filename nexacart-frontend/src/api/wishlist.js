import http from '@/api/http'

export function getWishlist(
    params = {},
) {
    return http.get(
        '/customer/wishlist',
        {
            params,
        },
    )
}

export function addToWishlist(
    productId,
) {
    return http.post(
        `/customer/wishlist/${productId}`,
    )
}

export function removeFromWishlist(
    productId,
) {
    return http.delete(
        `/customer/wishlist/${productId}`,
    )
}
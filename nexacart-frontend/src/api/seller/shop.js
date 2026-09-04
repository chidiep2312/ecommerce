
import http from '@/api/http'

export function getSellerShopProfile() {
    return http.get(
        '/seller/shop/profile',
    )
}
export function  getSellerSystemFee() {
    return http.get(
        '/seller/shop/fee',
    )
}

export function updateSellerShopProfile(
    payload,
) {
    return http.patch(
        '/seller/shop/profile',
        payload,
    )
}
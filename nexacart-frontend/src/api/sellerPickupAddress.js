import http from '@/api/http'

export function getSellerPickupAddresses() {
    return http.get(
        '/seller/pickup-addresses',
    )
}

export function createSellerPickupAddress(
    payload,
) {
    return http.post(
        '/seller/pickup-addresses',
        payload,
    )
}

export function syncSellerPickupAddress(
    addressId,
) {
    return http.post(
        `/seller/pickup-addresses/${addressId}/sync-ghn`,
    )
}

export function setDefaultSellerPickupAddress(
    addressId,
) {
    return http.patch(
        `/seller/pickup-addresses/${addressId}/default`,
    )
}
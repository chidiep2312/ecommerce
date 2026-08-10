import http from '@/api/http'

export function getCustomerAddresses() {
    return http.get(
        '/customer/addresses',
    )
}

export function createCustomerAddress(
    payload,
) {
    return http.post(
        '/customer/addresses',
        payload,
    )
}

export function updateCustomerAddress(
    addressId,
    payload,
) {
    return http.put(
        `/customer/addresses/${addressId}`,
        payload,
    )
}

export function setDefaultAddress(
    addressId,
) {
    return http.patch(
        `/customer/addresses/${addressId}/default`,
    )
}

export function deleteCustomerAddress(
    addressId,
) {
    return http.delete(
        `/customer/addresses/${addressId}`,
    )
}
import http from '@/api/http'

export function getCustomerProfile() {
    return http.get(
        '/profile',
    )
}

export function updateCustomerProfile(
    payload,
) {
    return http.patch(
        '/customer/profile',
        payload,
    )
}
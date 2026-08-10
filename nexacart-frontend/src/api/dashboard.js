import http from '@/api/http'

export function getCustomerDashboard() {
    return http.get(
        '/customer/dashboard',
    )
}
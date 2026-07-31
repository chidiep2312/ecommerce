import http from '@/api/http'

export function getAdminDashboard() {
    return http.get('/admin/dashboard')
}
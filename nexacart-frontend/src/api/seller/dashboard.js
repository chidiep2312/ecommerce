import http from '@/api/http'

export function getDashboard(
   
) {
    return http.get('/seller/dashboard')
}


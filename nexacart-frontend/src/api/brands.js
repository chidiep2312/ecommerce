import http from '@/api/http'

export function getBrands() {
    return http.get(
        '/brands',
    )
}


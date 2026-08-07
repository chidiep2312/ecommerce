import http from '@/api/http'

export function getCategories() {
    return http.get(
        '/categories',
    )
}


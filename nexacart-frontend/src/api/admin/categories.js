import http from '@/api/http'

export function getAdminCategories(
    params = {},
) {
    return http.get(
        '/admin/categories',
        {
            params,
        },
    )
}

export function createAdminCategory(
    data,
) {
    return http.post(
        '/admin/categories',
        data,
    )
}

export function getAdminCategory(
    categoryId,
) {
    return http.get(
        `/admin/categories/${categoryId}`,
    )
}

export function updateAdminCategory(
    categoryId,
    data,
) {
    return http.patch(
        `/admin/categories/${categoryId}`,
        data,
    )
}

export function deleteAdminCategory(
   categoryId,
) {
    return http.delete(
        `/admin/categories/${categoryId}`,
    )
}
import http from '@/api/http'

export function getAdminBrands(
    params = {},
) {
    return http.get(
        '/admin/brands',
        {
            params,
        },
    )
}

export function createAdminBrand(
    data,
) {
    return http.post(
        '/admin/brands',
        data,
    )
}

export function getAdminBrand(
    brandId,
) {
    return http.get(
        `/admin/brands/${brandId}`,
    )
}

export function updateAdminBrand(
    brandId,
    data,
) {
    return http.patch(
        `/admin/brands/${brandId}`,
        data,
    )
}

export function deleteAdminBrand(
    brandId,
) {
    return http.delete(
        `/admin/brands/${brandId}`,
    )
}
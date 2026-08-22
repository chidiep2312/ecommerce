import http from '@/api/http'

export function getProvinces() {
    return http.get(
        '/shipping/provinces',
    )
}

export function getDistricts(
    provinceId,
) {
    return http.get(
        `/shipping/districts/${provinceId}`,
    )
}

export function getWards(
    districtId,
) {
    return http.get(
        `/shipping/wards/${districtId}`,
    )
}

import http from '@/api/http'

export function getSellerVoucherUsages(
    params = {},
) {
    return http.get(
        '/seller/voucher-usages',
        {
            params,
        },
    )
}

export function getSellerVoucherSummary(
    params = {},
) {
    return http.get(
        '/seller/voucher-usages/summary',
        {
            params,
        },
    )
}
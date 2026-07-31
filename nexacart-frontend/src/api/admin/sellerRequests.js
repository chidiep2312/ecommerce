import http from '@/api/http'

export function getAdminSellerRequests(
    params = {},
) {
    return http.get(
        '/admin/seller-requests',
        {
            params,
        },
    )
}

export function getAdminSellerRequest(
    sellerRequestId,
) {
    return http.get(
        `/admin/seller-requests/${sellerRequestId}`,
    )
}

export function approveAdminSellerRequest(
    sellerRequestId,
) {
    return http.patch(
        `/admin/seller-requests/${sellerRequestId}/approve`,
    )
}

export function rejectAdminSellerRequest(
    sellerRequestId,
    rejectionReason,
) {
    return http.patch(
        `/admin/seller-requests/${sellerRequestId}/reject`,
        {
            rejection_reason: rejectionReason,
        },
    )
}
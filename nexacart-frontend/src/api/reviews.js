
import http from '@/api/http'

export function getReviewableOrderItem(
    orderId,
    itemId,
) {
    return http.get(
        `/customer/orders/${orderId}/items/${itemId}/review`,
    )
}

export function submitProductReview(
    orderId,
    itemId,
    payload,
) {
    return http.post(
        `/customer/orders/${orderId}/items/${itemId}/review`,
        payload,
        {
            headers: {
                'Content-Type':
                    'multipart/form-data',
            },
        },
    )
}
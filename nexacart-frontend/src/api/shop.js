// src/api/shop.js

import http from '@/api/http'

export function getPublicShop(
    slug,
) {
    return http.get(
        `/shops/${slug}`,
    )
}

export function getPublicShopProducts(
    slug,
    params = {},
) {
    return http.get(
        `/shops/${slug}/products`,
        {
            params,
        },
    )
}
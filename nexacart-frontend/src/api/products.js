import http from '@/api/http'

export function getProducts(params = {}) {
    return http.get(
        '/products',
        {
            params,
        },
    )
}
export function getProduct(slug) {
    
    return http.get(
           `/products/${slug}`,
       
    )
    
}
// export function getProductBySlug(slug) {
//     return http.get(
//         `/products/${slug}`,
//     )
// }
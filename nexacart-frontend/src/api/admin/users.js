import http from '@/api/http'

export function getAdminUsers(params = {}) {
    return http.get('/admin/users', {
        params,
    })
}

export function getAdminUser(userId) {
    return http.get(
        `/admin/users/${userId}`,
    )
}

export function updateAdminUserStatus(
    userId,
    payload,
) {
    return http.patch(
        `/admin/users/${userId}/status`,
        payload,
    )
}

export function updateAdminUserRoles(
    userId,
    payload,
) {
    return http.patch(
        `/admin/users/${userId}/role`,
        payload,
    )
}
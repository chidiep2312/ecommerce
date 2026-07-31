import http from '@/api/http'

export function login(payload) {
    return http.post('/login', payload)
}

export function register(payload) {
    return http.post('/register', payload)
}

export function getCurrentUser() {
    return http.get('/profile')
}

export function logout() {
    return http.post('/logout')
}
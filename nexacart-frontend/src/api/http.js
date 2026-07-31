import axios from 'axios'

import { STORAGE_KEYS } from '@/constants/storage'

const http = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    timeout: 15000,
    headers: {
        Accept: 'application/json',
    },
})

http.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem(
            STORAGE_KEYS.ACCESS_TOKEN,
        )

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        return config
    },
    (error) => Promise.reject(error),
)

http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem(
                STORAGE_KEYS.ACCESS_TOKEN,
            )

            localStorage.removeItem(
                STORAGE_KEYS.AUTH_USER,
            )

            const isLoginPage =
                window.location.pathname === '/login'

            if (!isLoginPage) {
                window.location.assign('/login')
            }
        }

        return Promise.reject(error)
    },
)

export default http
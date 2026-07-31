import {
    computed,
    ref,
} from 'vue'

import { defineStore } from 'pinia'

import {
    getCurrentUser,
    login as loginApi,
    logout as logoutApi,
    register as registerApi,
} from '@/api/auth'

import { STORAGE_KEYS } from '@/constants/storage'

function readStoredUser() {
    try {
        const storedValue = localStorage.getItem(
            STORAGE_KEYS.AUTH_USER,
        )

        if (!storedValue) {
            return null
        }

        return JSON.parse(storedValue)
    } catch (error) {
        console.error(
            'Không thể đọc thông tin người dùng:',
            error,
        )

        return null
    }
}

export const useAuthStore = defineStore(
    'auth',
    () => {
        const user = ref(readStoredUser())

        const token = ref(
            localStorage.getItem(
                STORAGE_KEYS.ACCESS_TOKEN,
            ),
        )

        const isLoading = ref(false)

        const isAuthenticated = computed(() => {
            return Boolean(
                token.value &&
                user.value,
            )
        })

        const role = computed(() => {
            return user.value?.role ?? null
        })

        function saveAuth(
            accessToken,
            authenticatedUser,
        ) {
            token.value = accessToken
            user.value = authenticatedUser

            localStorage.setItem(
                STORAGE_KEYS.ACCESS_TOKEN,
                accessToken,
            )

            localStorage.setItem(
                STORAGE_KEYS.AUTH_USER,
                JSON.stringify(
                    authenticatedUser,
                ),
            )
        }

        function clearAuth() {
            token.value = null
            user.value = null

            localStorage.removeItem(
                STORAGE_KEYS.ACCESS_TOKEN,
            )

            localStorage.removeItem(
                STORAGE_KEYS.AUTH_USER,
            )
        }

        async function login(credentials) {
            isLoading.value = true

            try {
                const response =
                    await loginApi(credentials)

                const data = response.data

                const accessToken =
                    data.token ??
                    data.data?.token 

                const authenticatedUser =
                    data.user ??
                    data.data?.user

                if (
                    !accessToken ||
                    !authenticatedUser
                ) {
                    throw new Error(
                        'Phản hồi đăng nhập không hợp lệ.',
                    )
                }

                saveAuth(
                    accessToken,
                    authenticatedUser,
                )

                return authenticatedUser
            } finally {
                isLoading.value = false
            }
        }

        async function register(payload) {
            isLoading.value = true

            try {
                const response =
                    await registerApi(payload)

                const data = response.data

                const accessToken =
                    data.token ??
                    data.data?.token 

                const authenticatedUser =
                    data.user ??
                    data.data?.user

                if (
                    accessToken &&
                    authenticatedUser
                ) {
                    saveAuth(
                        accessToken,
                        authenticatedUser,
                    )
                }

                return {
                    user: authenticatedUser,
                    token: accessToken,
                    response,
                }
            } finally {
                isLoading.value = false
            }
        }

        async function fetchCurrentUser() {
            if (!token.value) {
                return null
            }

            try {
                const response =
                    await getCurrentUser()

                user.value =
                    response.data.data ??
                    response.data.user ??
                    response.data

                localStorage.setItem(
                    STORAGE_KEYS.AUTH_USER,
                    JSON.stringify(user.value),
                )

                return user.value
            } catch (error) {
                clearAuth()

                throw error
            }
        }

        async function logout() {
            try {
                if (token.value) {
                    await logoutApi()
                }
            } catch (error) {
                console.error(
                    'Không thể đăng xuất trên máy chủ:',
                    error,
                )
            } finally {
                clearAuth()
            }
        }

        return {
            user,
            token,
            role,
            isLoading,
            isAuthenticated,

            login,
            register,
            logout,
            fetchCurrentUser,
            clearAuth,
        }
    },
)
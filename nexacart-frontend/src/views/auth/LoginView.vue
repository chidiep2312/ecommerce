<script setup>
import {
    AlertCircle,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
} from '@lucide/vue'

import {
    computed,
    reactive,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const showPassword = ref(false)
const submitError = ref('')

const form = reactive({
    email: '',
    password: '',
    remember: true,
})

const errors = reactive({
    email: '',
    password: '',
})

const canSubmit = computed(() => {
    return (
        form.email.trim() !== '' &&
        form.password !== '' &&
        !authStore.isLoading
    )
})

function validateForm() {
    errors.email = ''
    errors.password = ''

    let isValid = true

    if (!form.email.trim()) {
        errors.email =
            'Vui lòng nhập email.'

        isValid = false
    } else if (
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
            form.email,
        )
    ) {
        errors.email =
            'Email không đúng định dạng.'

        isValid = false
    }

    if (!form.password) {
        errors.password =
            'Vui lòng nhập mật khẩu.'

        isValid = false
    }

    return isValid
}

async function submitLogin() {
    if (!validateForm()) {
        return
    }

    submitError.value = ''

    try {
        const user = await authStore.login({
            email: form.email.trim(),
            password: form.password,
            remember: form.remember,
        })

        const redirectPath =
            route.query.redirect

        if (
            typeof redirectPath ===
            'string'
        ) {
            router.replace(redirectPath)
            return
        }

        const normalizedRole =
            String(user.role ?? '')
                .toUpperCase()

        if (
            normalizedRole === 'ADMIN'
        ) {
            router.replace('/admin')
            return
        }

        if (
            normalizedRole === 'SELLER'
        ) {
            router.replace('/seller')
            return
        }

        router.replace('/')
    } catch (error) {
        const status =
            error.response?.status

        if (status === 422) {
            const validationErrors =
                error.response?.data?.errors

            errors.email =
                validationErrors?.email?.[0] ??
                ''

            errors.password =
                validationErrors?.password?.[0] ??
                ''

            submitError.value =
                error.response?.data?.message ??
                'Thông tin đăng nhập không hợp lệ.'

            return
        }

        if (status === 401) {
            submitError.value =
                'Email hoặc mật khẩu không chính xác.'

            return
        }

        if (status === 403) {
            submitError.value =
                error.response?.data?.message ??
                'Tài khoản chưa được phép đăng nhập.'

            return
        }

        submitError.value =
            'Không thể đăng nhập. Vui lòng thử lại.'
    }
}
</script>

<template>
    <section class="auth-card">
        <header class="auth-card__header">
            <RouterLink
                to="/"
                class="auth-card__mobile-logo"
            >
                NexaCart
            </RouterLink>

            <p class="auth-card__eyebrow">
                Chào mừng trở lại
            </p>

            <h1>Đăng nhập</h1>

            <p>
                Đăng nhập để quản lý tài khoản
                và đơn hàng của bạn.
            </p>
        </header>

        <div
            v-if="submitError"
            class="auth-alert"
            role="alert"
        >
            <AlertCircle :size="18" />

            <span>
                {{ submitError }}
            </span>
        </div>

        <form
            class="auth-form"
            @submit.prevent="submitLogin"
        >
            <label class="form-field">
                <span>Email</span>

                <div
                    class="form-control"
                    :class="{
                        'form-control--error':
                            errors.email,
                    }"
                >
                    <Mail :size="18" />

                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                </div>

                <small v-if="errors.email">
                    {{ errors.email }}
                </small>
            </label>

            <label class="form-field">
                <div class="form-field__heading">
                    <span>Mật khẩu</span>

                    <RouterLink
                        to="/forgot-password"
                    >
                        Quên mật khẩu?
                    </RouterLink>
                </div>

                <div
                    class="form-control"
                    :class="{
                        'form-control--error':
                            errors.password,
                    }"
                >
                    <LockKeyhole :size="18" />

                    <input
                        v-model="form.password"
                        :type="
                            showPassword
                                ? 'text'
                                : 'password'
                        "
                        autocomplete="current-password"
                        placeholder="Nhập mật khẩu"
                    />

                    <button
                        type="button"
                        :aria-label="
                            showPassword
                                ? 'Ẩn mật khẩu'
                                : 'Hiện mật khẩu'
                        "
                        @click="
                            showPassword =
                                !showPassword
                        "
                    >
                        <EyeOff
                            v-if="showPassword"
                            :size="18"
                        />

                        <Eye
                            v-else
                            :size="18"
                        />
                    </button>
                </div>

                <small v-if="errors.password">
                    {{ errors.password }}
                </small>
            </label>

            <label class="remember-control">
                <input
                    v-model="form.remember"
                    type="checkbox"
                />

                <span>
                    Ghi nhớ đăng nhập
                </span>
            </label>

            <button
                type="submit"
                class="auth-submit"
                :disabled="!canSubmit"
            >
                <span
                    v-if="authStore.isLoading"
                    class="auth-submit__spinner"
                />

                <template v-else>
                    Đăng nhập
                </template>
            </button>
        </form>

        <p class="auth-card__switch">
            Chưa có tài khoản?

            <RouterLink to="/register">
                Đăng ký ngay
            </RouterLink>
        </p>
    </section>
</template>

<style scoped>
@import './auth-form.css';
</style>
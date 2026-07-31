<script setup>
import {
    AlertCircle,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
    User,
} from '@lucide/vue'

import {
    computed,
    reactive,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const submitError = ref('')

const form = reactive({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    acceptTerms: false,
})

const errors = reactive({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    acceptTerms: '',
})

const canSubmit = computed(() => {
    return (
        form.name.trim() !== '' &&
        form.email.trim() !== '' &&
        form.password !== '' &&
        form.passwordConfirmation !== '' &&
        form.acceptTerms &&
        !authStore.isLoading
    )
})

function validateForm() {
    Object.keys(errors).forEach((key) => {
        errors[key] = ''
    })

    let isValid = true

    if (form.name.trim().length < 2) {
        errors.name =
            'Họ tên phải có ít nhất 2 ký tự.'

        isValid = false
    }

    if (
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
            form.email,
        )
    ) {
        errors.email =
            'Email không đúng định dạng.'

        isValid = false
    }

    if (form.password.length < 8) {
        errors.password =
            'Mật khẩu phải có ít nhất 8 ký tự.'

        isValid = false
    }

    if (
        form.passwordConfirmation !==
        form.password
    ) {
        errors.passwordConfirmation =
            'Mật khẩu xác nhận không khớp.'

        isValid = false
    }

    if (!form.acceptTerms) {
        errors.acceptTerms =
            'Bạn phải đồng ý với điều khoản.'

        isValid = false
    }

    return isValid
}

async function submitRegister() {
    if (!validateForm()) {
        return
    }

    submitError.value = ''

    try {
        const result =
            await authStore.register({
                name: form.name.trim(),
                email: form.email.trim(),
                password: form.password,
                password_confirmation:
                    form.passwordConfirmation,
                role: 'BUYER',
            })

        if (
            result.token &&
            result.user
        ) {
            router.replace('/')
            return
        }

        router.replace({
            name: 'login',
            query: {
                registered: '1',
            },
        })
    } catch (error) {
        const status =
            error.response?.status

        if (status === 422) {
            const validationErrors =
                error.response?.data?.errors ??
                {}

            errors.name =
                validationErrors.name?.[0] ??
                ''

            errors.email =
                validationErrors.email?.[0] ??
                ''

            errors.password =
                validationErrors.password?.[0] ??
                ''

            submitError.value =
                error.response?.data?.message ??
                'Thông tin đăng ký chưa hợp lệ.'

            return
        }

        submitError.value =
            'Không thể đăng ký tài khoản. Vui lòng thử lại.'
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
                Tạo tài khoản mới
            </p>

            <h1>Đăng ký</h1>

            <p>
                Tạo tài khoản để mua sắm và theo
                dõi đơn hàng trên NexaCart.
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
            @submit.prevent="submitRegister"
        >
            <label class="form-field">
                <span>Họ và tên</span>

                <div
                    class="form-control"
                    :class="{
                        'form-control--error':
                            errors.name,
                    }"
                >
                    <User :size="18" />

                    <input
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        placeholder="Nhập họ và tên"
                    />
                </div>

                <small v-if="errors.name">
                    {{ errors.name }}
                </small>
            </label>

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
                <span>Mật khẩu</span>

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
                        autocomplete="new-password"
                        placeholder="Tối thiểu 8 ký tự"
                    />

                    <button
                        type="button"
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

            <label class="form-field">
                <span>Xác nhận mật khẩu</span>

                <div
                    class="form-control"
                    :class="{
                        'form-control--error':
                            errors.passwordConfirmation,
                    }"
                >
                    <LockKeyhole :size="18" />

                    <input
                        v-model="
                            form.passwordConfirmation
                        "
                        :type="
                            showPasswordConfirmation
                                ? 'text'
                                : 'password'
                        "
                        autocomplete="new-password"
                        placeholder="Nhập lại mật khẩu"
                    />

                    <button
                        type="button"
                        @click="
                            showPasswordConfirmation =
                                !showPasswordConfirmation
                        "
                    >
                        <EyeOff
                            v-if="
                                showPasswordConfirmation
                            "
                            :size="18"
                        />

                        <Eye
                            v-else
                            :size="18"
                        />
                    </button>
                </div>

                <small
                    v-if="
                        errors.passwordConfirmation
                    "
                >
                    {{
                        errors.passwordConfirmation
                    }}
                </small>
            </label>

            <label class="terms-control">
                <input
                    v-model="form.acceptTerms"
                    type="checkbox"
                />

                <span>
                    Tôi đồng ý với
                    <a href="#">
                        điều khoản sử dụng
                    </a>
                    và
                    <a href="#">
                        chính sách bảo mật
                    </a>
                    của NexaCart.
                </span>
            </label>

            <small
                v-if="errors.acceptTerms"
                class="terms-error"
            >
                {{ errors.acceptTerms }}
            </small>

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
                    Tạo tài khoản
                </template>
            </button>
        </form>

        <p class="auth-card__switch">
            Đã có tài khoản?

            <RouterLink to="/login">
                Đăng nhập
            </RouterLink>
        </p>
    </section>
</template>

<style scoped>
@import './auth-form.css';
</style>
<script setup>
import {
    Mail,
    Phone,
    Save,
    UserRound,
    CircleAlert,
    Clock3,
    Store,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    getCustomerProfile,
    updateCustomerProfile,
} from '@/api/profile'
import {
   submitSellerRequest,
} from '@/api/request'

import {
    STORAGE_KEYS,
} from '@/constants/storage'

import {
    useAuthStore,
} from '@/stores/auth'

const authStore = useAuthStore()
const showSellerRequestForm = ref(false)
const sellerRequestReason = ref('')
const sellerRequestLoading = ref(false)
const sellerRequestError = ref('')
const isLoading = ref(false)
const isSubmitting = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const validationErrors = ref({})

const originalProfile = ref(null)
const latestSellerRequest =
    computed(() => {
        return (
            originalProfile.value
                ?.latest_seller_request ??
            null
        )
    })

const canRequestSeller =
    computed(() => {
        if (
            originalProfile.value
                ?.role === 'seller'
        ) {
            return false
        }

        if (
            latestSellerRequest.value
                ?.status === 'pending'
        ) {
            return false
        }

        if (
            latestSellerRequest.value
                ?.status === 'approved'
        ) {
            return false
        }

        return true
    })

async function requestSellerUpgrade() {
    const reason =
        sellerRequestReason.value
            .trim()

    if (!reason) {
        sellerRequestError.value =
            'Vui lòng nhập lý do muốn trở thành người bán.'

        return
    }

    sellerRequestLoading.value = true
    sellerRequestError.value = ''

    try {
        const response =
            await submitSellerRequest({
                reason,
            })

        await fetchProfile()

        sellerRequestReason.value = ''
        showSellerRequestForm.value =
            false

        successMessage.value =
            response.data?.message ??
            'Đã gửi yêu cầu trở thành người bán.'
    } catch (error) {
        sellerRequestError.value =
            error.response?.data
                ?.errors?.reason?.[0] ??
            error.response?.data
                ?.message ??
            'Không thể gửi yêu cầu.'
    } finally {
        sellerRequestLoading.value =
            false
    }
}
const form = reactive({
    name: '',
    email: '',
    phone: '',
})

const hasChanges = computed(() => {
    if (!originalProfile.value) {
        return false
    }

    return (
        form.name.trim() !==
        (
            originalProfile.value
                .name ?? ''
        ) ||
        form.email.trim() !==
        (
            originalProfile.value
                .email ?? ''
        ) ||
        form.phone.trim() !==
        (
            originalProfile.value
                .phone ?? ''
        )
    )
})

const userInitial = computed(() => {
    const name =
        form.name.trim()

    if (!name) {
        return 'U'
    }

    return name
        .charAt(0)
        .toUpperCase()
})

async function fetchProfile() {
    isLoading.value = true

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await getCustomerProfile()

        const profile =
            response.data?.data

        if (!profile) {
            throw new Error(
                'Không nhận được thông tin tài khoản.',
            )
        }

        setProfileData(profile)
    } catch (error) {
        console.error(
            'Không thể tải hồ sơ:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            error.message ??
            'Không thể tải thông tin hồ sơ.'
    } finally {
        isLoading.value = false
    }
}

function setProfileData(profile) {
    originalProfile.value = {
        ...profile,
    }

    form.name =
        profile.name ?? ''

    form.email =
        profile.email ?? ''

    form.phone =
        profile.phone ?? ''
}

function resetValidationError(field) {
    if (
        validationErrors.value[
        field
        ]
    ) {
        delete validationErrors
            .value[field]
    }
}

function resetForm() {
    if (!originalProfile.value) {
        return
    }

    form.name =
        originalProfile.value
            .name ?? ''

    form.email =
        originalProfile.value
            .email ?? ''

    form.phone =
        originalProfile.value
            .phone ?? ''

    validationErrors.value = {}
    errorMessage.value = ''
    successMessage.value = ''
}

async function submitProfile() {
    if (
        !hasChanges.value ||
        isSubmitting.value
    ) {
        return
    }

    isSubmitting.value = true

    errorMessage.value = ''
    successMessage.value = ''
    validationErrors.value = {}

    try {
        const payload = {
            name:
                form.name.trim(),

            email:
                form.email.trim(),

            phone:
                form.phone.trim() ||
                null,
        }

        const response =
            await updateCustomerProfile(
                payload,
            )

        const updatedUser =
            response.data?.data

        if (!updatedUser) {
            throw new Error(
                'Không nhận được thông tin tài khoản sau khi cập nhật.',
            )
        }

        setProfileData(
            updatedUser,
        )

        /*
         * Đồng bộ Pinia.
         */
        authStore.user =
            updatedUser

        /*
         * Đồng bộ localStorage để reload
         * trang vẫn có thông tin mới.
         */
        localStorage.setItem(
            STORAGE_KEYS.AUTH_USER,
            JSON.stringify(
                updatedUser,
            ),
        )

        successMessage.value =
            response.data?.message ??
            'Cập nhật hồ sơ thành công.'
    } catch (error) {
        if (
            error.response?.status ===
            422
        ) {
            validationErrors.value =
                error.response?.data
                    ?.errors ?? {}

            return
        }

        console.error(
            'Không thể cập nhật hồ sơ:',
            error,
        )

        errorMessage.value =
            error.response?.data?.message ??
            error.message ??
            'Không thể cập nhật hồ sơ.'
    } finally {
        isSubmitting.value = false
    }
}

function formatDate(value) {
    if (!value) {
        return '--'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return '--'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
    ).format(date)
}

function getStatusLabel(status) {
    const labels = {
        active: 'Đang hoạt động',
        locked: 'Đã khóa',
        inactive: 'Ngừng hoạt động',
    }

    return (
        labels[status] ??
        status ??
        '--'
    )
}

onMounted(() => {
    fetchProfile()
})
</script>

<template>
    <div class="profile-page">
        <header class="profile-header">
            <div>
                <p class="profile-eyebrow">
                    HỒ SƠ CÁ NHÂN
                </p>

                <h2>
                    Thông tin tài khoản
                </h2>

                <p class="profile-description">
                    Xem và cập nhật thông tin
                    cá nhân của bạn trên NexaCart.
                </p>
            </div>
        </header>

        <div v-if="errorMessage" class="alert alert--error">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert--success">
            {{ successMessage }}
        </div>

        <div v-if="isLoading" class="loading-state">
            Đang tải thông tin hồ sơ...
        </div>

        <div v-else class="profile-layout">
            <!-- PROFILE CARD -->
            <aside class="profile-card">
                <div class="profile-avatar">
                    {{ userInitial }}
                </div>

                <h3>
                    {{
                        form.name ||
                        'Khách hàng'
                    }}
                </h3>

                <p class="profile-email">
                    {{
                        form.email ||
                        '--'
                    }}
                </p>

                <span class="role-badge">
                    Khách hàng
                </span>

                <dl class="account-information">
                    <div>
                        <dt>
                            Trạng thái
                        </dt>

                        <dd :class="{
                            active:
                                originalProfile
                                    ?.status ===
                                'active',
                        }">
                            {{
                                getStatusLabel(
                                    originalProfile
                                        ?.status,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Ngày tham gia
                        </dt>

                        <dd>
                            {{
                                formatDate(
                                    originalProfile
                                        ?.created_at,
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>
                            Vai trò
                        </dt>

                        <dd>
                            Khách hàng
                        </dd>
                    </div>
                </dl>
            </aside>

            <!-- EDIT FORM -->
            <section class="profile-form-panel">
                <header class="panel-header">
                    <div>
                        <h3>
                            Thông tin cá nhân
                        </h3>

                        <p>
                            Thông tin này được sử dụng
                            trong tài khoản và quá trình
                            mua hàng.
                        </p>
                    </div>
                </header>

                <form class="profile-form" @submit.prevent="
                    submitProfile
                ">
                    <div class="form-group">
                        <label for="customer-name">
                            Họ và tên
                        </label>

                        <div class="input-wrapper" :class="{
                            error:
                                validationErrors
                                    .name?.length,
                        }">
                            <UserRound :size="17" />

                            <input id="customer-name" v-model="form.name" type="text" autocomplete="name"
                                placeholder="Nhập họ và tên" @input="
                                    resetValidationError(
                                        'name',
                                    )
                                    ">
                        </div>

                        <small v-if="
                            validationErrors
                                .name?.[0]
                        " class="field-error">
                            {{
                                validationErrors
                                    .name[0]
                            }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="customer-email">
                            Email
                        </label>

                        <div class="input-wrapper" :class="{
                            error:
                                validationErrors
                                    .email?.length,
                        }">
                            <Mail :size="17" />

                            <input id="customer-email" v-model="form.email" type="email" autocomplete="email"
                                placeholder="Nhập email" @input="
                                    resetValidationError(
                                        'email',
                                    )
                                    ">
                        </div>

                        <small v-if="
                            validationErrors
                                .email?.[0]
                        " class="field-error">
                            {{
                                validationErrors
                                    .email[0]
                            }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="customer-phone">
                            Số điện thoại
                        </label>

                        <div class="input-wrapper" :class="{
                            error:
                                validationErrors
                                    .phone?.length,
                        }">
                            <Phone :size="17" />

                            <input id="customer-phone" v-model="form.phone" type="tel" autocomplete="tel"
                                placeholder="Nhập số điện thoại" @input="
                                    resetValidationError(
                                        'phone',
                                    )
                                    ">
                        </div>

                        <small v-if="
                            validationErrors
                                .phone?.[0]
                        " class="field-error">
                            {{
                                validationErrors
                                    .phone[0]
                            }}
                        </small>

                        <small v-else class="field-note">
                            Số điện thoại có thể được
                            sử dụng khi giao hàng.
                        </small>
                    </div>

                    <div class="readonly-section">
                        <h4>
                            Thông tin hệ thống
                        </h4>

                        <div class="readonly-grid">
                            <div>
                                <span>
                                    Vai trò
                                </span>

                                <strong>
                                    Khách hàng
                                </strong>
                            </div>

                            <div>
                                <span>
                                    Trạng thái
                                </span>

                                <strong>
                                    {{
                                        getStatusLabel(
                                            originalProfile
                                                ?.status,
                                        )
                                    }}
                                </strong>
                            </div>

                            <div>
                                <span>
                                    Ngày tham gia
                                </span>

                                <strong>
                                    {{
                                        formatDate(
                                            originalProfile
                                                ?.created_at,
                                        )
                                    }}
                                </strong>
                            </div>

                            <div>
                                <span>
                                    Mã tài khoản
                                </span>

                                <strong>
                                    #{{
                                        originalProfile
                                            ?.id ??
                                        '--'
                                    }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <footer class="form-actions">
                        <div class="change-status">
                            <span v-if="hasChanges">
                                Có thay đổi chưa được lưu
                            </span>

                            <span v-else>
                                Thông tin đã được cập nhật
                            </span>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="secondary-button" :disabled="!hasChanges ||
                                isSubmitting
                                " @click="resetForm">
                                Hủy thay đổi
                            </button>

                            <button type="submit" class="primary-button" :disabled="!hasChanges ||
                                isSubmitting
                                ">
                                <Save :size="17" />

                                {{
                                    isSubmitting
                                        ? 'Đang lưu...'
                                        : 'Lưu thay đổi'
                                }}
                            </button>
                        </div>
                    </footer>
                </form>
    <section v-if="
                originalProfile?.role !==
                'seller'
            " class="seller-upgrade-card">
                <div class="seller-upgrade-main">
                    <div class="seller-upgrade-icon">
                        <Store :size="24" />
                    </div>

                    <div>
                        <p class="seller-upgrade-label">
                            KÊNH NGƯỜI BÁN
                        </p>

                        <h3>
                            Trở thành người bán
                        </h3>

                        <p>
                            Đăng ký trở thành người bán
                            để mở cửa hàng và kinh doanh
                            sản phẩm trên NexaCart.
                        </p>
                    </div>
                </div>

                <!-- CHƯA GỬI HOẶC ĐÃ BỊ TỪ CHỐI -->
                <div v-if="canRequestSeller" class="seller-upgrade-action">
                    <button v-if="
                        !showSellerRequestForm
                    " type="button" class="seller-request-button" @click="
                        showSellerRequestForm =
                        true
                        ">
                        Gửi yêu cầu xét duyệt
                    </button>
                </div>

                <!-- PENDING -->
                <div v-else-if="
                    latestSellerRequest
                        ?.status ===
                    'pending'
                " class="seller-request-status pending">
                    <Clock3 :size="17" />

                    <div>
                        <strong>
                            Đang chờ xét duyệt
                        </strong>

                        <span>
                            Yêu cầu của bạn đã được
                            gửi đến quản trị viên.
                        </span>
                    </div>
                </div>

                <!-- REJECTED -->
                <div v-if="
                    latestSellerRequest
                        ?.status ===
                    'rejected'
                " class="seller-request-status rejected">
                    <CircleAlert :size="17" />

                    <div>
                        <strong>
                            Yêu cầu trước đã bị từ chối
                        </strong>

                        <span v-if="
                            latestSellerRequest
                                .review_note
                        ">
                            {{
                                latestSellerRequest
                                    .review_note
                            }}
                        </span>

                        <span v-else>
                            Bạn có thể điều chỉnh thông
                            tin và gửi lại yêu cầu.
                        </span>
                    </div>
                </div>

                <!-- FORM -->
                <form v-if="showSellerRequestForm" class="seller-request-form" @submit.prevent="
                    requestSellerUpgrade
                ">
                    <label for="seller-request-reason">
                        Lý do muốn trở thành người bán
                    </label>

                    <textarea id="seller-request-reason" v-model="sellerRequestReason
                        " rows="4" maxlength="1000"
                        placeholder="Mô tả ngắn về sản phẩm hoặc kế hoạch kinh doanh của bạn..." />

                    <small v-if="sellerRequestError" class="field-error">
                        {{ sellerRequestError }}
                    </small>

                    <div class="seller-request-actions">
                        <button type="button" class="secondary-button" :disabled="sellerRequestLoading
                            " @click="
                                showSellerRequestForm =
                                false
                                ">
                            Hủy
                        </button>

                        <button type="submit" class="seller-request-button" :disabled="sellerRequestLoading
                            ">
                            {{
                                sellerRequestLoading
                                    ? 'Đang gửi...'
                                    : 'Gửi yêu cầu'
                            }}
                        </button>
                    </div>
                </form>
            </section>
            </section>
        
        </div>
    </div>
</template>

<style scoped>
.profile-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1d2921;
    font-family: Roboto, Arial, sans-serif;
}

.profile-header {
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.profile-eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.profile-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.profile-description {
    margin: 7px 0 0;
    color: #6d7a72;
    font-size: 13px;
    line-height: 1.6;
}

.profile-layout {
    display: grid;
    grid-template-columns:
        260px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

.profile-card,
.profile-form-panel {
    border: 1px solid #dce5df;
    background: #ffffff;
}

/* PROFILE CARD */

.profile-card {
    padding: 26px 20px 0;
    text-align: center;
}

.profile-avatar {
    display: grid;
    width: 82px;
    height: 82px;
    place-items: center;
    margin: 0 auto 15px;
    background: #24734a;
    color: #ffffff;
    font-size: 29px;
    font-weight: 700;
}

.profile-card h3 {
    margin: 0;
    color: #202c24;
    font-size: 17px;
}

.profile-email {
    margin: 6px 0 12px;
    overflow-wrap: anywhere;
    color: #77847b;
    font-size: 12px;
}

.role-badge {
    display: inline-flex;
    min-height: 26px;
    align-items: center;
    padding: 0 9px;
    border: 1px solid #a4c8b1;
    background: #edf7f0;
    color: #226440;
    font-size: 11px;
    font-weight: 700;
}

.account-information {
    margin: 23px -20px 0;
    text-align: left;
}

.account-information>div {
    padding: 13px 20px;
    border-top: 1px solid #e8edea;
}

.account-information dt {
    margin-bottom: 5px;
    color: #7a877f;
    font-size: 10px;
    text-transform: uppercase;
}

.account-information dd {
    margin: 0;
    color: #35433a;
    font-size: 12px;
    font-weight: 600;
}

.account-information dd.active {
    color: #24734a;
}

/* FORM */

.panel-header {
    padding: 20px 22px;
    border-bottom: 1px solid #e5ebe7;
}

.panel-header h3 {
    margin: 0;
    font-size: 17px;
}

.panel-header p {
    max-width: 600px;
    margin: 6px 0 0;
    color: #78857c;
    font-size: 12px;
    line-height: 1.6;
}

.profile-form {
    padding: 22px;
}

.form-group {
    max-width: 620px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #435249;
    font-size: 12px;
    font-weight: 600;
}

.input-wrapper {
    display: flex;
    height: 44px;
    align-items: center;
    border: 1px solid #cad5ce;
    background: #ffffff;
}

.input-wrapper:focus-within {
    border-color: #24734a;
}

.input-wrapper.error {
    border-color: #b64b4b;
}

.input-wrapper svg {
    flex: 0 0 auto;
    margin-left: 13px;
    color: #7b887f;
}

.input-wrapper input {
    width: 100%;
    height: 100%;
    border: 0;
    outline: none;
    padding: 0 13px 0 10px;
    color: #26332b;
    background: transparent;
    font: inherit;
    font-size: 13px;
}

.field-error,
.field-note {
    display: block;
    margin-top: 6px;
    font-size: 11px;
}

.field-error {
    color: #a23a3a;
}

.field-note {
    color: #7b887f;
}

/* READONLY */

.readonly-section {
    margin-top: 30px;
    padding-top: 22px;
    border-top: 1px solid #e5ebe7;
}

.readonly-section h4 {
    margin: 0 0 14px;
    font-size: 13px;
}

.readonly-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.readonly-grid>div {
    padding: 14px;
    border: 1px solid #e0e7e2;
    background: #f8faf9;
}

.readonly-grid span,
.readonly-grid strong {
    display: block;
}

.readonly-grid span {
    color: #7a877f;
    font-size: 10px;
    text-transform: uppercase;
}

.readonly-grid strong {
    margin-top: 6px;
    color: #334139;
    font-size: 12px;
}

/* ACTION */

.form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #e5ebe7;
}

.change-status {
    color: #77847b;
    font-size: 11px;
}

.action-buttons {
    display: flex;
    gap: 9px;
}

.primary-button,
.secondary-button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    border-radius: 0;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.primary-button {
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
}

.primary-button:hover:not(:disabled) {
    background: #1d633f;
}

.secondary-button {
    border: 1px solid #c6d2ca;
    background: #ffffff;
    color: #46544b;
}

.secondary-button:hover:not(:disabled) {
    border-color: #24734a;
    background: #f2f7f4;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* MESSAGES */

.alert {
    padding: 12px 15px;
    border: 1px solid;
    font-size: 12px;
}

.alert--error {
    border-color: #ddb0b0;
    background: #fff3f3;
    color: #983737;
}

.alert--success {
    border-color: #9ec5aa;
    background: #edf7f0;
    color: #21633f;
}

.loading-state {
    display: grid;
    min-height: 350px;
    place-items: center;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #718078;
    font-size: 13px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }

    .profile-card {
        text-align: left;
    }

    .profile-avatar {
        margin-left: 0;
    }
}

@media (max-width: 620px) {
    .readonly-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .action-buttons {
        flex-direction: column-reverse;
    }

    .action-buttons button {
        width: 100%;
    }
}

.seller-upgrade-card {
    margin-top: 18px;
    padding: 22px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.seller-upgrade-main {
    display: flex;
    gap: 14px;
}

.seller-upgrade-icon {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 auto;
    place-items: center;
    color: #24734a;
    background: #edf6f0;
}

.seller-upgrade-label {
    margin: 0 0 5px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.seller-upgrade-main h3 {
    margin: 0;
    font-size: 16px;
}

.seller-upgrade-main p:last-child {
    max-width: 600px;
    margin: 7px 0 0;
    color: #718078;
    font-size: 12px;
    line-height: 1.6;
}

.seller-upgrade-action {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e5ebe7;
}

.seller-request-button {
    min-height: 40px;
    padding: 0 15px;
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.seller-request-button:hover:not(:disabled) {
    background: #1e633f;
}

.seller-request-status {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 18px;
    padding: 13px;
    border: 1px solid;
}

.seller-request-status>div {
    display: grid;
    gap: 4px;
}

.seller-request-status strong {
    font-size: 12px;
}

.seller-request-status span {
    font-size: 11px;
    line-height: 1.5;
}

.seller-request-status.pending {
    border-color: #ddc982;
    background: #fff9e8;
    color: #785f12;
}

.seller-request-status.rejected {
    border-color: #dfb1b1;
    background: #fff3f3;
    color: #963939;
}

.seller-request-form {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e5ebe7;
}

.seller-request-form label {
    display: block;
    margin-bottom: 7px;
    color: #46544b;
    font-size: 12px;
    font-weight: 600;
}

.seller-request-form textarea {
    width: 100%;
    box-sizing: border-box;
    resize: vertical;
    padding: 12px;
    border: 1px solid #cad5ce;
    outline: none;
    color: #26332b;
    background: #ffffff;
    font: inherit;
    font-size: 12px;
    line-height: 1.6;
}

.seller-request-form textarea:focus {
    border-color: #24734a;
}

.seller-request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 13px;
}

@media (max-width: 620px) {
    .seller-upgrade-main {
        flex-direction: column;
    }

    .seller-request-actions {
        flex-direction: column-reverse;
    }

    .seller-request-actions button {
        width: 100%;
    }
}
</style>
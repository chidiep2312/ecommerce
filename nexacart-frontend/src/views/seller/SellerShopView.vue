<script setup>
import {
    Building2,
    CircleDollarSign,
    Mail,
    Pencil,
    Phone,
    Save,
    Store,
    X,
} from '@lucide/vue'

import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    getSellerShopProfile,
    getSellerSystemFee,
    updateSellerShopProfile,
} from '@/api/seller/shop'
const shop = ref(null)
const systemFee = ref({
    current_fee: 0,
    total_fee: 0,
    fee_rate: 5,
    min_order_total: 300000,
})

const isLoading = ref(false)
const isSubmitting = ref(false)

const isEditing = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const validationErrors = ref({})

const form = reactive({
    name: '',
    phone: '',
    email: '',
    description: '',
})

const shopInitial = computed(() => {
    const name =
        shop.value?.name?.trim()

    if (!name) {
        return 'S'
    }

    return name
        .charAt(0)
        .toUpperCase()
})

const hasChanges = computed(() => {
    if (!shop.value) {
        return false
    }

    return (
        form.name.trim() !==
        (shop.value.name ?? '') ||
        form.phone.trim() !==
        (shop.value.phone ?? '') ||
        form.description.trim() !==
        (shop.value.description ?? '')
    )
})
async function fetchSystemFee() {
    try {
        const response = await getSellerSystemFee()

        systemFee.value = response.data?.data ?? {
            current_fee: 0,
            total_fee: 0,
            fee_rate: 5,
            min_order_total: 300000,
        }
    } catch (error) {
        console.error(
            'Không thể tải phí hệ thống:',
            error
        )
    }
}
async function fetchShopProfile() {
    isLoading.value = true

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await getSellerShopProfile()

        shop.value =
            response.data?.data ??
            null

        fillForm()
    } catch (error) {
        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tải hồ sơ cửa hàng.'
    } finally {
        isLoading.value = false
    }
}

function fillForm() {
    if (!shop.value) {
        return
    }

    form.name =
        shop.value.name ?? ''

    form.phone =
        shop.value.phone ?? ''

    form.email =
        shop.value.email ?? ''

    form.description =
        shop.value.description ?? ''
}

function startEdit() {
    fillForm()

    validationErrors.value = {}
    errorMessage.value = ''
    successMessage.value = ''

    isEditing.value = true
}

function cancelEdit() {
    fillForm()

    validationErrors.value = {}

    isEditing.value = false
}

function clearFieldError(field) {
    if (
        validationErrors.value[
        field
        ]
    ) {
        delete validationErrors
            .value[field]
    }
}

async function submitProfile() {
    if (
        isSubmitting.value ||
        !hasChanges.value
    ) {
        return
    }

    isSubmitting.value = true

    errorMessage.value = ''
    successMessage.value = ''
    validationErrors.value = {}

    try {
        const response =
            await updateSellerShopProfile({
                name:
                    form.name.trim(),

                phone:
                    form.phone.trim() ||
                    null,

                description:
                    form.description.trim() ||
                    null,
            })

        shop.value =
            response.data?.data

        fillForm()

        successMessage.value =
            response.data?.message ??
            'Cập nhật hồ sơ cửa hàng thành công.'

        isEditing.value = false
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

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể cập nhật hồ sơ cửa hàng.'
    } finally {
        isSubmitting.value = false
    }
}

function getStatusLabel(status) {
    const labels = {
        active:
            'Đang hoạt động',

        inactive:
            'Ngừng hoạt động',

        suspended:
            'Tạm khóa',
    }

    return (
        labels[status] ??
        status ??
        '--'
    )
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
function formatCurrency(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(Number(value ?? 0))
}

function paySystemFee() {
    if (Number(systemFee.value.current_fee) <= 0) {
        return
    }

    console.log(
        'Nộp phí:',
        systemFee.value.current_fee
    )
}
onMounted(() => {
    fetchShopProfile()
    fetchSystemFee()
})
</script>

<template>
    <div class="shop-profile-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">
                    CỬA HÀNG
                </p>

                <h1>
                    Hồ sơ cửa hàng
                </h1>

                <p>
                    Quản lý thông tin hiển thị
                    của cửa hàng trên NexaCart.
                </p>
            </div>

            <button v-if="
                shop &&
                !isEditing
            " type="button" class="edit-button" @click="
                startEdit
            ">
                <Pencil :size="16" />

                Chỉnh sửa
            </button>
        </header>

        <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>

        <div v-if="isLoading" class="loading-state">
            Đang tải hồ sơ cửa hàng...
        </div>

        <template v-else-if="shop">
            <!-- SHOP IDENTITY -->
            <section class="shop-identity-card">
                <div class="shop-avatar">
                    {{ shopInitial }}
                </div>

                <div class="shop-identity-content">
                    <div class="shop-name-row">
                        <h2>
                            {{ shop.name }}
                        </h2>

                        <span class="status-badge" :class="{
                            active:
                                shop.status ===
                                'active',

                            suspended:
                                shop.status ===
                                'suspended',
                        }">
                            {{
                                getStatusLabel(
                                    shop.status,
                                )
                            }}
                        </span>
                    </div>

                    <p>
                        {{
                            shop.description ||
                            'Chưa có giới thiệu cửa hàng.'
                        }}
                    </p>

                    <div class="shop-contact-row">
                        <span>
                            <Mail :size="14" />

                            {{
                                shop.email ??
                                '--'
                            }}
                        </span>

                        <span v-if="
                            shop.phone
                        ">
                            <Phone :size="14" />

                            {{
                                shop.phone
                            }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="system-fee-card">
                <header class="fee-header">
                    <div>
                        <p class="fee-eyebrow">
                            PHÍ HỆ THỐNG
                        </p>

                        <h2>
                            Phí nền tảng
                        </h2>

                        <p>
                            Phí dịch vụ phát sinh từ các đơn hàng
                            đủ điều kiện của cửa hàng.
                        </p>
                    </div>

                    <button type="button" class="pay-fee-button" :disabled="Number(systemFee.current_fee) <= 0
                        " @click="paySystemFee">
                        <CircleDollarSign :size="16" />

                        Nộp phí
                    </button>
                </header>

                <div class="fee-content">
                    <div class="fee-stat">
                        <span>
                            Phí tháng này
                        </span>

                        <strong>
                            {{
                                formatCurrency(
                                    systemFee.current_fee
                                )
                            }}
                        </strong>

                        <small>
                            Khoản phí phát sinh trong tháng hiện tại
                        </small>
                    </div>

                    <div class="fee-stat">
                        <span>
                            Tổng phí đã phát sinh
                        </span>

                        <strong>
                            {{
                                formatCurrency(
                                    systemFee.total_fee
                                )
                            }}
                        </strong>

                        <small>
                            Tổng phí từ các đơn đủ điều kiện
                        </small>
                    </div>

                    <div class="fee-policy">
                        <div>
                            <span>
                                Mức phí
                            </span>

                            <strong>
                                {{ systemFee.fee_rate }}%
                            </strong>
                        </div>

                        <div>
                            <span>
                                Áp dụng cho đơn từ
                            </span>

                            <strong>
                                {{
                                    formatCurrency(
                                        systemFee.min_order_total
                                    )
                                }}
                            </strong>
                        </div>
                    </div>
                </div>
            </section>
            <div class="profile-layout">
                <!-- EDITABLE INFORMATION -->
                <section class="profile-card">
                    <header class="card-header">
                        <div>
                            <h2>
                                Thông tin cửa hàng
                            </h2>

                            <p>
                                Thông tin khách hàng
                                có thể nhìn thấy khi
                                truy cập gian hàng.
                            </p>
                        </div>
                    </header>

                    <form class="profile-form" @submit.prevent="
                        submitProfile
                    ">
                        <!-- SHOP NAME -->
                        <div class="form-group">
                            <label for="shop-name">
                                Tên cửa hàng
                            </label>

                            <div class="input-wrapper" :class="{
                                disabled:
                                    !isEditing,

                                error:
                                    validationErrors
                                        .name?.length,
                            }">
                                <Store :size="17" />

                                <input id="shop-name" v-model="form.name
                                    " type="text" :disabled="!isEditing
                                        " placeholder="Tên cửa hàng" @input="
                                            clearFieldError(
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

                        <!-- EMAIL -->
                        <div class="form-group">
                            <label>
                                Email liên hệ
                            </label>

                            <div class="input-wrapper disabled">
                                <Mail :size="17" />

                                <input v-model="form.email
                                    " type="email" disabled>
                            </div>

                            <span class="field-note">
                                Email được lấy từ tài khoản
                                Seller và không chỉnh sửa
                                tại đây.
                            </span>
                        </div>

                        <!-- PHONE -->
                        <div class="form-group">
                            <label for="shop-phone">
                                Số điện thoại
                            </label>

                            <div class="input-wrapper" :class="{
                                disabled:
                                    !isEditing,

                                error:
                                    validationErrors
                                        .phone?.length,
                            }">
                                <Phone :size="17" />

                                <input id="shop-phone" v-model="form.phone
                                    " type="tel" :disabled="!isEditing
                                        " placeholder="Số điện thoại cửa hàng" @input="
                                            clearFieldError(
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
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="form-group">
                            <label for="shop-description">
                                Giới thiệu cửa hàng
                            </label>

                            <textarea id="shop-description" v-model="form.description
                                " :disabled="!isEditing
                                    " maxlength="1000" placeholder="Giới thiệu ngắn về cửa hàng..." @input="
                                        clearFieldError(
                                            'description',
                                        )
                                        " />

                            <div class="description-footer">
                                <small v-if="
                                    validationErrors
                                        .description?.[0]
                                " class="field-error">
                                    {{
                                        validationErrors
                                            .description[0]
                                    }}
                                </small>

                                <span>
                                    {{
                                        form.description
                                            .length
                                    }}/1000
                                </span>
                            </div>
                        </div>

                        <!-- ACTION -->
                        <footer v-if="isEditing" class="form-actions">
                            <button type="button" class="cancel-button" :disabled="isSubmitting
                                " @click="
                                    cancelEdit
                                ">
                                <X :size="16" />

                                Hủy
                            </button>

                            <button type="submit" class="save-button" :disabled="isSubmitting ||
                                !hasChanges
                                ">
                                <Save :size="16" />

                                {{
                                    isSubmitting
                                        ? 'Đang lưu...'
                                        : 'Lưu thay đổi'
                                }}
                            </button>
                        </footer>
                    </form>
                </section>

                <!-- SYSTEM INFO -->
                <aside class="system-card">
                    <header class="card-header">
                        <div>
                            <h2>
                                Thông tin hệ thống
                            </h2>

                            <p>
                                Các thông tin được
                                NexaCart quản lý.
                            </p>
                        </div>
                    </header>

                    <dl class="system-information">
                        <div>
                            <dt>
                                Mã Seller
                            </dt>

                            <dd>
                                #{{
                                    shop.id ??
                                    '--'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Slug cửa hàng
                            </dt>

                            <dd>
                                {{
                                    shop.slug ??
                                    '--'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Trạng thái
                            </dt>

                            <dd>
                                {{
                                    getStatusLabel(
                                        shop.status,
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
                                        shop.created_at,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div class="system-note">
                        <Building2 :size="18" />

                        <p>
                            Một số thông tin như trạng
                            thái tài khoản hoặc quyền
                            Seller được hệ thống quản lý
                            và không thể chỉnh sửa tại đây.
                        </p>
                    </div>
                </aside>
            </div>
        </template>
    </div>
</template>

<style scoped>
.shop-profile-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1f2c24;
    font-family: Roboto, Arial, sans-serif;
}

/* HEADER */

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 24px;
    background: #ffffff;
    border: 1px solid #dce5df;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.page-header h1 {
    margin: 0;
    font-size: 22px;
}

.page-header p:last-child {
    margin: 7px 0 0;
    color: #748178;
    font-size: 12px;
    line-height: 1.6;
}

.edit-button {
    display: inline-flex;
    min-height: 39px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    color: #24734a;
    background: #ffffff;
    border: 1px solid #24734a;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

/* SHOP IDENTITY */

.shop-identity-card {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px 24px;
    background: #ffffff;
    border: 1px solid #dce5df;
}

.shop-avatar {
    display: grid;
    width: 74px;
    height: 74px;
    flex: 0 0 74px;
    place-items: center;
    color: #ffffff;
    background: #24734a;
    font-size: 27px;
    font-weight: 700;
}

.shop-identity-content {
    min-width: 0;
}

.shop-name-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.shop-name-row h2 {
    margin: 0;
    font-size: 19px;
}

.status-badge {
    display: inline-flex;
    min-height: 24px;
    align-items: center;
    padding: 0 8px;
    color: #765e24;
    background: #fff8e6;
    border: 1px solid #ddc98c;
    font-size: 9px;
    font-weight: 700;
}

.status-badge.active {
    color: #226440;
    background: #edf7f0;
    border-color: #9fc5aa;
}

.status-badge.suspended {
    color: #923a3a;
    background: #fff2f2;
    border-color: #deb0b0;
}

.shop-identity-content>p {
    max-width: 750px;
    margin: 8px 0 0;
    color: #6f7d74;
    font-size: 11px;
    line-height: 1.6;
}

.shop-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 10px;
}

.shop-contact-row span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #6a786f;
    font-size: 10px;
}

.shop-contact-row svg {
    color: #24734a;
}

/* LAYOUT */

.profile-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) 300px;
    gap: 18px;
    align-items: start;
}

.profile-card,
.system-card {
    background: #ffffff;
    border: 1px solid #dce5df;
}

.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e6ece8;
}

.card-header h2 {
    margin: 0;
    font-size: 15px;
}

.card-header p {
    margin: 5px 0 0;
    color: #7a877f;
    font-size: 10px;
    line-height: 1.5;
}

/* FORM */

.profile-form {
    padding: 20px;
}

.form-group {
    max-width: 680px;
    margin-bottom: 19px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #46544b;
    font-size: 11px;
    font-weight: 600;
}

.input-wrapper {
    display: flex;
    height: 42px;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cad5ce;
}

.input-wrapper:focus-within {
    border-color: #24734a;
}

.input-wrapper.error {
    border-color: #b64949;
}

.input-wrapper.disabled {
    background: #f5f7f6;
}

.input-wrapper svg {
    flex: 0 0 auto;
    margin-left: 12px;
    color: #7a877f;
}

.input-wrapper input {
    width: 100%;
    height: 100%;
    padding: 0 12px 0 9px;
    color: #26332b;
    background: transparent;
    border: 0;
    outline: none;
    font: inherit;
    font-size: 12px;
}

.input-wrapper input:disabled {
    cursor: default;
    color: #647269;
}

.form-group textarea {
    width: 100%;
    min-height: 140px;
    box-sizing: border-box;
    padding: 12px;
    resize: vertical;
    color: #26332b;
    background: #ffffff;
    border: 1px solid #cad5ce;
    outline: none;
    font: inherit;
    font-size: 12px;
    line-height: 1.7;
}

.form-group textarea:focus {
    border-color: #24734a;
}

.form-group textarea:disabled {
    resize: none;
    color: #647269;
    background: #f5f7f6;
}

.field-note {
    display: block;
    margin-top: 6px;
    color: #89958d;
    font-size: 9px;
}

.field-error {
    display: block;
    margin-top: 5px;
    color: #9a3838;
    font-size: 10px;
}

.description-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 5px;
}

.description-footer span {
    margin-left: auto;
    color: #89958d;
    font-size: 9px;
}

/* ACTIONS */

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 25px;
    padding-top: 18px;
    border-top: 1px solid #e5ebe7;
}

.cancel-button,
.save-button {
    display: inline-flex;
    min-height: 39px;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 14px;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.cancel-button {
    color: #46544b;
    background: #ffffff;
    border: 1px solid #cad5ce;
}

.save-button {
    color: #ffffff;
    background: #24734a;
    border: 1px solid #24734a;
}

.save-button:hover:not(:disabled) {
    background: #1e633f;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

/* SYSTEM */

.system-information {
    margin: 0;
}

.system-information>div {
    padding: 13px 18px;
    border-bottom: 1px solid #edf1ee;
}

.system-information dt {
    color: #7d8981;
    font-size: 9px;
    text-transform: uppercase;
}

.system-information dd {
    margin: 5px 0 0;
    overflow-wrap: anywhere;
    color: #354239;
    font-size: 11px;
    font-weight: 600;
}

.system-note {
    display: grid;
    grid-template-columns:
        auto minmax(0, 1fr);
    gap: 9px;
    margin: 16px;
    padding: 12px;
    color: #68766d;
    background: #f5f8f6;
    border: 1px solid #dfe7e1;
}

.system-note svg {
    color: #24734a;
}

.system-note p {
    margin: 0;
    font-size: 10px;
    line-height: 1.6;
}

/* STATE */

.alert {
    padding: 11px 14px;
    border: 1px solid;
    font-size: 11px;
}

.alert-error {
    color: #963737;
    background: #fff3f3;
    border-color: #dfb1b1;
}

.alert-success {
    color: #21633f;
    background: #edf7f0;
    border-color: #9dc5aa;
}

.loading-state {
    display: grid;
    min-height: 350px;
    place-items: center;
    color: #748178;
    background: #ffffff;
    border: 1px solid #dce5df;
    font-size: 12px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .edit-button {
        width: 100%;
    }

    .shop-identity-card {
        align-items: flex-start;
    }

    .shop-avatar {
        width: 60px;
        height: 60px;
        flex-basis: 60px;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions button {
        width: 100%;
    }
}
/* SYSTEM FEE */

.system-fee-card {
    background: #ffffff;
    border: 1px solid #dce5df;
}

.fee-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px 20px;
    border-bottom: 1px solid #e6ece8;
}

.fee-eyebrow {
    margin: 0 0 5px;
    color: #24734a;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.fee-header h2 {
    margin: 0;
    color: #253129;
    font-size: 15px;
}

.fee-header > div > p:last-child {
    margin: 5px 0 0;
    color: #7a877f;
    font-size: 10px;
}

.pay-fee-button {
    display: inline-flex;
    min-height: 39px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    color: #ffffff;
    background: #24734a;
    border: 1px solid #24734a;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.pay-fee-button:hover:not(:disabled) {
    background: #1e633f;
}

.pay-fee-button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.fee-content {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr))
        minmax(240px, 0.8fr);
}

.fee-stat {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px;
    border-right: 1px solid #e6ece8;
}

.fee-stat > span {
    color: #748178;
    font-size: 10px;
    font-weight: 600;
}

.fee-stat > strong {
    color: #24734a;
    font-size: 21px;
    font-weight: 700;
}

.fee-stat > small {
    color: #89958d;
    font-size: 9px;
}

.fee-policy {
    padding: 13px 20px;
    background: #f7faf8;
}

.fee-policy > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 43px;
    border-bottom: 1px solid #e2e9e4;
}

.fee-policy > div:last-child {
    border-bottom: 0;
}

.fee-policy span {
    color: #748178;
    font-size: 10px;
}

.fee-policy strong {
    color: #354239;
    font-size: 11px;
}

@media (max-width: 800px) {
    .fee-content {
        grid-template-columns: 1fr;
    }

    .fee-stat {
        border-right: 0;
        border-bottom: 1px solid #e6ece8;
    }
}

@media (max-width: 650px) {
    .fee-header {
        align-items: stretch;
        flex-direction: column;
    }

    .pay-fee-button {
        width: 100%;
    }
}/* SYSTEM FEE */

.system-fee-card {
    background: #ffffff;
    border: 1px solid #dce5df;
}

.fee-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px 20px;
    border-bottom: 1px solid #e6ece8;
}

.fee-eyebrow {
    margin: 0 0 5px;
    color: #24734a;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.fee-header h2 {
    margin: 0;
    color: #253129;
    font-size: 15px;
}

.fee-header > div > p:last-child {
    margin: 5px 0 0;
    color: #7a877f;
    font-size: 10px;
}

.pay-fee-button {
    display: inline-flex;
    min-height: 39px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    color: #ffffff;
    background: #24734a;
    border: 1px solid #24734a;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.pay-fee-button:hover:not(:disabled) {
    background: #1e633f;
}

.pay-fee-button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.fee-content {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr))
        minmax(240px, 0.8fr);
}

.fee-stat {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px;
    border-right: 1px solid #e6ece8;
}

.fee-stat > span {
    color: #748178;
    font-size: 10px;
    font-weight: 600;
}

.fee-stat > strong {
    color: #24734a;
    font-size: 21px;
    font-weight: 700;
}

.fee-stat > small {
    color: #89958d;
    font-size: 9px;
}

.fee-policy {
    padding: 13px 20px;
    background: #f7faf8;
}

.fee-policy > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 43px;
    border-bottom: 1px solid #e2e9e4;
}

.fee-policy > div:last-child {
    border-bottom: 0;
}

.fee-policy span {
    color: #748178;
    font-size: 10px;
}

.fee-policy strong {
    color: #354239;
    font-size: 11px;
}

@media (max-width: 800px) {
    .fee-content {
        grid-template-columns: 1fr;
    }

    .fee-stat {
        border-right: 0;
        border-bottom: 1px solid #e6ece8;
    }
}

@media (max-width: 650px) {
    .fee-header {
        align-items: stretch;
        flex-direction: column;
    }

    .pay-fee-button {
        width: 100%;
    }
}
</style>
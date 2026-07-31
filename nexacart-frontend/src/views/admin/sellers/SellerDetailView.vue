<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getAdminSeller,
    updateAdminSellerStatus,
} from '@/api/admin/sellers'

const route = useRoute()
const router = useRouter()

const seller = ref(null)

const loading = ref(false)
const updatingStatus = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const sellerId = computed(() => {
    return route.params.id
})

const sellerInitial = computed(() => {
    return seller.value?.name
        ?.charAt(0)
        ?.toUpperCase() ?? 'S'
})

const isActive = computed(() => {
    return seller.value?.status === 'active'
})

const statusLabel = computed(() => {
    if (!seller.value) {
        return ''
    }

    return isActive.value
        ? 'Đang hoạt động'
        : 'Đã khóa'
})

const nextStatus = computed(() => {
    return isActive.value
        ? 'locked'
        : 'active'
})

const statusActionLabel = computed(() => {
    return isActive.value
        ? 'Khóa tài khoản'
        : 'Mở khóa tài khoản'
})

async function fetchSeller() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminSeller(
            sellerId.value,
        )

        seller.value =
            response.data?.data ?? null

        if (!seller.value) {
            errorMessage.value =
                'Không tìm thấy thông tin người bán.'
        }
    } catch (error) {
        if (error.response?.status === 404) {
            errorMessage.value =
                'Người bán không tồn tại.'
        } else if (error.response?.status === 422) {
            errorMessage.value =
                error.response?.data?.message ??
                'Người dùng này không phải người bán.'
        } else {
            errorMessage.value =
                error.response?.data?.message ??
                'Không thể tải thông tin người bán.'
        }
    } finally {
        loading.value = false
    }
}

async function toggleSellerStatus() {
    if (!seller.value) {
        return
    }

    const actionText = isActive.value
        ? 'khóa'
        : 'mở khóa'

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${actionText} tài khoản người bán "${seller.value.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    updatingStatus.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await updateAdminSellerStatus(
                seller.value.id,
                nextStatus.value,
            )

        seller.value =
            response.data?.data ??
            {
                ...seller.value,
                status: nextStatus.value,
            }

        successMessage.value =
            response.data?.message ??
            `Đã ${actionText} tài khoản người bán thành công.`
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể cập nhật trạng thái người bán.'
    } finally {
        updatingStatus.value = false
    }
}

function goBack() {
    router.push({
        name: 'admin-sellers',
    })
}

function formatDate(dateValue) {
    if (!dateValue) {
        return '—'
    }

    const date = new Date(dateValue)

    if (Number.isNaN(date.getTime())) {
        return dateValue
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        },
    ).format(date)
}

function getRequestStatusLabel(status) {
    const labels = {
        pending: 'Chờ duyệt',
        approved: 'Đã duyệt',
        rejected: 'Đã từ chối',
    }

    return labels[status] ?? status ?? '—'
}

onMounted(() => {
    fetchSeller()
})
</script>

<template>
    <section class="seller-detail-page">
        <header class="page-header">
            <div>
                <button
                    type="button"
                    class="back-button"
                    @click="goBack"
                >
                    ← Quay lại danh sách
                </button>

                <h1 class="page-title">
                    Chi tiết người bán
                </h1>

                <p class="page-description">
                    Xem thông tin tài khoản và hoạt động
                    của người bán.
                </p>
            </div>

            <button
                v-if="seller"
                type="button"
                class="button"
                :class="{
                    'button-danger': isActive,
                    'button-primary': !isActive,
                }"
                :disabled="updatingStatus"
                @click="toggleSellerStatus"
            >
                {{
                    updatingStatus
                        ? 'Đang xử lý...'
                        : statusActionLabel
                }}
            </button>
        </header>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="loading"
            class="state-card"
        >
            Đang tải thông tin người bán...
        </div>

        <template v-else-if="seller">
            <div class="seller-summary">
                <div class="seller-identity">
                    <div class="seller-avatar">
                        {{ sellerInitial }}
                    </div>

                    <div>
                        <div class="seller-name-row">
                            <h2 class="seller-name">
                                {{ seller.name }}
                            </h2>

                            <span
                                class="status-badge"
                                :class="{
                                    'status-active': isActive,
                                    'status-locked': !isActive,
                                }"
                            >
                                {{ statusLabel }}
                            </span>
                        </div>

                        <p class="seller-email">
                            {{ seller.email }}
                        </p>

                        <p class="seller-code">
                            Mã người bán:
                            #{{ seller.id }}
                        </p>
                    </div>
                </div>

                <div class="summary-date">
                    <span class="summary-date-label">
                        Ngày trở thành người bán
                    </span>

                    <strong>
                        {{
                            seller.seller_request
                                ?.approved_at_formatted ??
                            formatDate(
                                seller.seller_request
                                    ?.approved_at,
                            )
                        }}
                    </strong>
                </div>
            </div>

            <div class="statistics-grid">
                <article class="statistic-card">
                    <span class="statistic-label">
                        Sản phẩm
                    </span>

                    <strong class="statistic-value">
                        {{ seller.products_count ?? 0 }}
                    </strong>

                    <span class="statistic-description">
                        Tổng số sản phẩm đã đăng
                    </span>
                </article>

                <article class="statistic-card">
                    <span class="statistic-label">
                        Đơn hàng
                    </span>

                    <strong class="statistic-value">
                        {{
                            seller.seller_orders_count ??
                            0
                        }}
                    </strong>

                    <span class="statistic-description">
                        Tổng số đơn hàng đã nhận
                    </span>
                </article>

                <article class="statistic-card">
                    <span class="statistic-label">
                        Trạng thái
                    </span>

                    <strong
                        class="statistic-value statistic-status"
                        :class="{
                            'text-active': isActive,
                            'text-locked': !isActive,
                        }"
                    >
                        {{ statusLabel }}
                    </strong>

                    <span class="statistic-description">
                        Trạng thái tài khoản hiện tại
                    </span>
                </article>
            </div>

            <div class="detail-layout">
                <div class="detail-main">
                    <section class="detail-card">
                        <header class="card-header">
                            <div>
                                <h2 class="card-title">
                                    Thông tin tài khoản
                                </h2>

                                <p class="card-description">
                                    Thông tin cơ bản của người bán.
                                </p>
                            </div>
                        </header>

                        <div class="information-grid">
                            <div class="information-item">
                                <span class="information-label">
                                    Họ và tên
                                </span>

                                <strong class="information-value">
                                    {{ seller.name }}
                                </strong>
                            </div>

                            <div class="information-item">
                                <span class="information-label">
                                    Email
                                </span>

                                <strong class="information-value">
                                    {{ seller.email }}
                                </strong>
                            </div>

                            <div class="information-item">
                                <span class="information-label">
                                    Vai trò
                                </span>

                                <strong class="information-value">
                                    Người bán
                                </strong>
                            </div>

                            <div class="information-item">
                                <span class="information-label">
                                    Trạng thái
                                </span>

                                <span
                                    class="status-badge"
                                    :class="{
                                        'status-active':
                                            isActive,
                                        'status-locked':
                                            !isActive,
                                    }"
                                >
                                    {{ statusLabel }}
                                </span>
                            </div>

                            <div class="information-item">
                                <span class="information-label">
                                    Ngày tạo tài khoản
                                </span>

                                <strong class="information-value">
                                    {{
                                        seller
                                            .created_at_formatted ??
                                        formatDate(
                                            seller.created_at,
                                        )
                                    }}
                                </strong>
                            </div>

                            <div class="information-item">
                                <span class="information-label">
                                    Cập nhật gần nhất
                                </span>

                                <strong class="information-value">
                                    {{
                                        formatDate(
                                            seller.updated_at,
                                        )
                                    }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <section class="detail-card">
                        <header class="card-header">
                            <div>
                                <h2 class="card-title">
                                    Yêu cầu đăng ký người bán
                                </h2>

                                <p class="card-description">
                                    Thông tin yêu cầu đã được admin
                                    xét duyệt.
                                </p>
                            </div>
                        </header>

                        <div
                            v-if="seller.seller_request"
                            class="request-content"
                        >
                            <div class="information-grid">
                                <div class="information-item">
                                    <span class="information-label">
                                        Mã yêu cầu
                                    </span>

                                    <strong class="information-value">
                                        #{{
                                            seller
                                                .seller_request
                                                .id
                                        }}
                                    </strong>
                                </div>

                                <div class="information-item">
                                    <span class="information-label">
                                        Trạng thái
                                    </span>

                                    <span
                                        class="request-status"
                                        :class="[
                                            `request-${seller.seller_request.status}`,
                                        ]"
                                    >
                                        {{
                                            getRequestStatusLabel(
                                                seller
                                                    .seller_request
                                                    .status,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="information-item">
                                    <span class="information-label">
                                        Ngày xét duyệt
                                    </span>

                                    <strong class="information-value">
                                        {{
                                            seller
                                                .seller_request
                                                .approved_at_formatted ??
                                            formatDate(
                                                seller
                                                    .seller_request
                                                    .approved_at,
                                            )
                                        }}
                                    </strong>
                                </div>

                                <div class="information-item">
                                    <span class="information-label">
                                        Người xét duyệt
                                    </span>

                                    <strong class="information-value">
                                        {{
                                            seller
                                                .seller_request
                                                .reviewer
                                                ?.name ??
                                            '—'
                                        }}
                                    </strong>
                                </div>
                            </div>

                            <div
                                v-if="
                                    seller.seller_request
                                        .reason
                                "
                                class="request-section"
                            >
                                <h3 class="request-section-title">
                                    Lý do đăng ký
                                </h3>

                                <p class="request-text">
                                    {{
                                        seller
                                            .seller_request
                                            .reason
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-else
                            class="empty-state"
                        >
                            Không tìm thấy thông tin yêu cầu
                            đăng ký người bán.
                        </div>
                    </section>
                </div>

                <aside class="detail-sidebar">
                    <section class="detail-card">
                        <header class="card-header">
                            <div>
                                <h2 class="card-title">
                                    Quản lý tài khoản
                                </h2>

                                <p class="card-description">
                                    Kiểm soát khả năng hoạt động
                                    của người bán.
                                </p>
                            </div>
                        </header>

                        <div class="account-status">
                            <div
                                class="account-status-icon"
                                :class="{
                                    'account-active': isActive,
                                    'account-locked': !isActive,
                                }"
                            >
                                {{ isActive ? '✓' : '!' }}
                            </div>

                            <div>
                                <strong>
                                    {{ statusLabel }}
                                </strong>

                                <p>
                                    <template v-if="isActive">
                                        Người bán có thể đăng nhập
                                        và sử dụng hệ thống.
                                    </template>

                                    <template v-else>
                                        Người bán đang bị hạn chế
                                        truy cập hệ thống.
                                    </template>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="button status-button"
                            :class="{
                                'button-danger': isActive,
                                'button-primary': !isActive,
                            }"
                            :disabled="updatingStatus"
                            @click="toggleSellerStatus"
                        >
                            {{
                                updatingStatus
                                    ? 'Đang xử lý...'
                                    : statusActionLabel
                            }}
                        </button>

                        <p class="status-note">
                            Việc khóa tài khoản không làm thay đổi
                            vai trò seller và không xóa sản phẩm,
                            đơn hàng hoặc lịch sử hoạt động.
                        </p>
                    </section>
                </aside>
            </div>
        </template>
    </section>
</template>

<style scoped>
.seller-detail-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.back-button {
    border: 0;
    background: transparent;
    padding: 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.back-button:hover {
    text-decoration: underline;
}

.page-title {
    margin: 10px 0 0;
    color: #111827;
    font-family: Roboto, sans-serif;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.alert {
    border: 1px solid;
    padding: 12px 16px;
    font-size: 14px;
}

.alert-success {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.alert-error {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.state-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 60px 20px;
    color: #6b7280;
    text-align: center;
}

.button {
    min-height: 40px;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    font-weight: 500;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.button-primary {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.button-primary:hover:not(:disabled) {
    background: #15803d;
}

.button-danger {
    border-color: #dc2626;
    background: #dc2626;
    color: #ffffff;
}

.button-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.seller-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 22px;
}

.seller-identity {
    display: flex;
    align-items: center;
    gap: 16px;
}

.seller-avatar {
    display: flex;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-size: 24px;
    font-weight: 700;
}

.seller-name-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.seller-name {
    margin: 0;
    color: #111827;
    font-size: 21px;
    font-weight: 600;
}

.seller-email {
    margin: 7px 0 0;
    color: #4b5563;
    font-size: 14px;
}

.seller-code {
    margin: 4px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.summary-date {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    color: #111827;
    font-size: 14px;
}

.summary-date-label {
    color: #6b7280;
    font-size: 12px;
}

.status-badge,
.request-status {
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.status-active {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-locked {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.statistic-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 18px;
}

.statistic-label {
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
}

.statistic-value {
    margin-top: 10px;
    color: #111827;
    font-size: 26px;
    font-weight: 600;
}

.statistic-status {
    font-size: 18px;
}

.statistic-description {
    margin-top: 6px;
    color: #9ca3af;
    font-size: 12px;
}

.text-active {
    color: #15803d;
}

.text-locked {
    color: #dc2626;
}

.detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    align-items: start;
    gap: 20px;
}

.detail-main {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 20px;
}

.detail-sidebar {
    min-width: 0;
}

.detail-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.card-header {
    border-bottom: 1px solid #e5e7eb;
    padding: 18px 20px;
}

.card-title {
    margin: 0;
    color: #111827;
    font-size: 16px;
    font-weight: 600;
}

.card-description {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.information-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
    padding: 4px 20px 20px;
}

.information-item {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 7px;
    border-bottom: 1px solid #f3f4f6;
    padding: 16px 8px 16px 0;
}

.information-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 500;
}

.information-value {
    overflow-wrap: anywhere;
    color: #111827;
    font-size: 14px;
    font-weight: 500;
}

.request-content {
    padding-bottom: 20px;
}

.request-section {
    margin: 0 20px;
}

.request-section-title {
    margin: 0 0 8px;
    color: #111827;
    font-size: 13px;
    font-weight: 600;
}

.request-text {
    margin: 0;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 14px;
    color: #374151;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.request-pending {
    border-color: #fcd34d;
    background: #fffbeb;
    color: #92400e;
}

.request-approved {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.request-rejected {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.empty-state {
    padding: 40px 20px;
    color: #6b7280;
    text-align: center;
}

.account-status {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px;
}

.account-status-icon {
    display: flex;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
}

.account-active {
    background: #dcfce7;
    color: #15803d;
}

.account-locked {
    background: #fee2e2;
    color: #dc2626;
}

.account-status strong {
    color: #111827;
    font-size: 14px;
}

.account-status p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
}

.status-button {
    width: calc(100% - 40px);
    margin: 0 20px;
}

.status-note {
    margin: 14px 20px 20px;
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.5;
}

@media (max-width: 1000px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-header > .button {
        width: 100%;
    }

    .seller-summary {
        align-items: flex-start;
        flex-direction: column;
    }

    .summary-date {
        align-items: flex-start;
    }

    .statistics-grid {
        grid-template-columns: 1fr;
    }

    .information-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .seller-identity {
        align-items: flex-start;
    }

    .seller-name-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 7px;
    }
}
</style>
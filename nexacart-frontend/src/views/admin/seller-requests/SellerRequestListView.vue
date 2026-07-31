<script setup>
import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    approveAdminSellerRequest,
    getAdminSellerRequests,
    rejectAdminSellerRequest,
} from '@/api/admin/sellerRequests'

const sellerRequests = ref([])

const loading = ref(false)
const processingId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const selectedRequest = ref(null)

const showDetailModal = ref(false)
const showRejectModal = ref(false)

const rejectionReason = ref('')
const rejectionError = ref('')

const filters = ref({
    search: '',
    status: '',
    per_page: 10,
    page: 1,
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
})

let searchTimeout = null

const hasRequests = computed(() => {
    return sellerRequests.value.length > 0
})

const pageNumbers = computed(() => {
    const currentPage =
        pagination.value.current_page

    const lastPage =
        pagination.value.last_page

    const startPage = Math.max(
        1,
        currentPage - 2,
    )

    const endPage = Math.min(
        lastPage,
        currentPage + 2,
    )

    const pages = []

    for (
        let page = startPage;
        page <= endPage;
        page += 1
    ) {
        pages.push(page)
    }

    return pages
})

async function fetchSellerRequests() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getAdminSellerRequests({
                search:
                    filters.value.search ||
                    undefined,

                status:
                    filters.value.status ||
                    undefined,

                per_page:
                    filters.value.per_page,

                page:
                    filters.value.page,
            })

        const responseData = response.data

        sellerRequests.value =
            responseData.data ?? []

        pagination.value = {
            current_page:
                responseData.current_page ?? 1,

            last_page:
                responseData.last_page ?? 1,

            per_page:
                responseData.per_page ?? 10,

            total:
                responseData.total ?? 0,

            from:
                responseData.from ?? 0,

            to:
                responseData.to ?? 0,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách yêu cầu.'
    } finally {
        loading.value = false
    }
}

function resetFilters() {
    filters.value = {
        search: '',
        status: '',
        per_page: 10,
        page: 1,
    }

    fetchSellerRequests()
}

function changePage(page) {
    if (
        page < 1 ||
        page > pagination.value.last_page ||
        page === pagination.value.current_page
    ) {
        return
    }

    filters.value.page = page

    fetchSellerRequests()
}

function openDetailModal(sellerRequest) {
    selectedRequest.value =
        sellerRequest

    showDetailModal.value = true
}

function closeDetailModal() {
    selectedRequest.value = null
    showDetailModal.value = false
}

async function approveRequest(
    sellerRequest,
) {
    const confirmed = window.confirm(
        `Bạn có chắc muốn duyệt yêu cầu của "${sellerRequest.user?.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    processingId.value =
        sellerRequest.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await approveAdminSellerRequest(
                sellerRequest.id,
            )

        successMessage.value =
            response.data?.message ??
            'Duyệt yêu cầu thành công.'

        closeDetailModal()

        await fetchSellerRequests()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể duyệt yêu cầu.'
    } finally {
        processingId.value = null
    }
}

function openRejectModal(
    sellerRequest,
) {
    selectedRequest.value =
        sellerRequest

    rejectionReason.value = ''
    rejectionError.value = ''

    showRejectModal.value = true
}

function closeRejectModal() {
    selectedRequest.value = null

    rejectionReason.value = ''
    rejectionError.value = ''

    showRejectModal.value = false
}

async function submitReject() {
    rejectionError.value = ''

    if (
        rejectionReason.value.trim()
            .length < 5
    ) {
        rejectionError.value =
            'Lý do từ chối phải có ít nhất 5 ký tự.'

        return
    }

    processingId.value =
        selectedRequest.value.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await rejectAdminSellerRequest(
                selectedRequest.value.id,
                rejectionReason.value.trim(),
            )

        successMessage.value =
            response.data?.message ??
            'Đã từ chối yêu cầu.'

        closeRejectModal()

        await fetchSellerRequests()
    } catch (error) {
        const validationMessage =
            error.response?.data?.errors
                ?.rejection_reason?.[0]

        rejectionError.value =
            validationMessage ??
            error.response?.data?.message ??
            'Không thể từ chối yêu cầu.'
    } finally {
        processingId.value = null
    }
}

function getStatusLabel(status) {
    const labels = {
        pending: 'Chờ duyệt',
        approved: 'Đã duyệt',
        rejected: 'Đã từ chối',
    }

    return labels[status] ?? status
}

function formatDate(dateValue) {
    if (!dateValue) {
        return '—'
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
    ).format(
        new Date(dateValue),
    )
}

watch(
    () => filters.value.search,
    () => {
        clearTimeout(searchTimeout)

        searchTimeout = setTimeout(
            () => {
                filters.value.page = 1

                fetchSellerRequests()
            },
            500,
        )
    },
)

watch(
    () => filters.value.status,
    () => {
        filters.value.page = 1

        fetchSellerRequests()
    },
)

watch(
    () => filters.value.per_page,
    () => {
        filters.value.page = 1

        fetchSellerRequests()
    },
)

onMounted(() => {
    fetchSellerRequests()
})
</script>

<template>
    <section class="seller-request-page">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    Yêu cầu đăng ký người bán
                </h1>

                <p class="page-description">
                    Xem xét và xử lý các yêu cầu
                    nâng cấp tài khoản customer
                    thành seller.
                </p>
            </div>
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

        <div class="filter-panel">
            <div class="filter-field search-field">
                <label for="seller-request-search">
                    Tìm kiếm
                </label>

                <input
                    id="seller-request-search"
                    v-model.trim="filters.search"
                    type="search"
                    placeholder="Tên hoặc email người dùng"
                >
            </div>

            <div class="filter-field">
                <label for="seller-request-status">
                    Trạng thái
                </label>

                <select
                    id="seller-request-status"
                    v-model="filters.status"
                >
                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option value="pending">
                        Chờ duyệt
                    </option>

                    <option value="approved">
                        Đã duyệt
                    </option>

                    <option value="rejected">
                        Đã từ chối
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="seller-request-per-page">
                    Số dòng
                </label>

                <select
                    id="seller-request-per-page"
                    v-model.number="filters.per_page"
                >
                    <option :value="10">
                        10
                    </option>

                    <option :value="20">
                        20
                    </option>

                    <option :value="50">
                        50
                    </option>
                </select>
            </div>

            <div class="filter-actions">
                <button
                    type="button"
                    class="button button-secondary"
                    @click="resetFilters"
                >
                    Đặt lại
                </button>
            </div>
        </div>

        <div class="table-card">
            <div
                v-if="loading"
                class="table-state"
            >
                Đang tải danh sách yêu cầu...
            </div>

            <div
                v-else-if="!hasRequests"
                class="table-state"
            >
                Không tìm thấy yêu cầu phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>Người đăng ký</th>
                            <th>Lý do đăng ký</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th>Người xử lý</th>

                            <th class="action-column">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="sellerRequest in sellerRequests"
                            :key="sellerRequest.id"
                        >
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        {{
                                            sellerRequest.user
                                                ?.name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div>
                                        <div class="user-name">
                                            {{
                                                sellerRequest.user
                                                    ?.name ??
                                                'Không xác định'
                                            }}
                                        </div>

                                        <div class="user-email">
                                            {{
                                                sellerRequest.user
                                                    ?.email ??
                                                '—'
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <p class="reason-preview">
                                    {{
                                        sellerRequest.reason ||
                                        'Không cung cấp lý do'
                                    }}
                                </p>
                            </td>

                            <td>
                                {{
                                    formatDate(
                                        sellerRequest.created_at,
                                    )
                                }}
                            </td>

                            <td>
                                <span
                                    class="status-badge"
                                    :class="[
                                        `status-${sellerRequest.status}`,
                                    ]"
                                >
                                    {{
                                        getStatusLabel(
                                            sellerRequest.status,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                <template
                                    v-if="
                                        sellerRequest.reviewer
                                    "
                                >
                                    <div class="reviewer-name">
                                        {{
                                            sellerRequest.reviewer
                                                .name
                                        }}
                                    </div>

                                    <div class="reviewed-date">
                                        {{
                                            formatDate(
                                                sellerRequest.reviewed_at,
                                            )
                                        }}
                                    </div>
                                </template>

                                <span v-else>
                                    —
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="button-link"
                                        @click="
                                            openDetailModal(
                                                sellerRequest,
                                            )
                                        "
                                    >
                                        Chi tiết
                                    </button>

                                    <template
                                        v-if="
                                            sellerRequest.status ===
                                            'pending'
                                        "
                                    >
                                        <button
                                            type="button"
                                            class="button-link button-approve"
                                            :disabled="
                                                processingId ===
                                                sellerRequest.id
                                            "
                                            @click="
                                                approveRequest(
                                                    sellerRequest,
                                                )
                                            "
                                        >
                                            Duyệt
                                        </button>

                                        <button
                                            type="button"
                                            class="button-link button-reject"
                                            :disabled="
                                                processingId ===
                                                sellerRequest.id
                                            "
                                            @click="
                                                openRejectModal(
                                                    sellerRequest,
                                                )
                                            "
                                        >
                                            Từ chối
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer
                v-if="
                    !loading &&
                    pagination.total > 0
                "
                class="pagination"
            >
                <div class="pagination-info">
                    Hiển thị
                    {{ pagination.from }}
                    –
                    {{ pagination.to }}
                    trong
                    {{ pagination.total }}
                    yêu cầu
                </div>

                <div class="pagination-buttons">
                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination.current_page === 1
                        "
                        @click="
                            changePage(
                                pagination.current_page - 1,
                            )
                        "
                    >
                        Trước
                    </button>

                    <button
                        v-for="page in pageNumbers"
                        :key="page"
                        type="button"
                        class="page-button"
                        :class="{
                            active:
                                page ===
                                pagination.current_page,
                        }"
                        @click="changePage(page)"
                    >
                        {{ page }}
                    </button>

                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination.current_page ===
                            pagination.last_page
                        "
                        @click="
                            changePage(
                                pagination.current_page + 1,
                            )
                        "
                    >
                        Sau
                    </button>
                </div>
            </footer>
        </div>

        <div
            v-if="showDetailModal && selectedRequest"
            class="modal-overlay"
            @click.self="closeDetailModal"
        >
            <div class="modal">
                <header class="modal-header">
                    <div>
                        <h2 class="modal-title">
                            Chi tiết yêu cầu
                        </h2>

                        <p class="modal-subtitle">
                            Mã yêu cầu:
                            #{{ selectedRequest.id }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="modal-close"
                        @click="closeDetailModal"
                    >
                        ×
                    </button>
                </header>

                <div class="modal-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">
                                Người đăng ký
                            </span>

                            <span class="detail-value">
                                {{
                                    selectedRequest.user
                                        ?.name ??
                                    'Không xác định'
                                }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">
                                Email
                            </span>

                            <span class="detail-value">
                                {{
                                    selectedRequest.user
                                        ?.email ??
                                    '—'
                                }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">
                                Vai trò hiện tại
                            </span>

                            <span class="detail-value">
                                {{
                                    selectedRequest.user
                                        ?.role ??
                                    'customer'
                                }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">
                                Trạng thái tài khoản
                            </span>

                            <span class="detail-value">
                                {{
                                    selectedRequest.user
                                        ?.status ??
                                    '—'
                                }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">
                                Ngày gửi
                            </span>

                            <span class="detail-value">
                                {{
                                    formatDate(
                                        selectedRequest.created_at,
                                    )
                                }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">
                                Trạng thái yêu cầu
                            </span>

                            <span
                                class="status-badge"
                                :class="[
                                    `status-${selectedRequest.status}`,
                                ]"
                            >
                                {{
                                    getStatusLabel(
                                        selectedRequest.status,
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3 class="detail-section-title">
                            Lý do đăng ký
                        </h3>

                        <p class="detail-reason">
                            {{
                                selectedRequest.reason ||
                                'Người dùng không cung cấp lý do.'
                            }}
                        </p>
                    </div>

                    <div
                        v-if="
                            selectedRequest.status ===
                            'rejected'
                        "
                        class="detail-section"
                    >
                        <h3 class="detail-section-title">
                            Lý do từ chối
                        </h3>

                        <p class="detail-reason rejection-text">
                            {{
                                selectedRequest.rejection_reason ||
                                'Không có lý do.'
                            }}
                        </p>
                    </div>
                </div>

                <footer class="modal-footer">
                    <button
                        type="button"
                        class="button button-secondary"
                        @click="closeDetailModal"
                    >
                        Đóng
                    </button>

                    <template
                        v-if="
                            selectedRequest.status ===
                            'pending'
                        "
                    >
                        <button
                            type="button"
                            class="button button-danger"
                            @click="
                                closeDetailModal();
                                openRejectModal(
                                    selectedRequest,
                                )
                            "
                        >
                            Từ chối
                        </button>

                        <button
                            type="button"
                            class="button button-primary"
                            :disabled="
                                processingId ===
                                selectedRequest.id
                            "
                            @click="
                                approveRequest(
                                    selectedRequest,
                                )
                            "
                        >
                            Duyệt yêu cầu
                        </button>
                    </template>
                </footer>
            </div>
        </div>

        <div
            v-if="showRejectModal && selectedRequest"
            class="modal-overlay"
            @click.self="closeRejectModal"
        >
            <div class="modal modal-small">
                <header class="modal-header">
                    <div>
                        <h2 class="modal-title">
                            Từ chối yêu cầu
                        </h2>

                        <p class="modal-subtitle">
                            {{
                                selectedRequest.user
                                    ?.name
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="modal-close"
                        @click="closeRejectModal"
                    >
                        ×
                    </button>
                </header>

                <div class="modal-body">
                    <div class="form-field">
                        <label for="rejection-reason">
                            Lý do từ chối
                            <span class="required">
                                *
                            </span>
                        </label>

                        <textarea
                            id="rejection-reason"
                            v-model.trim="rejectionReason"
                            rows="5"
                            maxlength="1000"
                            placeholder="Nhập lý do từ chối yêu cầu..."
                        />

                        <div class="textarea-footer">
                            <span
                                v-if="rejectionError"
                                class="field-error"
                            >
                                {{ rejectionError }}
                            </span>

                            <span class="character-count">
                                {{
                                    rejectionReason.length
                                }}/1000
                            </span>
                        </div>
                    </div>
                </div>

                <footer class="modal-footer">
                    <button
                        type="button"
                        class="button button-secondary"
                        @click="closeRejectModal"
                    >
                        Hủy
                    </button>

                    <button
                        type="button"
                        class="button button-danger"
                        :disabled="
                            processingId ===
                            selectedRequest.id
                        "
                        @click="submitReject"
                    >
                        <template
                            v-if="
                                processingId ===
                                selectedRequest.id
                            "
                        >
                            Đang xử lý...
                        </template>

                        <template v-else>
                            Xác nhận từ chối
                        </template>
                    </button>
                </footer>
            </div>
        </div>
    </section>
</template>

<style scoped>
.seller-request-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-title {
    margin: 0;
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

.filter-panel {
    display: grid;
    grid-template-columns:
        minmax(260px, 1fr)
        200px
        120px
        auto;
    align-items: end;
    gap: 16px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 16px;
}

.filter-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.filter-field label {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.filter-field input,
.filter-field select,
.form-field textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #111827;
    font: inherit;
    outline: none;
}

.filter-field input,
.filter-field select {
    height: 40px;
    padding: 0 12px;
}

.form-field textarea {
    resize: vertical;
    padding: 12px;
    line-height: 1.5;
}

.filter-field input:focus,
.filter-field select:focus,
.form-field textarea:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.filter-actions {
    display: flex;
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

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover {
    background: #f9fafb;
}

.button-danger {
    border-color: #dc2626;
    background: #dc2626;
    color: #ffffff;
}

.button-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.table-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.table-wrapper {
    overflow-x: auto;
}

.request-table {
    width: 100%;
    border-collapse: collapse;
}

.request-table th,
.request-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
}

.request-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.request-table td {
    color: #4b5563;
    font-size: 14px;
}

.request-table tbody tr:hover {
    background: #f9fafb;
}

.user-info {
    display: flex;
    min-width: 220px;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    display: flex;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-weight: 700;
}

.user-name {
    color: #111827;
    font-weight: 500;
}

.user-email {
    margin-top: 3px;
    color: #9ca3af;
    font-size: 12px;
}

.reason-preview {
    display: -webkit-box;
    max-width: 260px;
    margin: 0;
    overflow: hidden;
    line-height: 1.5;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.status-pending {
    border-color: #fcd34d;
    background: #fffbeb;
    color: #92400e;
}

.status-approved {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-rejected {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.reviewer-name {
    color: #374151;
    font-weight: 500;
}

.reviewed-date {
    margin-top: 3px;
    color: #9ca3af;
    font-size: 12px;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    white-space: nowrap;
}

.button-link {
    border: 0;
    background: transparent;
    padding: 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button-link:hover:not(:disabled) {
    text-decoration: underline;
}

.button-link:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.button-approve {
    color: #15803d;
}

.button-reject {
    color: #dc2626;
}

.action-column {
    min-width: 180px;
}

.table-state {
    padding: 48px 20px;
    color: #6b7280;
    text-align: center;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
}

.pagination-info {
    color: #6b7280;
    font-size: 13px;
}

.pagination-buttons {
    display: flex;
    gap: 6px;
}

.page-button {
    min-width: 36px;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #374151;
    cursor: pointer;
}

.page-button:hover:not(:disabled) {
    border-color: #16a34a;
    color: #15803d;
}

.page-button.active {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.page-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.modal-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(17 24 39 / 55%);
    padding: 20px;
}

.modal {
    width: min(680px, 100%);
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid #d1d5db;
    background: #ffffff;
}

.modal-small {
    width: min(520px, 100%);
}

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    border-bottom: 1px solid #e5e7eb;
    padding: 18px 20px;
}

.modal-title {
    margin: 0;
    color: #111827;
    font-size: 20px;
    font-weight: 600;
}

.modal-subtitle {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.modal-close {
    border: 0;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    font-size: 28px;
    line-height: 1;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e5e7eb;
    padding: 16px 20px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 500;
}

.detail-value {
    color: #111827;
    font-size: 14px;
}

.detail-section {
    margin-top: 24px;
}

.detail-section-title {
    margin: 0 0 10px;
    color: #111827;
    font-size: 14px;
    font-weight: 600;
}

.detail-reason {
    margin: 0;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 14px;
    color: #374151;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.rejection-text {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-field label {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.required {
    color: #dc2626;
}

.textarea-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.field-error {
    color: #dc2626;
    font-size: 12px;
}

.character-count {
    margin-left: auto;
    color: #9ca3af;
    font-size: 12px;
}

@media (max-width: 900px) {
    .filter-panel {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .filter-panel {
        grid-template-columns: 1fr;
    }

    .pagination {
        align-items: flex-start;
        flex-direction: column;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .modal-footer {
        align-items: stretch;
        flex-direction: column-reverse;
    }

    .modal-footer .button {
        width: 100%;
    }
}
</style>
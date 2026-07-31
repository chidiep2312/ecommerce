<script setup>
import {
    onMounted,
    reactive,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import {
    deleteAdminVoucher,
    getAdminVouchers,
    updateAdminVoucher,
} from '@/api/admin/vouchers'

import VoucherStatusBadge from '@/components/admin/vouchers/VoucherStatusBadge.vue'

const router = useRouter()

const vouchers = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
})

const loading = ref(false)
const actionVoucherId = ref(null)
const errorMessage = ref('')

const filters = reactive({
    search: '',
    type: '',
    status: '',
    effective_status: '',
    per_page: 15,
    page: 1,
})

function unwrapResponse(response) {
    return response?.data ?? {}
}

function extractCollection(response) {
    const body = unwrapResponse(response)

    /*
     * Laravel Resource Collection mặc định:
     *
     * {
     *   data: [...],
     *   links: {},
     *   meta: {}
     * }
     */
    if (Array.isArray(body.data)) {
        return {
            rows: body.data,
            meta: body.meta ?? {},
        }
    }

    /*
     * Trường hợp response có thêm success:
     *
     * {
     *   success: true,
     *   data: {
     *      data: [...],
     *      meta: {}
     *   }
     * }
     */
    if (Array.isArray(body.data?.data)) {
        return {
            rows: body.data.data,
            meta: body.data.meta ?? {},
        }
    }

    return {
        rows: [],
        meta: {},
    }
}

async function fetchVouchers() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminVouchers({
            search:
                filters.search.trim() || undefined,

            type:
                filters.type || undefined,

            status:
                filters.status || undefined,

            effective_status:
                filters.effective_status ||
                undefined,

            per_page: filters.per_page,
            page: filters.page,
        })

        const result =
            extractCollection(response)

        vouchers.value = result.rows

        pagination.value = {
            current_page:
                result.meta.current_page ?? 1,

            last_page:
                result.meta.last_page ?? 1,

            from:
                result.meta.from ?? 0,

            to:
                result.meta.to ?? 0,

            total:
                result.meta.total ??
                result.rows.length,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách voucher.'
    } finally {
        loading.value = false
    }
}

function applyFilters() {
    filters.page = 1
    fetchVouchers()
}

function resetFilters() {
    filters.search = ''
    filters.type = ''
    filters.status = ''
    filters.effective_status = ''
    filters.per_page = 15
    filters.page = 1

    fetchVouchers()
}

function changePage(page) {
    if (
        page < 1 ||
        page > pagination.value.last_page ||
        page === pagination.value.current_page
    ) {
        return
    }

    filters.page = page
    fetchVouchers()
}

function viewVoucher(voucherId) {
    router.push({
        name: 'admin.vouchers.show',
        params: {
            id: voucherId,
        },
    })
}

function editVoucher(voucherId) {
    router.push({
        name: 'admin.vouchers.edit',
        params: {
            id: voucherId,
        },
    })
}

async function toggleStatus(voucher) {
    const nextStatus =
        voucher.status === 'active'
            ? 'inactive'
            : 'active'

    const confirmed = window.confirm(
        nextStatus === 'inactive'
            ? `Bạn có chắc muốn tắt voucher ${voucher.code}?`
            : `Bạn có chắc muốn bật voucher ${voucher.code}?`,
    )

    if (!confirmed) {
        return
    }

    actionVoucherId.value = voucher.id

    try {
        const response =
            await updateAdminVoucher(
                voucher.id,
                nextStatus,
            )

        const updatedVoucher =
            response.data?.data

        if (updatedVoucher) {
            Object.assign(
                voucher,
                updatedVoucher,
            )
        } else {
            voucher.status = nextStatus
        }
    } catch (error) {
        window.alert(
            error.response?.data?.message ??
            'Không thể cập nhật trạng thái voucher.',
        )
    } finally {
        actionVoucherId.value = null
    }
}

async function removeVoucher(voucher) {
    const confirmed = window.confirm(
        `Bạn có chắc muốn xóa voucher ${voucher.code}?`,
    )

    if (!confirmed) {
        return
    }

    actionVoucherId.value = voucher.id

    try {
        await deleteAdminVoucher(voucher.id)

        await fetchVouchers()
    } catch (error) {
        window.alert(
            error.response?.data?.message ??
            'Không thể xóa voucher.',
        )
    } finally {
        actionVoucherId.value = null
    }
}

function formatCurrency(value) {
    if (
        value === null ||
        value === undefined
    ) {
        return '—'
    }

    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(Number(value))
}

function formatVoucherValue(voucher) {
    if (voucher.type === 'percent') {
        return `${Number(voucher.value)}%`
    }

    return formatCurrency(voucher.value)
}

function formatDateTime(value) {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            dateStyle: 'short',
            timeStyle: 'short',
        },
    ).format(new Date(value))
}

function getEffectiveStatus(voucher) {
    return (
        voucher.effective_status ??
        voucher.status
    )
}

function getUsageText(voucher) {
    const used =
        voucher.usages_count ??
        voucher.used_count ??
        0

    if (voucher.usage_limit === null) {
        return `${used} / Không giới hạn`
    }

    return `${used} / ${voucher.usage_limit}`
}

onMounted(fetchVouchers)
</script>

<template>
    <section class="voucher-page">
        <header class="page-header">
            <div>
                <h1>Quản lý voucher</h1>

                <p>
                    Tạo và quản lý các chương trình
                    giảm giá của hệ thống.
                </p>
            </div>

            <button
                type="button"
                class="button button--primary"
                @click="
                    router.push({
                        name:
                            'admin.vouchers.create',
                    })
                "
            >
                Thêm voucher
            </button>
        </header>

        <div class="filter-panel">
            <form
                class="filters"
                @submit.prevent="applyFilters"
            >
                <div class="filter-item filter-search">
                    <label for="voucher-search">
                        Tìm kiếm
                    </label>

                    <input
                        id="voucher-search"
                        v-model="filters.search"
                        type="search"
                        placeholder="Nhập mã voucher"
                    >
                </div>

                <div class="filter-item">
                    <label for="type-filter">
                        Loại
                    </label>

                    <select
                        id="type-filter"
                        v-model="filters.type"
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option value="percent">
                            Phần trăm
                        </option>

                        <option value="fixed">
                            Số tiền cố định
                        </option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="status-filter">
                        Trạng thái cài đặt
                    </label>

                    <select
                        id="status-filter"
                        v-model="filters.status"
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option value="active">
                            Đang bật
                        </option>

                        <option value="inactive">
                            Đã tắt
                        </option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="effective-filter">
                        Trạng thái thực tế
                    </label>

                    <select
                        id="effective-filter"
                        v-model="
                            filters.effective_status
                        "
                    >
                        <option value="">
                            Tất cả
                        </option>

                        <option value="active">
                            Đang hoạt động
                        </option>

                        <option value="upcoming">
                            Sắp diễn ra
                        </option>

                        <option value="expired">
                            Đã hết hạn
                        </option>

                        <option value="exhausted">
                            Đã hết lượt
                        </option>

                        <option value="inactive">
                            Đã tắt
                        </option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button
                        type="submit"
                        class="button button--primary"
                    >
                        Lọc
                    </button>

                    <button
                        type="button"
                        class="button button--secondary"
                        @click="resetFilters"
                    >
                        Đặt lại
                    </button>
                </div>
            </form>
        </div>

        <div
            v-if="errorMessage"
            class="alert alert--error"
        >
            {{ errorMessage }}
        </div>

        <div class="table-panel">
            <div
                v-if="loading"
                class="state-message"
            >
                Đang tải danh sách voucher...
            </div>

            <div
                v-else-if="vouchers.length === 0"
                class="state-message"
            >
                Không có voucher phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table>
                    <thead>
                        <tr>
                            <th>Mã voucher</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Đơn tối thiểu</th>
                            <th>Lượt sử dụng</th>
                            <th>Thời gian áp dụng</th>
                            <th>Trạng thái</th>
                            <th class="text-right">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="voucher in vouchers"
                            :key="voucher.id"
                        >
                            <td>
                                <strong class="voucher-code">
                                    {{ voucher.code }}
                                </strong>
                            </td>

                            <td>
                                {{
                                    voucher.type ===
                                    'percent'
                                        ? 'Phần trăm'
                                        : 'Số tiền cố định'
                                }}
                            </td>

                            <td>
                                <strong>
                                    {{
                                        formatVoucherValue(
                                            voucher,
                                        )
                                    }}
                                </strong>

                                <div
                                    v-if="
                                        voucher.type ===
                                            'percent' &&
                                        voucher.max_discount_amount
                                    "
                                    class="sub-text"
                                >
                                    Tối đa
                                    {{
                                        formatCurrency(
                                            voucher.max_discount_amount,
                                        )
                                    }}
                                </div>
                            </td>

                            <td>
                                {{
                                    formatCurrency(
                                        voucher.min_order_amount,
                                    )
                                }}
                            </td>

                            <td>
                                {{
                                    getUsageText(
                                        voucher,
                                    )
                                }}

                                <div class="sub-text">
                                    Mỗi khách:
                                    {{
                                        voucher.usage_limit_per_user ??
                                        'Không giới hạn'
                                    }}
                                </div>
                            </td>

                            <td>
                                <div>
                                    {{
                                        formatDateTime(
                                            voucher.starts_at,
                                        )
                                    }}
                                </div>

                                <div class="sub-text">
                                    đến
                                    {{
                                        formatDateTime(
                                            voucher.expires_at,
                                        )
                                    }}
                                </div>
                            </td>

                            <td>
                                <VoucherStatusBadge
                                    :status="
                                        getEffectiveStatus(
                                            voucher,
                                        )
                                    "
                                />
                            </td>

                            <td>
                                <div class="row-actions">
                                    <button
                                        type="button"
                                        class="action-link"
                                        @click="
                                            viewVoucher(
                                                voucher.id,
                                            )
                                        "
                                    >
                                        Xem
                                    </button>

                                    <button
                                        type="button"
                                        class="action-link"
                                        @click="
                                            editVoucher(
                                                voucher.id,
                                            )
                                        "
                                    >
                                        Sửa
                                    </button>

                                    <button
                                        type="button"
                                        class="action-link"
                                        :disabled="
                                            actionVoucherId ===
                                            voucher.id
                                        "
                                        @click="
                                            toggleStatus(
                                                voucher,
                                            )
                                        "
                                    >
                                        {{
                                            voucher.status ===
                                            'active'
                                                ? 'Tắt'
                                                : 'Bật'
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        class="action-link action-link--danger"
                                        :disabled="
                                            actionVoucherId ===
                                            voucher.id
                                        "
                                        @click="
                                            removeVoucher(
                                                voucher,
                                            )
                                        "
                                    >
                                        Xóa
                                    </button>
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
                <p>
                    Hiển thị
                    {{ pagination.from }}–{{
                        pagination.to
                    }}
                    trong
                    {{ pagination.total }}
                    voucher
                </p>

                <div class="pagination-actions">
                    <button
                        type="button"
                        :disabled="
                            pagination.current_page <= 1
                        "
                        @click="
                            changePage(
                                pagination.current_page -
                                1,
                            )
                        "
                    >
                        Trước
                    </button>

                    <span>
                        Trang
                        {{ pagination.current_page }}
                        /
                        {{ pagination.last_page }}
                    </span>

                    <button
                        type="button"
                        :disabled="
                            pagination.current_page >=
                            pagination.last_page
                        "
                        @click="
                            changePage(
                                pagination.current_page +
                                1,
                            )
                        "
                    >
                        Sau
                    </button>
                </div>
            </footer>
        </div>
    </section>
</template>

<style scoped>
.voucher-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    font-family: Roboto, sans-serif;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.page-header h1 {
    margin: 0;
    color: #0f172a;
    font-size: 24px;
}

.page-header p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 14px;
}

.filter-panel,
.table-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.filters {
    display: grid;
    grid-template-columns:
        minmax(220px, 1.5fr)
        repeat(3, minmax(150px, 1fr))
        auto;
    gap: 14px;
    align-items: end;
    padding: 18px;
}

.filter-item label {
    display: block;
    margin-bottom: 6px;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
}

.filter-item input,
.filter-item select {
    width: 100%;
    min-height: 39px;
    padding: 8px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 0;
    background: #ffffff;
    outline: none;
}

.filter-item input:focus,
.filter-item select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.filter-actions {
    display: flex;
    gap: 8px;
}

.button {
    min-height: 39px;
    padding: 8px 15px;
    border: 1px solid transparent;
    border-radius: 0;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.button--primary {
    color: #ffffff;
    background: #16a34a;
    border-color: #16a34a;
}

.button--primary:hover {
    background: #15803d;
}

.button--secondary {
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
}

.alert {
    padding: 12px 14px;
    border: 1px solid;
    font-size: 14px;
}

.alert--error {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 13px 15px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: middle;
    font-size: 13px;
}

th {
    color: #475569;
    background: #f8fafc;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

td {
    color: #334155;
}

.voucher-code {
    color: #15803d;
    letter-spacing: 0.03em;
}

.sub-text {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.text-right {
    text-align: right;
}

.row-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    white-space: nowrap;
}

.action-link {
    padding: 0;
    border: 0;
    background: transparent;
    color: #15803d;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.action-link--danger {
    color: #dc2626;
}

.action-link:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.state-message {
    padding: 50px 20px;
    color: #64748b;
    text-align: center;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
}

.pagination p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.pagination-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pagination-actions button {
    min-height: 34px;
    padding: 6px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 0;
    background: #ffffff;
    cursor: pointer;
}

.pagination-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

@media (max-width: 1100px) {
    .filters {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .page-header {
        flex-direction: column;
    }

    .filters {
        grid-template-columns: 1fr;
    }

    .pagination {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}
</style>
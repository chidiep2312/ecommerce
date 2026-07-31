<script setup>
import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue'
import { useRouter } from 'vue-router'

import {
    getAdminSellers,
    updateAdminSellerStatus,
} from '@/api/admin/sellers'

const router = useRouter()

const sellers = ref([])

const loading = ref(false)
const updatingSellerId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

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

const hasSellers = computed(() => {
    return sellers.value.length > 0
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
async function fetchSellers() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminSellers({
            search:
                filters.value.search || undefined,

            status:
                filters.value.status || undefined,

            per_page:
                filters.value.per_page,

            page:
                filters.value.page,
        })

        const responseData = response.data

        sellers.value =
            responseData.data ?? []

        pagination.value = {
            current_page:
                responseData.meta
                    ?.current_page ?? 1,

            last_page:
                responseData.meta
                    ?.last_page ?? 1,

            per_page:
                responseData.meta
                    ?.per_page ?? 10,

            total:
                responseData.meta
                    ?.total ?? 0,

            from:
                responseData.meta
                    ?.from ?? 0,

            to:
                responseData.meta
                    ?.to ?? 0,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách người bán.'
    } finally {
        loading.value = false
    }
}
function resetFilters() {
    filters.value.search = ''
    filters.value.status = ''
    filters.value.per_page = 10
    filters.value.page = 1

    fetchSellers()
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

    fetchSellers()
}

function viewSeller(seller) {
    router.push({
        name: 'admin-sellers-show',
        params: {
            id: seller.id,
        },
    })
}

async function toggleSellerStatus(seller) {
    const nextStatus =
        seller.status === 'active'
            ? 'locked'
            : 'active'

    const actionText =
        nextStatus === 'locked'
            ? 'khóa'
            : 'mở khóa'

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${actionText} tài khoản người bán "${seller.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    updatingSellerId.value = seller.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await updateAdminSellerStatus(
                seller.id,
                nextStatus,
            )

        /*
         * Cập nhật trực tiếp seller trong bảng,
         * không cần tải lại toàn bộ danh sách.
         */
        seller.status =
            response.data?.data?.status ??
            nextStatus

        successMessage.value =
            response.data?.message ??
            `Đã ${actionText} tài khoản người bán.`
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể cập nhật trạng thái người bán.'
    } finally {
        updatingSellerId.value = null
    }
}

/*
 * Khi thay đổi số dòng mỗi trang,
 * quay về trang đầu rồi tải lại.
 */
watch(
    () => filters.value.per_page,
    () => {
        filters.value.page = 1
        fetchSellers()
    },
)

/*
 * Debounce tìm kiếm.
 * Không gọi API sau mỗi lần gõ phím ngay lập tức.
 */
watch(
    () => filters.value.search,
    () => {
        clearTimeout(searchTimeout)

        searchTimeout = setTimeout(() => {
            filters.value.page = 1
            fetchSellers()
        }, 500)
    },
)

/*
 * Khi thay đổi trạng thái lọc.
 */
watch(
    () => filters.value.status,
    () => {
        filters.value.page = 1
        fetchSellers()
    },
)

onMounted(() => {
    fetchSellers()
})
</script>

<template>
    <section class="seller-page">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    Quản lý người bán
                </h1>

                <p class="page-description">
                    Quản lý các tài khoản đã được duyệt
                    trở thành người bán.
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
                <label for="seller-search">
                    Tìm kiếm
                </label>

                <input
                    id="seller-search"
                    v-model.trim="filters.search"
                    type="search"
                    placeholder="Tên hoặc email người bán"
                >
            </div>

            <div class="filter-field">
                <label for="seller-status">
                    Trạng thái
                </label>

                <select
                    id="seller-status"
                    v-model="filters.status"
                >
                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option value="active">
                        Đang hoạt động
                    </option>

                    <option value="locked">
                        Đã khóa
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="seller-per-page">
                    Số dòng
                </label>

                <select
                    id="seller-per-page"
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
                Đang tải danh sách người bán...
            </div>

            <div
                v-else-if="!hasSellers"
                class="table-state"
            >
                Không tìm thấy người bán phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="seller-table">
                    <thead>
                        <tr>
                            <th>Người bán</th>
                            <th>Email</th>
                            <th>Sản phẩm</th>
                            <th>Đơn hàng</th>
                            <th>Ngày trở thành seller</th>
                            <th>Trạng thái</th>
                            <th class="action-column">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="seller in sellers"
                            :key="seller.id"
                        >
                            <td>
                                <div class="seller-info">
                                    <div class="seller-avatar">
                                        {{
                                            seller.name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div>
                                        <div class="seller-name">
                                            {{ seller.name }}
                                        </div>

                                        <div class="seller-id">
                                            ID: {{ seller.id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{ seller.email }}
                            </td>

                            <td>
                                {{
                                    seller.products_count ??
                                    0
                                }}
                            </td>

                            <td>
                                {{
                                    seller.seller_orders_count ??
                                    0
                                }}
                            </td>

                            <td>
                                {{
                                    seller.approved_at_formatted ??
                                    seller.updated_at_formatted ??
                                    '—'
                                }}
                            </td>

                            <td>
                                <span
                                    class="status-badge"
                                    :class="{
                                        'status-active':
                                            seller.status ===
                                            'active',

                                        'status-locked':
                                            seller.status ===
                                            'locked',
                                    }"
                                >
                                    {{
                                        seller.status === 'active'
                                            ? 'Đang hoạt động'
                                            : 'Đã khóa'
                                    }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="button-link"
                                        @click="viewSeller(seller)"
                                    >
                                        Chi tiết
                                    </button>

                                    <button
                                        type="button"
                                        class="button-link"
                                        :class="{
                                            'button-danger':
                                                seller.status ===
                                                'active',
                                        }"
                                        :disabled="
                                            updatingSellerId ===
                                            seller.id
                                        "
                                        @click="
                                            toggleSellerStatus(
                                                seller,
                                            )
                                        "
                                    >
                                        <template
                                            v-if="
                                                updatingSellerId ===
                                                seller.id
                                            "
                                        >
                                            Đang xử lý...
                                        </template>

                                        <template v-else>
                                            {{
                                                seller.status ===
                                                'active'
                                                    ? 'Khóa'
                                                    : 'Mở khóa'
                                            }}
                                        </template>
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
                <div class="pagination-info">
                    Hiển thị
                    {{ pagination.from }}
                    –
                    {{ pagination.to }}
                    trong
                    {{ pagination.total }}
                    người bán
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
    </section>
</template>

<style scoped>
.seller-page {
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
.filter-field select {
    width: 100%;
    height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    padding: 0 12px;
    color: #111827;
    font: inherit;
    outline: none;
}

.filter-field input:focus,
.filter-field select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.filter-actions {
    display: flex;
    align-items: center;
}

.button {
    height: 40px;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-weight: 500;
}

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover {
    background: #f9fafb;
}

.table-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.table-wrapper {
    overflow-x: auto;
}

.seller-table {
    width: 100%;
    border-collapse: collapse;
}

.seller-table th,
.seller-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.seller-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
}

.seller-table td {
    color: #4b5563;
    font-size: 14px;
}

.seller-table tbody tr:hover {
    background: #f9fafb;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.seller-avatar {
    display: flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-weight: 700;
}

.seller-name {
    color: #111827;
    font-weight: 500;
}

.seller-id {
    margin-top: 3px;
    color: #9ca3af;
    font-size: 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
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

.table-actions {
    display: flex;
    align-items: center;
    gap: 12px;
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

.button-link:hover {
    text-decoration: underline;
}

.button-danger {
    color: #dc2626;
}

.button-link:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.action-column {
    width: 160px;
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

@media (max-width: 900px) {
    .filter-panel {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .filter-panel {
        grid-template-columns: 1fr;
    }

    .pagination {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
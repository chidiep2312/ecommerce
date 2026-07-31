<script setup>
import {
    Eye,
    LockKeyhole,
    Search,
    UnlockKeyhole,
} from '@lucide/vue'
import {
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue'
import { useRouter } from 'vue-router'

import {
    getAdminUsers,
    updateAdminUserStatus,
} from '@/api/admin/users'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'
import AdminPagination from '@/components/admin/AdminPagination.vue'

const router = useRouter()

const users = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const updatingUserId = ref(null)

const filters = reactive({
    search: '',
    role: '',
    status: '',
    perPage: 10,
    sortBy: 'created_at',
    sortDirection: 'desc',
})

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    from: 0,
    to: 0,
    total: 0,
})

let searchTimer = null

async function loadUsers(page = 1) {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminUsers({
            page,
            per_page: filters.perPage,
            search:
                filters.search || undefined,
            role:
                filters.role || undefined,
            status:
                filters.status || undefined,
            sort_by: filters.sortBy,
            sort_direction:
                filters.sortDirection,
        })

        users.value =
            response.data.data ?? []

        const meta =
            response.data.meta ?? {}

        pagination.currentPage =
            meta.current_page ?? 1

        pagination.lastPage =
            meta.last_page ?? 1

        pagination.from =
            meta.from ?? 0

        pagination.to =
            meta.to ?? 0

        pagination.total =
            meta.total ?? 0
    } catch (error) {
        console.error(error)

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách người dùng.'

        users.value = []
    } finally {
        isLoading.value = false
    }
}

function handleSearch() {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(() => {
        loadUsers(1)
    }, 400)
}

function resetFilters() {
    filters.search = ''
    filters.role = ''
    filters.status = ''
    filters.perPage = 10
    filters.sortBy = 'created_at'
    filters.sortDirection = 'desc'

    loadUsers(1)
}

function viewUser(userId) {
    router.push({
        name: 'admin-user-detail',
        params: {
            id: userId,
        },
    })
}

async function toggleStatus(user) {
    const nextStatus =
        user.status === 'active'
            ? 'locked'
            : 'active'

    const action =
        nextStatus === 'locked'
            ? 'khóa'
            : 'mở khóa'

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${action} tài khoản "${user.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    updatingUserId.value = user.id
    console.log(nextStatus);
    try {
        const response =
            await updateAdminUserStatus(
                user.id,
                {
                    status: nextStatus,
                },
            )

        user.status =
            response.data.data.status
    } catch (error) {
        window.alert(
            error.response?.data?.message ??
            'Không thể cập nhật trạng thái tài khoản.',
        )
    } finally {
        updatingUserId.value = null
    }
}

function roleLabel(role) {
    return {
        admin: 'Quản trị viên',
        seller: 'Người bán',
        customer: 'Người mua',
    }[role] ?? role
}

function statusLabel(status) {
    return {
        acive: 'Đang hoạt động',
        locked: 'Đã khóa',
    }[status] ?? status
}

function formatDate(value) {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        },
    ).format(new Date(value))
}

watch(
    () => [
        filters.role,
        filters.status,
        filters.perPage,
        filters.sortBy,
        filters.sortDirection,
    ],
    () => {
        loadUsers(1)
    },
)

onMounted(() => {
    loadUsers()
})
</script>

<template>
    <section>
        <AdminPageHeader title="Quản lý người dùng" description="Theo dõi và quản lý tài khoản trên hệ thống." />

        <div class="user-filter">
            <div class="user-filter__search">
                <Search :size="18" />

                <input v-model="filters.search" type="search" placeholder="Tìm tên, email hoặc số điện thoại..."
                    @input="handleSearch" />
            </div>

            <select v-model="filters.role">
                <option value="">
                    Tất cả vai trò
                </option>
                <option value="customer">
                    Người mua
                </option>
                <option value="seller">
                    Người bán
                </option>
                <option value="admin">
                    Quản trị viên
                </option>
            </select>

            <select v-model="filters.status">
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

            <select v-model="filters.perPage">
                <option :value="10">
                    10 dòng
                </option>
                <option :value="20">
                    20 dòng
                </option>
                <option :value="50">
                    50 dòng
                </option>
            </select>

            <button type="button" class="admin-button admin-button--secondary" @click="resetFilters">
                Đặt lại
            </button>
        </div>

        <p v-if="errorMessage" class="user-error">
            {{ errorMessage }}
        </p>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Vai trò</th>
                        <th>Số điện thoại</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng ký</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody v-if="isLoading">
                    <tr>
                        <td colspan="6" class="user-empty">
                            Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>

                <tbody v-else-if="users.length === 0">
                    <tr>
                        <td colspan="6" class="user-empty">
                            Không tìm thấy người dùng.
                        </td>
                    </tr>
                </tbody>

                <tbody v-else>
                    <tr v-for="user in users" :key="user.id">
                        <td>
                            <div class="user-identity">
                                <div class="user-avatar">
                                    {{
                                        user.name
                                            ?.charAt(0)
                                            ?.toUpperCase()
                                    }}
                                </div>

                                <div>
                                    <strong>
                                        {{ user.name }}
                                    </strong>

                                    <span>
                                        {{ user.email }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="user-badge" :class="`user-badge--${user.role.toLowerCase()}`
                                ">
                                {{ roleLabel(user.role) }}
                            </span>
                        </td>

                        <td>
                            {{ user.phone ?? '—' }}
                        </td>

                        <td>
                            <span class="user-status" :class="`user-status--${user.status.toLowerCase()}`
                                ">
                                {{
                                    statusLabel(
                                        user.status,
                                    )
                                }}
                            </span>
                        </td>

                        <td>
                            {{
                                formatDate(
                                    user.created_at,
                                )
                            }}
                        </td>

                        <td>
                            <div class="user-actions">
                                <button type="button" title="Xem chi tiết" @click="
                                    viewUser(user.id)
                                    ">
                                    <Eye :size="17" />
                                </button>

                                <button v-if="
                                    user.role !==
                                    'admin'
                                " type="button" :disabled="updatingUserId ===
                                        user.id
                                        " :title="user.status ===
                                            'active'
                                            ? 'Khóa tài khoản'
                                            : 'Mở khóa tài khoản'
                                        " @click="
                                        toggleStatus(
                                            user,
                                        )
                                        ">
                                    <LockKeyhole v-if="
                                        user.status ===
                                        'active'
                                    " :size="17" />

                                    <UnlockKeyhole v-else :size="17" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AdminPagination :current-page="pagination.currentPage" :last-page="pagination.lastPage" :from="pagination.from"
            :to="pagination.to" :total="pagination.total" @change="loadUsers" />
    </section>
</template>

<style scoped>
.user-filter {
    display: grid;
    grid-template-columns:
        minmax(280px, 1fr) 170px 170px 120px auto;
    gap: 12px;
    margin-bottom: 18px;
    padding: 16px;
    background: #ffffff;
    border: 1px solid var(--admin-border-light);
}

.user-filter__search {
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px;
    border: 1px solid var(--admin-border);
}

.user-filter__search:focus-within {
    border-color: var(--admin-primary-600);
}

.user-filter__search input {
    width: 100%;
    border: 0;
    outline: 0;
}

.user-filter select {
    min-height: 42px;
    padding: 0 10px;
    border: 1px solid var(--admin-border);
    background: #ffffff;
    outline: none;
}

.user-error {
    padding: 12px;
    background: #fee2e2;
    border-left: 4px solid #dc2626;
    color: #991b1b;
}

.user-empty {
    height: 160px;
    color: var(--admin-text-secondary);
    text-align: center;
}

.user-identity {
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
    font-weight: 700;
}

.user-identity>div:last-child {
    display: flex;
    flex-direction: column;
}

.user-identity span {
    color: var(--admin-text-secondary);
    font-size: 12px;
}

.user-badge,
.user-status {
    display: inline-block;
    min-width: 105px;
    padding: 5px 9px;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
}

.user-badge--admin {
    background: #f3e8ff;
    color: #6b21a8;
}

.user-badge--seller {
    background: #fef3c7;
    color: #92400e;
}

.user-badge--buyer {
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
}

.user-status--active {
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
}

.user-status--inactive {
    background: #fee2e2;
    color: #991b1b;
}

.user-status--pending {
    background: #fef3c7;
    color: #92400e;
}

.user-actions {
    display: flex;
    gap: 6px;
}

.user-actions button {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 1px solid var(--admin-border);
    background: #ffffff;
    color: var(--admin-text-secondary);
}

.user-actions button:hover:not(:disabled) {
    border-color: var(--admin-primary-600);
    background: var(--admin-primary-50);
    color: var(--admin-primary-700);
}

.user-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

@media (max-width: 1100px) {
    .user-filter {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .user-filter__search {
        grid-column: 1 / -1;
    }
}

@media (max-width: 650px) {
    .user-filter {
        grid-template-columns: 1fr;
    }

    .user-filter__search {
        grid-column: auto;
    }
}
</style>
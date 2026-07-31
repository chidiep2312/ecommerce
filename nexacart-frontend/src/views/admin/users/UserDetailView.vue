<script setup>
import {
    ArrowLeft,
    LockKeyhole,
    Mail,
    Phone,
    ShieldCheck,
    UnlockKeyhole,
    UserRound,
} from '@lucide/vue'
import {
    onMounted,
    ref,
} from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getAdminUser,
    updateAdminUserStatus,
} from '@/api/admin/users'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const route = useRoute()
const router = useRouter()

const user = ref(null)
const isLoading = ref(false)
const isUpdating = ref(false)

async function loadUser() {
    isLoading.value = true

    try {
        const response =
            await getAdminUser(
                route.params.id,
            )

        user.value =
            response.data.data
    } catch (error) {
        console.error(
            'Không thể tải thông tin người dùng:',
            error,
        )
    } finally {
        isLoading.value = false
    }
}

async function changeStatus() {
    if (!user.value) {
        return
    }

    const nextStatus =
        user.value.status === 'active'
            ? 'locked'
            : 'active'

    if (
        !window.confirm(
            'Bạn có chắc muốn thay đổi trạng thái tài khoản?',
        )
    ) {
        return
    }

    isUpdating.value = true

    try {
        await updateAdminUserStatus(
            user.value.id,
            {
                status: nextStatus,
            },
        )

        user.value.status = nextStatus
    } finally {
        isUpdating.value = false
    }
}

function formatDate(date) {
    if (!date) {
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
    ).format(new Date(date))
}

onMounted(loadUser)
</script>

<template>
    <section>
        <AdminPageHeader
            title="Chi tiết người dùng"
            description="Xem thông tin và trạng thái tài khoản."
        >
            <template #actions>
                <button
                    type="button"
                    class="admin-button admin-button--secondary"
                    @click="router.back()"
                >
                    <ArrowLeft :size="18" />
                    Quay lại
                </button>
            </template>
        </AdminPageHeader>

        <div
            v-if="isLoading"
            class="user-detail-loading"
        >
            Đang tải thông tin...
        </div>

        <div
            v-else-if="!user"
            class="user-detail-loading"
        >
            Không tìm thấy người dùng.
        </div>

        <div
            v-else
            class="user-detail-grid"
        >
            <aside class="user-profile-card">
                <div class="user-profile-card__avatar">
                    {{
                        user.name
                            ?.charAt(0)
                            ?.toUpperCase()
                    }}
                </div>

                <h2>{{ user.name }}</h2>
                <p>{{ user.email }}</p>

                <span
                    class="user-profile-card__status"
                    :class="{
                        'user-profile-card__status--inactive':
                            user.status !==
                            'active',
                    }"
                >
                    {{
                        user.status ===
                        'active'
                            ? 'Đang hoạt động'
                            : 'Đã khóa'
                    }}
                </span>

                <button
                    type="button"
                    class="user-profile-card__action"
                    :class="{
                        'user-profile-card__action--unlock':
                            user.status !==
                            'active',
                    }"
                    :disabled="isUpdating"
                    @click="changeStatus"
                >
                    <LockKeyhole
                        v-if="
                            user.status ===
                            'active'
                        "
                        :size="18"
                    />

                    <UnlockKeyhole
                        v-else
                        :size="18"
                    />

                    {{
                        user.status ===
                        'active'
                            ? 'Khóa tài khoản'
                            : 'Mở khóa tài khoản'
                    }}
                </button>
            </aside>

            <main class="user-detail-content">
                <section class="user-detail-panel">
                    <div class="user-detail-panel__header">
                        <h2>Thông tin tài khoản</h2>
                    </div>

                    <div class="user-detail-list">
                        <div>
                            <UserRound :size="19" />

                            <span>Họ và tên</span>

                            <strong>
                                {{ user.name }}
                            </strong>
                        </div>

                        <div>
                            <Mail :size="19" />

                            <span>Email</span>

                            <strong>
                                {{ user.email }}
                            </strong>
                        </div>

                        <div>
                            <Phone :size="19" />

                            <span>Số điện thoại</span>

                            <strong>
                                {{ user.phone ?? '—' }}
                            </strong>
                        </div>

                        <div>
                            <ShieldCheck :size="19" />

                            <span>Vai trò</span>

                            <strong>
                                {{ user.role }}
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="user-detail-panel">
                    <div class="user-detail-panel__header">
                        <h2>Thông tin hoạt động</h2>
                    </div>

                    <div class="user-detail-metrics">
                        <article>
                            <span>Ngày đăng ký</span>

                            <strong>
                                {{
                                    formatDate(
                                        user.created_at,
                                    )
                                }}
                            </strong>
                        </article>

                        <article>
                            <span>
                                Đăng nhập gần nhất
                            </span>

                            <strong>
                                {{
                                    formatDate(
                                        user.last_login_at,
                                    )
                                }}
                            </strong>
                        </article>

                        <article>
                            <span>Tổng đơn hàng</span>

                            <strong>
                                {{
                                    user.orders_count ??
                                    0
                                }}
                            </strong>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </section>
</template>

<style scoped>
.user-detail-loading {
    min-height: 280px;
    display: grid;
    place-items: center;
    background: #ffffff;
    border: 1px solid var(--admin-border-light);
    color: var(--admin-text-secondary);
}

.user-detail-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
}

.user-profile-card {
    align-self: start;
    padding: 28px 22px;
    background: #ffffff;
    border: 1px solid var(--admin-border-light);
    text-align: center;
}

.user-profile-card__avatar {
    width: 96px;
    height: 96px;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
    font-size: 34px;
    font-weight: 700;
}

.user-profile-card h2 {
    margin: 0 0 5px;
    font-size: 20px;
}

.user-profile-card p {
    margin: 0 0 18px;
    color: var(--admin-text-secondary);
}

.user-profile-card__status {
    display: inline-block;
    padding: 6px 12px;
    background: var(--admin-primary-100);
    color: var(--admin-primary-800);
    font-size: 12px;
    font-weight: 600;
}

.user-profile-card__status--inactive {
    background: #fee2e2;
    color: #991b1b;
}

.user-profile-card__action {
    width: 100%;
    min-height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    margin-top: 22px;
    border: 1px solid var(--admin-danger);
    background: var(--admin-danger);
    color: #ffffff;
    font-weight: 500;
}

.user-profile-card__action--unlock {
    border-color: var(--admin-primary-700);
    background: var(--admin-primary-700);
}

.user-detail-content {
    display: grid;
    gap: 20px;
}

.user-detail-panel {
    background: #ffffff;
    border: 1px solid var(--admin-border-light);
}

.user-detail-panel__header {
    padding: 17px 20px;
    border-bottom: 1px solid
        var(--admin-border-light);
}

.user-detail-panel__header h2 {
    margin: 0;
    font-size: 17px;
}

.user-detail-list {
    padding: 4px 20px;
}

.user-detail-list > div {
    min-height: 62px;
    display: grid;
    grid-template-columns:
        24px
        minmax(130px, 180px)
        1fr;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid
        var(--admin-border-light);
}

.user-detail-list > div:last-child {
    border-bottom: 0;
}

.user-detail-list svg {
    color: var(--admin-primary-700);
}

.user-detail-list span {
    color: var(--admin-text-secondary);
}

.user-detail-metrics {
    display: grid;
    grid-template-columns:
        repeat(3, minmax(0, 1fr));
}

.user-detail-metrics article {
    padding: 22px;
    border-right: 1px solid
        var(--admin-border-light);
}

.user-detail-metrics article:last-child {
    border-right: 0;
}

.user-detail-metrics span {
    display: block;
    margin-bottom: 8px;
    color: var(--admin-text-secondary);
    font-size: 13px;
}

.user-detail-metrics strong {
    font-size: 17px;
}

@media (max-width: 900px) {
    .user-detail-grid {
        grid-template-columns: 1fr;
    }

    .user-detail-metrics {
        grid-template-columns: 1fr;
    }

    .user-detail-metrics article {
        border-right: 0;
        border-bottom: 1px solid
            var(--admin-border-light);
    }
}

@media (max-width: 600px) {
    .user-detail-list > div {
        grid-template-columns: 24px 1fr;
    }

    .user-detail-list strong {
        grid-column: 2;
    }
}
</style>
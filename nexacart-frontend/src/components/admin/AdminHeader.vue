<script setup>
import {
    Bell,
    LogOut,
    Menu,
    PanelLeftClose,
    Search,
    UserRound,
} from '@lucide/vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

defineEmits([
    'toggle-sidebar',
    'toggle-mobile-sidebar',
])

const router = useRouter()
const authStore = useAuthStore()

const isProfileOpen = ref(false)

async function handleLogout() {
    await authStore.logout()

    router.push({
        name: 'login',
    })
}
</script>

<template>
    <header class="admin-header">
        <div class="admin-header__left">
            <button
                type="button"
                class="admin-header__desktop-toggle"
                @click="$emit('toggle-sidebar')"
            >
                <PanelLeftClose :size="21" />
            </button>

            <button
                type="button"
                class="admin-header__mobile-toggle"
                @click="
                    $emit(
                        'toggle-mobile-sidebar',
                    )
                "
            >
                <Menu :size="22" />
            </button>

            <div class="admin-header__search">
                <Search :size="18" />

                <input
                    type="search"
                    placeholder="Tìm kiếm trong hệ thống..."
                />
            </div>
        </div>

        <div class="admin-header__actions">
            <button
                type="button"
                class="admin-header__icon-button"
                aria-label="Thông báo"
            >
                <Bell :size="20" />
                <span
                    class="admin-header__notification-badge"
                >
                    3
                </span>
            </button>

            <div class="admin-header__profile">
                <button
                    type="button"
                    class="admin-header__profile-button"
                    @click="
                        isProfileOpen =
                            !isProfileOpen
                    "
                >
                    <div
                        class="admin-header__avatar"
                    >
                        <UserRound :size="19" />
                    </div>

                    <div
                        class="admin-header__profile-info"
                    >
                        <strong>
                            {{
                                authStore.user
                                    ?.name ??
                                'Quản trị viên'
                            }}
                        </strong>

                        <span>
                            {{
                                authStore.role ??
                                'ADMIN'
                            }}
                        </span>
                    </div>
                </button>

                <div
                    v-if="isProfileOpen"
                    class="admin-header__dropdown"
                >
                    <button type="button">
                        <UserRound :size="17" />
                        Hồ sơ cá nhân
                    </button>

                    <button
                        type="button"
                        class="admin-header__logout"
                        @click="handleLogout"
                    >
                        <LogOut :size="17" />
                        Đăng xuất
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.admin-header {
    position: sticky;
    top: 0;
    z-index: 70;
    height: var(--admin-header-height);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: #ffffff;
    border-bottom: 1px solid
        var(--admin-border-light);
}

.admin-header__left,
.admin-header__actions {
    display: flex;
    align-items: center;
}

.admin-header__left {
    gap: 18px;
}

.admin-header__actions {
    gap: 12px;
}

.admin-header__desktop-toggle,
.admin-header__mobile-toggle,
.admin-header__icon-button {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 1px solid var(--admin-border);
    background: #ffffff;
    color: #374151;
}

.admin-header__desktop-toggle:hover,
.admin-header__mobile-toggle:hover,
.admin-header__icon-button:hover {
    background: var(--admin-primary-50);
    border-color: var(--admin-primary-600);
    color: var(--admin-primary-700);
}

.admin-header__mobile-toggle {
    display: none;
}

.admin-header__search {
    width: min(420px, 38vw);
    height: 42px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 13px;
    border: 1px solid var(--admin-border);
    background: #f9fafb;
    color: var(--admin-text-secondary);
}

.admin-header__search:focus-within {
    border-color: var(--admin-primary-600);
    background: #ffffff;
}

.admin-header__search input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--admin-text-primary);
}

.admin-header__icon-button {
    position: relative;
}

.admin-header__notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    display: grid;
    place-items: center;
    padding: 0 4px;
    background: var(--admin-danger);
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
}

.admin-header__profile {
    position: relative;
}

.admin-header__profile-button {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0;
    border: 0;
    background: transparent;
}

.admin-header__avatar {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: var(--admin-primary-700);
    color: #ffffff;
}

.admin-header__profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.admin-header__profile-info strong {
    color: var(--admin-text-primary);
    font-size: 13px;
}

.admin-header__profile-info span {
    color: var(--admin-text-secondary);
    font-size: 11px;
}

.admin-header__dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 200px;
    background: #ffffff;
    border: 1px solid var(--admin-border);
    box-shadow: 0 12px 32px
        rgb(15 23 42 / 12%);
}

.admin-header__dropdown button {
    width: 100%;
    min-height: 44px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    border: 0;
    border-bottom: 1px solid
        var(--admin-border-light);
    background: #ffffff;
    color: var(--admin-text-primary);
    text-align: left;
}

.admin-header__dropdown button:hover {
    background: var(--admin-primary-50);
}

.admin-header__dropdown
    .admin-header__logout {
    color: var(--admin-danger);
}

@media (max-width: 900px) {
    .admin-header {
        padding: 0 16px;
    }

    .admin-header__desktop-toggle {
        display: none;
    }

    .admin-header__mobile-toggle {
        display: grid;
    }

    .admin-header__search {
        width: min(340px, 45vw);
    }
}

@media (max-width: 650px) {
    .admin-header__search {
        display: none;
    }

    .admin-header__profile-info {
        display: none;
    }
}
</style>
<script setup>
import {
    Heart,
    KeyRound,
    LayoutDashboard,
    LogOut,
    MapPin,
    Package,
    Star,
    UserRound,
} from '@lucide/vue'

import { computed } from 'vue'
import {
    useRoute,
    useRouter,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
})

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()

const menuItems = [
    {
        label: 'Tổng quan',
        icon: LayoutDashboard,
        routeName: 'customer-dashboard',
    },
    {
        label: 'Hồ sơ cá nhân',
        icon: UserRound,
       routeName: 'customer-profile',
    },
    {
        label: 'Địa chỉ giao hàng',
        icon: MapPin,
       routeName: 'customer-addresses',
    },
    {
        label: 'Đơn hàng của tôi',
        icon: Package,
        routeName: 'customer-orders',
    },
    {
        label: 'Đánh giá của tôi',
        icon: Star,
     //   routeName: 'customer-reviews',
    },
    {
        label: 'Sản phẩm yêu thích',
        icon: Heart,
        routeName: 'customer-wishlist',
    },
    {
        label: 'Đổi mật khẩu',
        icon: KeyRound,
      //  routeName: 'customer-change-password',
    },
]

const userInitial = computed(() => {
    const name =
        props.user?.name?.trim()

    if (!name) {
        return 'U'
    }

    return name
        .charAt(0)
        .toUpperCase()
})

function isActive(item) {
    return (
        route.name ===
        item.routeName
    )
}

function navigateTo(item) {
    if (
        route.name ===
        item.routeName
    ) {
        return
    }

    router.push({
        name: item.routeName,
    })
}

async function logout() {
    const confirmed =
        window.confirm(
            'Bạn có chắc muốn đăng xuất không?',
        )

    if (!confirmed) {
        return
    }

    await authStore.logout()

    router.push({
        name: 'login',
    })
}
</script>

<template>
    <aside class="account-sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                {{ userInitial }}
            </div>

            <div class="sidebar-user-info">
                <strong>
                    {{
                        user?.name ??
                        'Khách hàng'
                    }}
                </strong>

                <span>
                    {{
                        user?.email ??
                        '--'
                    }}
                </span>
            </div>
        </div>

        <nav class="account-menu">
            <button
                v-for="item in menuItems"
                :key="item.routeName"
                type="button"
                class="menu-item"
                :class="{
                    active:
                        isActive(item),
                }"
                @click="
                    navigateTo(item)
                "
            >
                <component
                    :is="item.icon"
                    :size="18"
                />

                <span>
                    {{ item.label }}
                </span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <button
                type="button"
                class="logout-button"
                @click="logout"
            >
                <LogOut :size="18" />

                <span>Đăng xuất</span>
            </button>
        </div>
    </aside>
</template>

<style scoped>
.account-sidebar {
    position: sticky;
    top: 88px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 16px;
    border-bottom: 1px solid #e5ebe7;
}

.sidebar-avatar {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    background: #24734a;
    color: #ffffff;
    font-size: 18px;
    font-weight: 700;
}

.sidebar-user-info {
    min-width: 0;
}

.sidebar-user-info strong,
.sidebar-user-info span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-user-info strong {
    color: #202c24;
    font-size: 14px;
}

.sidebar-user-info span {
    margin-top: 4px;
    color: #7a877f;
    font-size: 11px;
}

.account-menu {
    padding: 8px 0;
}

.menu-item,
.logout-button {
    display: flex;
    width: 100%;
    min-height: 46px;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    border: 0;
    border-left: 3px solid transparent;
    background: #ffffff;
    color: #526158;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
}

.menu-item svg {
    color: #74837a;
}

.menu-item:hover {
    background: #f5f9f6;
    color: #205f3f;
}

.menu-item.active {
    border-left-color: #24734a;
    background: #edf6f0;
    color: #1f633f;
    font-weight: 700;
}

.menu-item.active svg {
    color: #24734a;
}

.sidebar-footer {
    padding: 8px 0;
    border-top: 1px solid #e5ebe7;
}

.logout-button {
    color: #a33f3f;
}

.logout-button:hover {
    background: #fff3f3;
}

@media (max-width: 820px) {
    .account-sidebar {
        position: static;
    }

    .account-menu {
        display: grid;
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .menu-item {
        border-left: 0;
        border-bottom: 2px solid transparent;
    }

    .menu-item.active {
        border-bottom-color: #24734a;
    }
}

@media (max-width: 560px) {
    .account-menu {
        grid-template-columns: 1fr;
    }
}
</style>
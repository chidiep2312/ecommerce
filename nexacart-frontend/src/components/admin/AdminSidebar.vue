<script setup>
import {
    BarChart3,
    Boxes,
    ChevronRight,
    ClipboardList,
    FolderTree,
    Gift,
    LayoutDashboard,
    Package,
    Settings,
    ShieldCheck,
    Store,
    Tags,
    Users,
    X,
} from '@lucide/vue'

defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['close-mobile'])

const menuGroups = [
    {
        title: 'Tổng quan',
        items: [
            {
                label: 'Dashboard',
                route: '/admin/dashboard',
                icon: LayoutDashboard,
            },
            {
                label: 'Báo cáo',
                route: '/admin/reports',
                icon: BarChart3,
            },
        ],
    },
    {
        title: 'Quản lý',
        items: [
            {
                label: 'Người dùng',
                route: '/admin/users',
                icon: Users,
            },
            {
                label: 'Người bán',
                route: '/admin/sellers',
                icon: Store,
            },
            {
                label: 'Yêu cầu người bán',
                route: '/admin/seller-requests',
                icon: Store,
            },
            {
                label: 'Sản phẩm',
                route: '/admin/products',
                icon: Package,
            },
            {
                label: 'Đơn hàng',
                route: '/admin/orders',
                icon: ClipboardList,
            },
        ],
    },
    {
        title: 'Danh mục',
        items: [
            {
                label: 'Danh mục sản phẩm',
                route: '/admin/categories',
                icon: FolderTree,
            },
            {
                label: 'Thương hiệu',
                route: '/admin/brands',
                icon: Tags,
            },
            {
                label: 'Kho hàng',
                route: '/admin/inventory',
                icon: Boxes,
            },
            {
                label: 'Voucher',
                route: '/admin/vouchers',
                icon: Gift,
            },
        ],
    },
    {
        title: 'Hệ thống',
        items: [
            {
                label: 'Phân quyền',
                route: '/admin/roles',
                icon: ShieldCheck,
            },
            {
                label: 'Cài đặt',
                route: '/admin/settings',
                icon: Settings,
            },
        ],
    },
]
</script>

<template>
    <aside
        class="admin-sidebar"
        :class="{
            'admin-sidebar--collapsed':
                collapsed,
            'admin-sidebar--mobile-open':
                mobileOpen,
        }"
    >
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__brand-mark">
                N
            </div>

            <div
                v-if="!collapsed"
                class="admin-sidebar__brand-text"
            >
                <strong>NexaCart</strong>
                <span>Administration</span>
            </div>

            <button
                type="button"
                class="admin-sidebar__mobile-close"
                @click="$emit('close-mobile')"
            >
                <X :size="20" />
            </button>
        </div>

        <div class="admin-sidebar__profile">
            <div class="admin-sidebar__avatar">
                AD
            </div>

            <div
                v-if="!collapsed"
                class="admin-sidebar__profile-text"
            >
                <strong>Quản trị viên</strong>
                <span>Administrator</span>
            </div>
        </div>

        <nav class="admin-sidebar__navigation">
            <section
                v-for="group in menuGroups"
                :key="group.title"
                class="admin-sidebar__group"
            >
                <p
                    v-if="!collapsed"
                    class="admin-sidebar__group-title"
                >
                    {{ group.title }}
                </p>

                <RouterLink
                    v-for="item in group.items"
                    :key="item.route"
                    :to="item.route"
                    class="admin-sidebar__link"
                    @click="$emit('close-mobile')"
                >
                    <component
                        :is="item.icon"
                        :size="20"
                        :stroke-width="1.8"
                    />

                    <span v-if="!collapsed">
                        {{ item.label }}
                    </span>

                    <ChevronRight
                        v-if="!collapsed"
                        class="admin-sidebar__link-arrow"
                        :size="16"
                    />
                </RouterLink>
            </section>
        </nav>
    </aside>
</template>

<style scoped>
.admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    width: var(--admin-sidebar-width);
    height: 100vh;
    overflow-y: auto;
    background: var(--admin-primary-950);
    color: #ffffff;
    transition: width 0.2s ease;
}

.admin-sidebar--collapsed {
    width: var(
        --admin-sidebar-collapsed-width
    );
}

.admin-sidebar__brand {
    position: sticky;
    top: 0;
    z-index: 2;
    height: var(--admin-header-height);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 18px;
    background: var(--admin-primary-950);
    border-bottom: 1px solid
        rgb(255 255 255 / 10%);
}

.admin-sidebar__brand-mark {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    background: var(--admin-primary-500);
    color: var(--admin-primary-950);
    font-size: 20px;
    font-weight: 700;
}

.admin-sidebar__brand-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.admin-sidebar__brand-text strong {
    font-size: 17px;
}

.admin-sidebar__brand-text span {
    color: var(--admin-primary-300);
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-sidebar__profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 18px;
    border-bottom: 1px solid
        rgb(255 255 255 / 10%);
}

.admin-sidebar__avatar {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: var(--admin-primary-800);
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
}

.admin-sidebar__profile-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.admin-sidebar__profile-text strong {
    overflow: hidden;
    font-size: 14px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-sidebar__profile-text span {
    color: var(--admin-primary-300);
    font-size: 12px;
}

.admin-sidebar__navigation {
    padding: 14px 0 28px;
}

.admin-sidebar__group {
    margin-bottom: 18px;
}

.admin-sidebar__group-title {
    margin: 0;
    padding: 8px 20px;
    color: var(--admin-primary-300);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-sidebar__link {
    position: relative;
    min-height: 46px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 20px;
    color: #d1fae5;
    border-left: 4px solid transparent;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.admin-sidebar__link:hover {
    background: rgb(255 255 255 / 7%);
    color: #ffffff;
}

.admin-sidebar__link.router-link-active {
    background: var(--admin-primary-900);
    color: #ffffff;
    border-left-color:
        var(--admin-primary-400);
}

.admin-sidebar__link-arrow {
    margin-left: auto;
    opacity: 0.45;
}

.admin-sidebar--collapsed
    .admin-sidebar__brand {
    justify-content: center;
    padding: 0;
}

.admin-sidebar--collapsed
    .admin-sidebar__profile {
    justify-content: center;
    padding: 18px 0;
}

.admin-sidebar--collapsed
    .admin-sidebar__link {
    justify-content: center;
    padding: 0;
}

.admin-sidebar__mobile-close {
    display: none;
    margin-left: auto;
    border: 0;
    background: transparent;
    color: #ffffff;
}

@media (max-width: 900px) {
    .admin-sidebar,
    .admin-sidebar--collapsed {
        width: var(--admin-sidebar-width);
        transform: translateX(-100%);
        transition: transform 0.2s ease;
    }

    .admin-sidebar--mobile-open {
        transform: translateX(0);
    }

    .admin-sidebar__mobile-close {
        display: grid;
        place-items: center;
    }
}
</style>
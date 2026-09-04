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
                route: '/admin',
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
            'admin-sidebar--collapsed': collapsed,
            'admin-sidebar--mobile-open': mobileOpen,
        }"
    >
        <!-- Brand Header -->
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__brand-mark">
                N
            </div>

            <div v-if="!collapsed" class="admin-sidebar__brand-text">
                <strong class="admin-sidebar__brand-name">NexaCart</strong>
                <span class="admin-sidebar__brand-badge">Admin System</span>
            </div>

            <button
                type="button"
                class="admin-sidebar__mobile-close"
                aria-label="Đóng menu"
                @click="$emit('close-mobile')"
            >
                <X :size="18" />
            </button>
        </div>

        <!-- Profile Box -->
        <div class="admin-sidebar__profile-container">
            <div class="admin-sidebar__profile">
                <div class="admin-sidebar__avatar-wrap">
                    <div class="admin-sidebar__avatar">AD</div>
                    <span class="admin-sidebar__status-square" title="Online" />
                </div>

                <div v-if="!collapsed" class="admin-sidebar__profile-text">
                    <strong>Quản trị viên</strong>
                    <span>admin@nexacart.vn</span>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="admin-sidebar__navigation custom-scrollbar">
            <section
                v-for="group in menuGroups"
                :key="group.title"
                class="admin-sidebar__group"
            >
                <div v-if="!collapsed" class="admin-sidebar__group-header">
                    <span class="admin-sidebar__group-line" />
                    <p class="admin-sidebar__group-title">{{ group.title }}</p>
                </div>
                <div v-else class="admin-sidebar__group-divider" />

                <ul class="admin-sidebar__menu-list">
                    <li v-for="item in group.items" :key="item.route">
                        <RouterLink
                            :to="item.route"
                            class="admin-sidebar__link"
                            :title="collapsed ? item.label : undefined"
                            @click="$emit('close-mobile')"
                        >
                            <span class="admin-sidebar__icon-box">
                                <component
                                    :is="item.icon"
                                    :size="18"
                                    :stroke-width="2"
                                />
                            </span>

                            <span v-if="!collapsed" class="admin-sidebar__label">
                                {{ item.label }}
                            </span>

                            <ChevronRight
                                v-if="!collapsed"
                                class="admin-sidebar__link-arrow"
                                :size="14"
                            />
                        </RouterLink>
                    </li>
                </ul>
            </section>
        </nav>
    </aside>
</template>

<style scoped>
/* ==================== ROOT CONTAINER (SHARP & DEEP GREEN) ==================== */
.admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    width: var(--admin-sidebar-width, 260px);
    height: 100vh;
    display: flex;
    flex-direction: column;
    /* Tông xanh lá đậm chuyên nghiệp */
    background: #041d15;
    color: #ecfdf5;
    border-right: 1px solid rgba(16, 185, 129, 0.18);
    box-shadow: 6px 0 20px rgba(0, 0, 0, 0.35);
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    border-radius: 0; /* Vuông vức hoàn toàn */
}

.admin-sidebar--collapsed {
    width: var(--admin-sidebar-collapsed-width, 70px);
}

/* ==================== BRAND HEADER ==================== */
.admin-sidebar__brand {
    position: sticky;
    top: 0;
    z-index: 10;
    height: var(--admin-header-height, 64px);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    background: #02150f;
    border-bottom: 1px solid rgba(16, 185, 129, 0.18);
}

.admin-sidebar__brand-mark {
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    background: #10b981;
    color: #021a12;
    font-size: 20px;
    font-weight: 900;
    border-radius: 0;
    box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
}

.admin-sidebar__brand-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.admin-sidebar__brand-name {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: #ffffff;
    line-height: 1.2;
}

.admin-sidebar__brand-badge {
    color: #34d399;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.admin-sidebar__mobile-close {
    display: none;
    margin-left: auto;
    width: 32px;
    height: 32px;
    border: 1px solid rgba(16, 185, 129, 0.2);
    background: rgba(16, 185, 129, 0.08);
    color: #6ee7b7;
    cursor: pointer;
    border-radius: 0;
    transition: all 0.15s ease;
}

.admin-sidebar__mobile-close:hover {
    background: #10b981;
    color: #021a12;
}

/* ==================== PROFILE BOX ==================== */
.admin-sidebar__profile-container {
    padding: 12px 14px 4px;
}

.admin-sidebar__profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: rgba(16, 185, 129, 0.04);
    border: 1px solid rgba(16, 185, 129, 0.14);
    border-radius: 0;
    transition: background 0.15s ease;
}

.admin-sidebar__profile:hover {
    background: rgba(16, 185, 129, 0.08);
}

.admin-sidebar__avatar-wrap {
    position: relative;
    flex: 0 0 34px;
}

.admin-sidebar__avatar {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    background: #064e3b;
    border: 1px solid #10b981;
    color: #a7f3d0;
    font-size: 12px;
    font-weight: 700;
    border-radius: 0;
}

.admin-sidebar__status-square {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background-color: #34d399;
    border: 1.5px solid #041d15;
    border-radius: 0;
}

.admin-sidebar__profile-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.admin-sidebar__profile-text strong {
    font-size: 13px;
    font-weight: 600;
    color: #f0fdf4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-sidebar__profile-text span {
    color: #6ee7b7;
    font-size: 11px;
    opacity: 0.8;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ==================== NAVIGATION MENU ==================== */
.admin-sidebar__navigation {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0 24px;
}

.admin-sidebar__group {
    margin-bottom: 12px;
}

.admin-sidebar__group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px 6px;
}

.admin-sidebar__group-line {
    width: 4px;
    height: 10px;
    background-color: #10b981;
}

.admin-sidebar__group-title {
    margin: 0;
    color: #6ee7b7;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.admin-sidebar__group-divider {
    height: 1px;
    background: rgba(16, 185, 129, 0.12);
    margin: 10px 14px;
}

.admin-sidebar__menu-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
}

.admin-sidebar__link {
    position: relative;
    height: 42px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    color: #a7f3d0;
    font-size: 13.5px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 0;
    border-left: 3px solid transparent;
    transition: all 0.15s ease;
}

.admin-sidebar__icon-box {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6ee7b7;
    transition: transform 0.15s ease, color 0.15s ease;
}

.admin-sidebar__label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-sidebar__link-arrow {
    margin-left: auto;
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.15s ease;
    color: #34d399;
}

/* Hover State */
.admin-sidebar__link:hover {
    background: rgba(16, 185, 129, 0.1);
    color: #ffffff;
    border-left-color: rgba(16, 185, 129, 0.4);
}

.admin-sidebar__link:hover .admin-sidebar__icon-box {
    color: #34d399;
    transform: scale(1.05);
}

.admin-sidebar__link:hover .admin-sidebar__link-arrow {
    opacity: 0.7;
    transform: translateX(0);
}

/* Active State (RouterLink Active) */
.admin-sidebar__link.router-link-active {
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.22) 0%, rgba(16, 185, 129, 0.04) 100%);
    color: #ffffff;
    font-weight: 600;
    border-left-color: #10b981;
}

.admin-sidebar__link.router-link-active .admin-sidebar__icon-box {
    color: #34d399;
}

.admin-sidebar__link.router-link-active .admin-sidebar__link-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ==================== COLLAPSED MODE ==================== */
.admin-sidebar--collapsed .admin-sidebar__brand {
    justify-content: center;
    padding: 0;
}

.admin-sidebar--collapsed .admin-sidebar__profile-container {
    padding: 8px 6px;
}

.admin-sidebar--collapsed .admin-sidebar__profile {
    justify-content: center;
    padding: 8px 0;
    background: transparent;
    border-color: transparent;
}

.admin-sidebar--collapsed .admin-sidebar__link {
    justify-content: center;
    padding: 0;
    height: 44px;
}

/* ==================== SCROLLBAR ==================== */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(16, 185, 129, 0.25);
    border-radius: 0;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(16, 185, 129, 0.5);
}

/* ==================== MOBILE RESPONSIVE ==================== */
@media (max-width: 900px) {
    .admin-sidebar,
    .admin-sidebar--collapsed {
        width: 260px;
        transform: translateX(-100%);
    }

    .admin-sidebar--mobile-open {
        transform: translateX(0);
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.6);
    }

    .admin-sidebar__mobile-close {
        display: grid;
        place-items: center;
    }
}
</style>
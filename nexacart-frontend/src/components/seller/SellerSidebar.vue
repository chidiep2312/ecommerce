<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const route = useRoute()
const authStore = useAuthStore()

const seller = computed(() => {
    return authStore.user ?? {}
})

const sellerInitial = computed(() => {
    return seller.value.name?.charAt(0)?.toUpperCase() ?? 'S'
})

const managementItems = [
    {
        label: 'Tổng quan',
        routeName: 'seller-dashboard',
        icon: 'dashboard',
    },
    {
        label: 'Sản phẩm',
        routeName: 'seller-products',
        icon: 'products',
    },
    {
        label: 'Đơn hàng',
        routeName: 'seller-orders',
        icon: 'orders',
    },
    {
        label: 'Kho hàng',
        routeName: 'seller-inventory',
        icon: 'inventory',
    },
    {
        label: 'Khuyến mãi',
        routeName: 'seller-vouchers',
        icon: 'voucher',
    },
    {
        label: 'Doanh thu',
        // routeName: 'seller-reports',
        icon: 'reports',
    },
]

const accountItems = [
    {
        label: 'Hồ sơ cửa hàng',
        routeName: 'seller-profile',
        icon: 'profile',
    },
    {
        label: 'Cài đặt',
        // routeName: 'seller-settings',
        icon: 'settings',
    },
    {
        label: 'Địa chỉ kho',
        routeName: 'seller-address',
        icon: 'address',
    },
]

function isRouteActive(routeName) {
    if (!routeName) return false
    return (
        route.name === routeName ||
        route.matched.some(matchedRoute => matchedRoute.name === routeName)
    )
}

function closeSidebar() {
    emit('close')
}
</script>

<template>
    <!-- Backdrop cho Mobile -->
    <div
        v-if="open"
        class="sidebar-backdrop"
        @click="closeSidebar"
    ></div>

    <aside
        class="seller-sidebar"
        :class="{ 'sidebar-open': open }"
    >
        <!-- 1. Header / Logo Thương hiệu -->
        <header class="sidebar-brand">
            <RouterLink
                :to="{ name: 'seller-dashboard' }"
                class="brand-link"
            >
                <span class="brand-mark">N</span>
                <span class="brand-content">
                    <strong class="brand-name">NexaCart</strong>
                    <small class="brand-role">KÊNH BÁN HÀNG</small>
                </span>
            </RouterLink>

            <button
                type="button"
                class="btn-sidebar-close"
                aria-label="Đóng menu"
                @click="closeSidebar"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </header>

        <!-- 2. Thông tin tài khoản người bán -->
        <section class="seller-card">
            <div class="seller-avatar">
                {{ sellerInitial }}
            </div>
            <div class="seller-details">
                <strong class="seller-name" :title="seller.name">
                    {{ seller.name ?? 'Chủ cửa hàng' }}
                </strong>
                <span class="seller-email" :title="seller.email">
                    {{ seller.email ?? 'seller@nexacart.vn' }}
                </span>
            </div>
        </section>

        <!-- 3. Danh mục Menu Điều Hướng -->
        <nav class="sidebar-nav">
            <!-- Nhóm QUẢN LÝ -->
            <div class="nav-group">
                <p class="group-title">QUẢN LÝ CỬA HÀNG</p>

                <RouterLink
                    v-for="item in managementItems"
                    :key="item.label"
                    :to="item.routeName ? { name: item.routeName } : '#'"
                    class="nav-item"
                    :class="{
                        'is-active': isRouteActive(item.routeName),
                        'is-disabled': !item.routeName
                    }"
                    @click="item.routeName && closeSidebar()"
                >
                    <span class="nav-icon">
                        <!-- Dashboard -->
                        <svg v-if="item.icon === 'dashboard'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>

                        <!-- Products -->
                        <svg v-else-if="item.icon === 'products'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>

                        <!-- Orders -->
                        <svg v-else-if="item.icon === 'orders'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>

                        <!-- Inventory -->
                        <svg v-else-if="item.icon === 'inventory'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>

                        <!-- Voucher -->
                        <svg v-else-if="item.icon === 'voucher'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="6" width="20" height="12"></rect>
                            <line x1="6" y1="12" x2="6.01" y2="12"></line>
                            <line x1="10" y1="12" x2="18" y2="12"></line>
                        </svg>

                        <!-- Reports / Revenue -->
                        <svg v-else-if="item.icon === 'reports'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="20" x2="12" y2="10"></line>
                            <line x1="18" y1="20" x2="18" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="16"></line>
                        </svg>
                    </span>

                    <span class="nav-label">{{ item.label }}</span>
                </RouterLink>
            </div>

            <!-- Nhóm TÀI KHOẢN -->
            <div class="nav-group">
                <p class="group-title">TÀI KHOẢN & THIẾT LẬP</p>

                <RouterLink
                    v-for="item in accountItems"
                    :key="item.label"
                    :to="item.routeName ? { name: item.routeName } : '#'"
                    class="nav-item"
                    :class="{
                        'is-active': isRouteActive(item.routeName),
                        'is-disabled': !item.routeName
                    }"
                    @click="item.routeName && closeSidebar()"
                >
                    <span class="nav-icon">
                        <!-- Profile -->
                        <svg v-if="item.icon === 'profile'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>

                        <!-- Address -->
                        <svg v-else-if="item.icon === 'address'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>

                        <!-- Settings -->
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </span>

                    <span class="nav-label">{{ item.label }}</span>
                </RouterLink>
            </div>
        </nav>

        <!-- 4. Footer: Xem gian hàng -->
        <footer class="sidebar-footer">
            <RouterLink to="/" class="storefront-link">
                <span>Xem trang bán hàng</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </RouterLink>
        </footer>
    </aside>
</template>

<style scoped>
/* ================= Base Reset & Color Scheme ================= */
.seller-sidebar {
    --brand-green-primary: #15803d;     /* Xanh lá chủ đạo */
    --brand-green-hover: #166534;       /* Xanh lá đậm khi hover */
    --brand-green-light: #f0fdf4;       /* Nền xanh lá rất nhạt */
    --brand-green-border: #bbf7d0;      /* Viền xanh lá */
    --color-text-main: #111827;
    --color-text-muted: #6b7280;
    --color-border: #e5e7eb;
    --sidebar-width: 260px;

    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    width: var(--sidebar-width);
    background: #ffffff;
    border-right: 1px solid var(--color-border);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    transition: transform 0.2s ease;
    box-sizing: border-box;
}

/* ================= 1. Brand Header ================= */
.sidebar-brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 64px;
    padding: 0 16px;
    border-bottom: 1px solid var(--color-border);
    background: #ffffff;
}

.brand-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}

.brand-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: var(--brand-green-primary);
    color: #ffffff;
    font-size: 1.125rem;
    font-weight: 800;
    border-radius: 0; /* Vuông góc */
    letter-spacing: -0.02em;
}

.brand-content {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1rem;
    font-weight: 800;
    color: var(--color-text-main);
    letter-spacing: -0.01em;
}

.brand-role {
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--brand-green-primary);
    letter-spacing: 0.05em;
    font-family: monospace;
}

.btn-sidebar-close {
    display: none;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: 1px solid var(--color-border);
    color: var(--color-text-muted);
    cursor: pointer;
    border-radius: 0;
}

.btn-sidebar-close svg {
    width: 16px;
    height: 16px;
}

/* ================= 2. Seller Profile Box ================= */
.seller-card {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 12px 12px 4px 12px;
    padding: 10px 12px;
    background: var(--brand-green-light);
    border: 1px solid var(--brand-green-border);
    border-left: 3px solid var(--brand-green-primary); /* Điểm nhấn phẳng */
}

.seller-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--brand-green-primary);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
    border-radius: 0;
}

.seller-details {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.seller-name {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--color-text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.seller-email {
    font-size: 0.6875rem;
    color: var(--color-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: monospace;
}

/* ================= 3. Navigation Links ================= */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Custom Slim Scrollbar (Square) */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: #e5e7eb;
}

.nav-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.group-title {
    margin: 0 0 6px 16px;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 40px;
    padding: 0 16px;
    color: var(--color-text-muted);
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: background-color 0.1s ease, color 0.1s ease, border-color 0.1s ease;
    border-radius: 0;
}

.nav-item:hover:not(.is-disabled) {
    background: #f9fafb;
    color: var(--color-text-main);
    border-left-color: #cbd5e1;
}

.nav-item.is-active {
    background: var(--brand-green-light);
    color: var(--brand-green-hover);
    font-weight: 700;
    border-left-color: var(--brand-green-primary);
}

.nav-item.is-disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.nav-icon svg {
    width: 16px;
    height: 16px;
}

.is-active .nav-icon svg {
    stroke: var(--brand-green-primary);
}

.nav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ================= 4. Footer ================= */
.sidebar-footer {
    padding: 12px 14px;
    border-top: 1px solid var(--color-border);
    background: #ffffff;
}

.storefront-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 12px;
    background: #ffffff;
    border: 1px solid var(--brand-green-primary);
    color: var(--brand-green-primary);
    font-size: 0.8125rem;
    font-weight: 700;
    text-decoration: none;
    border-radius: 0; /* Vuông vắn */
    transition: all 0.15s ease;
}

.storefront-link svg {
    width: 14px;
    height: 14px;
}

.storefront-link:hover {
    background: var(--brand-green-primary);
    color: #ffffff;
}

/* ================= 5. Mobile Responsive & Backdrop ================= */
.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 90;
    background: rgba(0, 0, 0, 0.4);
}

@media (max-width: 1024px) {
    .seller-sidebar {
        transform: translateX(-100%);
    }

    .seller-sidebar.sidebar-open {
        transform: translateX(0);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar-backdrop {
        display: block;
    }

    .btn-sidebar-close {
        display: flex;
    }
}
</style>
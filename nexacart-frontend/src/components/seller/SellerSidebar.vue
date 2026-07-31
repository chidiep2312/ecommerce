<script setup>
import {
    computed,
} from 'vue'
import {
    RouterLink,
    useRoute,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits([
    'close',
])

const route = useRoute()
const authStore = useAuthStore()

const seller = computed(() => {
    return authStore.user ?? {}
})

const sellerInitial = computed(() => {
    return (
        seller.value.name
            ?.charAt(0)
            ?.toUpperCase() ??
        'S'
    )
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
     //   routeName: 'seller-reports',
        icon: 'reports',
    },
]

const accountItems = [
    {
        label: 'Hồ sơ cửa hàng',
     //   routeName: 'seller-profile',
        icon: 'profile',
    },
    {
        label: 'Cài đặt',
      //  routeName: 'seller-settings',
        icon: 'settings',
    },
]

function isRouteActive(routeName) {
    return (
        route.name === routeName ||
        route.matched.some(
            matchedRoute =>
                matchedRoute.name === routeName,
        )
    )
}

function closeSidebar() {
    emit('close')
}
</script>

<template>
    <aside
        class="seller-sidebar"
        :class="{
            'sidebar-open': open,
        }"
    >
        <header class="sidebar-brand">
            <RouterLink
                :to="{
                    name: 'seller-dashboard',
                }"
                class="brand-link"
            >
                <span class="brand-mark">
                    N
                </span>

                <span class="brand-content">
                    <strong class="brand-name">
                        NexaCart
                    </strong>

                    <small class="brand-role">
                        Seller Center
                    </small>
                </span>
            </RouterLink>

            <button
                type="button"
                class="sidebar-close"
                aria-label="Đóng menu"
                @click="closeSidebar"
            >
                ×
            </button>
        </header>

        <section class="seller-summary">
            <div class="seller-avatar">
                {{ sellerInitial }}
            </div>

            <div class="seller-information">
                <strong>
                    {{
                        seller.name ??
                        'Người bán'
                    }}
                </strong>

                <span>
                    {{
                        seller.email ??
                        'Chưa có email'
                    }}
                </span>
            </div>
        </section>

        <nav class="sidebar-navigation">
            <p class="navigation-title">
                QUẢN LÝ
            </p>

            <RouterLink
                v-for="item in managementItems"
                :key="item.routeName"
                :to="{
                    name: item.routeName,
                }"
                class="navigation-link"
                :class="{
                    active:
                        isRouteActive(
                            item.routeName,
                        ),
                }"
            >
                <span class="navigation-icon">
                    <svg
                        v-if="
                            item.icon ===
                            'dashboard'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'products'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="m12 3 8 4-8 4-8-4 8-4Zm-8 8 8 4 8-4v6l-8 4-8-4v-6Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'orders'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M6 3h12v18H6V3Zm3 4h6v2H9V7Zm0 4h6v2H9v-2Zm0 4h4v2H9v-2Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'inventory'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M3 6 12 2l9 4v12l-9 4-9-4V6Zm9-1.8L7.1 6.4 12 8.6l4.9-2.2L12 4.2Zm-7 5v7.5l6 2.7v-7.5L5 9.2Zm8 10.2 6-2.7V9.2l-6 2.7v7.5Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'voucher'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M3 6h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4V6Zm6 2v8h2V8H9Zm4 0v2h3V8h-3Zm0 4v2h3v-2h-3Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'reports'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"
                        />
                    </svg>

                    <svg
                        v-else-if="
                            item.icon ===
                            'profile'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm-9 9a9 9 0 0 1 18 0H3Z"
                        />
                    </svg>

                    <svg
                        v-else
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="m10.7 2 .5 2.1a8 8 0 0 1 1.6 0L13.3 2l2.4 1 .9 1.9c.5.3.9.7 1.3 1.1l2-.7 1 2.4-1.8 1.2c.1.5.1 1.1 0 1.6l1.8 1.2-1 2.4-2-.7c-.4.4-.8.8-1.3 1.1l-.9 1.9-2.4 1-.5-2.1a8 8 0 0 1-1.6 0l-.5 2.1-2.4-1-.9-1.9a8 8 0 0 1-1.3-1.1l-2 .7-1-2.4 1.8-1.2a8 8 0 0 1 0-1.6L3.1 7.7l1-2.4 2 .7c.4-.4.8-.8 1.3-1.1L8.3 3l2.4-1ZM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"
                        />
                    </svg>
                </span>

                <span>
                    {{ item.label }}
                </span>
            </RouterLink>

            <p class="navigation-title account-title">
                TÀI KHOẢN
            </p>

            <RouterLink
                v-for="item in accountItems"
                :key="item.routeName"
                :to="{
                    name: item.routeName,
                }"
                class="navigation-link"
                :class="{
                    active:
                        isRouteActive(
                            item.routeName,
                        ),
                }"
            >
                <span class="navigation-icon">
                    <svg
                        v-if="
                            item.icon ===
                            'profile'
                        "
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm-9 9a9 9 0 0 1 18 0H3Z"
                        />
                    </svg>

                    <svg
                        v-else
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="m10.7 2 .5 2.1a8 8 0 0 1 1.6 0L13.3 2l2.4 1 .9 1.9c.5.3.9.7 1.3 1.1l2-.7 1 2.4-1.8 1.2c.1.5.1 1.1 0 1.6l1.8 1.2-1 2.4-2-.7c-.4.4-.8.8-1.3 1.1l-.9 1.9-2.4 1-.5-2.1a8 8 0 0 1-1.6 0l-.5 2.1-2.4-1-.9-1.9a8 8 0 0 1-1.3-1.1l-2 .7-1-2.4 1.8-1.2a8 8 0 0 1 0-1.6L3.1 7.7l1-2.4 2 .7c.4-.4.8-.8 1.3-1.1L8.3 3l2.4-1ZM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"
                        />
                    </svg>
                </span>

                <span>
                    {{ item.label }}
                </span>
            </RouterLink>
        </nav>

        <footer class="sidebar-footer">
            <RouterLink
                to="/"
                class="storefront-link"
            >
                Xem trang bán hàng
            </RouterLink>
        </footer>
    </aside>
</template>

<style scoped>
.seller-sidebar {
    position: fixed;
    z-index: 100;
    top: 0;
    bottom: 0;
    left: 0;
    display: flex;
    width: 260px;
    flex-direction: column;
    border-right: 1px solid #dfe5e1;
    background: #ffffff;
    transition: transform 0.25s ease;
}

.sidebar-brand {
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 20px;
}

.brand-link {
    display: flex;
    align-items: center;
    gap: 12px;
    color: inherit;
    text-decoration: none;
}

.brand-mark {
    display: flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    background: #15803d;
    color: #ffffff;
    font-size: 20px;
    font-weight: 700;
}

.brand-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.brand-name {
    color: #111827;
    font-size: 17px;
}

.brand-role {
    color: #15803d;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.sidebar-close {
    display: none;
    border: 0;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    font-size: 26px;
}

.seller-summary {
    display: flex;
    align-items: center;
    gap: 11px;
    margin: 18px 16px 8px;
    border: 1px solid #dcfce7;
    background: #f0fdf4;
    padding: 12px;
}

.seller-avatar {
    display: flex;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #15803d;
    color: #ffffff;
    font-weight: 700;
}

.seller-information {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 3px;
}

.seller-information strong,
.seller-information span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.seller-information strong {
    color: #111827;
    font-size: 14px;
}

.seller-information span {
    color: #6b7280;
    font-size: 11px;
}

.sidebar-navigation {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.navigation-title {
    margin: 12px 10px 8px;
    color: #9ca3af;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.9px;
}

.account-title {
    margin-top: 24px;
}

.navigation-link {
    display: flex;
    min-height: 44px;
    align-items: center;
    gap: 12px;
    border-left: 3px solid transparent;
    padding: 0 12px;
    color: #4b5563;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.navigation-link:hover {
    background: #f3f4f6;
    color: #166534;
}

.navigation-link.active {
    border-left-color: #15803d;
    background: #f0fdf4;
    color: #166534;
}

.navigation-icon {
    display: flex;
    width: 20px;
    height: 20px;
    align-items: center;
    justify-content: center;
}

.navigation-icon svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.sidebar-footer {
    border-top: 1px solid #e5e7eb;
    padding: 16px;
}

.storefront-link {
    display: flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid #16a34a;
    color: #15803d;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
}

.storefront-link:hover {
    background: #f0fdf4;
}

@media (max-width: 1024px) {
    .seller-sidebar {
        transform: translateX(-100%);
    }

    .seller-sidebar.sidebar-open {
        transform: translateX(0);
    }

    .sidebar-close {
        display: block;
    }
}

@media (max-width: 680px) {
    .seller-sidebar {
        width: min(290px, 86vw);
    }
}
</style>
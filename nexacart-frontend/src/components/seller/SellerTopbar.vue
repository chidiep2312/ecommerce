<script setup>
import {
    computed,
} from 'vue'
import {
    useRoute,
} from 'vue-router'

import SellerAccountMenu from '@/components/seller/SellerAccountMenu.vue'

const emit = defineEmits([
    'open-sidebar',
])

const route = useRoute()

const pageTitle = computed(() => {
    return (
        route.meta.title ??
        'Seller Center'
    )
})

function openSidebar() {
    emit('open-sidebar')
}
</script>

<template>
    <header class="seller-topbar">
        <div class="topbar-left">
            <button
                type="button"
                class="menu-button"
                aria-label="Mở menu"
                @click="openSidebar"
            >
                <span />
                <span />
                <span />
            </button>

            <div>
                <h1 class="topbar-title">
                    {{ pageTitle }}
                </h1>

                <p class="topbar-subtitle">
                    Quản lý hoạt động bán hàng
                    trên NexaCart
                </p>
            </div>
        </div>

        <div class="topbar-actions">
            <button
                type="button"
                class="notification-button"
                aria-label="Thông báo"
            >
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        d="M12 2a7 7 0 0 0-7 7v4.5L3 17h18l-2-3.5V9a7 7 0 0 0-7-7Zm-3 17a3 3 0 0 0 6 0H9Z"
                    />
                </svg>

                <span class="notification-dot" />
            </button>

            <SellerAccountMenu />
        </div>
    </header>
</template>

<style scoped>
.seller-topbar {
    position: sticky;
    z-index: 50;
    top: 0;
    display: flex;
    min-height: 74px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-bottom: 1px solid #dfe5e1;
    background: #ffffff;
    padding: 0 28px;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.menu-button {
    display: none;
    width: 40px;
    height: 40px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    padding: 9px;
    cursor: pointer;
}

.menu-button span {
    display: block;
    width: 100%;
    height: 2px;
    margin: 4px 0;
    background: #374151;
}

.topbar-title {
    margin: 0;
    color: #111827;
    font-size: 19px;
    font-weight: 600;
}

.topbar-subtitle {
    margin: 4px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.topbar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-button {
    position: relative;
    display: flex;
    width: 40px;
    height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #4b5563;
    cursor: pointer;
}

.notification-button svg {
    width: 19px;
    height: 19px;
    fill: currentColor;
}

.notification-dot {
    position: absolute;
    top: 7px;
    right: 7px;
    width: 7px;
    height: 7px;
    border: 2px solid #ffffff;
    background: #dc2626;
}

@media (max-width: 1024px) {
    .menu-button {
        display: block;
    }
}

@media (max-width: 680px) {
    .seller-topbar {
        min-height: 64px;
        padding: 0 14px;
    }

    .topbar-title {
        font-size: 16px;
    }

    .topbar-subtitle {
        display: none;
    }
}
</style>
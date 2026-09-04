<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SellerAccountMenu from '@/components/seller/SellerAccountMenu.vue'

const emit = defineEmits(['open-sidebar'])

const route = useRoute()

const pageTitle = computed(() => {
    return route.meta?.title ?? 'Tổng quan'
})

function openSidebar() {
    emit('open-sidebar')
}
</script>

<template>
    <header class="seller-topbar">
        <!-- Bên trái: Nút Menu Mobile & Tiêu đề trang -->
        <div class="topbar-left">
            <button
                type="button"
                class="btn-menu-toggle"
                aria-label="Mở menu điều hướng"
                @click="openSidebar"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <div class="title-wrapper">
              
            </div>
        </div>

        <!-- Bên phải: Thông báo & Menu tài khoản -->
        <div class="topbar-right">
            <!-- Nút Thông Báo (Góc vuông, badge vuông) -->
            <button
                type="button"
                class="btn-action-icon"
                aria-label="Xem thông báo"
                title="Thông báo mới"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <!-- Điểm báo đỏ hình vuông sắc nét -->
                <span class="alert-indicator" />
            </button>

            <div class="divider-vertical" />

            <!-- Component Tài khoản người bán -->
            <SellerAccountMenu />
        </div>
    </header>
</template>

<style scoped>
/* ================= Root & Layout ================= */
.seller-topbar {
    --brand-green-primary: #15803d;
    --brand-green-light: #f0fdf4;
    --color-text-main: #111827;
    --color-text-muted: #6b7280;
    --color-border: #e5e7eb;

    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    min-height: 64px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 24px;
    background: #ffffff;
    border-bottom: 1px solid var(--color-border);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    box-sizing: border-box;
}

/* ================= 1. Left Section ================= */
.topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

/* Nút Toggle Mobile (Góc vuông) */
.btn-menu-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    padding: 0;
    background: #ffffff;
    border: 1px solid var(--color-border);
    color: var(--color-text-main);
    cursor: pointer;
    border-radius: 0; /* Vuông góc */
    transition: background-color 0.1s ease, border-color 0.1s ease;
}

.btn-menu-toggle:hover {
    background: #f9fafb;
    border-color: #cbd5e1;
}

.btn-menu-toggle svg {
    width: 18px;
    height: 18px;
}

.title-wrapper {
    display: flex;
    flex-direction: column;
}

.title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.topbar-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-text-main);
    letter-spacing: -0.01em;
}

.portal-badge {
    display: inline-block;
    padding: 1px 6px;
    font-size: 0.625rem;
    font-weight: 700;
    font-family: monospace;
    letter-spacing: 0.05em;
    background: var(--brand-green-light);
    border: 1px solid #bbf7d0;
    color: var(--brand-green-primary);
}

.topbar-subtitle {
    margin: 2px 0 0 0;
    font-size: 0.75rem;
    color: var(--color-text-muted);
}

/* ================= 2. Right Section ================= */
.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Nút Icon Thông Báo (Góc vuông) */
.btn-action-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    padding: 0;
    background: #ffffff;
    border: 1px solid var(--color-border);
    color: var(--color-text-muted);
    cursor: pointer;
    border-radius: 0; /* Không bo góc */
    transition: all 0.1s ease;
}

.btn-action-icon:hover {
    background: #f9fafb;
    color: var(--brand-green-primary);
    border-color: #cbd5e1;
}

.btn-action-icon svg {
    width: 18px;
    height: 18px;
}

/* Chấm đỏ vuông vắn thông báo */
.alert-indicator {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 6px;
    height: 6px;
    background: #dc2626;
    border-radius: 0; /* Vuông góc */
    box-shadow: 0 0 0 2px #ffffff;
}

.divider-vertical {
    width: 1px;
    height: 24px;
    background: var(--color-border);
}

/* ================= 3. Responsive Breakpoints ================= */
@media (max-width: 1024px) {
    .btn-menu-toggle {
        display: flex;
    }
}

@media (max-width: 640px) {
    .seller-topbar {
        min-height: 56px;
        padding: 0 14px;
    }

    .topbar-title {
        font-size: 0.9375rem;
    }

    .portal-badge {
        display: none;
    }

    .topbar-subtitle {
        display: none;
    }
}
</style>
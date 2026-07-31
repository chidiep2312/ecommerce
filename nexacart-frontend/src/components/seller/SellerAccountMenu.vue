<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue'
import {
    RouterLink,
    useRouter,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const menuOpen = ref(false)

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

function toggleMenu() {
    menuOpen.value = !menuOpen.value
}

function closeMenu() {
    menuOpen.value = false
}

function handleDocumentClick(event) {
    if (
        !event.target.closest(
            '.seller-account',
        )
    ) {
        closeMenu()
    }
}

async function logout() {
    closeMenu()

    try {
        await authStore.logout()
    } finally {
        router.push({
            name: 'login',
        })
    }
}

onMounted(() => {
    document.addEventListener(
        'click',
        handleDocumentClick,
    )
})

onBeforeUnmount(() => {
    document.removeEventListener(
        'click',
        handleDocumentClick,
    )
})
</script>

<template>
    <div class="seller-account">
        <button
            type="button"
            class="account-button"
            @click.stop="toggleMenu"
        >
            <span class="account-avatar">
                {{ sellerInitial }}
            </span>

            <span class="account-information">
                <strong>
                    {{
                        seller.name ??
                        'Người bán'
                    }}
                </strong>

                <small>
                    Seller
                </small>
            </span>

            <span class="account-arrow">
                ▾
            </span>
        </button>

        <div
            v-if="menuOpen"
            class="account-dropdown"
        >
            <RouterLink
                :to="{
                    name: 'seller-profile',
                }"
                class="dropdown-item"
                @click="closeMenu"
            >
                Hồ sơ cửa hàng
            </RouterLink>

            <RouterLink
                :to="{
                    name: 'seller-settings',
                }"
                class="dropdown-item"
                @click="closeMenu"
            >
                Cài đặt tài khoản
            </RouterLink>

            <button
                type="button"
                class="
                    dropdown-item
                    logout-button
                "
                @click="logout"
            >
                Đăng xuất
            </button>
        </div>
    </div>
</template>

<style scoped>
.seller-account {
    position: relative;
}

.account-button {
    display: flex;
    min-height: 46px;
    align-items: center;
    gap: 10px;
    border: 0;
    background: transparent;
    padding: 3px 5px;
    cursor: pointer;
    font: inherit;
}

.account-avatar {
    display: flex;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #15803d;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
}

.account-information {
    display: flex;
    min-width: 120px;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.account-information strong {
    max-width: 160px;
    overflow: hidden;
    color: #111827;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-information small {
    color: #15803d;
    font-size: 11px;
}

.account-arrow {
    color: #9ca3af;
    font-size: 12px;
}

.account-dropdown {
    position: absolute;
    z-index: 80;
    top: calc(100% + 8px);
    right: 0;
    width: 210px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    box-shadow:
        0 10px 24px
        rgb(0 0 0 / 8%);
}

.dropdown-item {
    display: flex;
    width: 100%;
    min-height: 42px;
    align-items: center;
    border: 0;
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
    padding: 0 14px;
    color: #374151;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    text-align: left;
    text-decoration: none;
}

.dropdown-item:hover {
    background: #f9fafb;
    color: #15803d;
}

.logout-button {
    color: #dc2626;
}

@media (max-width: 680px) {
    .account-information,
    .account-arrow {
        display: none;
    }
}
</style>
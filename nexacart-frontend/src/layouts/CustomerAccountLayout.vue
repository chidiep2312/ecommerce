<script setup>
import {
    computed,
    onMounted,
} from 'vue'

import CustomerSidebar
    from '@/components/customer/CustomerSidebar.vue'

import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const user = computed(() => {
    return authStore.user
})

async function ensureCurrentUser() {
    if (
        authStore.token &&
        !authStore.user
    ) {
        try {
            await authStore
                .fetchCurrentUser()
        } catch (error) {
            console.error(
                'Không thể tải thông tin tài khoản:',
                error,
            )
        }
    }
}

onMounted(() => {
    ensureCurrentUser()
})
</script>

<template>
    <div class="account-page">
        <div class="account-container">
            <header class="account-heading">
                <p>TÀI KHOẢN</p>

                <h1>Tài khoản của tôi</h1>
            </header>

            <div class="account-layout">
                <CustomerSidebar
                    :user="user"
                />

                <main class="account-content">
                    <RouterView />
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.account-page {
    min-height: calc(100vh - 70px);
    background: #f5f7f6;
    color: #18221c;
    font-family: Roboto, Arial, sans-serif;
}

.account-container {
    width: min(
        calc(100% - 40px),
        1320px
    );
    margin: 0 auto;
    padding: 34px 0 60px;
}

.account-heading {
    margin-bottom: 22px;
}

.account-heading p {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.13em;
}

.account-heading h1 {
    margin: 0;
    font-size: 28px;
}

.account-layout {
    display: grid;
    grid-template-columns:
        245px minmax(0, 1fr);
    gap: 22px;
    align-items: start;
}

.account-content {
    min-width: 0;
}

@media (max-width: 820px) {
    .account-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .account-container {
        width: calc(100% - 28px);
        padding-top: 24px;
    }
}
</style>
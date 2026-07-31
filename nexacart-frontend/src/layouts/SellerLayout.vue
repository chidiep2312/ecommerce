<script setup>
import {
    ref,
    watch,
} from 'vue'
import {
    RouterView,
    useRoute,
} from 'vue-router'

import SellerSidebar from '@/components/seller/SellerSidebar.vue'
import SellerTopbar from '@/components/seller/SellerTopbar.vue'

const route = useRoute()

const sidebarOpen = ref(false)

function openSidebar() {
    sidebarOpen.value = true
}

function closeSidebar() {
    sidebarOpen.value = false
}

watch(
    () => route.fullPath,
    () => {
        closeSidebar()
    },
)
</script>

<template>
    <div class="seller-layout">
        <div
            v-if="sidebarOpen"
            class="sidebar-overlay"
            @click="closeSidebar"
        />

        <SellerSidebar
            :open="sidebarOpen"
            @close="closeSidebar"
        />

        <div class="seller-main">
            <SellerTopbar
                @open-sidebar="openSidebar"
            />

            <main class="seller-content">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<style scoped>
.seller-layout {
    min-height: 100vh;
    background: #f4f6f5;
    color: #111827;
    font-family:
        Roboto,
        Arial,
        sans-serif;
}

.seller-main {
    min-height: 100vh;
    margin-left: 260px;
}

.seller-content {
    padding: 26px 28px 40px;
}

.sidebar-overlay {
    display: none;
}

@media (max-width: 1024px) {
    .seller-main {
        margin-left: 0;
    }

    .sidebar-overlay {
        position: fixed;
        z-index: 90;
        inset: 0;
        display: block;
        background: rgb(17 24 39 / 48%);
    }
}

@media (max-width: 680px) {
    .seller-content {
        padding: 18px 14px 30px;
    }
}
</style>
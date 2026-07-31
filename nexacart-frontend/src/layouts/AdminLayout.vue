<script setup>
import { ref } from 'vue'

import AdminHeader from '@/components/admin/AdminHeader.vue'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'

const isSidebarCollapsed = ref(false)
const isMobileSidebarOpen = ref(false)

function toggleSidebar() {
    isSidebarCollapsed.value =
        !isSidebarCollapsed.value
}

function toggleMobileSidebar() {
    isMobileSidebarOpen.value =
        !isMobileSidebarOpen.value
}

function closeMobileSidebar() {
    isMobileSidebarOpen.value = false
}
</script>

<template>
    <div
        class="admin-layout"
        :class="{
            'admin-layout--collapsed':
                isSidebarCollapsed,
        }"
    >
        <AdminSidebar
            :collapsed="isSidebarCollapsed"
            :mobile-open="isMobileSidebarOpen"
            @close-mobile="closeMobileSidebar"
        />

        <div
            v-if="isMobileSidebarOpen"
            class="admin-layout__overlay"
            @click="closeMobileSidebar"
        />

        <div class="admin-layout__main">
            <AdminHeader
                @toggle-sidebar="toggleSidebar"
                @toggle-mobile-sidebar="
                    toggleMobileSidebar
                "
            />

            <main class="admin-layout__content">
                <div class="admin-container">
                    <RouterView />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.admin-layout {
    min-height: 100vh;
    background: var(--admin-background);
}

.admin-layout__main {
    min-height: 100vh;
    margin-left: var(--admin-sidebar-width);
    transition: margin-left 0.2s ease;
}

.admin-layout--collapsed
    .admin-layout__main {
    margin-left:
        var(--admin-sidebar-collapsed-width);
}

.admin-layout__content {
    padding: 24px;
}

.admin-layout__overlay {
    display: none;
}

@media (max-width: 900px) {
    .admin-layout__main,
    .admin-layout--collapsed
        .admin-layout__main {
        margin-left: 0;
    }

    .admin-layout__content {
        padding: 16px;
    }

    .admin-layout__overlay {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: block;
        background: rgb(0 0 0 / 45%);
    }
}
</style>
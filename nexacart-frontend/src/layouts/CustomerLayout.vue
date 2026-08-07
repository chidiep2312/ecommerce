<script setup>
import { RouterView } from 'vue-router'

import CustomerFooter from '@/components/customer/CustomerFooter.vue'
import CustomerHeader from '@/components/customer/CustomerHeader.vue'
import {
    useCartStore,
} from '@/stores/cart'
import {
    onMounted
} from 'vue'
const cartStore = useCartStore()
onMounted(async () => {
    try {
        await cartStore.fetchCart()
    } catch (error) {
        console.error(
            'Không thể đồng bộ giỏ hàng:',
            error,
        )
    }
})
</script>

<template>
    <div class="customer-layout">
        <CustomerHeader />

        <main class="customer-layout__main">
            <div class="customer-container">
                <RouterView />
            </div>
        </main>

        <CustomerFooter />
    </div>
</template>

<style scoped>
.customer-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: var(--color-page);
}

.customer-layout__main {
    flex: 1;
    padding-block: 36px;
}

.customer-container {
    width: min(
        calc(100% - 48px),
        var(--content-max-width)
    );
    margin-inline: auto;
}

@media (max-width: 860px) {
    .customer-layout__main {
        padding-block: 24px;
    }

    .customer-container {
        width: min(calc(100% - 32px), 100%);
    }
}
</style>
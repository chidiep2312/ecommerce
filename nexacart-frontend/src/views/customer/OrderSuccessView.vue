<script setup>
import {
    Check,
    PackageCheck,
    ShoppingBag,
} from '@lucide/vue'

import { useRoute } from 'vue-router'

const route = useRoute()

const orderCode =
    route.params.orderCode
</script>

<template>
    <section class="order-success">
        <div class="order-success__icon">
            <Check :size="34" />
        </div>

        <p class="order-success__eyebrow">
            Đặt hàng thành công
        </p>

        <h1>
            Cảm ơn bạn đã đặt hàng.
        </h1>

        <p class="order-success__description">
            Đơn hàng
            <strong>
                #{{ orderCode }}
            </strong>
            đã được tiếp nhận. Bạn có thể theo
            dõi quá trình xử lý trong trang đơn
            hàng của mình.
        </p>

        <div class="order-success__actions">
            <RouterLink
                :to="{
                    name: 'customer-orders',
                }"
                class="order-success__primary"
            >
                <PackageCheck :size="18" />

                Xem đơn hàng
            </RouterLink>

            <RouterLink
                to="/products"
                class="order-success__secondary"
            >
                <ShoppingBag :size="18" />

                Tiếp tục mua sắm
            </RouterLink>
        </div>
    </section>
</template>

<style scoped>
.order-success {
    display: grid;
    place-items: center;
    min-height: 560px;
    padding: 48px 24px;
    text-align: center;
    background:
        linear-gradient(
            145deg,
            var(--color-primary-50),
            var(--color-white)
        );
    border: 1px solid var(--color-border);
    border-radius: 22px;
}

.order-success__icon {
    display: grid;
    place-items: center;
    width: 78px;
    height: 78px;
    margin-bottom: 24px;
    color: var(--color-white);
    background: var(--color-success);
    border-radius: 50%;
    box-shadow:
        0 12px 28px rgb(22 131 93 / 20%);
}

.order-success__eyebrow {
    color: var(--color-success);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.order-success h1 {
    margin-top: 12px;
    font-size: 36px;
}

.order-success__description {
    max-width: 570px;
    margin-top: 16px;
    font-size: 14px;
    line-height: 1.8;
}

.order-success__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
    margin-top: 28px;
}

.order-success__primary,
.order-success__secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 46px;
    padding-inline: 18px;
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
}

.order-success__primary {
    color: var(--color-white);
    background: var(--color-primary-700);
}

.order-success__secondary {
    color: var(--color-primary-800);
    background: var(--color-white);
    border: 1px solid var(--color-primary-200);
}

@media (max-width: 560px) {
    .order-success {
        min-height: 480px;
    }

    .order-success h1 {
        font-size: 30px;
    }

    .order-success__actions {
        width: 100%;
        flex-direction: column;
    }
}
</style>
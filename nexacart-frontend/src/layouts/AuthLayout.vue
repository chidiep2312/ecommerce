<script setup>
import {
    ShieldCheck,
    ShoppingBag,
    Truck,
} from '@lucide/vue'
</script>

<template>
    <main class="auth-layout">
        <section class="auth-layout__brand">
            <RouterLink
                to="/"
                class="auth-layout__logo"
            >
                <span>
                    N
                </span>

                <strong>
                    NexaCart
                </strong>
            </RouterLink>

            <div class="auth-layout__content">
                <p class="auth-layout__eyebrow">
                    Mua sắm thông minh
                </p>

                <h1>
                    Một tài khoản cho toàn bộ trải nghiệm mua sắm.
                </h1>

                <p>
                    Quản lý đơn hàng, địa chỉ nhận hàng,
                    sản phẩm yêu thích và theo dõi quá
                    trình giao hàng trên NexaCart.
                </p>

                <div class="auth-layout__benefits">
                    <article>
                        <ShieldCheck :size="21" />

                        <div>
                            <strong>
                                Tài khoản an toàn
                            </strong>

                            <span>
                                Thông tin đăng nhập được bảo vệ.
                            </span>
                        </div>
                    </article>

                    <article>
                        <ShoppingBag :size="21" />

                        <div>
                            <strong>
                                Quản lý đơn hàng
                            </strong>

                            <span>
                                Theo dõi toàn bộ lịch sử mua sắm.
                            </span>
                        </div>
                    </article>

                    <article>
                        <Truck :size="21" />

                        <div>
                            <strong>
                                Theo dõi giao hàng
                            </strong>

                            <span>
                                Cập nhật trạng thái đơn hàng dễ dàng.
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="auth-layout__form">
            <RouterView />
        </section>
    </main>
</template>

<style scoped>
.auth-layout {
    display: grid;
    grid-template-columns:
        minmax(420px, 0.95fr)
        minmax(480px, 1.05fr);
    min-height: 100vh;
    background: var(--color-white);
}

.auth-layout__brand {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 44px 52px;
    overflow: hidden;
    color: var(--color-white);
    background:
        radial-gradient(
            circle at 20% 15%,
            rgb(255 255 255 / 16%),
            transparent 32%
        ),
        linear-gradient(
            145deg,
            var(--color-primary-800),
            var(--color-primary-600)
        );
}

.auth-layout__brand::after {
    position: absolute;
    right: -120px;
    bottom: -150px;
    width: 360px;
    height: 360px;
    background: rgb(255 255 255 / 8%);
    border-radius: 50%;
    content: '';
}

.auth-layout__logo {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 11px;
    width: fit-content;
    color: var(--color-white);
}

.auth-layout__logo > span {
    display: grid;
    place-items: center;
    width: 39px;
    height: 39px;
    color: var(--color-primary-800);
    background: var(--color-white);
    border-radius: 12px;
    font-size: 19px;
    font-weight: 800;
}

.auth-layout__logo strong {
    font-size: 20px;
}

.auth-layout__content {
    position: relative;
    z-index: 1;
    display: grid;
    align-content: center;
    flex: 1;
    max-width: 570px;
}

.auth-layout__eyebrow {
    margin-bottom: 18px;
    color: rgb(255 255 255 / 72%);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.auth-layout__content h1 {
    color: var(--color-white);
    font-size: clamp(38px, 5vw, 58px);
    line-height: 1.08;
    letter-spacing: -0.05em;
}

.auth-layout__content > p:not(.auth-layout__eyebrow) {
    max-width: 530px;
    margin-top: 22px;
    color: rgb(255 255 255 / 76%);
    font-size: 15px;
    line-height: 1.8;
}

.auth-layout__benefits {
    display: grid;
    gap: 14px;
    margin-top: 38px;
}

.auth-layout__benefits article {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 13px;
    padding: 14px 16px;
    background: rgb(255 255 255 / 9%);
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: var(--radius-lg);
    backdrop-filter: blur(12px);
}

.auth-layout__benefits strong {
    display: block;
    font-size: 13px;
}

.auth-layout__benefits span {
    display: block;
    margin-top: 4px;
    color: rgb(255 255 255 / 70%);
    font-size: 11px;
}

.auth-layout__form {
    display: grid;
    place-items: center;
    min-height: 100vh;
    padding: 40px 24px;
    background: var(--color-background);
}

@media (max-width: 940px) {
    .auth-layout {
        grid-template-columns: 1fr;
    }

    .auth-layout__brand {
        display: none;
    }
}
</style>
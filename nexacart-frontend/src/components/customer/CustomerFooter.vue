<script setup>
const currentYear = new Date().getFullYear()
</script>

<template>
    <footer class="customer-footer">
        <div class="customer-container footer-main">
            <section class="footer-brand">
                <RouterLink
                    to="/"
                    class="footer-logo"
                >
                    <span class="footer-logo__mark">
                        N
                    </span>

                    <span class="footer-logo__text">
                        NexaCart
                    </span>
                </RouterLink>

                <p class="footer-brand__description">
                    Nền tảng mua sắm trực tuyến tập trung
                    vào trải nghiệm rõ ràng, thuận tiện và
                    đáng tin cậy.
                </p>
            </section>

            <section class="footer-column">
                <h2 class="footer-column__title">
                    Mua sắm
                </h2>

                <nav class="footer-column__links">
                    <RouterLink to="/products">
                        Tất cả sản phẩm
                    </RouterLink>

                    <RouterLink
                        :to="{
                            name: 'products',
                            query: {
                                sort: 'newest',
                            },
                        }"
                    >
                        Sản phẩm mới
                    </RouterLink>

                    <RouterLink
                        :to="{
                            name: 'products',
                            query: {
                                promotion: 1,
                            },
                        }"
                    >
                        Khuyến mãi
                    </RouterLink>
                </nav>
            </section>

            <section class="footer-column">
                <h2 class="footer-column__title">
                    Tài khoản
                </h2>

                <nav class="footer-column__links">
                    <RouterLink to="/profile">
                        Thông tin cá nhân
                    </RouterLink>

                    <RouterLink to="/orders">
                        Đơn hàng
                    </RouterLink>

                    <RouterLink to="/wishlist">
                        Danh sách yêu thích
                    </RouterLink>
                </nav>
            </section>

            <section class="footer-column">
                <h2 class="footer-column__title">
                    Hỗ trợ
                </h2>

                <nav class="footer-column__links">
                    <a href="#">
                        Trung tâm trợ giúp
                    </a>

                    <a href="#">
                        Chính sách đổi trả
                    </a>

                    <a href="#">
                        Điều khoản sử dụng
                    </a>

                    <a href="#">
                        Chính sách bảo mật
                    </a>
                </nav>
            </section>
        </div>

        <div class="footer-bottom">
            <div class="customer-container footer-bottom__content">
                <p>
                    © {{ currentYear }} NexaCart.
                    Bảo lưu mọi quyền.
                </p>

                <p>
                    Giao diện được thiết kế cho trải nghiệm
                    thương mại điện tử hiện đại.
                </p>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.customer-footer {
    margin-top: 80px;
    color: rgb(255 255 255 / 72%);
    background: #015828;
}

.customer-container {
    width: min(
        calc(100% - 48px),
        var(--content-max-width)
    );
    margin-inline: auto;
}

.footer-main {
    display: grid;
    grid-template-columns:
        minmax(260px, 1.5fr)
        repeat(3, minmax(140px, 1fr));
    gap: 64px;
    padding-block: 64px;
}

.footer-brand {
    max-width: 380px;
}

.footer-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.footer-logo__mark {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    color: var(--color-white);
    background: var(--color-primary-600);
    border-radius: 10px;
    font-weight: 700;
}

.footer-logo__text {
    color: var(--color-white);
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.03em;
}

.footer-brand__description {
    margin-top: 20px;
    color: inherit;
    font-size: 14px;
    line-height: 1.75;
}

.footer-column__title {
    margin-bottom: 20px;
    color: var(--color-white);
    font-size: 14px;
    font-weight: 600;
}

.footer-column__links {
    display: grid;
    gap: 13px;
}

.footer-column__links a {
    width: fit-content;
    color: rgb(255 255 255 / 62%);
    font-size: 13px;
    transition: color var(--transition-fast);
}

.footer-column__links a:hover {
    color: var(--color-white);
}

.footer-bottom {
    border-top: 1px solid rgb(255 255 255 / 10%);
}

.footer-bottom__content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 72px;
}

.footer-bottom__content p {
    color: rgb(255 255 255 / 45%);
    font-size: 12px;
}

@media (max-width: 900px) {
    .footer-main {
        grid-template-columns: repeat(2, 1fr);
        gap: 44px;
    }

    .footer-brand {
        grid-column: 1 / -1;
    }
}

@media (max-width: 560px) {
    .customer-container {
        width: min(calc(100% - 32px), 100%);
    }

    .footer-main {
        grid-template-columns: 1fr;
        gap: 34px;
        padding-block: 48px;
    }

    .footer-brand {
        grid-column: auto;
    }

    .footer-bottom__content {
        align-items: flex-start;
        flex-direction: column;
        justify-content: center;
        gap: 6px;
        padding-block: 18px;
    }
}
</style>
<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
    <main class="not-found">
        <p class="not-found__code">
            404
        </p>

        <h1>Không tìm thấy trang</h1>

        <p class="not-found__description">
            Đường dẫn bạn truy cập không tồn tại
            hoặc đã được thay đổi.
        </p>

        <RouterLink
            to="/"
            class="not-found__link"
        >
            Trở về trang chủ
        </RouterLink>
    </main>
</template>

<style scoped>
.not-found {
    display: grid;
    place-items: center;
    align-content: center;
    min-height: 100vh;
    padding: 24px;
    text-align: center;
}

.not-found__code {
    margin-bottom: 12px;
    color: var(--color-primary-600);
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.not-found__description {
    max-width: 480px;
    margin-top: 12px;
}

.not-found__link {
    margin-top: 28px;
    padding: 11px 18px;
    color: var(--color-white);
    background: var(--color-primary-600);
    border-radius: var(--radius-md);
    font-size: 14px;
    font-weight: 600;
}
</style>
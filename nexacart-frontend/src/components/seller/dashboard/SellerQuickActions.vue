<script setup>
const emit = defineEmits([
    'create-product',
    'view-orders',
    'view-products',
])
</script>

<template>
    <article class="panel">
        <header class="panel-header">
            <div>
                <h2 class="panel-title">
                    Thao tác nhanh
                </h2>

                <p class="panel-description">
                    Các chức năng thường dùng
                </p>
            </div>
        </header>

        <div class="quick-actions">
            <button
                type="button"
                class="quick-action"
                @click="
                    emit('create-product')
                "
            >
                <span class="quick-code">
                    +
                </span>

                <span class="quick-content">
                    <strong>
                        Thêm sản phẩm
                    </strong>

                    <small>
                        Tạo sản phẩm mới
                    </small>
                </span>
            </button>

            <button
                type="button"
                class="quick-action"
                @click="emit('view-orders')"
            >
                <span class="quick-code">
                    ĐH
                </span>

                <span class="quick-content">
                    <strong>
                        Xử lý đơn hàng
                    </strong>

                    <small>
                        Xác nhận đơn mới
                    </small>
                </span>
            </button>

            <button
                type="button"
                class="quick-action"
                @click="
                    emit('view-products')
                "
            >
                <span class="quick-code">
                    SP
                </span>

                <span class="quick-content">
                    <strong>
                        Quản lý sản phẩm
                    </strong>

                    <small>
                        Giá bán và tồn kho
                    </small>
                </span>
            </button>
        </div>
    </article>
</template>

<style scoped>
.panel {
    border: 1px solid #e1e7e3;
    background: #ffffff;
}

.panel-header {
    border-bottom: 1px solid #e5e7eb;
    padding: 17px 19px;
}

.panel-title {
    margin: 0;
    color: #111827;
    font-size: 16px;
    font-weight: 600;
}

.panel-description {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    padding: 8px 18px 18px;
}

.quick-action {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 12px;
    border: 0;
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
    padding: 13px 0;
    cursor: pointer;
    font: inherit;
    text-align: left;
}

.quick-action:hover strong {
    color: #15803d;
}

.quick-code {
    display: flex;
    width: 35px;
    height: 35px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #f0fdf4;
    color: #15803d;
    font-size: 12px;
    font-weight: 700;
}

.quick-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.quick-content strong {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.quick-content small {
    color: #9ca3af;
    font-size: 11px;
}
</style>
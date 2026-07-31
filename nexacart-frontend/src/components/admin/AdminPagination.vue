<script setup>
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue'

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1,
    },
    lastPage: {
        type: Number,
        default: 1,
    },
    from: {
        type: Number,
        default: 0,
    },
    to: {
        type: Number,
        default: 0,
    },
    total: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits(['change'])

function changePage(page) {
    if (
        page < 1 ||
        page > props.lastPage ||
        page === props.currentPage
    ) {
        return
    }

    emit('change', page)
}
</script>

<template>
    <div class="admin-pagination">
        <p class="admin-pagination__summary">
            Hiển thị
            <strong>{{ from }}</strong>
            đến
            <strong>{{ to }}</strong>
            trong
            <strong>{{ total }}</strong>
            kết quả
        </p>

        <div class="admin-pagination__controls">
            <button
                type="button"
                :disabled="currentPage <= 1"
                aria-label="Trang trước"
                @click="changePage(currentPage - 1)"
            >
                <ChevronLeft :size="18" />
            </button>

            <button
                v-for="page in lastPage"
                :key="page"
                type="button"
                :class="{
                    'admin-pagination__page--active':
                        page === currentPage,
                }"
                @click="changePage(page)"
            >
                {{ page }}
            </button>

            <button
                type="button"
                :disabled="currentPage >= lastPage"
                aria-label="Trang sau"
                @click="changePage(currentPage + 1)"
            >
                <ChevronRight :size="18" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.admin-pagination {
    min-height: 66px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid var(--admin-border-light);
    border-top: 0;
}

.admin-pagination__summary {
    margin: 0;
    color: var(--admin-text-secondary);
    font-size: 13px;
}

.admin-pagination__controls {
    display: flex;
    align-items: center;
    gap: 4px;
}

.admin-pagination__controls button {
    min-width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    padding: 0 10px;
    border: 1px solid var(--admin-border);
    background: #ffffff;
    color: var(--admin-text-primary);
}

.admin-pagination__controls button:hover:not(:disabled) {
    border-color: var(--admin-primary-600);
    background: var(--admin-primary-50);
    color: var(--admin-primary-700);
}

.admin-pagination__controls button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.admin-pagination__controls
    .admin-pagination__page--active {
    border-color: var(--admin-primary-700);
    background: var(--admin-primary-700);
    color: #ffffff;
}

@media (max-width: 650px) {
    .admin-pagination {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-pagination__controls {
        max-width: 100%;
        overflow-x: auto;
    }
}
</style>
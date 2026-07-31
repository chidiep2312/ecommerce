<script setup>
import {
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue'

import { computed } from 'vue'

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },

    lastPage: {
        type: Number,
        required: true,
    },
})

const emit = defineEmits(['change'])

const visiblePages = computed(() => {
    const pages = []
    const range = 2

    const start = Math.max(
        1,
        props.currentPage - range,
    )

    const end = Math.min(
        props.lastPage,
        props.currentPage + range,
    )

    for (let page = start; page <= end; page++) {
        pages.push(page)
    }

    return pages
})

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
    <nav
        v-if="lastPage > 1"
        class="pagination"
        aria-label="Phân trang"
    >
        <button
            type="button"
            class="pagination__button pagination__button--arrow"
            :disabled="currentPage === 1"
            aria-label="Trang trước"
            @click="changePage(currentPage - 1)"
        >
            <ChevronLeft :size="18" />
        </button>

        <button
            v-if="visiblePages[0] > 1"
            type="button"
            class="pagination__button"
            @click="changePage(1)"
        >
            1
        </button>

        <span
            v-if="visiblePages[0] > 2"
            class="pagination__ellipsis"
        >
            ...
        </span>

        <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="pagination__button"
            :class="{
                'pagination__button--active':
                    page === currentPage,
            }"
            :aria-current="
                page === currentPage
                    ? 'page'
                    : undefined
            "
            @click="changePage(page)"
        >
            {{ page }}
        </button>

        <span
            v-if="
                visiblePages.at(-1) <
                lastPage - 1
            "
            class="pagination__ellipsis"
        >
            ...
        </span>

        <button
            v-if="
                visiblePages.at(-1) < lastPage
            "
            type="button"
            class="pagination__button"
            @click="changePage(lastPage)"
        >
            {{ lastPage }}
        </button>

        <button
            type="button"
            class="pagination__button pagination__button--arrow"
            :disabled="currentPage === lastPage"
            aria-label="Trang sau"
            @click="changePage(currentPage + 1)"
        >
            <ChevronRight :size="18" />
        </button>
    </nav>
</template>

<style scoped>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.pagination__button {
    display: grid;
    place-items: center;
    min-width: 40px;
    height: 40px;
    padding-inline: 10px;
    color: var(--color-text-secondary);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 13px;
    font-weight: 600;
    transition:
        color var(--transition-fast),
        background-color var(--transition-fast),
        border-color var(--transition-fast);
}

.pagination__button:hover:not(:disabled) {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
    border-color: var(--color-primary-200);
}

.pagination__button--active {
    color: var(--color-white);
    background: var(--color-primary-700);
    border-color: var(--color-primary-700);
}

.pagination__button--active:hover:not(:disabled) {
    color: var(--color-white);
    background: var(--color-primary-800);
}

.pagination__button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.pagination__ellipsis {
    display: grid;
    place-items: center;
    min-width: 28px;
    color: var(--color-text-muted);
    font-size: 13px;
}

@media (max-width: 520px) {
    .pagination {
        gap: 5px;
    }

    .pagination__button {
        min-width: 36px;
        height: 36px;
        padding-inline: 8px;
    }
}
</style>
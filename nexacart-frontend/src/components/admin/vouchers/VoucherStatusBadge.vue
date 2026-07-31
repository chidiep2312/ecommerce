<script setup>
import { computed } from 'vue'

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
})

const statusConfig = computed(() => {
    const configs = {
        active: {
            label: 'Đang hoạt động',
            className: 'status-badge--active',
        },

        inactive: {
            label: 'Đã tắt',
            className: 'status-badge--inactive',
        },

        upcoming: {
            label: 'Sắp diễn ra',
            className: 'status-badge--upcoming',
        },

        expired: {
            label: 'Đã hết hạn',
            className: 'status-badge--expired',
        },

        exhausted: {
            label: 'Đã hết lượt',
            className: 'status-badge--exhausted',
        },

        usage_exhausted: {
            label: 'Đã hết lượt',
            className: 'status-badge--exhausted',
        },
    }

    return configs[props.status] ?? {
        label: props.status,
        className: 'status-badge--default',
    }
})
</script>

<template>
    <span
        class="status-badge"
        :class="statusConfig.className"
    >
        {{ statusConfig.label }}
    </span>
</template>

<style scoped>
.status-badge {
    display: inline-block;
    padding: 5px 9px;
    border: 1px solid transparent;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge--active {
    color: #166534;
    background: #dcfce7;
    border-color: #86efac;
}

.status-badge--inactive {
    color: #475569;
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.status-badge--upcoming {
    color: #854d0e;
    background: #fef9c3;
    border-color: #fde047;
}

.status-badge--expired,
.status-badge--exhausted {
    color: #991b1b;
    background: #fee2e2;
    border-color: #fca5a5;
}

.status-badge--default {
    color: #334155;
    background: #f8fafc;
    border-color: #cbd5e1;
}
</style>
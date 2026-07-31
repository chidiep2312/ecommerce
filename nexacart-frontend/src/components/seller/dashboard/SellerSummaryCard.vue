<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },

    value: {
        type: [
            String,
            Number,
        ],
        default: 0,
    },

    description: {
        type: String,
        default: '',
    },

    type: {
        type: String,
        default: 'default',
    },

    code: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <article class="summary-card">
        <div
            class="summary-icon"
            :class="`icon-${type}`"
        >
            {{ code }}
        </div>

        <div class="summary-content">
            <span class="summary-label">
                {{ label }}
            </span>

            <strong class="summary-value">
                {{ value }}
            </strong>

            <span class="summary-description">
                {{ description }}
            </span>
        </div>
    </article>
</template>

<style scoped>
.summary-card {
    display: flex;
    min-height: 132px;
    align-items: flex-start;
    gap: 14px;
    border: 1px solid #e1e7e3;
    background: #ffffff;
    padding: 20px;
}

.summary-icon {
    display: flex;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #374151;
    font-size: 12px;
    font-weight: 700;
}

.icon-revenue {
    background: #dcfce7;
    color: #166534;
    font-size: 19px;
}

.icon-orders {
    background: #ecfdf5;
    color: #047857;
}

.icon-pending {
    background: #fffbeb;
    color: #b45309;
}

.icon-products {
    background: #f3f4f6;
    color: #374151;
}

.summary-content {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.summary-label {
    color: #6b7280;
    font-size: 12px;
    font-weight: 500;
}

.summary-value {
    margin-top: 9px;
    overflow: hidden;
    color: #111827;
    font-size: 23px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.summary-description {
    margin-top: 8px;
    color: #9ca3af;
    font-size: 11px;
}
</style>
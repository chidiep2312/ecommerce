<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    trend: {
        type: String,
        default: '',
    },
    icon: {
        type: [Object, Function],
        required: true,
    },
})
</script>

<template>
    <article class="admin-stat-card">
        <div class="admin-stat-card__header">
            <div>
                <span class="admin-stat-card__title">
                    {{ title }}
                </span>

                <strong class="admin-stat-card__value">
                    {{ value }}
                </strong>
            </div>

            <div class="admin-stat-card__icon">
                <component
                    :is="icon"
                    :size="23"
                />
            </div>
        </div>

        <div class="admin-stat-card__footer">
            <span
                v-if="trend"
                class="admin-stat-card__trend"
            >
                {{ trend }}
            </span>

            <span>
                {{ description }}
            </span>
        </div>
    </article>
</template>

<style scoped>
.admin-stat-card {
    padding: 20px;
    background: #ffffff;
    border: 1px solid
        var(--admin-border-light);
    border-top: 3px solid
        var(--admin-primary-600);
}

.admin-stat-card__header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.admin-stat-card__title {
    display: block;
    margin-bottom: 10px;
    color: var(--admin-text-secondary);
    font-size: 13px;
    font-weight: 500;
}

.admin-stat-card__value {
    display: block;
    color: var(--admin-text-primary);
    font-size: 29px;
    line-height: 1.1;
}

.admin-stat-card__icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    background: var(--admin-primary-50);
    color: var(--admin-primary-700);
}

.admin-stat-card__footer {
    display: flex;
    gap: 8px;
    margin-top: 18px;
    color: var(--admin-text-secondary);
    font-size: 12px;
}

.admin-stat-card__trend {
    color: var(--admin-primary-700);
    font-weight: 600;
}
</style>
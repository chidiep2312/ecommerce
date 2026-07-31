<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="admin-page-header">
        <div>
            <h1>
                {{ title }}
            </h1>

            <p v-if="description">
                {{ description }}
            </p>
        </div>

        <div class="admin-page-header__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
.admin-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.admin-page-header h1 {
    margin: 0 0 5px;
    color: var(--admin-text-primary);
    font-size: 25px;
    font-weight: 700;
}

.admin-page-header p {
    margin: 0;
    color: var(--admin-text-secondary);
}

.admin-page-header__actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

@media (max-width: 650px) {
    .admin-page-header {
        flex-direction: column;
    }

    .admin-page-header__actions {
        width: 100%;
    }
}
</style>
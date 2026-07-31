<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },

    variant: {
        type: String,
        default: 'primary',
        validator: (value) =>
            ['primary', 'secondary', 'ghost'].includes(
                value,
            ),
    },

    loading: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    block: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <button
        :type="type"
        class="button"
        :class="[
            `button--${variant}`,
            {
                'button--block': block,
            },
        ]"
        :disabled="disabled || loading"
    >
        <span
            v-if="loading"
            class="button__loader"
            aria-hidden="true"
        />

        <span>
            <slot />
        </span>
    </button>
</template>

<style scoped>
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 44px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    font-size: 14px;
    font-weight: 600;
    transition:
        background-color var(--transition-fast),
        border-color var(--transition-fast),
        color var(--transition-fast),
        transform var(--transition-fast);
}

.button:active:not(:disabled) {
    transform: translateY(1px);
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.button--primary {
    color: var(--color-white);
    background: var(--color-primary-600);
}

.button--primary:hover:not(:disabled) {
    background: var(--color-primary-700);
}

.button--secondary {
    color: var(--color-text-primary);
    background: var(--color-white);
    border-color: var(--color-border-strong);
}

.button--secondary:hover:not(:disabled) {
    background: var(--color-gray-50);
}

.button--ghost {
    color: var(--color-primary-700);
    background: transparent;
}

.button--ghost:hover:not(:disabled) {
    background: var(--color-primary-50);
}

.button--block {
    width: 100%;
}

.button__loader {
    width: 16px;
    height: 16px;
    border: 2px solid rgb(255 255 255 / 40%);
    border-top-color: currentColor;
    border-radius: 50%;
    animation: rotate 700ms linear infinite;
}

@keyframes rotate {
    to {
        transform: rotate(360deg);
    }
}
</style>
<script setup>
const model = defineModel({
    type: [Boolean, Array],
    default: false,
})

defineProps({
    value: {
        type: [String, Number, Boolean],
        default: undefined,
    },

    label: {
        type: String,
        required: true,
    },

    name: {
        type: String,
        required: true,
    },

    count: {
        type: Number,
        default: null,
    },

    disabled: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <label
        class="checkbox"
        :class="{
            'checkbox--disabled': disabled,
        }"
    >
        <input
            v-model="model"
            :name="name"
            type="checkbox"
            :value="value"
            :disabled="disabled"
            class="checkbox__input"
        />

        <span
            class="checkbox__control"
            aria-hidden="true"
        >
            <svg
                viewBox="0 0 12 10"
                fill="none"
            >
                <path
                    d="M1 5L4.2 8L11 1"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>

        <span class="checkbox__label">
            {{ label }}
        </span>

        <span
            v-if="count !== null"
            class="checkbox__count"
        >
            {{ count }}
        </span>
    </label>
</template>

<style scoped>
.checkbox {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 10px;
    min-height: 32px;
    cursor: pointer;
}

.checkbox__input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.checkbox__control {
    display: grid;
    place-items: center;
    width: 18px;
    height: 18px;
    color: var(--color-white);
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: 5px;
    transition:
        background-color var(--transition-fast),
        border-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.checkbox__control svg {
    width: 11px;
    height: 9px;
    opacity: 0;
    transform: scale(0.7);
    transition:
        opacity var(--transition-fast),
        transform var(--transition-fast);
}

.checkbox__input:checked + .checkbox__control {
    background: var(--color-primary-700);
    border-color: var(--color-primary-700);
}

.checkbox__input:checked
    + .checkbox__control
    svg {
    opacity: 1;
    transform: scale(1);
}

.checkbox__input:focus-visible
    + .checkbox__control {
    box-shadow:
        0 0 0 4px rgb(113 56 214 / 14%);
}

.checkbox__label {
    color: var(--color-text-secondary);
    font-size: 13px;
}

.checkbox__count {
    color: var(--color-text-muted);
    font-size: 12px;
}

.checkbox:hover .checkbox__label {
    color: var(--color-text-primary);
}

.checkbox--disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
</style>
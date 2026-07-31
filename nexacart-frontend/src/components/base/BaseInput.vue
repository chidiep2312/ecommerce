<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },

    label: {
        type: String,
        required: true,
    },

    type: {
        type: String,
        default: 'text',
    },

    name: {
        type: String,
        required: true,
    },

    placeholder: {
        type: String,
        default: '',
    },

    autocomplete: {
        type: String,
        default: 'off',
    },

    error: {
        type: String,
        default: '',
    },

    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

function handleInput(event) {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <div class="field">
        <label
            class="field__label"
            :for="name"
        >
            {{ label }}
        </label>

        <input
            :id="name"
            :name="name"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :disabled="disabled"
            :aria-invalid="Boolean(error)"
            :aria-describedby="
                error ? `${name}-error` : undefined
            "
            class="field__input"
            :class="{
                'field__input--error': error,
            }"
            @input="handleInput"
        />

        <p
            v-if="error"
            :id="`${name}-error`"
            class="field__error"
        >
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.field {
    display: grid;
    gap: 8px;
}

.field__label {
    color: var(--color-text-primary);
    font-size: 13px;
    font-weight: 600;
}

.field__input {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    color: var(--color-text-primary);
    background: var(--color-surface);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-md);
    transition:
        border-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.field__input::placeholder {
    color: var(--color-text-muted);
}

.field__input:hover:not(:disabled) {
    border-color: var(--color-gray-400);
}

.field__input:focus {
    border-color: var(--color-primary-500);
    box-shadow: 0 0 0 4px rgb(137 87 243 / 12%);
    outline: none;
}

.field__input--error {
    border-color: var(--color-danger);
}

.field__input:disabled {
    cursor: not-allowed;
    background: var(--color-gray-100);
    opacity: 0.7;
}

.field__error {
    color: var(--color-danger);
    font-size: 12px;
    line-height: 1.4;
}
</style>
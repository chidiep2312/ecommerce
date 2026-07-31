<script setup>
import {
    Minus,
    Plus,
} from '@lucide/vue'

const model = defineModel({
    type: Number,
    default: 1,
})

const props = defineProps({
    min: {
        type: Number,
        default: 1,
    },

    max: {
        type: Number,
        default: 99,
    },

    disabled: {
        type: Boolean,
        default: false,
    },
})

function decrease() {
    if (
        props.disabled ||
        model.value <= props.min
    ) {
        return
    }

    model.value--
}

function increase() {
    if (
        props.disabled ||
        model.value >= props.max
    ) {
        return
    }

    model.value++
}

function handleInput(event) {
    const value = Number(event.target.value)

    if (Number.isNaN(value)) {
        model.value = props.min
        return
    }

    model.value = Math.min(
        props.max,
        Math.max(props.min, value),
    )
}
</script>

<template>
    <div
        class="quantity-selector"
        :class="{
            'quantity-selector--disabled':
                disabled,
        }"
    >
        <button
            type="button"
            class="quantity-selector__button"
            :disabled="
                disabled || model <= min
            "
            aria-label="Giảm số lượng"
            @click="decrease"
        >
            <Minus :size="16" />
        </button>

        <input
            :value="model"
            type="number"
            :min="min"
            :max="max"
            :disabled="disabled"
            class="quantity-selector__input"
            aria-label="Số lượng sản phẩm"
            @input="handleInput"
        />

        <button
            type="button"
            class="quantity-selector__button"
            :disabled="
                disabled || model >= max
            "
            aria-label="Tăng số lượng"
            @click="increase"
        >
            <Plus :size="16" />
        </button>
    </div>
</template>

<style scoped>
.quantity-selector {
    display: grid;
    grid-template-columns: 42px 52px 42px;
    overflow: hidden;
    width: fit-content;
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: var(--radius-md);
}

.quantity-selector__button {
    display: grid;
    place-items: center;
    height: 44px;
    padding: 0;
    color: var(--color-text-primary);
    background: transparent;
    border: 0;
    transition:
        color var(--transition-fast),
        background-color var(--transition-fast);
}

.quantity-selector__button:hover:not(:disabled) {
    color: var(--color-primary-700);
    background: var(--color-primary-50);
}

.quantity-selector__button:disabled {
    cursor: not-allowed;
    opacity: 0.35;
}

.quantity-selector__input {
    width: 100%;
    height: 44px;
    padding: 0;
    color: var(--color-text-primary);
    appearance: textfield;
    background: var(--color-white);
    border: 0;
    border-right: 1px solid var(--color-border);
    border-left: 1px solid var(--color-border);
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    outline: none;
}

.quantity-selector__input::-webkit-inner-spin-button,
.quantity-selector__input::-webkit-outer-spin-button {
    margin: 0;
    appearance: none;
}

.quantity-selector--disabled {
    opacity: 0.55;
}
</style>
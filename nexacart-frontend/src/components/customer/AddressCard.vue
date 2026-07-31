<script setup>
import {
    Check,
    MapPin,
    Pencil,
    Phone,
} from '@lucide/vue'

defineProps({
    address: {
        type: Object,
        required: true,
    },

    selected: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits([
    'select',
    'edit',
])
</script>

<template>
    <article
        class="address-card"
        :class="{
            'address-card--selected': selected,
        }"
        @click="emit('select', address)"
    >
        <button
            type="button"
            class="address-card__select"
            :aria-label="
                `Chọn địa chỉ của ${address.recipientName}`
            "
            @click.stop="emit('select', address)"
        >
            <span
                v-if="selected"
                class="address-card__check"
            >
                <Check :size="14" />
            </span>
        </button>

        <div class="address-card__content">
            <div class="address-card__heading">
                <div>
                    <strong>
                        {{ address.recipientName }}
                    </strong>

                    <span
                        v-if="address.isDefault"
                        class="address-card__default"
                    >
                        Mặc định
                    </span>
                </div>

                <button
                    type="button"
                    class="address-card__edit"
                    aria-label="Chỉnh sửa địa chỉ"
                    @click.stop="emit('edit', address)"
                >
                    <Pencil :size="16" />
                </button>
            </div>

            <div class="address-card__line">
                <Phone :size="15" />

                <span>
                    {{ address.phone }}
                </span>
            </div>

            <div class="address-card__line">
                <MapPin :size="15" />

                <span>
                    {{ address.fullAddress }}
                </span>
            </div>
        </div>
    </article>
</template>

<style scoped>
.address-card {
    position: relative;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    padding: 18px;
    cursor: pointer;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    transition:
        border-color var(--transition-fast),
        background-color var(--transition-fast),
        box-shadow var(--transition-fast);
}

.address-card:hover {
    border-color: var(--color-primary-200);
}

.address-card--selected {
    background: var(--color-primary-50);
    border-color: var(--color-primary-500);
    box-shadow:
        0 0 0 3px rgb(113 56 214 / 8%);
}

.address-card__select {
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    margin-top: 1px;
    padding: 0;
    background: var(--color-white);
    border: 1px solid var(--color-border-strong);
    border-radius: 50%;
}

.address-card--selected
    .address-card__select {
    background: var(--color-primary-700);
    border-color: var(--color-primary-700);
}

.address-card__check {
    display: grid;
    place-items: center;
    color: var(--color-white);
}

.address-card__content {
    display: grid;
    gap: 10px;
    min-width: 0;
}

.address-card__heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.address-card__heading > div {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.address-card__heading strong {
    color: var(--color-text-primary);
    font-size: 14px;
}

.address-card__default {
    padding: 4px 7px;
    color: var(--color-primary-700);
    background: var(--color-primary-100);
    border-radius: var(--radius-pill);
    font-size: 10px;
    font-weight: 700;
}

.address-card__edit {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 34px;
    height: 34px;
    padding: 0;
    color: var(--color-text-muted);
    background: transparent;
    border: 0;
    border-radius: var(--radius-md);
}

.address-card__edit:hover {
    color: var(--color-primary-700);
    background: var(--color-primary-100);
}

.address-card__line {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 8px;
    color: var(--color-text-secondary);
    font-size: 12px;
    line-height: 1.6;
}

.address-card__line svg {
    margin-top: 2px;
    color: var(--color-text-muted);
}
</style>
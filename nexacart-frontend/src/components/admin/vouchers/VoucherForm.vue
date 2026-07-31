<script setup>
import {
    computed,
    reactive,
    watch,
} from 'vue'

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({}),
    },

    loading: {
        type: Boolean,
        default: false,
    },

    serverErrors: {
        type: Object,
        default: () => ({}),
    },

    submitLabel: {
        type: String,
        default: 'Lưu voucher',
    },
})

const emit = defineEmits([
    'submit',
    'cancel',
])

const form = reactive({
    code: '',
    type: 'percent',
    value: '',
    min_order_amount: 0,
    max_discount_amount: '',
    usage_limit: '',
    usage_limit_per_user: '',
    starts_at: '',
    expires_at: '',
    status: 'active',
})

const isPercentage = computed(() => {
    return form.type === 'percent'
})

function convertToDatetimeLocal(value) {
    if (!value) {
        return ''
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return ''
    }

    const timezoneOffset =
        date.getTimezoneOffset() * 60000

    return new Date(
        date.getTime() - timezoneOffset,
    )
        .toISOString()
        .slice(0, 16)
}

function setFormData(data = {}) {
    form.code = data.code ?? ''
    form.type = data.type ?? 'percent'
    form.value = data.value ?? ''
    form.min_order_amount =
        data.min_order_amount ?? 0

    form.max_discount_amount =
        data.max_discount_amount ?? ''

    form.usage_limit =
        data.usage_limit ?? ''

    form.usage_limit_per_user =
        data.usage_limit_per_user ?? ''

    form.starts_at =
        convertToDatetimeLocal(
            data.starts_at,
        )

    form.expires_at =
        convertToDatetimeLocal(
            data.expires_at,
        )

    form.status =
        data.status ?? 'active'
}

watch(
    () => props.initialData,
    (data) => {
        setFormData(data)
    },
    {
        immediate: true,
        deep: true,
    },
)

watch(
    () => form.type,
    (type) => {
        if (type === 'fixed') {
            form.max_discount_amount = ''
        }
    },
)

function normalizeNullableNumber(value) {
    if (
        value === '' ||
        value === null ||
        value === undefined
    ) {
        return null
    }

    return Number(value)
}

function handleSubmit() {
    const payload = {
        code: form.code
            .trim()
            .toUpperCase(),

        type: form.type,

        value: Number(form.value),

        min_order_amount:
            Number(form.min_order_amount || 0),

        max_discount_amount:
            isPercentage.value
                ? normalizeNullableNumber(
                    form.max_discount_amount,
                )
                : null,

        usage_limit:
            normalizeNullableNumber(
                form.usage_limit,
            ),

        usage_limit_per_user:
            normalizeNullableNumber(
                form.usage_limit_per_user,
            ),

        starts_at: form.starts_at,

        expires_at: form.expires_at,

        status: form.status,
    }

    emit('submit', payload)
}

function getError(field) {
    return props.serverErrors?.[field]?.[0] ?? ''
}
</script>

<template>
    <form
        class="voucher-form"
        @submit.prevent="handleSubmit"
    >
        <div class="form-section">
            <div class="section-heading">
                <h2>Thông tin voucher</h2>

                <p>
                    Thiết lập mã và giá trị giảm giá.
                </p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="voucher-code">
                        Mã voucher
                        <span class="required">*</span>
                    </label>

                    <input
                        id="voucher-code"
                        v-model="form.code"
                        type="text"
                        maxlength="50"
                        placeholder="Ví dụ: SALE10"
                        required
                    >

                    <p
                        v-if="getError('code')"
                        class="field-error"
                    >
                        {{ getError('code') }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="voucher-type">
                        Loại giảm giá
                        <span class="required">*</span>
                    </label>

                    <select
                        id="voucher-type"
                        v-model="form.type"
                    >
                        <option value="percent">
                            Giảm theo phần trăm
                        </option>

                        <option value="fixed">
                            Giảm số tiền cố định
                        </option>
                    </select>

                    <p
                        v-if="getError('type')"
                        class="field-error"
                    >
                        {{ getError('type') }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="voucher-value">
                        Giá trị giảm
                        <span class="required">*</span>
                    </label>

                    <div class="input-suffix">
                        <input
                            id="voucher-value"
                            v-model="form.value"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                        >

                        <span>
                            {{
                                isPercentage
                                    ? '%'
                                    : '₫'
                            }}
                        </span>
                    </div>

                    <p
                        v-if="getError('value')"
                        class="field-error"
                    >
                        {{ getError('value') }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="min-order">
                        Giá trị đơn tối thiểu
                    </label>

                    <div class="input-suffix">
                        <input
                            id="min-order"
                            v-model="form.min_order_amount"
                            type="number"
                            min="0"
                            step="1000"
                        >

                        <span>₫</span>
                    </div>

                    <p
                        v-if="
                            getError(
                                'min_order_amount',
                            )
                        "
                        class="field-error"
                    >
                        {{
                            getError(
                                'min_order_amount',
                            )
                        }}
                    </p>
                </div>

                <div
                    v-if="isPercentage"
                    class="form-group"
                >
                    <label for="max-discount">
                        Mức giảm tối đa
                    </label>

                    <div class="input-suffix">
                        <input
                            id="max-discount"
                            v-model="
                                form.max_discount_amount
                            "
                            type="number"
                            min="0"
                            step="1000"
                            placeholder="Không giới hạn"
                        >

                        <span>₫</span>
                    </div>

                    <p
                        v-if="
                            getError(
                                'max_discount_amount',
                            )
                        "
                        class="field-error"
                    >
                        {{
                            getError(
                                'max_discount_amount',
                            )
                        }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="voucher-status">
                        Trạng thái
                    </label>

                    <select
                        id="voucher-status"
                        v-model="form.status"
                    >
                        <option value="active">
                            Đang bật
                        </option>

                        <option value="inactive">
                            Đã tắt
                        </option>
                    </select>

                    <p
                        v-if="getError('status')"
                        class="field-error"
                    >
                        {{ getError('status') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-heading">
                <h2>Giới hạn sử dụng</h2>

                <p>
                    Để trống nếu không giới hạn số lượt.
                </p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="usage-limit">
                        Tổng lượt sử dụng
                    </label>

                    <input
                        id="usage-limit"
                        v-model="form.usage_limit"
                        type="number"
                        min="1"
                        placeholder="Không giới hạn"
                    >

                    <p
                        v-if="
                            getError('usage_limit')
                        "
                        class="field-error"
                    >
                        {{
                            getError('usage_limit')
                        }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="user-limit">
                        Lượt sử dụng mỗi khách
                    </label>

                    <input
                        id="user-limit"
                        v-model="
                            form.usage_limit_per_user
                        "
                        type="number"
                        min="1"
                        placeholder="Không giới hạn"
                    >

                    <p
                        v-if="
                            getError(
                                'usage_limit_per_user',
                            )
                        "
                        class="field-error"
                    >
                        {{
                            getError(
                                'usage_limit_per_user',
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-heading">
                <h2>Thời gian áp dụng</h2>

                <p>
                    Xác định thời gian bắt đầu và kết thúc.
                </p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="starts-at">
                        Thời gian bắt đầu
                        <span class="required">*</span>
                    </label>

                    <input
                        id="starts-at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        required
                    >

                    <p
                        v-if="getError('starts_at')"
                        class="field-error"
                    >
                        {{ getError('starts_at') }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="expires-at">
                        Thời gian kết thúc
                        <span class="required">*</span>
                    </label>

                    <input
                        id="expires-at"
                        v-model="form.expires_at"
                        type="datetime-local"
                        required
                    >

                    <p
                        v-if="getError('expires_at')"
                        class="field-error"
                    >
                        {{ getError('expires_at') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button
                type="button"
                class="button button--secondary"
                :disabled="loading"
                @click="emit('cancel')"
            >
                Hủy
            </button>

            <button
                type="submit"
                class="button button--primary"
                :disabled="loading"
            >
                {{
                    loading
                        ? 'Đang lưu...'
                        : submitLabel
                }}
            </button>
        </div>
    </form>
</template>

<style scoped>
.voucher-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 32px;
    padding: 24px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.section-heading h2 {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 16px;
}

.section-heading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
}

.form-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.form-group {
    min-width: 0;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    min-height: 40px;
    padding: 8px 11px;
    border: 1px solid #cbd5e1;
    border-radius: 0;
    background: #ffffff;
    color: #0f172a;
    font: inherit;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.input-suffix {
    display: flex;
}

.input-suffix input {
    border-right: 0;
}

.input-suffix span {
    display: flex;
    min-width: 45px;
    align-items: center;
    justify-content: center;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
}

.required,
.field-error {
    color: #dc2626;
}

.field-error {
    margin: 6px 0 0;
    font-size: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.button {
    min-height: 40px;
    padding: 8px 18px;
    border: 1px solid transparent;
    border-radius: 0;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

.button--primary {
    color: #ffffff;
    background: #16a34a;
    border-color: #16a34a;
}

.button--primary:hover:not(:disabled) {
    background: #15803d;
}

.button--secondary {
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
}

@media (max-width: 900px) {
    .form-section {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 640px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
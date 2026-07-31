<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { createAdminVoucher } from '@/api/admin/vouchers'

import VoucherForm from '@/components/admin/vouchers/VoucherForm.vue'

const router = useRouter()

const loading = ref(false)
const serverErrors = ref({})
const errorMessage = ref('')

async function handleSubmit(payload) {
    loading.value = true
    serverErrors.value = {}
    errorMessage.value = ''

    try {
        await createAdminVoucher(payload)

        router.push({
            name: 'admin.vouchers',
        })
    } catch (error) {
        if (
            error.response?.status === 422
        ) {
            serverErrors.value =
                error.response.data.errors ?? {}

            return
        }

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tạo voucher.'
    } finally {
        loading.value = false
    }
}

function handleCancel() {
    router.push({
        name: 'admin-vouchers',
    })
}
</script>

<template>
    <section class="page">
        <header class="page-header">
            <div>
                <h1>Tạo voucher</h1>

                <p>
                    Thiết lập chương trình giảm giá
                    mới cho khách hàng.
                </p>
            </div>
             <button
                    type="button"
                    class="button button--primary"
                    @click="
                        router.push({
                            name:
                                'admin-vouchers',
                        })
                    "
                >
                    Quay lại
                </button>
        </header>

        <div
            v-if="errorMessage"
            class="alert"
        >
            {{ errorMessage }}
        </div>

        <VoucherForm
            :loading="loading"
            :server-errors="serverErrors"
            submit-label="Tạo voucher"
            @submit="handleSubmit"
            @cancel="handleCancel"
        />
    </section>
</template>

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    font-family: Roboto, sans-serif;
}

.page-header h1 {
    margin: 0;
    color: #0f172a;
    font-size: 24px;
}

.page-header p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 14px;
}

.alert {
    padding: 12px 14px;
    color: #991b1b;
    background: #fef2f2;
    border: 1px solid #fecaca;
}

.button {
    min-height: 40px;
    padding: 8px 16px;
    border: 1px solid transparent;
    border-radius: 0;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}

.button--primary {
    color: #ffffff;
    background: #16a34a;
    border-color: #16a34a;
}

.button--secondary {
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
}
</style>
<script setup>
import {
    onMounted,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getAdminVoucher,
    updateAdminVoucher,
} from '@/api/admin/vouchers'

import VoucherForm from '@/components/admin/vouchers/VoucherForm.vue'

const route = useRoute()
const router = useRouter()

const voucher = ref(null)
const loading = ref(false)
const loadingVoucher = ref(true)
const serverErrors = ref({})
const errorMessage = ref('')

async function fetchVoucher() {
    loadingVoucher.value = true
    errorMessage.value = ''

    try {
        const response =
            await getAdminVoucher(
                route.params.id,
            )

        voucher.value =
            response.data?.data ??
            response.data
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải thông tin voucher.'
    } finally {
        loadingVoucher.value = false
    }
}

async function handleSubmit(payload) {
    loading.value = true
    serverErrors.value = {}
    errorMessage.value = ''

    try {
        await updateAdminVoucher(
            route.params.id,
            payload,
        )

        router.push({
            name: 'admin.vouchers.show',
            params: {
                id: route.params.id,
            },
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
            'Không thể cập nhật voucher.'
    } finally {
        loading.value = false
    }
}

function handleCancel() {
    router.push({
        name: 'admin.vouchers.index',
    })
}

onMounted(fetchVoucher)
</script>

<template>
    <section class="page">
        <header class="page-header">
            <div>
                <h1>Chỉnh sửa voucher</h1>

                <p>
                    Cập nhật điều kiện và thời gian
                    sử dụng voucher.
                </p>
            </div>
        </header>

        <div
            v-if="errorMessage"
            class="alert"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="loadingVoucher"
            class="loading-state"
        >
            Đang tải voucher...
        </div>

        <VoucherForm
            v-else-if="voucher"
            :initial-data="voucher"
            :loading="loading"
            :server-errors="serverErrors"
            submit-label="Lưu thay đổi"
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

.loading-state {
    padding: 50px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    text-align: center;
}
</style>
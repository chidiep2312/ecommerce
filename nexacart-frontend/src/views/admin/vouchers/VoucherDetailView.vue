<script setup>
import {
    onMounted,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import { getAdminVoucher } from '@/api/admin/vouchers'

import VoucherStatusBadge from '@/components/admin/vouchers/VoucherStatusBadge.vue'

const route = useRoute()
const router = useRouter()

const voucher = ref(null)
const loading = ref(true)
const errorMessage = ref('')

async function fetchVoucher() {
    loading.value = true

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
            'Không thể tải voucher.'
    } finally {
        loading.value = false
    }
}

function formatCurrency(value) {
    if (
        value === null ||
        value === undefined
    ) {
        return 'Không giới hạn'
    }

    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(Number(value))
}

function formatDateTime(value) {
    if (!value) {
        return '—'
    }

    return new Intl.DateTimeFormat(
        'vi-VN',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(new Date(value))
}

function getValue() {
    if (!voucher.value) {
        return '—'
    }

    if (
        voucher.value.type ===
        'percentage'
    ) {
        return `${Number(
            voucher.value.value,
        )}%`
    }

    return formatCurrency(
        voucher.value.value,
    )
}

onMounted(fetchVoucher)
</script>

<template>
    <section class="page">
        <header class="page-header">
            <div>
                <h1>Chi tiết voucher</h1>

                <p>
                    Thông tin và điều kiện áp dụng
                    voucher.
                </p>
            </div>

            <div class="header-actions">
                <button
                    type="button"
                    class="button button--secondary"
                    @click="
                        router.push({
                            name:
                                'admin-vouchers',
                        })
                    "
                >
                    Quay lại
                </button>

                <button
                    v-if="voucher"
                    type="button"
                    class="button button--primary"
                    @click="
                        router.push({
                            name:
                                'admin.vouchers.edit',
                            params: {
                                id: voucher.id,
                            },
                        })
                    "
                >
                    Chỉnh sửa
                </button>
            </div>
        </header>

        <div
            v-if="loading"
            class="state"
        >
            Đang tải voucher...
        </div>

        <div
            v-else-if="errorMessage"
            class="alert"
        >
            {{ errorMessage }}
        </div>

        <div
            v-else-if="voucher"
            class="detail-card"
        >
            <div class="detail-heading">
                <div>
                    <span class="caption">
                        Mã voucher
                    </span>

                    <h2>{{ voucher.code }}</h2>
                </div>

                <VoucherStatusBadge
                    :status="
                        voucher.effective_status ??
                        voucher.status
                    "
                />
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span>Loại giảm giá</span>

                    <strong>
                        {{
                            voucher.type ===
                            'percentage'
                                ? 'Giảm theo phần trăm'
                                : 'Giảm số tiền cố định'
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Giá trị giảm</span>

                    <strong>{{ getValue() }}</strong>
                </div>

                <div class="detail-item">
                    <span>Đơn hàng tối thiểu</span>

                    <strong>
                        {{
                            formatCurrency(
                                voucher.min_order_amount,
                            )
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Mức giảm tối đa</span>

                    <strong>
                        {{
                            formatCurrency(
                                voucher.max_discount_amount,
                            )
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Tổng lượt sử dụng</span>

                    <strong>
                        {{
                            voucher.usage_limit ??
                            'Không giới hạn'
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Đã sử dụng</span>

                    <strong>
                        {{
                            voucher.usages_count ??
                            voucher.used_count ??
                            0
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Lượt mỗi khách</span>

                    <strong>
                        {{
                            voucher.usage_limit_per_user ??
                            'Không giới hạn'
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Trạng thái cài đặt</span>

                    <strong>
                        {{
                            voucher.status ===
                            'active'
                                ? 'Đang bật'
                                : 'Đã tắt'
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Bắt đầu</span>

                    <strong>
                        {{
                            formatDateTime(
                                voucher.starts_at,
                            )
                        }}
                    </strong>
                </div>

                <div class="detail-item">
                    <span>Kết thúc</span>

                    <strong>
                        {{
                            formatDateTime(
                                voucher.expires_at,
                            )
                        }}
                    </strong>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    font-family: Roboto, sans-serif;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
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

.header-actions {
    display: flex;
    gap: 10px;
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

.state,
.alert {
    padding: 50px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.alert {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
}

.detail-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.detail-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.caption {
    color: #64748b;
    font-size: 12px;
}

.detail-heading h2 {
    margin: 5px 0 0;
    color: #15803d;
    font-size: 22px;
    letter-spacing: 0.04em;
}

.detail-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 7px;
    padding: 18px 24px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-item span {
    color: #64748b;
    font-size: 12px;
}

.detail-item strong {
    color: #0f172a;
    font-size: 14px;
}

@media (max-width: 640px) {
    .page-header {
        flex-direction: column;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>
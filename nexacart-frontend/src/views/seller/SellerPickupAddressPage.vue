<script setup>
import {
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    getDistricts,
    getProvinces,
    getWards,
} from '@/api/ghn'

import {
    createSellerPickupAddress,
    getSellerPickupAddresses,
    setDefaultSellerPickupAddress,
    syncSellerPickupAddress,
} from '@/api/sellerPickupAddress'

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const addresses = ref([])

const provinces = ref([])
const districts = ref([])
const wards = ref([])

const isLoading = ref(false)
const isSubmitting = ref(false)

const isLoadingDistricts = ref(false)
const isLoadingWards = ref(false)

const actionAddressId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref({})

const form = reactive({
    contact_name: '',
    phone: '',

    province: '',
    province_id: null,

    district: '',
    district_id: null,

    ward: '',
    ward_code: null,

    address_line: '',

    is_default: false,
})

/*
|--------------------------------------------------------------------------
| Load address
|--------------------------------------------------------------------------
*/

async function loadAddresses() {
    isLoading.value = true

    try {
        const response =
            await getSellerPickupAddresses()

        addresses.value =
            response.data?.data ?? []
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể tải địa chỉ lấy hàng.'
    } finally {
        isLoading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| GHN Province
|--------------------------------------------------------------------------
*/

async function loadProvinces() {
    try {
        const response =
            await getProvinces()

        provinces.value =
            response.data?.data ?? []
    } catch (error) {
        console.error(error)

        errorMessage.value =
            'Không thể tải tỉnh/thành.'
    }
}

/*
|--------------------------------------------------------------------------
| Province change
|--------------------------------------------------------------------------
*/

async function handleProvinceChange() {
    /*
     * Xóa district và ward cũ.
     */
    form.district = ''
    form.district_id = null

    form.ward = ''
    form.ward_code = null

    districts.value = []
    wards.value = []

    /*
     * Tìm Province object.
     */
    const province =
        provinces.value.find(
            (item) => {
                return (
                    Number(
                        item.ProvinceID,
                    ) ===
                    Number(
                        form.province_id,
                    )
                )
            },
        )

    /*
     * Lưu tên để hiển thị.
     */
    form.province =
        province
            ?.ProvinceName ?? ''

    if (!form.province_id) {
        return
    }

    isLoadingDistricts.value = true

    try {
        const response =
            await getDistricts(
                form.province_id,
            )

        districts.value =
            response.data?.data ?? []
    } catch (error) {
        console.error(error)

        errorMessage.value =
            'Không thể tải quận/huyện.'
    } finally {
        isLoadingDistricts.value =
            false
    }
}

/*
|--------------------------------------------------------------------------
| District change
|--------------------------------------------------------------------------
*/

async function handleDistrictChange() {
    /*
     * Ward cũ không còn hợp lệ.
     */
    form.ward = ''
    form.ward_code = null

    wards.value = []

    const district =
        districts.value.find(
            (item) => {
                return (
                    Number(
                        item.DistrictID,
                    ) ===
                    Number(
                        form.district_id,
                    )
                )
            },
        )

    form.district =
        district
            ?.DistrictName ?? ''

    if (!form.district_id) {
        return
    }

    isLoadingWards.value = true

    try {
        const response =
            await getWards(
                form.district_id,
            )

        wards.value =
            response.data?.data ?? []
    } catch (error) {
        console.error(error)

        errorMessage.value =
            'Không thể tải phường/xã.'
    } finally {
        isLoadingWards.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Ward change
|--------------------------------------------------------------------------
*/

function handleWardChange() {
    const ward =
        wards.value.find(
            (item) => {
                return (
                    String(
                        item.WardCode,
                    ) ===
                    String(
                        form.ward_code,
                    )
                )
            },
        )

    form.ward =
        ward
            ?.WardName ?? ''
}

/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/

function resetForm() {
    form.contact_name = ''
    form.phone = ''

    form.province = ''
    form.province_id = null

    form.district = ''
    form.district_id = null

    form.ward = ''
    form.ward_code = null

    form.address_line = ''
    form.is_default = false

    districts.value = []
    wards.value = []

    validationErrors.value = {}
}

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

async function submitAddress() {
    isSubmitting.value = true

    errorMessage.value = ''
    successMessage.value = ''
    validationErrors.value = {}

    const payload = {
        contact_name:
            form.contact_name.trim(),

        phone:
            form.phone.trim(),

        province:
            form.province,

        province_id:
            form.province_id,

        district:
            form.district,

        district_id:
            form.district_id,

        ward:
            form.ward,

        ward_code:
            form.ward_code,

        address_line:
            form.address_line.trim(),

        is_default:
            form.is_default,
    }

    try {
        const response =
            await createSellerPickupAddress(
                payload,
            )

        successMessage.value =
            response.data?.message ??
            'Đã tạo địa chỉ lấy hàng.'

        resetForm()

        await loadAddresses()
    } catch (error) {
        if (
            error.response?.status ===
            422
        ) {
            validationErrors.value =
                error.response
                    ?.data
                    ?.errors ?? {}

            return
        }

        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể tạo địa chỉ.'
    } finally {
        isSubmitting.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Sync GHN
|--------------------------------------------------------------------------
*/

async function retrySync(
    address,
) {
    if (actionAddressId.value) {
        return
    }

    actionAddressId.value =
        address.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await syncSellerPickupAddress(
                address.id,
            )

        successMessage.value =
            response.data?.message ??
            'Đã đồng bộ GHN.'

        await loadAddresses()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể đồng bộ GHN.'
    } finally {
        actionAddressId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Default
|--------------------------------------------------------------------------
*/

async function makeDefault(
    address,
) {
    if (
        address.is_default ||
        actionAddressId.value
    ) {
        return
    }

    actionAddressId.value =
        address.id

    try {
        const response =
            await setDefaultSellerPickupAddress(
                address.id,
            )

        successMessage.value =
            response.data?.message ??
            'Đã cập nhật địa chỉ mặc định.'

        await loadAddresses()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể cập nhật.'
    } finally {
        actionAddressId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await Promise.all([
        loadAddresses(),
        loadProvinces(),
    ])
})
</script>

<template>
    <div class="pickup-page">
        <h1>
            Địa chỉ lấy hàng
        </h1>

        <p>
            Seller thiết lập địa chỉ để
            đơn vị vận chuyển đến lấy hàng.
        </p>

        <div
            v-if="errorMessage"
            class="alert error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="successMessage"
            class="alert success"
        >
            {{ successMessage }}
        </div>

        <!-- FORM -->
        <form
            class="pickup-form"
            @submit.prevent="submitAddress"
        >
            <div class="grid">
                <div class="form-group">
                    <label>
                        Người liên hệ
                    </label>

                    <input
                        v-model="
                            form.contact_name
                        "
                        type="text"
                    >

                    <small
                        v-if="
                            validationErrors
                                .contact_name?.[0]
                        "
                    >
                        {{
                            validationErrors
                                .contact_name[0]
                        }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        Số điện thoại
                    </label>

                    <input
                        v-model="form.phone"
                        type="tel"
                    >
                </div>

                <!-- Province -->
                <div class="form-group">
                    <label>
                        Tỉnh / Thành phố
                    </label>

                    <select
                        v-model.number="
                            form.province_id
                        "
                        @change="
                            handleProvinceChange
                        "
                    >
                        <option :value="null">
                            Chọn Tỉnh / Thành
                        </option>

                        <option
                            v-for="
                                province
                                in provinces
                            "
                            :key="
                                province
                                    .ProvinceID
                            "
                            :value="
                                province
                                    .ProvinceID
                            "
                        >
                            {{
                                province
                                    .ProvinceName
                            }}
                        </option>
                    </select>
                </div>

                <!-- District -->
                <div class="form-group">
                    <label>
                        Quận / Huyện
                    </label>

                    <select
                        v-model.number="
                            form.district_id
                        "
                        :disabled="
                            !form.province_id ||
                            isLoadingDistricts
                        "
                        @change="
                            handleDistrictChange
                        "
                    >
                        <option :value="null">
                            {{
                                isLoadingDistricts
                                    ? 'Đang tải...'
                                    : 'Chọn Quận / Huyện'
                            }}
                        </option>

                        <option
                            v-for="
                                district
                                in districts
                            "
                            :key="
                                district
                                    .DistrictID
                            "
                            :value="
                                district
                                    .DistrictID
                            "
                        >
                            {{
                                district
                                    .DistrictName
                            }}
                        </option>
                    </select>
                </div>

                <!-- Ward -->
                <div class="form-group">
                    <label>
                        Phường / Xã
                    </label>

                    <select
                        v-model="
                            form.ward_code
                        "
                        :disabled="
                            !form.district_id ||
                            isLoadingWards
                        "
                        @change="
                            handleWardChange
                        "
                    >
                        <option :value="null">
                            {{
                                isLoadingWards
                                    ? 'Đang tải...'
                                    : 'Chọn Phường / Xã'
                            }}
                        </option>

                        <option
                            v-for="
                                ward in wards
                            "
                            :key="
                                ward.WardCode
                            "
                            :value="
                                ward.WardCode
                            "
                        >
                            {{
                                ward.WardName
                            }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        Địa chỉ cụ thể
                    </label>

                    <input
                        v-model="
                            form.address_line
                        "
                        type="text"
                        placeholder="
                            123 Nguyễn Huệ
                        "
                    >
                </div>
            </div>

            <label class="checkbox">
                <input
                    v-model="
                        form.is_default
                    "
                    type="checkbox"
                >

                Đặt làm địa chỉ mặc định
            </label>

            <button
                type="submit"
                :disabled="isSubmitting"
            >
                {{
                    isSubmitting
                        ? 'Đang lưu...'
                        : 'Lưu địa chỉ'
                }}
            </button>
        </form>

        <!-- LIST -->
        <section class="address-list">
            <h2>
                Danh sách địa chỉ
            </h2>

            <div v-if="isLoading">
                Đang tải...
            </div>

            <article
                v-for="
                    address in addresses
                "
                v-else
                :key="address.id"
                class="address-card"
            >
                <div>
                    <strong>
                        {{
                            address
                                .contact_name
                        }}
                    </strong>

                    <span>
                        {{ address.phone }}
                    </span>

                    <p>
                        {{
                            address
                                .address_line
                        }},
                        {{ address.ward }},
                        {{ address.district }},
                        {{ address.province }}
                    </p>

                    <p>
                        GHN Shop ID:
                        {{
                            address
                                .ghn_shop_id ??
                            'Chưa có'
                        }}
                    </p>

                    <p>
                        Trạng thái GHN:
                        {{
                            address
                                .ghn_sync_status
                        }}
                    </p>

                    <strong
                        v-if="
                            address.is_default
                        "
                    >
                        Địa chỉ mặc định
                    </strong>
                </div>

                <div class="actions">
                    <button
                        v-if="
                            address
                                .ghn_sync_status ===
                            'failed'
                        "
                        type="button"
                        :disabled="
                            actionAddressId ===
                            address.id
                        "
                        @click="
                            retrySync(
                                address,
                            )
                        "
                    >
                        Đồng bộ lại GHN
                    </button>

                    <button
                        v-if="
                            !address
                                .is_default
                        "
                        type="button"
                        :disabled="
                            actionAddressId ===
                            address.id
                        "
                        @click="
                            makeDefault(
                                address,
                            )
                        "
                    >
                        Đặt mặc định
                    </button>
                </div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.pickup-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.pickup-form,
.address-card {
    padding: 20px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 12px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    height: 42px;
    box-sizing: border-box;
    padding: 0 10px;
    border: 1px solid #cad5ce;
    background: #ffffff;
    font: inherit;
}

.form-group small {
    color: #a33d3d;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 18px 0;
}

button {
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
    cursor: pointer;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.address-list {
    display: grid;
    gap: 12px;
}

.address-card {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.address-card p {
    margin: 7px 0;
}

.actions {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.alert {
    padding: 12px;
}

.alert.success {
    background: #edf7f0;
}

.alert.error {
    background: #fff2f2;
}

@media (
    max-width: 700px
) {
    .grid {
        grid-template-columns:
            1fr;
    }

    .address-card {
        flex-direction: column;
    }
}
</style>
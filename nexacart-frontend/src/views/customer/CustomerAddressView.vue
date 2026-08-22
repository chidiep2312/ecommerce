<script setup>
import {
    Check,
    MapPin,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue'

import {
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    createCustomerAddress,
    deleteCustomerAddress,
    getCustomerAddresses,
    setDefaultAddress,
    updateCustomerAddress,
} from '@/api/address'

import {
    getProvinces,
    getDistricts,
     getWards,
} from '@/api/ghn'



/*
|--------------------------------------------------------------------------
| Address
|--------------------------------------------------------------------------
*/

const addresses = ref([])

const isLoading = ref(false)
const isSubmitting = ref(false)
const actionAddressId = ref(null)

const showForm = ref(false)
const editingAddress = ref(null)

const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref({})

/*
|--------------------------------------------------------------------------
| GHN master data
|--------------------------------------------------------------------------
*/

const provinces = ref([])
const districts = ref([])
const wards = ref([])

const isLoadingProvinces = ref(false)
const isLoadingDistricts = ref(false)
const isLoadingWards = ref(false)

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = reactive({
    recipient_name: '',
    phone: '',

    province: '',
    district: '',
    ward: '',

    province_id: null,
    district_id: null,
    ward_code: null,

    address_line: '',
    is_default: false,
})

/*
|--------------------------------------------------------------------------
| GHN
|--------------------------------------------------------------------------
*/

async function loadProvinces() {
    isLoadingProvinces.value = true

    try {
        const response =
       await getProvinces()

        provinces.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []
    } catch (error) {
        console.error(
            'Không thể tải tỉnh/thành GHN:',
            error,
        )

        errorMessage.value =
            'Không thể tải danh sách tỉnh/thành.'
    } finally {
        isLoadingProvinces.value = false
    }
}

async function loadDistricts(
    provinceId,
) {
    districts.value = []

    if (!provinceId) {
        return
    }

    isLoadingDistricts.value = true

    try {
        const response =
               await getDistricts(
                provinceId,
            )

        districts.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []
    } catch (error) {
        console.error(
            'Không thể tải quận/huyện GHN:',
            error,
        )

        errorMessage.value =
            'Không thể tải danh sách quận/huyện.'
    } finally {
        isLoadingDistricts.value = false
    }
}

async function loadWards(
    districtId,
) {
    wards.value = []

    if (!districtId) {
        return
    }

    isLoadingWards.value = true

    try {
        const response =
              await getWards(
        districtId,
    )

        wards.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []
    } catch (error) {
        console.error(
            'Không thể tải phường/xã GHN:',
            error,
        )

        errorMessage.value =
            'Không thể tải danh sách phường/xã.'
    } finally {
        isLoadingWards.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Province changed
|--------------------------------------------------------------------------
*/

async function handleProvinceChange() {
    /*
     * Province thay đổi thì District và Ward
     * cũ không còn hợp lệ.
     */
    form.district = ''
    form.district_id = null

    form.ward = ''
    form.ward_code = null

    districts.value = []
    wards.value = []

    const selectedProvince =
        provinces.value.find(
            (province) => {
                return (
                    Number(
                        province.ProvinceID,
                    ) ===
                    Number(
                        form.province_id,
                    )
                )
            },
        )

    form.province =
        selectedProvince
            ?.ProvinceName ?? ''

    if (!form.province_id) {
        return
    }

    await loadDistricts(
        form.province_id,
    )
}

/*
|--------------------------------------------------------------------------
| District changed
|--------------------------------------------------------------------------
*/

async function handleDistrictChange() {
    /*
     * District thay đổi thì Ward cũ
     * không còn hợp lệ.
     */
    form.ward = ''
    form.ward_code = null

    wards.value = []

    const selectedDistrict =
        districts.value.find(
            (district) => {
                return (
                    Number(
                        district.DistrictID,
                    ) ===
                    Number(
                        form.district_id,
                    )
                )
            },
        )

    form.district =
        selectedDistrict
            ?.DistrictName ?? ''

    if (!form.district_id) {
        return
    }

    await loadWards(
        form.district_id,
    )
}

/*
|--------------------------------------------------------------------------
| Ward changed
|--------------------------------------------------------------------------
*/

function handleWardChange() {
    const selectedWard =
        wards.value.find(
            (ward) => {
                return (
                    String(
                        ward.WardCode,
                    ) ===
                    String(
                        form.ward_code,
                    )
                )
            },
        )

    form.ward =
        selectedWard
            ?.WardName ?? ''
}

/*
|--------------------------------------------------------------------------
| Fetch addresses
|--------------------------------------------------------------------------
*/

async function fetchAddresses() {
    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getCustomerAddresses()

        addresses.value =
            Array.isArray(
                response.data?.data,
            )
                ? response.data.data
                : []
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách địa chỉ.'
    } finally {
        isLoading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Reset form
|--------------------------------------------------------------------------
*/

function resetForm() {
    form.recipient_name = ''
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

    editingAddress.value = null
    validationErrors.value = {}
}

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

function openCreateForm() {
    resetForm()

    if (
        addresses.value.length === 0
    ) {
        form.is_default = true
    }

    showForm.value = true
}

/*
|--------------------------------------------------------------------------
| Edit
|--------------------------------------------------------------------------
*/

async function openEditForm(
    address,
) {
    editingAddress.value = address

    form.recipient_name =
        address.recipient_name ?? ''

    form.phone =
        address.phone ?? ''

    form.province =
        address.province ?? ''

    form.province_id =
        address.province_id ??
        address.ghn_province_id ??
        null

    form.district =
        address.district ?? ''

    form.district_id =
        address.district_id ??
        address.ghn_district_id ??
        null

    form.ward =
        address.ward ?? ''

    form.ward_code =
        address.ward_code ??
        address.ghn_ward_code ??
        null

    form.address_line =
        address.address_line ?? ''

    form.is_default =
        Boolean(
            address.is_default,
        )

    /*
     * Hỗ trợ dữ liệu địa chỉ cũ:
     * nếu database chưa có province_id,
     * thử tìm province bằng tên.
     */
    if (
        !form.province_id &&
        form.province
    ) {
        const matchedProvince =
            provinces.value.find(
                (province) => {
                    return (
                        province
                            .ProvinceName ===
                        form.province
                    )
                },
            )

        form.province_id =
            matchedProvince
                ?.ProvinceID ?? null
    }

    /*
     * Phải load District trước khi
     * có thể hiển thị District đang chọn.
     */
    if (form.province_id) {
        await loadDistricts(
            form.province_id,
        )

        if (
            !form.district_id &&
            form.district
        ) {
            const matchedDistrict =
                districts.value.find(
                    (district) => {
                        return (
                            district
                                .DistrictName ===
                            form.district
                        )
                    },
                )

            form.district_id =
                matchedDistrict
                    ?.DistrictID ??
                null
        }
    }

    /*
     * Phải load Ward sau khi đã có District.
     */
    if (form.district_id) {
        await loadWards(
            form.district_id,
        )

        if (
            !form.ward_code &&
            form.ward
        ) {
            const matchedWard =
                wards.value.find(
                    (ward) => {
                        return (
                            ward.WardName ===
                            form.ward
                        )
                    },
                )

            form.ward_code =
                matchedWard
                    ?.WardCode ??
                null
        }
    }

    validationErrors.value = {}
    showForm.value = true
}

function closeForm() {
    if (isSubmitting.value) {
        return
    }

    showForm.value = false

    resetForm()
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
        recipient_name:
            form.recipient_name.trim(),

        phone:
            form.phone.trim(),

        /*
         * Tên dùng hiển thị.
         */
        province:
            form.province,

        district:
            form.district,

        ward:
            form.ward,

        /*
         * ID/code GHN dùng để
         * tính shipping.
         */
        province_id:
            form.province_id,

        district_id:
            form.district_id,

        ward_code:
            form.ward_code,

        address_line:
            form.address_line.trim(),

        is_default:
            form.is_default,
    }

    try {
        let response

        if (editingAddress.value) {
            response =
                await updateCustomerAddress(
                    editingAddress.value.id,
                    payload,
                )
        } else {
            response =
                await createCustomerAddress(
                    payload,
                )
        }

        successMessage.value =
            response.data?.message ??
            'Lưu địa chỉ thành công.'

        showForm.value = false

        resetForm()

        await fetchAddresses()
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
            'Không thể lưu địa chỉ.'
    } finally {
        isSubmitting.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Set default
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

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await setDefaultAddress(
                address.id,
            )

        successMessage.value =
            response.data?.message ??
            'Đã cập nhật địa chỉ mặc định.'

        await fetchAddresses()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể đặt địa chỉ mặc định.'
    } finally {
        actionAddressId.value = null
    }
}

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

async function removeAddress(
    address,
) {
    if (actionAddressId.value) {
        return
    }

    const confirmed =
        window.confirm(
            `Bạn có chắc muốn xóa địa chỉ của "${address.recipient_name}" không?`,
        )

    if (!confirmed) {
        return
    }

    actionAddressId.value =
        address.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await deleteCustomerAddress(
                address.id,
            )

        successMessage.value =
            response.data?.message ??
            'Xóa địa chỉ thành công.'

        await fetchAddresses()
    } catch (error) {
        errorMessage.value =
            error.response
                ?.data
                ?.message ??
            'Không thể xóa địa chỉ.'
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
        fetchAddresses(),
        loadProvinces(),
    ])
})
</script>

<template>
    <div class="address-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">
                    ĐỊA CHỈ GIAO HÀNG
                </p>

                <h2>
                    Sổ địa chỉ
                </h2>

                <span>
                    Quản lý địa chỉ được sử dụng
                    khi đặt hàng trên NexaCart.
                </span>
            </div>

            <button type="button" class="add-button" @click="openCreateForm">
                <Plus :size="17" />

                Thêm địa chỉ
            </button>
        </header>

        <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>

        <div v-if="isLoading" class="state-box">
            Đang tải địa chỉ...
        </div>

        <div v-else-if="
            addresses.length === 0
        " class="empty-state">
            <MapPin :size="42" />

            <h3>
                Chưa có địa chỉ giao hàng
            </h3>

            <p>
                Thêm địa chỉ để quá trình
                thanh toán nhanh hơn.
            </p>

            <button type="button" @click="openCreateForm">
                <Plus :size="17" />

                Thêm địa chỉ đầu tiên
            </button>
        </div>

        <section v-else class="address-list">
            <article v-for="address in addresses" :key="address.id" class="address-card" :class="{
                default:
                    address.is_default,
            }">
                <div class="address-main">
                    <div class="address-icon">
                        <MapPin :size="20" />
                    </div>

                    <div class="address-content">
                        <div class="address-title">
                            <strong>
                                {{
                                    address
                                        .recipient_name
                                }}
                            </strong>

                            <span class="divider">
                                |
                            </span>

                            <span>
                                {{
                                    address.phone
                                }}
                            </span>

                            <span v-if="
                                address.is_default
                            " class="default-badge">
                                Mặc định
                            </span>
                        </div>

                        <p>
                            {{
                                address.address_line
                            }}
                        </p>

                        <p>
                            {{
                                address.ward
                            }},
                            {{
                                address.district
                            }},
                            {{
                                address.province
                            }}
                        </p>
                    </div>
                </div>

                <div class="address-actions">
                    <button type="button" class="text-button" @click="
                        openEditForm(
                            address,
                        )
                        ">
                        <Pencil :size="15" />

                        Sửa
                    </button>

                    <button v-if="
                        !address.is_default
                    " type="button" class="text-button danger" :disabled="actionAddressId ===
                        address.id
                        " @click="
                            removeAddress(
                                address,
                            )
                            ">
                        <Trash2 :size="15" />

                        Xóa
                    </button>

                    <button v-if="
                        !address.is_default
                    " type="button" class="default-button" :disabled="actionAddressId ===
                        address.id
                        " @click="
                            makeDefault(
                                address,
                            )
                            ">
                        Đặt làm mặc định
                    </button>
                </div>
            </article>
        </section>

        <!-- FORM MODAL -->
        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <section class="address-modal">
                <header class="modal-header">
                    <div>
                        <p>
                            {{
                                editingAddress
                                    ? 'CHỈNH SỬA'
                                    : 'ĐỊA CHỈ MỚI'
                            }}
                        </p>

                        <h3>
                            {{
                                editingAddress
                                    ? 'Cập nhật địa chỉ'
                                    : 'Thêm địa chỉ giao hàng'
                            }}
                        </h3>
                    </div>

                    <button type="button" class="close-button" @click="closeForm">
                        <X :size="20" />
                    </button>
                </header>

                <form @submit.prevent="
                    submitAddress
                ">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                Người nhận
                            </label>

                            <input v-model="form
                                .recipient_name
                                " type="text" placeholder="Nguyễn Văn A">

                            <small v-if="
                                validationErrors
                                    .recipient_name
                                ?.[0]
                            ">
                                {{
                                    validationErrors
                                        .recipient_name[0]
                                }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                Số điện thoại
                            </label>

                            <input v-model="form.phone
                                " type="tel" placeholder="0901234567">

                            <small v-if="
                                validationErrors
                                    .phone?.[0]
                            ">
                                {{
                                    validationErrors
                                        .phone[0]
                                }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                Tỉnh / Thành phố
                            </label>

                            <select v-model.number="form.province_id
                                " :disabled="isLoadingProvinces
                                    " @change="
                                        handleProvinceChange
                                    ">
                                <option :value="null">
                                    {{
                                        isLoadingProvinces
                                            ? 'Đang tải...'
                                            : 'Chọn Tỉnh / Thành phố'
                                    }}
                                </option>

                                <option v-for="
province in provinces
            " :key="province.ProvinceID
                " :value="province.ProvinceID
                    ">
                                    {{
                                        province.ProvinceName
                                    }}
                                </option>
                            </select>

                            <small v-if="
                                validationErrors
                                    .province_id?.[0]
                            ">
                                {{
                                    validationErrors
                                        .province_id[0]
                                }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                Quận / Huyện
                            </label>

                            <select v-model.number="form.district_id
                                " :disabled="!form.province_id ||
                                    isLoadingDistricts
                                    " @change="
                handleDistrictChange
            ">
                                <option :value="null">
                                    {{
                                        isLoadingDistricts
                                            ? 'Đang tải...'
                                            : 'Chọn Quận / Huyện'
                                    }}
                                </option>

                                <option v-for="
district in districts
            " :key="district.DistrictID
                " :value="district.DistrictID
                    ">
                                    {{
                                        district.DistrictName
                                    }}
                                </option>
                            </select>

                            <small v-if="
                                validationErrors
                                    .district_id?.[0]
                            ">
                                {{
                                    validationErrors
                                        .district_id[0]
                                }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                Phường / Xã
                            </label>

                            <select v-model="form.ward_code
                                " :disabled="!form.district_id ||
            isLoadingWards
            " @change="
            handleWardChange
        ">
                                <option :value="null">
                                    {{
                                        isLoadingWards
                                            ? 'Đang tải...'
                                    : 'Chọn Phường / Xã'
                                    }}
                                </option>

                                <option v-for="
ward in wards
            " :key="ward.WardCode
                " :value="ward.WardCode
                ">
                                    {{
                                        ward.WardName
                                    }}
                                </option>
                            </select>

                            <small v-if="
                                validationErrors
                                    .ward_code?.[0]
                            ">
                                {{
                                    validationErrors
                                .ward_code[0]
                                }}
                            </small>
                        </div>

                        <div class="form-group full-width">
                            <label>
                                Địa chỉ cụ thể
                            </label>

                            <input v-model="form
                                .address_line
                                " type="text" placeholder="123 Nguyễn Huệ">
                        </div>
                    </div>

                    <label class="default-checkbox">
                        <input v-model="form.is_default
                            " type="checkbox" :disabled="editingAddress
                                ?.is_default
                                ">

                        <span>
                            Đặt làm địa chỉ
                            mặc định
                        </span>
                    </label>

                    <footer class="form-actions">
                        <button type="button" class="cancel-button" :disabled="isSubmitting
                            " @click="closeForm">
                            Hủy
                        </button>

                        <button type="submit" class="save-button" :disabled="isSubmitting
                            ">
                            <Check :size="17" />

                            {{
                                isSubmitting
                                    ? 'Đang lưu...'
                                    : 'Lưu địa chỉ'
                            }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </div>
</template>
<style scoped>
.address-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    font-family: Roboto, Arial, sans-serif;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.13em;
}

.page-header h2 {
    margin: 0;
    font-size: 22px;
}

.page-header span {
    display: block;
    margin-top: 6px;
    color: #708078;
    font-size: 13px;
}

.add-button,
.empty-state button,
.save-button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    border: 1px solid #24734a;
    background: #24734a;
    color: #ffffff;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.address-list {
    display: grid;
    gap: 14px;
}

.address-card {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    padding: 20px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.address-card.default {
    border-left: 3px solid #24734a;
}

.address-main {
    display: flex;
    gap: 14px;
    min-width: 0;
}

.address-icon {
    display: grid;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    place-items: center;
    background: #edf6f0;
    color: #24734a;
}

.address-content {
    min-width: 0;
}

.address-title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.address-title strong {
    color: #243128;
    font-size: 14px;
}

.address-title>span {
    color: #758279;
    font-size: 12px;
}

.divider {
    color: #ccd4cf !important;
}

.default-badge {
    display: inline-flex;
    min-height: 24px;
    align-items: center;
    padding: 0 8px;
    border: 1px solid #24734a;
    color: #24734a !important;
    background: #edf6f0;
    font-weight: 600;
}

.address-content p {
    margin: 7px 0 0;
    color: #66746b;
    font-size: 12px;
    line-height: 1.5;
}

.address-actions {
    display: flex;
    flex: 0 0 auto;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: flex-end;
    gap: 8px;
}

.text-button,
.default-button {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    gap: 5px;
    padding: 0 10px;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.text-button {
    border: 0;
    background: transparent;
    color: #24734a;
}

.text-button.danger {
    color: #9b3e3e;
}

.default-button {
    border: 1px solid #c4d0c8;
    background: #ffffff;
    color: #3f4f45;
}

.state-box,
.empty-state {
    display: flex;
    min-height: 300px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    border: 1px solid #dce5df;
    background: #ffffff;
    color: #718078;
    text-align: center;
}

.empty-state svg {
    color: #24734a;
}

.empty-state h3 {
    margin: 14px 0 5px;
    color: #29362e;
}

.empty-state p {
    margin: 0 0 17px;
    font-size: 12px;
}

.alert {
    padding: 12px 15px;
    border: 1px solid;
    font-size: 12px;
}

.alert-error {
    border-color: #deb0b0;
    background: #fff2f2;
    color: #983636;
}

.alert-success {
    border-color: #9bc6a9;
    background: #edf7f0;
    color: #21633f;
}

/* MODAL */

.modal-backdrop {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgb(18 27 21 / 55%);
}

.address-modal {
    width: min(100%, 650px);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    border: 1px solid #ccd7cf;
    background: #ffffff;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 20px 22px;
    border-bottom: 1px solid #e5ebe7;
}

.modal-header p {
    margin: 0 0 5px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.13em;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.close-button {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #d0d9d3;
    background: #ffffff;
    color: #56645b;
    cursor: pointer;
}

.address-modal form {
    padding: 22px;
}

.form-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #46544b;
    font-size: 12px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    height: 42px;
    box-sizing: border-box;
    padding: 0 11px;
    border: 1px solid #cad5ce;
    outline: none;
    color: #26332b;
    background: #ffffff;
    font: inherit;
    font-size: 12px;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #24734a;
}

.form-group select:disabled {
    cursor: not-allowed;
    background: #f5f7f5;
    color: #8a958e;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #9d3a3a;
    font-size: 10px;
}

.full-width {
    grid-column: 1 / -1;
}

.default-checkbox {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 18px;
    color: #526158;
    font-size: 12px;
    cursor: pointer;
}

.default-checkbox input {
    width: 16px;
    height: 16px;
    accent-color: #24734a;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #e5ebe7;
}

.cancel-button {
    min-height: 40px;
    padding: 0 15px;
    border: 1px solid #c7d2cb;
    background: #ffffff;
    color: #46544b;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

@media (max-width: 700px) {

    .page-header,
    .address-card {
        align-items: stretch;
        flex-direction: column;
    }

    .add-button {
        width: 100%;
    }

    .address-actions {
        justify-content: flex-start;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions button {
        width: 100%;
    }
}
</style>
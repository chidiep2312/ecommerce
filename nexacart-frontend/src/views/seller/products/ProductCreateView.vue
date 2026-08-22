<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    createSellerProduct,
} from '@/api/seller/products'

const router = useRouter()

const submitting = ref(false)

const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref({})

const categories = ref([])
const brands = ref([])

const images = ref([])
const imagePreviews = ref([])
const mainImageIndex = ref(null)

const fileInput = ref(null)

const form = reactive({
    name: '',
    sku: '',
    category_id: '',
    brand_id: '',
    description: '',
    price: '',
    original_price: '',
    stock: 0,
    status: 'active',

    /*
     * Shipping profile.
     *
     * GHN:
     * - weight: gram
     * - length/width/height: cm
     */
    weight: '',
    length: '',
    width: '',
    height: '',
})

const totalImageCount = computed(() => {
    return images.value.length
})

const canSubmit = computed(() => {
    return !submitting.value
})

/*
 * Hiện trang Edit của project cũng đang dùng
 * dữ liệu mẫu cho Category/Brand.
 *
 * Khi có API thật thì thay nội dung function này
 * bằng Promise.all([...]).
 */
async function fetchOptions() {
    categories.value = [
        {
            id: 1,
            name: 'Điện thoại',
        },
        {
            id: 2,
            name: 'Máy tính',
        },
        {
            id: 3,
            name: 'Phụ kiện',
        },
    ]

    brands.value = [
        {
            id: 1,
            name: 'Apple',
        },
        {
            id: 2,
            name: 'Samsung',
        },
        {
            id: 3,
            name: 'Logitech',
        },
    ]
}

function openFileDialog() {
    fileInput.value?.click()
}

function handleImageSelection(event) {
    const selectedFiles =
        Array.from(
            event.target.files ?? [],
        )

    if (!selectedFiles.length) {
        return
    }

    errorMessage.value = ''

    clearFieldError('images')
    clearFieldError('main_image_index')

    const availableSlots =
        8 - totalImageCount.value

    if (availableSlots <= 0) {
        errorMessage.value =
            'Mỗi sản phẩm chỉ được có tối đa 8 hình ảnh.'

        event.target.value = ''

        return
    }

    const acceptedFiles = []

    for (const file of selectedFiles) {
        if (
            !file.type.startsWith(
                'image/',
            )
        ) {
            errorMessage.value =
                'Chỉ được chọn các tệp hình ảnh.'

            continue
        }

        if (
            file.size >
            5 * 1024 * 1024
        ) {
            errorMessage.value =
                `Ảnh "${file.name}" vượt quá dung lượng 5 MB.`

            continue
        }

        acceptedFiles.push(file)
    }

    acceptedFiles
        .slice(
            0,
            availableSlots,
        )
        .forEach(file => {
            images.value.push(file)

            imagePreviews.value.push({
                name: file.name,

                url:
                    URL.createObjectURL(
                        file,
                    ),
            })
        })

    /*
     * Ảnh đầu tiên tự động trở thành ảnh chính.
     */
    if (
        mainImageIndex.value ===
            null &&
        images.value.length > 0
    ) {
        mainImageIndex.value = 0
    }

    event.target.value = ''
}

function removeImage(index) {
    const preview =
        imagePreviews.value[
            index
        ]

    if (preview?.url) {
        URL.revokeObjectURL(
            preview.url,
        )
    }

    images.value.splice(
        index,
        1,
    )

    imagePreviews.value.splice(
        index,
        1,
    )

    /*
     * Không còn ảnh.
     */
    if (
        images.value.length ===
        0
    ) {
        mainImageIndex.value =
            null

        return
    }

    /*
     * Nếu xóa đúng ảnh chính thì chọn ảnh đầu.
     */
    if (
        mainImageIndex.value ===
        index
    ) {
        mainImageIndex.value = 0

        return
    }

    /*
     * Nếu xóa ảnh đứng trước ảnh chính
     * thì index ảnh chính phải giảm 1.
     */
    if (
        mainImageIndex.value >
        index
    ) {
        mainImageIndex.value--
    }
}

function selectMainImage(index) {
    mainImageIndex.value = index

    clearFieldError(
        'main_image_index',
    )
}

function isMainImage(index) {
    return (
        mainImageIndex.value ===
        index
    )
}

function getFieldError(field) {
    return (
        validationErrors.value[
            field
        ]?.[0] ?? ''
    )
}

function clearFieldError(field) {
    if (
        validationErrors.value[
            field
        ]
    ) {
        delete validationErrors
            .value[field]
    }
}

function validateForm() {
    const errors = {}

    if (!form.name.trim()) {
        errors.name = [
            'Tên sản phẩm là bắt buộc.',
        ]
    }

    if (!form.category_id) {
        errors.category_id = [
            'Vui lòng chọn danh mục.',
        ]
    }

    if (
        form.price === '' ||
        Number(form.price) < 0
    ) {
        errors.price = [
            'Giá bán phải lớn hơn hoặc bằng 0.',
        ]
    }

    if (
        form.original_price !==
            '' &&
        Number(
            form.original_price,
        ) < Number(form.price)
    ) {
        errors.original_price = [
            'Giá gốc phải lớn hơn hoặc bằng giá bán.',
        ]
    }

    if (
        form.stock === '' ||
        Number(form.stock) < 0
    ) {
        errors.stock = [
            'Tồn kho phải lớn hơn hoặc bằng 0.',
        ]
    }

    /*
     * Shipping fields bắt buộc vì Checkout
     * cần build package để tính phí GHN.
     */
    if (
        form.weight === '' ||
        Number(form.weight) <= 0
    ) {
        errors.weight = [
            'Khối lượng phải lớn hơn 0 gram.',
        ]
    }

    if (
        form.length === '' ||
        Number(form.length) <= 0
    ) {
        errors.length = [
            'Chiều dài phải lớn hơn 0 cm.',
        ]
    }

    if (
        form.width === '' ||
        Number(form.width) <= 0
    ) {
        errors.width = [
            'Chiều rộng phải lớn hơn 0 cm.',
        ]
    }

    if (
        form.height === '' ||
        Number(form.height) <= 0
    ) {
        errors.height = [
            'Chiều cao phải lớn hơn 0 cm.',
        ]
    }

    if (
        totalImageCount.value ===
        0
    ) {
        errors.images = [
            'Sản phẩm cần có ít nhất một hình ảnh.',
        ]
    }

    if (
        totalImageCount.value > 0 &&
        mainImageIndex.value ===
            null
    ) {
        errors.main_image_index = [
            'Vui lòng chọn ảnh chính.',
        ]
    }

    validationErrors.value =
        errors

    return (
        Object.keys(errors)
            .length === 0
    )
}

function buildFormData() {
    const formData =
        new FormData()

    /*
     * CREATE:
     *
     * Không có:
     * _method = PATCH
     *
     * vì đây là POST thật.
     */
    formData.append(
        'name',
        form.name.trim(),
    )

    if (form.sku.trim()) {
        formData.append(
            'sku',
            form.sku.trim(),
        )
    }

    formData.append(
        'category_id',
        String(
            form.category_id,
        ),
    )

    if (form.brand_id) {
        formData.append(
            'brand_id',
            String(
                form.brand_id,
            ),
        )
    }

    formData.append(
        'description',
        form.description ?? '',
    )

    formData.append(
        'price',
        String(form.price),
    )

    if (
        form.original_price !==
        ''
    ) {
        formData.append(
            'original_price',
            String(
                form.original_price,
            ),
        )
    }

    formData.append(
        'stock',
        String(form.stock),
    )

    formData.append(
        'status',
        form.status,
    )

    /*
     * Shipping profile.
     */
    formData.append(
        'weight',
        String(form.weight),
    )

    formData.append(
        'length',
        String(form.length),
    )

    formData.append(
        'width',
        String(form.width),
    )

    formData.append(
        'height',
        String(form.height),
    )

    /*
     * Upload ảnh.
     */
    images.value.forEach(file => {
        formData.append(
            'images[]',
            file,
        )
    })

    /*
     * Index tính theo images[] vừa gửi:
     *
     * images[0]
     * images[1]
     * images[2]
     *
     * main_image_index = 1
     * => images[1] là ảnh chính.
     */
    if (
        mainImageIndex.value !==
        null
    ) {
        formData.append(
            'main_image_index',
            String(
                mainImageIndex
                    .value,
            ),
        )
    }

    return formData
}

function extractCreatedProduct(
    response,
) {
    const responseData =
        response.data?.data

    if (
        responseData?.data &&
        typeof responseData
            .data === 'object'
    ) {
        return responseData.data
    }

    if (
        responseData &&
        typeof responseData ===
            'object'
    ) {
        return responseData
    }

    return null
}

async function submitForm() {
    successMessage.value = ''
    errorMessage.value = ''

    if (!validateForm()) {
        errorMessage.value =
            'Vui lòng kiểm tra lại thông tin sản phẩm.'

        scrollToFirstError()

        return
    }

    submitting.value = true

    try {
        const response =
            await createSellerProduct(
                buildFormData(),
            )

        const product =
            extractCreatedProduct(
                response,
            )

        successMessage.value =
            response.data?.message ??
            'Tạo sản phẩm thành công.'

        /*
         * Nếu backend trả product vừa tạo
         * thì chuyển sang trang chi tiết.
         */
        if (product?.id) {
            await router.push({
                name:
                    'seller-products-show',

                params: {
                    id: product.id,
                },

                query: {
                    created: 1,
                },
            })

            return
        }

        /*
         * Fallback nếu API chưa trả product.
         */
        await router.push(
            '/seller/products',
        )
    } catch (error) {
        if (
            error.response?.status ===
            422
        ) {
            validationErrors.value =
                error.response.data
                    ?.errors ?? {}

            errorMessage.value =
                error.response.data
                    ?.message ??
                'Dữ liệu sản phẩm chưa hợp lệ.'

            scrollToFirstError()

            return
        }

        if (
            error.response?.status ===
            403
        ) {
            errorMessage.value =
                'Bạn không có quyền tạo sản phẩm.'

            return
        }

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể tạo sản phẩm.'
    } finally {
        submitting.value = false
    }
}

function scrollToFirstError() {
    requestAnimationFrame(() => {
        document
            .querySelector(
                '.field-error',
            )
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            })
    })
}

function cancelCreate() {
    router.push(
        '/seller/products',
    )
}

/*
 * Giải phóng Object URL.
 *
 * Nếu không revoke thì preview URL có thể
 * tồn tại trong memory tới khi reload page.
 */
function revokeAllPreviews() {
    imagePreviews.value.forEach(
        preview => {
            if (preview?.url) {
                URL.revokeObjectURL(
                    preview.url,
                )
            }
        },
    )
}

onMounted(async () => {
    await fetchOptions()
})

onBeforeUnmount(() => {
    revokeAllPreviews()
})
</script>

<template>
    <section class="product-create-page">
        <header class="page-header">
            <div class="page-heading">
                <button
                    type="button"
                    class="back-button"
                    aria-label="Quay lại"
                    @click="cancelCreate"
                >
                    ←
                </button>

                <div>
                    <h2 class="page-title">
                        Thêm sản phẩm
                    </h2>

                    <p class="page-description">
                        Tạo sản phẩm mới, thiết lập giá,
                        tồn kho, thông tin vận chuyển
                        và hình ảnh.
                    </p>
                </div>
            </div>

            <div class="page-actions">
                <button
                    type="button"
                    class="
                        button
                        button-secondary
                    "
                    :disabled="submitting"
                    @click="cancelCreate"
                >
                    Hủy
                </button>

                <button
                    type="button"
                    class="
                        button
                        button-primary
                    "
                    :disabled="!canSubmit"
                    @click="submitForm"
                >
                    {{
                        submitting
                            ? 'Đang tạo...'
                            : 'Tạo sản phẩm'
                    }}
                </button>
            </div>
        </header>

        <div
            v-if="successMessage"
            class="alert alert-success"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <form
            class="create-layout"
            @submit.prevent="submitForm"
        >
            <main class="create-main">
                <!-- =========================
                     BASIC INFORMATION
                ========================== -->
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Thông tin cơ bản
                            </h3>

                            <p class="panel-description">
                                Tên, mã SKU, danh mục
                                và thương hiệu sản phẩm.
                            </p>
                        </div>
                    </header>

                    <div class="panel-content">
                        <div class="form-grid">
                            <div
                                class="
                                    form-group
                                    full-column
                                "
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'name',
                                        ),
                                }"
                            >
                                <label for="product-name">
                                    Tên sản phẩm
                                    <span>*</span>
                                </label>

                                <input
                                    id="product-name"
                                    v-model="form.name"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Nhập tên sản phẩm"
                                    @input="
                                        clearFieldError(
                                            'name',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'name',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'name',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'sku',
                                        ),
                                }"
                            >
                                <label for="product-sku">
                                    Mã SKU
                                </label>

                                <input
                                    id="product-sku"
                                    v-model="form.sku"
                                    type="text"
                                    maxlength="100"
                                    placeholder="Ví dụ: SP-001"
                                    @input="
                                        clearFieldError(
                                            'sku',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'sku',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'sku',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'status',
                                        ),
                                }"
                            >
                                <label for="product-status">
                                    Trạng thái bán
                                    <span>*</span>
                                </label>

                                <select
                                    id="product-status"
                                    v-model="
                                        form.status
                                    "
                                    @change="
                                        clearFieldError(
                                            'status',
                                        )
                                    "
                                >
                                    <option value="active">
                                        Đang bán
                                    </option>

                                    <option value="inactive">
                                        Ngừng bán
                                    </option>
                                </select>

                                <p
                                    v-if="
                                        getFieldError(
                                            'status',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'status',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'category_id',
                                        ),
                                }"
                            >
                                <label for="category-id">
                                    Danh mục
                                    <span>*</span>
                                </label>

                                <select
                                    id="category-id"
                                    v-model="
                                        form.category_id
                                    "
                                    @change="
                                        clearFieldError(
                                            'category_id',
                                        )
                                    "
                                >
                                    <option value="">
                                        Chọn danh mục
                                    </option>

                                    <option
                                        v-for="
                                            category in
                                            categories
                                        "
                                        :key="
                                            category.id
                                        "
                                        :value="
                                            category.id
                                        "
                                    >
                                        {{
                                            category.name
                                        }}
                                    </option>
                                </select>

                                <p
                                    v-if="
                                        getFieldError(
                                            'category_id',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'category_id',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'brand_id',
                                        ),
                                }"
                            >
                                <label for="brand-id">
                                    Thương hiệu
                                </label>

                                <select
                                    id="brand-id"
                                    v-model="
                                        form.brand_id
                                    "
                                    @change="
                                        clearFieldError(
                                            'brand_id',
                                        )
                                    "
                                >
                                    <option value="">
                                        Không thương hiệu
                                    </option>

                                    <option
                                        v-for="
                                            brand in
                                            brands
                                        "
                                        :key="
                                            brand.id
                                        "
                                        :value="
                                            brand.id
                                        "
                                    >
                                        {{ brand.name }}
                                    </option>
                                </select>

                                <p
                                    v-if="
                                        getFieldError(
                                            'brand_id',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'brand_id',
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- =========================
                     PRICE + STOCK
                ========================== -->
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Giá và tồn kho
                            </h3>

                            <p class="panel-description">
                                Thiết lập giá bán, giá
                                gốc và số lượng hiện có.
                            </p>
                        </div>
                    </header>

                    <div class="panel-content">
                        <div
                            class="
                                form-grid
                                three-columns
                            "
                        >
                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'price',
                                        ),
                                }"
                            >
                                <label for="price">
                                    Giá bán
                                    <span>*</span>
                                </label>

                                <div class="money-input">
                                    <input
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        placeholder="0"
                                        @input="
                                            clearFieldError(
                                                'price',
                                            )
                                        "
                                    >

                                    <span>₫</span>
                                </div>

                                <p
                                    v-if="
                                        getFieldError(
                                            'price',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'price',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'original_price',
                                        ),
                                }"
                            >
                                <label for="original-price">
                                    Giá gốc
                                </label>

                                <div class="money-input">
                                    <input
                                        id="original-price"
                                        v-model="
                                            form.original_price
                                        "
                                        type="number"
                                        min="0"
                                        step="1000"
                                        placeholder="0"
                                        @input="
                                            clearFieldError(
                                                'original_price',
                                            )
                                        "
                                    >

                                    <span>₫</span>
                                </div>

                                <p
                                    v-if="
                                        getFieldError(
                                            'original_price',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'original_price',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'stock',
                                        ),
                                }"
                            >
                                <label for="stock">
                                    Tồn kho
                                    <span>*</span>
                                </label>

                                <input
                                    id="stock"
                                    v-model.number="
                                        form.stock
                                    "
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="0"
                                    @input="
                                        clearFieldError(
                                            'stock',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'stock',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'stock',
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- =========================
                     SHIPPING PROFILE
                ========================== -->
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Thông tin vận chuyển
                            </h3>

                            <p class="panel-description">
                                Dữ liệu dùng để đóng kiện
                                và tính phí vận chuyển GHN.
                            </p>
                        </div>
                    </header>

                    <div class="panel-content">
                        <div
                            class="
                                form-grid
                                four-columns
                            "
                        >
                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'weight',
                                        ),
                                }"
                            >
                                <label for="weight">
                                    Khối lượng (g)
                                    <span>*</span>
                                </label>

                                <input
                                    id="weight"
                                    v-model.number="
                                        form.weight
                                    "
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="Ví dụ: 1500"
                                    @input="
                                        clearFieldError(
                                            'weight',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'weight',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'weight',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'length',
                                        ),
                                }"
                            >
                                <label for="length">
                                    Dài (cm)
                                    <span>*</span>
                                </label>

                                <input
                                    id="length"
                                    v-model.number="
                                        form.length
                                    "
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="40"
                                    @input="
                                        clearFieldError(
                                            'length',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'length',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'length',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'width',
                                        ),
                                }"
                            >
                                <label for="width">
                                    Rộng (cm)
                                    <span>*</span>
                                </label>

                                <input
                                    id="width"
                                    v-model.number="
                                        form.width
                                    "
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="30"
                                    @input="
                                        clearFieldError(
                                            'width',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'width',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'width',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                class="form-group"
                                :class="{
                                    'has-error':
                                        getFieldError(
                                            'height',
                                        ),
                                }"
                            >
                                <label for="height">
                                    Cao (cm)
                                    <span>*</span>
                                </label>

                                <input
                                    id="height"
                                    v-model.number="
                                        form.height
                                    "
                                    type="number"
                                    min="1"
                                    step="1"
                                    placeholder="10"
                                    @input="
                                        clearFieldError(
                                            'height',
                                        )
                                    "
                                >

                                <p
                                    v-if="
                                        getFieldError(
                                            'height',
                                        )
                                    "
                                    class="field-error"
                                >
                                    {{
                                        getFieldError(
                                            'height',
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <p class="shipping-help">
                            Nhập kích thước của một đơn vị
                            sản phẩm sau khi đóng gói.
                            Khối lượng dùng gram, kích thước
                            dùng centimet.
                        </p>
                    </div>
                </section>

                <!-- =========================
                     DESCRIPTION
                ========================== -->
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Mô tả sản phẩm
                            </h3>

                            <p class="panel-description">
                                Cung cấp thông tin chi
                                tiết giúp khách hàng hiểu sản phẩm.
                            </p>
                        </div>
                    </header>

                    <div class="panel-content">
                        <div
                            class="form-group"
                            :class="{
                                'has-error':
                                    getFieldError(
                                        'description',
                                    ),
                            }"
                        >
                            <label for="description">
                                Nội dung mô tả
                            </label>

                            <textarea
                                id="description"
                                v-model="
                                    form.description
                                "
                                rows="10"
                                placeholder="Nhập mô tả sản phẩm..."
                                @input="
                                    clearFieldError(
                                        'description',
                                    )
                                "
                            />

                            <p
                                v-if="
                                    getFieldError(
                                        'description',
                                    )
                                "
                                class="field-error"
                            >
                                {{
                                    getFieldError(
                                        'description',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- =========================
                     IMAGES
                ========================== -->
                <section class="panel">
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Hình ảnh sản phẩm
                            </h3>

                            <p class="panel-description">
                                Tối đa 8 ảnh, mỗi ảnh
                                không vượt quá 5 MB.
                            </p>
                        </div>

                        <span class="image-count">
                            {{ totalImageCount }}/8
                        </span>
                    </header>

                    <div class="panel-content">
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            multiple
                            hidden
                            @change="
                                handleImageSelection
                            "
                        >

                        <button
                            type="button"
                            class="upload-box"
                            :disabled="
                                totalImageCount >= 8
                            "
                            @click="
                                openFileDialog
                            "
                        >
                            <span class="upload-icon">
                                +
                            </span>

                            <strong>
                                Thêm hình ảnh
                            </strong>

                            <small>
                                JPG, PNG, WEBP,
                                tối đa 5 MB
                            </small>
                        </button>

                        <p
                            v-if="
                                getFieldError(
                                    'images',
                                )
                            "
                            class="
                                field-error
                                image-error
                            "
                        >
                            {{
                                getFieldError(
                                    'images',
                                )
                            }}
                        </p>

                        <p
                            v-if="
                                getFieldError(
                                    'main_image_index',
                                )
                            "
                            class="
                                field-error
                                image-error
                            "
                        >
                            {{
                                getFieldError(
                                    'main_image_index',
                                )
                            }}
                        </p>

                        <div
                            v-if="
                                imagePreviews.length
                            "
                            class="image-grid"
                        >
                            <article
                                v-for="
                                    (
                                        preview,
                                        index
                                    ) in
                                    imagePreviews
                                "
                                :key="
                                    `new-${index}`
                                "
                                class="image-card"
                                :class="{
                                    'main-image':
                                        isMainImage(
                                            index,
                                        ),
                                }"
                            >
                                <div class="image-preview">
                                    <img
                                        :src="
                                            preview.url
                                        "
                                        :alt="
                                            preview.name
                                        "
                                    >

                                    <span class="new-label">
                                        Ảnh mới
                                    </span>

                                    <span
                                        v-if="
                                            isMainImage(
                                                index,
                                            )
                                        "
                                        class="main-label"
                                    >
                                        Ảnh chính
                                    </span>
                                </div>

                                <div class="image-actions">
                                    <button
                                        type="button"
                                        class="
                                            image-main-button
                                        "
                                        :disabled="
                                            isMainImage(
                                                index,
                                            )
                                        "
                                        @click="
                                            selectMainImage(
                                                index,
                                            )
                                        "
                                    >
                                        {{
                                            isMainImage(
                                                index,
                                            )
                                                ? 'Đang chọn'
                                                : 'Chọn làm chính'
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        class="
                                            image-remove-button
                                        "
                                        @click="
                                            removeImage(
                                                index,
                                            )
                                        "
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </main>

            <!-- =========================
                 SIDEBAR
            ========================== -->
            <aside class="create-sidebar">
                <section
                    class="
                        panel
                        sticky-panel
                    "
                >
                    <header class="panel-header">
                        <div>
                            <h3 class="panel-title">
                                Xuất bản
                            </h3>

                            <p class="panel-description">
                                Kiểm tra trước khi tạo.
                            </p>
                        </div>
                    </header>

                    <dl class="summary-list">
                        <div>
                            <dt>
                                Trạng thái
                            </dt>

                            <dd>
                                {{
                                    form.status ===
                                    'active'
                                        ? 'Đang bán'
                                        : 'Ngừng bán'
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Tồn kho
                            </dt>

                            <dd>
                                {{ form.stock || 0 }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Hình ảnh
                            </dt>

                            <dd>
                                {{ totalImageCount }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Khối lượng
                            </dt>

                            <dd>
                                {{
                                    form.weight
                                        ? `${form.weight} g`
                                        : 'Chưa nhập'
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div class="sidebar-actions">
                        <button
                            type="submit"
                            class="
                                button
                                button-primary
                                full-button
                            "
                            :disabled="
                                !canSubmit
                            "
                        >
                            {{
                                submitting
                                    ? 'Đang tạo...'
                                    : 'Tạo sản phẩm'
                            }}
                        </button>

                        <button
                            type="button"
                            class="
                                button
                                button-secondary
                                full-button
                            "
                            :disabled="
                                submitting
                            "
                            @click="
                                cancelCreate
                            "
                        >
                            Hủy
                        </button>
                    </div>
                </section>
            </aside>
        </form>
    </section>
</template>

<style scoped>
.product-create-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}

.page-heading {
    display: flex;
    align-items: center;
    gap: 13px;
}

.back-button {
    display: flex;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #374151;
    cursor: pointer;
    font-size: 20px;
}

.back-button:hover {
    border-color: #15803d;
    color: #15803d;
}

.page-title {
    margin: 0;
    color: #111827;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 7px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.button-primary {
    border-color: #15803d;
    background: #15803d;
    color: #ffffff;
}

.button-primary:hover:not(:disabled) {
    background: #166534;
}

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover:not(:disabled) {
    border-color: #9ca3af;
    background: #f9fafb;
}

.full-button {
    width: 100%;
}

.alert {
    border: 1px solid;
    padding: 12px 15px;
    font-size: 13px;
}

.alert-success {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.alert-error {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #991b1b;
}

.create-layout {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        300px;
    gap: 20px;
    align-items: start;
}

.create-main,
.create-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel {
    border: 1px solid #e1e7e3;
    background: #ffffff;
}

.panel-header {
    display: flex;
    min-height: 68px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 18px;
}

.panel-title {
    margin: 0;
    color: #111827;
    font-size: 15px;
    font-weight: 600;
}

.panel-description {
    margin: 5px 0 0;
    color: #9ca3af;
    font-size: 12px;
}

.panel-content {
    padding: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-grid.three-columns {
    grid-template-columns:
        repeat(3, minmax(0, 1fr));
}

.form-grid.four-columns {
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
}

.full-column {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label {
    color: #374151;
    font-size: 12px;
    font-weight: 500;
}

.form-group label span {
    color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #111827;
    font: inherit;
    font-size: 13px;
    outline: none;
}

.form-group input,
.form-group select {
    min-height: 42px;
    padding: 0 11px;
}

.form-group textarea {
    min-height: 210px;
    resize: vertical;
    padding: 11px;
    line-height: 1.7;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #16a34a;
}

.form-group.has-error input,
.form-group.has-error select,
.form-group.has-error textarea {
    border-color: #dc2626;
}

.field-error {
    margin: 0;
    color: #dc2626;
    font-size: 11px;
}

.money-input {
    position: relative;
}

.money-input input {
    padding-right: 38px;
}

.money-input span {
    position: absolute;
    top: 50%;
    right: 12px;
    color: #6b7280;
    transform: translateY(-50%);
}

.shipping-help {
    margin: 14px 0 0;
    color: #6b7280;
    font-size: 11px;
    line-height: 1.6;
}

.image-count {
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    padding: 5px 8px;
    color: #166534;
    font-size: 11px;
}

.upload-box {
    display: flex;
    width: 100%;
    min-height: 120px;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 7px;
    border: 1px dashed #9ca3af;
    border-radius: 0;
    background: #fafbfa;
    color: #374151;
    cursor: pointer;
    font: inherit;
}

.upload-box:hover:not(:disabled) {
    border-color: #15803d;
    background: #f0fdf4;
}

.upload-box:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.upload-icon {
    display: flex;
    width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    background: #15803d;
    color: #ffffff;
    font-size: 21px;
}

.upload-box strong {
    font-size: 13px;
}

.upload-box small {
    color: #9ca3af;
    font-size: 11px;
}

.image-error {
    margin-top: 8px;
}

.image-grid {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.image-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.image-card.main-image {
    border-color: #15803d;
}

.image-preview {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #f3f4f6;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-label,
.new-label {
    position: absolute;
    padding: 4px 7px;
    color: #ffffff;
    font-size: 10px;
    font-weight: 500;
}

.main-label {
    right: 6px;
    bottom: 6px;
    background: #15803d;
}

.new-label {
    top: 6px;
    left: 6px;
    background: #374151;
}

.image-actions {
    display: flex;
    min-height: 42px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-top: 1px solid #e5e7eb;
    padding: 0 8px;
}

.image-actions button {
    border: 0;
    background: transparent;
    padding: 4px 0;
    cursor: pointer;
    font: inherit;
    font-size: 10px;
    font-weight: 500;
}

.image-actions button:disabled {
    cursor: default;
}

.image-main-button {
    color: #15803d;
}

.image-remove-button {
    color: #dc2626;
}

.sticky-panel {
    position: sticky;
    top: 94px;
}

.summary-list {
    margin: 0;
}

.summary-list > div {
    display: flex;
    min-height: 46px;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #f3f4f6;
    padding: 0 18px;
}

.summary-list dt {
    color: #9ca3af;
    font-size: 11px;
}

.summary-list dd {
    margin: 0;
    color: #374151;
    font-size: 12px;
    font-weight: 500;
}

.sidebar-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 18px;
}

@media (max-width: 1150px) {
    .create-layout {
        grid-template-columns: 1fr;
    }

    .sticky-panel {
        position: static;
    }

    .create-sidebar {
        order: -1;
    }

    .summary-list {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
    }

    .summary-list > div {
        border-right: 1px solid #f3f4f6;
    }

    .sidebar-actions {
        flex-direction: row;
    }
}

@media (max-width: 900px) {
    .form-grid.four-columns {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 850px) {
    .form-grid.three-columns {
        grid-template-columns: 1fr;
    }

    .image-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 680px) {
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-actions {
        width: 100%;
    }

    .page-actions .button {
        flex: 1;
    }

    .form-grid,
    .form-grid.four-columns {
        grid-template-columns: 1fr;
    }

    .image-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .summary-list {
        grid-template-columns: 1fr;
    }

    .summary-list > div {
        border-right: 0;
    }

    .sidebar-actions {
        flex-direction: column;
    }
}

@media (max-width: 430px) {
    .page-heading {
        align-items: flex-start;
    }

    .page-actions {
        flex-direction: column;
    }

    .image-grid {
        grid-template-columns: 1fr;
    }
}
</style>

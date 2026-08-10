<script setup>
import {
    ArrowLeft,
    ImagePlus,
    PackageCheck,
    Send,
    Star,
    X,
} from '@lucide/vue'

import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    getReviewableOrderItem,
    submitProductReview,
} from '@/api/reviews'

const route = useRoute()
const router = useRouter()

const orderItem = ref(null)

const isLoading = ref(false)
const isSubmitting = ref(false)

const errorMessage = ref('')
const validationErrors = ref({})

const hoverRating = ref(0)

const form = reactive({
    rating: 0,
    comment: '',
})

const selectedImages = ref([])
const imagePreviews = ref([])

const displayRating = computed(() => {
    return (
        hoverRating.value ||
        form.rating
    )
})

const ratingText = computed(() => {
    const labels = {
        1: 'Rất không hài lòng',
        2: 'Không hài lòng',
        3: 'Bình thường',
        4: 'Hài lòng',
        5: 'Rất hài lòng',
    }

    return (
        labels[displayRating.value] ??
        'Chọn mức đánh giá'
    )
})

const canSubmit = computed(() => {
    return (
        form.rating >= 1 &&
        form.rating <= 5 &&
        !isSubmitting.value
    )
})

async function fetchOrderItem() {
    const orderId =
        route.params.orderId

    const itemId =
        route.params.itemId

    if (
        !orderId ||
        !itemId
    ) {
        errorMessage.value =
            'Không xác định được sản phẩm cần đánh giá.'

        return
    }

    isLoading.value = true
    errorMessage.value = ''

    try {
        const response =
            await getReviewableOrderItem(
                orderId,
                itemId,
            )

        orderItem.value =
            response.data?.data ??
            null
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải thông tin sản phẩm.'

        orderItem.value = null
    } finally {
        isLoading.value = false
    }
}

function selectRating(rating) {
    form.rating = rating

    if (
        validationErrors.value.rating
    ) {
        delete validationErrors
            .value.rating
    }
}

function handleImages(event) {
    const files =
        Array.from(
            event.target.files ??
            [],
        )

    const availableSlots =
        5 -
        selectedImages.value.length

    const acceptedFiles =
        files
            .filter(file => {
                return file.type
                    .startsWith(
                        'image/',
                    )
            })
            .slice(
                0,
                availableSlots,
            )

    for (
        const file
        of acceptedFiles
    ) {
        selectedImages.value.push(
            file,
        )

        imagePreviews.value.push(
            URL.createObjectURL(
                file,
            ),
        )
    }

    event.target.value = ''
}

function removeImage(index) {
    URL.revokeObjectURL(
        imagePreviews.value[
            index
        ],
    )

    imagePreviews.value.splice(
        index,
        1,
    )

    selectedImages.value.splice(
        index,
        1,
    )
}

async function submitReview() {
    if (!canSubmit.value) {
        return
    }

    isSubmitting.value = true

    errorMessage.value = ''
    validationErrors.value = {}

    try {
        const payload =
            new FormData()

        payload.append(
            'rating',
            String(
                form.rating,
            ),
        )

        if (
            form.comment.trim()
        ) {
            payload.append(
                'comment',
                form.comment.trim(),
            )
        }

        selectedImages.value
            .forEach(image => {
                payload.append(
                    'images[]',
                    image,
                )
            })

        await submitProductReview(
            route.params.orderId,
            route.params.itemId,
            payload,
        )

        router.push({
            name:
                'customer-order-detail',

            params: {
                id:
                    route.params
                        .orderId,
            },

            query: {
                reviewed:
                    'success',
            },
        })
    } catch (error) {
        if (
            error.response?.status ===
            422
        ) {
            validationErrors.value =
                error.response?.data
                    ?.errors ?? {}

            return
        }

        errorMessage.value =
            error.response?.data
                ?.message ??
            'Không thể gửi đánh giá.'
    } finally {
        isSubmitting.value = false
    }
}

function goBack() {
    router.push({
        name:
            'customer-order-detail',

        params: {
            id:
                route.params
                    .orderId,
        },
    })
}

function formatPrice(value) {
    return new Intl.NumberFormat(
        'vi-VN',
        {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        },
    ).format(
        Number(value ?? 0),
    )
}

function getProductImage(item) {
    return (
        item?.product
            ?.main_image?.url ??
        item?.product_image ??
        ''
    )
}

onMounted(() => {
    fetchOrderItem()
})

onBeforeUnmount(() => {
    imagePreviews.value
        .forEach(url => {
            URL.revokeObjectURL(
                url,
            )
        })
})
</script>

<template>
    <div class="review-page">
        <header class="review-header">
            <div>
                <p class="eyebrow">
                    ĐÁNH GIÁ SẢN PHẨM
                </p>

                <h1>
                    Chia sẻ trải nghiệm
                </h1>

                <p>
                    Đánh giá của bạn giúp
                    người mua khác có thêm
                    thông tin về sản phẩm.
                </p>
            </div>

            <button
                type="button"
                class="back-button"
                @click="goBack"
            >
                <ArrowLeft
                    :size="16"
                />

                Quay lại đơn hàng
            </button>
        </header>

        <div
            v-if="errorMessage"
            class="alert alert-error"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="isLoading"
            class="state-box"
        >
            Đang tải sản phẩm...
        </div>

        <template
            v-else-if="
                orderItem
            "
        >
            <!-- PRODUCT -->
            <section class="product-card">
                <div class="product-image">
                    <img
                        v-if="
                            getProductImage(
                                orderItem,
                            )
                        "
                        :src="
                            getProductImage(
                                orderItem,
                            )
                        "
                        :alt="
                            orderItem
                                .product_name
                        "
                    >

                    <PackageCheck
                        v-else
                        :size="32"
                    />
                </div>

                <div class="product-information">
                    <span>
                        Sản phẩm đã mua
                    </span>

                    <h2>
                        {{
                            orderItem
                                .product_name
                        }}
                    </h2>

                    <p>
                        SKU:
                        {{
                            orderItem
                                .product_sku ??
                            '--'
                        }}
                    </p>

                    <div>
                        <span>
                            Số lượng:
                            {{
                                orderItem
                                    .quantity
                            }}
                        </span>

                        <strong>
                            {{
                                formatPrice(
                                    orderItem
                                        .unit_price,
                                )
                            }}
                        </strong>
                    </div>
                </div>
            </section>

            <section class="review-card">
                <!-- RATING -->
                <div class="review-section">
                    <header>
                        <h2>
                            Bạn đánh giá sản phẩm
                            thế nào?
                        </h2>

                        <p>
                            Chọn từ 1 đến 5 sao.
                        </p>
                    </header>

                    <div class="rating-area">
                        <div class="stars">
                            <button
                                v-for="
                                    rating
                                    in 5
                                "
                                :key="
                                    rating
                                "
                                type="button"
                                :class="{
                                    active:
                                        rating <=
                                        displayRating,
                                }"
                                @mouseenter="
                                    hoverRating =
                                        rating
                                "
                                @mouseleave="
                                    hoverRating =
                                        0
                                "
                                @click="
                                    selectRating(
                                        rating,
                                    )
                                "
                            >
                                <Star
                                    :size="32"
                                    :fill="
                                        rating <=
                                        displayRating
                                            ? 'currentColor'
                                            : 'none'
                                    "
                                />
                            </button>
                        </div>

                        <strong>
                            {{
                                ratingText
                            }}
                        </strong>
                    </div>

                    <small
                        v-if="
                            validationErrors
                                .rating?.[0]
                        "
                        class="field-error"
                    >
                        {{
                            validationErrors
                                .rating[0]
                        }}
                    </small>
                </div>

                <!-- COMMENT -->
                <div class="review-section">
                    <header>
                        <h2>
                            Nhận xét của bạn
                        </h2>

                        <p>
                            Chia sẻ về chất lượng,
                            trải nghiệm sử dụng hoặc
                            mức độ hài lòng.
                        </p>
                    </header>

                    <div class="comment-box">
                        <textarea
                            v-model="
                                form.comment
                            "
                            maxlength="1000"
                            placeholder="Ví dụ: Sản phẩm đúng mô tả, đóng gói cẩn thận..."
                        />

                        <span>
                            {{
                                form.comment
                                    .length
                            }}/1000
                        </span>
                    </div>

                    <small
                        v-if="
                            validationErrors
                                .comment?.[0]
                        "
                        class="field-error"
                    >
                        {{
                            validationErrors
                                .comment[0]
                        }}
                    </small>
                </div>

                <!-- IMAGES -->
                <div class="review-section">
                    <header>
                        <h2>
                            Hình ảnh thực tế
                        </h2>

                        <p>
                            Có thể thêm tối đa
                            5 hình ảnh.
                        </p>
                    </header>

                    <div class="image-list">
                        <article
                            v-for="(
                                preview,
                                index
                            ) in imagePreviews"
                            :key="
                                preview
                            "
                            class="preview-image"
                        >
                            <img
                                :src="
                                    preview
                                "
                                alt="Ảnh đánh giá"
                            >

                            <button
                                type="button"
                                @click="
                                    removeImage(
                                        index,
                                    )
                                "
                            >
                                <X
                                    :size="14"
                                />
                            </button>
                        </article>

                        <label
                            v-if="
                                selectedImages
                                    .length <
                                5
                            "
                            class="upload-button"
                        >
                            <ImagePlus
                                :size="22"
                            />

                            <span>
                                Thêm ảnh
                            </span>

                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                @change="
                                    handleImages
                                "
                            >
                        </label>
                    </div>

                    <small
                        v-if="
                            validationErrors
                                .images?.[0]
                        "
                        class="field-error"
                    >
                        {{
                            validationErrors
                                .images[0]
                        }}
                    </small>
                </div>

                <footer class="review-actions">
                    <p>
                        Đánh giá chỉ có thể được
                        gửi cho sản phẩm thuộc đơn
                        hàng đã hoàn thành.
                    </p>

                    <button
                        type="button"
                        :disabled="
                            !canSubmit
                        "
                        @click="
                            submitReview
                        "
                    >
                        <Send
                            :size="17"
                        />

                        {{
                            isSubmitting
                                ? 'Đang gửi...'
                                : 'Gửi đánh giá'
                        }}
                    </button>
                </footer>
            </section>
        </template>
    </div>
</template>

<style scoped>
.review-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #1f2c24;
    font-family: Roboto, Arial, sans-serif;
}

/* HEADER */

.review-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 22px 24px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.eyebrow {
    margin: 0 0 7px;
    color: #24734a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.review-header h1 {
    margin: 0;
    font-size: 22px;
}

.review-header p:last-child {
    max-width: 600px;
    margin: 7px 0 0;
    color: #748178;
    font-size: 12px;
    line-height: 1.6;
}

.back-button {
    display: inline-flex;
    min-height: 38px;
    flex: 0 0 auto;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    color: #46544b;
    background: #ffffff;
    border: 1px solid #cad5ce;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

/* PRODUCT */

.product-card {
    display: grid;
    grid-template-columns:
        100px minmax(0, 1fr);
    gap: 17px;
    padding: 18px;
    border: 1px solid #dce5df;
    background: #ffffff;
}

.product-image {
    display: grid;
    width: 100px;
    height: 100px;
    place-items: center;
    overflow: hidden;
    color: #8a978f;
    background: #f4f7f5;
    border: 1px solid #e0e7e2;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-information {
    min-width: 0;
}

.product-information > span {
    color: #24734a;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.product-information h2 {
    margin: 6px 0 0;
    font-size: 15px;
}

.product-information p {
    margin: 6px 0 0;
    color: #7a877f;
    font-size: 10px;
}

.product-information > div {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 11px;
}

.product-information > div span {
    color: #6d7b72;
    font-size: 10px;
}

.product-information > div strong {
    color: #24734a;
    font-size: 12px;
}

/* REVIEW */

.review-card {
    border: 1px solid #dce5df;
    background: #ffffff;
}

.review-section {
    padding: 23px;
    border-bottom: 1px solid #e5ebe7;
}

.review-section > header h2 {
    margin: 0;
    font-size: 14px;
}

.review-section > header p {
    margin: 5px 0 0;
    color: #7a877f;
    font-size: 10px;
}

/* STAR */

.rating-area {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 18px;
}

.stars {
    display: flex;
    gap: 4px;
}

.stars button {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    padding: 0;
    color: #bac3bd;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.stars button.active {
    color: #c48620;
}

.rating-area > strong {
    color: #5d6b62;
    font-size: 11px;
}

/* COMMENT */

.comment-box {
    position: relative;
    margin-top: 15px;
}

.comment-box textarea {
    width: 100%;
    min-height: 140px;
    box-sizing: border-box;
    padding: 13px 13px 29px;
    resize: vertical;
    color: #29362e;
    background: #fafcfb;
    border: 1px solid #cad5ce;
    outline: none;
    font: inherit;
    font-size: 12px;
    line-height: 1.7;
}

.comment-box textarea:focus {
    background: #ffffff;
    border-color: #24734a;
}

.comment-box > span {
    position: absolute;
    right: 11px;
    bottom: 9px;
    color: #8b978f;
    font-size: 9px;
}

/* IMAGES */

.image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.preview-image {
    position: relative;
    width: 90px;
    height: 90px;
    overflow: hidden;
    border: 1px solid #d7e0da;
}

.preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-image button {
    position: absolute;
    top: 5px;
    right: 5px;
    display: grid;
    width: 25px;
    height: 25px;
    place-items: center;
    padding: 0;
    color: #ffffff;
    background: rgb(30 40 33 / 75%);
    border: 0;
    cursor: pointer;
}

.upload-button {
    display: flex;
    width: 90px;
    height: 90px;
    box-sizing: border-box;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 6px;
    color: #24734a;
    background: #f5f9f6;
    border: 1px dashed #9eb9a7;
    font-size: 9px;
    font-weight: 600;
    cursor: pointer;
}

.upload-button input {
    display: none;
}

/* ACTION */

.review-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 18px 23px;
    background: #fafcfb;
}

.review-actions p {
    margin: 0;
    color: #7a877f;
    font-size: 10px;
    line-height: 1.5;
}

.review-actions button {
    display: inline-flex;
    min-width: 140px;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 15px;
    color: #ffffff;
    background: #24734a;
    border: 1px solid #24734a;
    font: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.review-actions button:hover:not(:disabled) {
    background: #1d633f;
}

.review-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* STATE */

.alert {
    padding: 11px 14px;
    border: 1px solid;
    font-size: 11px;
}

.alert-error {
    color: #963737;
    background: #fff3f3;
    border-color: #dfb1b1;
}

.field-error {
    display: block;
    margin-top: 6px;
    color: #983838;
    font-size: 10px;
}

.state-box {
    display: grid;
    min-height: 350px;
    place-items: center;
    color: #748178;
    background: #ffffff;
    border: 1px solid #dce5df;
    font-size: 12px;
}

@media (max-width: 650px) {
    .review-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .back-button {
        width: 100%;
        justify-content: center;
    }

    .product-card {
        grid-template-columns:
            76px minmax(0, 1fr);
    }

    .product-image {
        width: 76px;
        height: 76px;
    }

    .rating-area {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }

    .review-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .review-actions button {
        width: 100%;
    }
}
</style>
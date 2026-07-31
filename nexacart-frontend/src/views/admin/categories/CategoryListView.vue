<script setup>
import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    createAdminCategory,
    deleteAdminCategory,
    getAdminCategories,
    updateAdminCategory,
} from '@/api/admin/categories'

const categories = ref([])

const loading = ref(false)
const submitting = ref(false)
const deletingCategoryId = ref(null)

const errorMessage = ref('')
const successMessage = ref('')

const showFormModal = ref(false)

const editingCategory = ref(null)

const filters = ref({
    search: '',
    status: '',
    per_page: 10,
    page: 1,
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
})

const form = ref({
    name: '',
    slug: '',
    status: 'active',
})

const formErrors = ref({})

let searchTimeout = null

const hasCategories = computed(() => {
    return categories.value.length > 0
})

const isEditing = computed(() => {
    return editingCategory.value !== null
})

const modalTitle = computed(() => {
    return isEditing.value
        ? 'Cập nhật danh mục'
        : 'Thêm danh mục'
})

const submitButtonText = computed(() => {
    if (submitting.value) {
        return 'Đang lưu...'
    }

    return isEditing.value
        ? 'Cập nhật'
        : 'Thêm danh mục'
})

const pageNumbers = computed(() => {
    const currentPage =
        pagination.value.current_page

    const lastPage =
        pagination.value.last_page

    const startPage = Math.max(
        1,
        currentPage - 2,
    )

    const endPage = Math.min(
        lastPage,
        currentPage + 2,
    )

    const pages = []

    for (
        let page = startPage;
        page <= endPage;
        page += 1
    ) {
        pages.push(page)
    }

    return pages
})

async function fetchCategories() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await getAdminCategories({
            search:
                filters.value.search ||
                undefined,

            status:
                filters.value.status ||
                undefined,

            per_page:
                filters.value.per_page,

            page:
                filters.value.page,
        })

        const responseData = response.data

        categories.value =
            responseData.data ?? []

        pagination.value = {
            current_page:
                responseData.meta
                    ?.current_page ?? 1,

            last_page:
                responseData.meta
                    ?.last_page ?? 1,

            per_page:
                responseData.meta
                    ?.per_page ?? 10,

            total:
                responseData.meta
                    ?.total ?? 0,

            from:
                responseData.meta
                    ?.from ?? 0,

            to:
                responseData.meta
                    ?.to ?? 0,
        }
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể tải danh sách danh mục.'
    } finally {
        loading.value = false
    }
}

function resetForm() {
    editingCategory.value = null

    form.value = {
        name: '',
        slug: '',
        status: 'active',
    }

    formErrors.value = {}
}

function openCreateModal() {
    resetForm()

    successMessage.value = ''
    errorMessage.value = ''

    showFormModal.value = true
}

function openEditModal(category) {
    editingCategory.value = category

    form.value = {
        name: category.name ?? '',
        slug: category.slug ?? '',
        status:
            category.status ?? 'active',
    }

    formErrors.value = {}
    successMessage.value = ''
    errorMessage.value = ''

    showFormModal.value = true
}

function closeFormModal() {
    if (submitting.value) {
        return
    }

    showFormModal.value = false

    resetForm()
}

function generateSlug() {
    form.value.slug = form.value.name
        .normalize('NFD')
        .replace(
            /[\u0300-\u036f]/g,
            '',
        )
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
}

async function submitCategory() {
    submitting.value = true

    formErrors.value = {}
    errorMessage.value = ''
    successMessage.value = ''

    try {
        let response

        if (isEditing.value) {
            response =
                await updateAdminCategory(
                    editingCategory.value.id,
                    {
                        name: form.value.name,
                        slug: form.value.slug,
                        status:
                            form.value.status,
                    },
                )
        } else {
            response =
                await createAdminCategory({
                    name: form.value.name,
                    slug: form.value.slug,
                    status:
                        form.value.status,
                })
        }

        successMessage.value =
            response.data?.message ??
            (
                isEditing.value
                    ? 'Cập nhật danh mục thành công.'
                    : 'Tạo danh mục thành công.'
            )

        closeFormModal()

        await fetchCategories()
    } catch (error) {
        if (
            error.response?.status === 422
        ) {
            formErrors.value =
                error.response?.data
                    ?.errors ?? {}

            return
        }

        errorMessage.value =
            error.response?.data?.message ??
            'Không thể lưu danh mục.'
    } finally {
        submitting.value = false
    }
}

async function removeCategory(category) {
    const confirmed = window.confirm(
        `Bạn có chắc muốn xóa danh mục "${category.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    deletingCategoryId.value = category.id

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await deleteAdminCategory(
              category.id,
            )

        successMessage.value =
            response.data?.message ??
            'Xóa danh mục thành công.'

        /*
         * Nếu xóa bản ghi cuối cùng
         * của trang hiện tại thì quay
         * về trang trước.
         */
        if (
            categories.value.length === 1 &&
            filters.value.page > 1
        ) {
            filters.value.page -= 1
        }

        await fetchCategories()
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể xóa danh mục.'
    } finally {
        deletingCategoryId.value = null
    }
}

async function toggleCategoryStatus(category) {
    const nextStatus =
        category.status === 'active'
            ? 'inactive'
            : 'active'

    const actionText =
        nextStatus === 'inactive'
            ? 'ngừng hoạt động'
            : 'kích hoạt'

    const confirmed = window.confirm(
        `Bạn có chắc muốn ${actionText} danh mục sản phẩm "${category.name}" không?`,
    )

    if (!confirmed) {
        return
    }

    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response =
            await updateAdminCategory(
               category.id,
                {
                    status: nextStatus,
                },
            )

      category.status =
            response.data?.data?.status ??
            nextStatus

        successMessage.value =
            response.data?.message ??
            `Đã ${actionText} danh mục.`
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ??
            'Không thể cập nhật trạng thái danh mục.'
    }
}

function resetFilters() {
    filters.value.search = ''
    filters.value.status = ''
    filters.value.per_page = 10
    filters.value.page = 1

    fetchBrands()
}

function changePage(page) {
    if (
        page < 1 ||
        page > pagination.value.last_page ||
        page === pagination.value.current_page
    ) {
        return
    }

    filters.value.page = page

    fetchBrands()
}

watch(
    () => filters.value.search,
    () => {
        clearTimeout(searchTimeout)

        searchTimeout = setTimeout(() => {
            filters.value.page = 1
            fetchBrands()
        }, 500)
    },
)

watch(
    () => filters.value.status,
    () => {
        filters.value.page = 1
        fetchCategories()
    },
)

watch(
    () => filters.value.per_page,
    () => {
        filters.value.page = 1
        fetchCategories()
    },
)

onMounted(() => {
    fetchCategories()
})
</script>

<template>
    <section class="brand-page">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    Quản lý danh mục
                </h1>

                <p class="page-description">
                    Quản lý danh sách danh mục sản phẩm.
                   
                </p>
            </div>

            <button
                type="button"
                class="button button-primary"
                @click="openCreateModal"
            >
                Thêm danh mục
            </button>
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

        <div class="filter-panel">
            <div class="filter-field search-field">
                <label for="brand-search">
                    Tìm kiếm
                </label>

                <input
                    id="brand-search"
                    v-model.trim="filters.search"
                    type="search"
                    placeholder="Tên hoặc slug danh mục"
                >
            </div>

            <div class="filter-field">
                <label for="brand-status">
                    Trạng thái
                </label>

                <select
                    id="brand-status"
                    v-model="filters.status"
                >
                    <option value="">
                        Tất cả trạng thái
                    </option>

                    <option value="active">
                        Đang hoạt động
                    </option>

                    <option value="inactive">
                        Ngừng hoạt động
                    </option>
                </select>
            </div>

            <div class="filter-field">
                <label for="brand-per-page">
                    Số dòng
                </label>

                <select
                    id="brand-per-page"
                    v-model.number="filters.per_page"
                >
                    <option :value="10">
                        10
                    </option>

                    <option :value="20">
                        20
                    </option>

                    <option :value="50">
                        50
                    </option>
                </select>
            </div>

            <div class="filter-actions">
                <button
                    type="button"
                    class="button button-secondary"
                    @click="resetFilters"
                >
                    Đặt lại
                </button>
            </div>
        </div>

        <div class="table-card">
            <div
                v-if="loading"
                class="table-state"
            >
                Đang tải danh sách danh mục...
            </div>

            <div
                v-else-if="!hasCategories"
                class="table-state"
            >
                Không tìm thấy danh mục phù hợp.
            </div>

            <div
                v-else
                class="table-wrapper"
            >
                <table class="brand-table">
                    <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th>Slug</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>

                            <th class="action-column">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                        >
                            <td>
                                <div class="brand-info">
                                    <div class="brand-symbol">
                                        {{
                                            category.name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div>
                                        <div class="brand-name">
                                            {{ category.name }}
                                        </div>

                                        <div class="brand-id">
                                            ID: {{ category.id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <code class="slug-value">
                                    {{ category.slug }}
                                </code>
                            </td>

                            <td>
                                <span
                                    class="status-badge"
                                    :class="{
                                        'status-active':
                                          category.status ===
                                            'active',

                                        'status-inactive':
                                           category.status ===
                                            'inactive',
                                    }"
                                >
                                    {{
                                       category.status ===
                                        'active'
                                            ? 'Đang hoạt động'
                                            : 'Ngừng hoạt động'
                                    }}
                                </span>
                            </td>

                            <td>
                                {{
                                   category
                                        .created_at_formatted ??
                                    '—'
                                }}
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="button-link"
                                        @click="
                                            openEditModal(
                                                category,
                                            )
                                        "
                                    >
                                        Sửa
                                    </button>

                                    <button
                                        type="button"
                                        class="button-link"
                                        :class="{
                                            'button-warning':
                                                category.status ===
                                                'active',
                                        }"
                                        @click="
                                            toggleCategoryStatus(
                                                category,
                                            )
                                        "
                                    >
                                        {{
                                           category.status ===
                                            'active'
                                                ? 'Ngừng'
                                                : 'Kích hoạt'
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        class="
                                            button-link
                                            button-danger-link
                                        "
                                        :disabled="
                                            deletingCategoryId ===
                                            category.id
                                        "
                                        @click="
                                            removeCategory(
                                               category,
                                            )
                                        "
                                    >
                                        {{
                                            deletingCategoryId ===
                                           category.id
                                                ? 'Đang xóa...'
                                                : 'Xóa'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer
                v-if="
                    !loading &&
                    pagination.total > 0
                "
                class="pagination"
            >
                <div class="pagination-info">
                    Hiển thị
                    {{ pagination.from }}
                    –
                    {{ pagination.to }}
                    trong
                    {{ pagination.total }}
                    thương hiệu
                </div>

                <div class="pagination-buttons">
                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination.current_page === 1
                        "
                        @click="
                            changePage(
                                pagination.current_page - 1,
                            )
                        "
                    >
                        Trước
                    </button>

                    <button
                        v-for="page in pageNumbers"
                        :key="page"
                        type="button"
                        class="page-button"
                        :class="{
                            active:
                                page ===
                                pagination.current_page,
                        }"
                        @click="changePage(page)"
                    >
                        {{ page }}
                    </button>

                    <button
                        type="button"
                        class="page-button"
                        :disabled="
                            pagination.current_page ===
                            pagination.last_page
                        "
                        @click="
                            changePage(
                                pagination.current_page + 1,
                            )
                        "
                    >
                        Sau
                    </button>
                </div>
            </footer>
        </div>

        <div
            v-if="showFormModal"
            class="modal-overlay"
            @click.self="closeFormModal"
        >
            <div class="modal">
                <header class="modal-header">
                    <div>
                        <h2 class="modal-title">
                            {{ modalTitle }}
                        </h2>

                        <p class="modal-description">
                            Nhập thông tin thương hiệu.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="modal-close"
                        :disabled="submitting"
                        @click="closeFormModal"
                    >
                        ×
                    </button>
                </header>

                <form
                    class="brand-form"
                    @submit.prevent="submitCategory"
                >
                    <div class="form-field">
                        <label for="brand-name">
                            Tên danh mục
                            <span class="required">
                                *
                            </span>
                        </label>

                        <input
                            id="brand-name"
                            v-model.trim="form.name"
                            type="text"
                            maxlength="255"
                            placeholder="Ví dụ: Apple"
                            @blur="
                                !form.slug &&
                                generateSlug()
                            "
                        >

                        <p
                            v-if="formErrors.name"
                            class="field-error"
                        >
                            {{ formErrors.name[0] }}
                        </p>
                    </div>

                    <div class="form-field">
                        <div class="label-row">
                            <label for="brand-slug">
                                Slug
                                <span class="required">
                                    *
                                </span>
                            </label>

                            <button
                                type="button"
                                class="generate-button"
                                @click="generateSlug"
                            >
                                Tạo từ tên
                            </button>
                        </div>

                        <input
                            id="brand-slug"
                            v-model.trim="form.slug"
                            type="text"
                            maxlength="255"
                            placeholder="Ví dụ: apple"
                        >

                        <p class="field-help">
                            Slug dùng trên đường dẫn URL
                            và không được trùng.
                        </p>

                        <p
                            v-if="formErrors.slug"
                            class="field-error"
                        >
                            {{ formErrors.slug[0] }}
                        </p>
                    </div>

                    <div class="form-field">
                        <label for="form-brand-status">
                            Trạng thái
                        </label>

                        <select
                            id="form-brand-status"
                            v-model="form.status"
                        >
                            <option value="active">
                                Đang hoạt động
                            </option>

                            <option value="inactive">
                                Ngừng hoạt động
                            </option>
                        </select>

                        <p
                            v-if="formErrors.status"
                            class="field-error"
                        >
                            {{ formErrors.status[0] }}
                        </p>
                    </div>

                    <footer class="modal-actions">
                        <button
                            type="button"
                            class="button button-secondary"
                            :disabled="submitting"
                            @click="closeFormModal"
                        >
                            Hủy
                        </button>

                        <button
                            type="submit"
                            class="button button-primary"
                            :disabled="submitting"
                        >
                            {{ submitButtonText }}
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
.brand-page {
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

.page-title {
    margin: 0;
    color: #111827;
    font-family: Roboto, sans-serif;
    font-size: 24px;
    font-weight: 600;
}

.page-description {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.alert {
    border: 1px solid;
    padding: 12px 16px;
    font-size: 14px;
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

.button {
    height: 40px;
    border: 1px solid transparent;
    border-radius: 0;
    padding: 0 16px;
    cursor: pointer;
    font: inherit;
    font-weight: 500;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.button-primary {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.button-primary:hover:not(:disabled) {
    background: #15803d;
}

.button-secondary {
    border-color: #d1d5db;
    background: #ffffff;
    color: #374151;
}

.button-secondary:hover:not(:disabled) {
    background: #f9fafb;
}

.filter-panel {
    display: grid;
    grid-template-columns:
        minmax(260px, 1fr)
        200px
        120px
        auto;
    align-items: end;
    gap: 16px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 16px;
}

.filter-field,
.form-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.filter-field label,
.form-field label {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.filter-field input,
.filter-field select,
.form-field input,
.form-field select {
    width: 100%;
    height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    padding: 0 12px;
    color: #111827;
    font: inherit;
    outline: none;
}

.filter-field input:focus,
.filter-field select:focus,
.form-field input:focus,
.form-field select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 1px #16a34a;
}

.filter-actions {
    display: flex;
    align-items: center;
}

.table-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.table-wrapper {
    overflow-x: auto;
}

.brand-table {
    width: 100%;
    border-collapse: collapse;
}

.brand-table th,
.brand-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.brand-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
}

.brand-table td {
    color: #4b5563;
    font-size: 14px;
}

.brand-table tbody tr:hover {
    background: #f9fafb;
}

.brand-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-symbol {
    display: flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #15803d;
    font-weight: 700;
}

.brand-name {
    color: #111827;
    font-weight: 500;
}

.brand-id {
    margin-top: 3px;
    color: #9ca3af;
    font-size: 12px;
}

.slug-value {
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 3px 7px;
    color: #374151;
    font-size: 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-inactive {
    border-color: #d1d5db;
    background: #f9fafb;
    color: #4b5563;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.button-link {
    border: 0;
    background: transparent;
    padding: 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 500;
}

.button-link:hover:not(:disabled) {
    text-decoration: underline;
}

.button-link:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.button-warning {
    color: #b45309;
}

.button-danger-link {
    color: #dc2626;
}

.action-column {
    width: 190px;
}

.table-state {
    padding: 48px 20px;
    color: #6b7280;
    text-align: center;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
}

.pagination-info {
    color: #6b7280;
    font-size: 13px;
}

.pagination-buttons {
    display: flex;
    gap: 6px;
}

.page-button {
    min-width: 36px;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 0;
    background: #ffffff;
    color: #374151;
    cursor: pointer;
}

.page-button:hover:not(:disabled) {
    border-color: #16a34a;
    color: #15803d;
}

.page-button.active {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}

.page-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.modal-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(17 24 39 / 55%);
    padding: 20px;
}

.modal {
    width: 100%;
    max-width: 520px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 18px 20px;
}

.modal-title {
    margin: 0;
    color: #111827;
    font-size: 18px;
    font-weight: 600;
}

.modal-description {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.modal-close {
    border: 0;
    background: transparent;
    padding: 0;
    color: #6b7280;
    cursor: pointer;
    font-size: 26px;
    line-height: 1;
}

.brand-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 20px;
}

.label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.generate-button {
    border: 0;
    background: transparent;
    padding: 0;
    color: #15803d;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
    font-weight: 500;
}

.generate-button:hover {
    text-decoration: underline;
}

.required {
    color: #dc2626;
}

.field-help {
    margin: 0;
    color: #9ca3af;
    font-size: 12px;
}

.field-error {
    margin: 0;
    color: #dc2626;
    font-size: 12px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e5e7eb;
    margin: 2px -20px -20px;
    padding: 16px 20px;
}

@media (max-width: 900px) {
    .filter-panel {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-header > .button {
        width: 100%;
    }

    .filter-panel {
        grid-template-columns: 1fr;
    }

    .pagination {
        align-items: flex-start;
        flex-direction: column;
    }

    .modal-overlay {
        align-items: flex-start;
        overflow-y: auto;
    }
}
</style>
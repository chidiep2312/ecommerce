<?php

namespace App\Http\Requests\Category;

use App\Enums\CategoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                Rule::enum(CategoryStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'name.max' => 'Tên danh mục tối đa 255 ký tự.',
        ];
    }
}
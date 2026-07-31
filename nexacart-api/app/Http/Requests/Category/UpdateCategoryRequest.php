<?php

namespace App\Http\Requests\Category;

use App\Enums\CategoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category),
            ],

            'description' => [
                'sometimes',
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
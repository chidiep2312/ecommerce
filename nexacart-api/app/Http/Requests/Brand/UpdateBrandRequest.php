<?php

namespace App\Http\Requests\Brand;

use App\Enums\BrandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brand = $this->route('brand');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')
                    ->ignore($brand),
            ],

            'status' => [
                'sometimes',
                Rule::enum(BrandStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên thương hiệu không được để trống.',
            'name.unique' => 'Tên thương hiệu đã tồn tại.',
            'name.max' => 'Tên thương hiệu tối đa 255 ký tự.',
        ];
    }
}
<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    'import',
                    'adjustment',
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' =>
                'Vui lòng chọn loại cập nhật.',

            'type.in' =>
                'Loại cập nhật tồn kho không hợp lệ.',

            'quantity.required' =>
                'Vui lòng nhập số lượng.',

            'quantity.integer' =>
                'Số lượng phải là số nguyên.',

            'quantity.min' =>
                'Số lượng không được nhỏ hơn 0.',

            'quantity.max' =>
                'Số lượng vượt quá giới hạn cho phép.',
        ];
    }
}
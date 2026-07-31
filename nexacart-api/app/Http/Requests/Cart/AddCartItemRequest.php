<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at'),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'
                => 'Sản phẩm không được để trống.',

            'product_id.exists'
                => 'Sản phẩm không tồn tại.',

            'quantity.required'
                => 'Số lượng không được để trống.',

            'quantity.integer'
                => 'Số lượng phải là số nguyên.',

            'quantity.min'
                => 'Số lượng phải lớn hơn 0.',

            'quantity.max'
                => 'Số lượng không hợp lệ.',
        ];
    }
}
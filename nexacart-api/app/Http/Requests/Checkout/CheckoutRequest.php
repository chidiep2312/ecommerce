<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'seller_id' => [
            'required',
            'integer',
            'exists:users,id',
        ],

        'shipping_name' => [
            'required',
            'string',
            'max:255',
        ],

        'shipping_phone' => [
            'required',
            'string',
            'max:20',
        ],

        'shipping_address' => [
            'required',
            'string',
            'max:500',
        ],

        'voucher_code' => [
            'nullable',
            'string',
            'max:50',
        ],

        'customer_note' => [
            'nullable',
            'string',
            'max:1000',
        ],
    ];
}

    public function messages(): array
    {
        return [
            'shipping_name.required'
                => 'Tên người nhận không được để trống.',

            'shipping_phone.required'
                => 'Số điện thoại không được để trống.',

            'shipping_address.required'
                => 'Địa chỉ giao hàng không được để trống.',
        ];
    }
}
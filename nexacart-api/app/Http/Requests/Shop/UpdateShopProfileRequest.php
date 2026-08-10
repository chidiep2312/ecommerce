<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopProfileRequest extends FormRequest
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
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'Vui lòng nhập tên cửa hàng.',

            'name.max' =>
                'Tên cửa hàng không được vượt quá 255 ký tự.',

            'phone.max' =>
                'Số điện thoại không được vượt quá 20 ký tự.',

            'description.max' =>
                'Mô tả cửa hàng không được vượt quá 1000 ký tự.',
        ];
    }
}
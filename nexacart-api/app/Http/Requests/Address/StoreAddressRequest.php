<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'province' => [
                'required',
                'string',
                'max:255',
            ],

            'district' => [
                'required',
                'string',
                'max:255',
            ],

            'ward' => [
                'required',
                'string',
                'max:255',
            ],

            'address_line' => [
                'required',
                'string',
                'max:500',
            ],

            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
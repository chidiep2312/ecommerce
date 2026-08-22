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
            'max:100',
        ],

        'phone' => [
            'required',
            'string',
            'max:20',
        ],

        'province' => [
            'required',
            'string',
        ],

        'province_id' => [
            'required',
            'integer',
        ],

        'district' => [
            'required',
            'string',
        ],

        'district_id' => [
            'required',
            'integer',
        ],

        'ward' => [
            'required',
            'string',
        ],

        'ward_code' => [
            'required',
            'string',
        ],

        'address_line' => [
            'required',
            'string',
            'max:255',
        ],

        'is_default' => [
            'boolean',
        ],
    ];
}
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerPickupAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
   public function rules(): array
{
    return [
        'contact_name' => [
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
            'max:100',
        ],

        'province_id' => [
            'required',
            'integer',
        ],

        'district' => [
            'required',
            'string',
            'max:100',
        ],

        'district_id' => [
            'required',
            'integer',
        ],

        'ward' => [
            'required',
            'string',
            'max:100',
        ],

        'ward_code' => [
            'required',
            'string',
            'max:20',
        ],

        'address_line' => [
            'required',
            'string',
            'max:255',
        ],

        'is_default' => [
            'sometimes',
            'boolean',
        ],
    ];
}
}

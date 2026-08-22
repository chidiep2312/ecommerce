<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingQuoteRequest extends FormRequest
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

            'address_id' => [
                'required',
                'integer',
                'exists:addresses,id',
            ],
            'cart_item_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'cart_item_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:cart_items,id',
            ],
        ];
    }
}

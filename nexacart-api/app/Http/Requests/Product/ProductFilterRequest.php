<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seller_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                    'drafted',
                ]),
            ],
            'is_suspended' => [
                'nullable',
               'boolean'
            ],


            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
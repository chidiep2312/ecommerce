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
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:255',
                'exists:categories,slug',
            ],

            'brand' => [
                'nullable',
                'string',
                'max:255',
                'exists:brands,slug',
            ],

            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_price',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'newest',
                    'oldest',
                    'price_asc',
                    'price_desc',
                    'name_asc',
                    'name_desc',
                ]),
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
<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at'),
            ],

            'brand_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('brands', 'id')
                    ->whereNull('deleted_at'),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->ignore($product),
            ],

            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');

            $price = $this->input(
                'price',
                $product->price
            );

            $salePrice = $this->input(
                'sale_price',
                $product->sale_price
            );

            if (
                $salePrice !== null &&
                (float) $salePrice > (float) $price
            ) {
                $validator->errors()->add(
                    'sale_price',
                    'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc.'
                );
            }
        });
    }
}
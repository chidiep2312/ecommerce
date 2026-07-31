<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at'),
            ],

            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')
                    ->whereNull('deleted_at'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku'),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:price',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'
                => 'Danh mục không được để trống.',

            'category_id.exists'
                => 'Danh mục không tồn tại.',

            'brand_id.exists'
                => 'Thương hiệu không tồn tại.',

            'name.required'
                => 'Tên sản phẩm không được để trống.',

            'sku.required'
                => 'SKU không được để trống.',

            'sku.unique'
                => 'SKU đã tồn tại.',

            'price.required'
                => 'Giá sản phẩm không được để trống.',

            'price.min'
                => 'Giá sản phẩm không được âm.',

            'sale_price.lte'
                => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá gốc.',

            'stock.required'
                => 'Tồn kho không được để trống.',

            'stock.min'
                => 'Tồn kho không được âm.',
        ];
    }
}
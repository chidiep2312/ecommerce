<?php

namespace App\Http\Requests\ProductImage;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => [
                'required',
                'array',
                'min:1',
                'max:8',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'main_image_index' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required'
            => 'Bạn chưa chọn ảnh sản phẩm.',

            'images.array'
            => 'Danh sách ảnh không hợp lệ.',

            'images.max'
            => 'Mỗi lần chỉ được tải lên tối đa 8 ảnh.',

            'images.*.image'
            => 'Tệp tải lên phải là hình ảnh.',

            'images.*.mimes'
            => 'Ảnh chỉ được có định dạng JPG, JPEG, PNG hoặc WEBP.',

            'images.*.max'
            => 'Mỗi ảnh không được vượt quá 2 MB.',
        ];
    }
}

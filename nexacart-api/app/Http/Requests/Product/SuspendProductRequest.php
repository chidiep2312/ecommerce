<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class SuspendProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->value === 'admin';
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' =>
                'Vui lòng nhập lý do khóa sản phẩm.',

            'reason.min' =>
                'Lý do khóa phải có ít nhất 5 ký tự.',

            'reason.max' =>
                'Lý do khóa không được vượt quá 1000 ký tự.',
        ];
    }
}
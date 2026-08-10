<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $this->user()->id
                ),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'Vui lòng nhập họ tên.',

            'name.max' =>
                'Họ tên không được vượt quá 255 ký tự.',

            'email.required' =>
                'Vui lòng nhập email.',

            'email.email' =>
                'Email không đúng định dạng.',

            'email.unique' =>
                'Email này đã được sử dụng.',

            'phone.max' =>
                'Số điện thoại không được vượt quá 20 ký tự.',
        ];
    }
}
<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(UserStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'
                => 'Trạng thái tài khoản không được để trống.',

            'status.enum'
                => 'Trạng thái tài khoản không hợp lệ.',
        ];
    }
}
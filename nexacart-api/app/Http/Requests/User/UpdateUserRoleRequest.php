<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in([
                    UserRole::Customer->value,
                    UserRole::Seller->value,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required'
                => 'Vai trò tài khoản không được để trống.',

            'role.in'
                => 'Vai trò chỉ có thể là customer hoặc seller.',
        ];
    }
}
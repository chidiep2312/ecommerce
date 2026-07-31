<?php

namespace App\Http\Requests\Admin\Seller;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
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
            'status.required' =>
                'Trạng thái người bán là bắt buộc.',

            'status.enum' =>
                'Trạng thái người bán không hợp lệ.',
        ];
    }
}
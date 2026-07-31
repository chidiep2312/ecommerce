<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
                Rule::enum(OrderStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'
                => 'Trạng thái đơn hàng không được để trống.',
        ];
    }
}
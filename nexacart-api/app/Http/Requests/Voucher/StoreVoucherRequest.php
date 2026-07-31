<?php

namespace App\Http\Requests\Voucher;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code'),
            ],

            'type' => [
                'required',
                Rule::enum(VoucherType::class),
            ],

            'value' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'min_order_amount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'max_discount_amount' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'usage_limit_per_user' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'starts_at' => [
                'required',
                'date',
            ],

            'expires_at' => [
                'required',
                'date',
                'after:starts_at',
            ],

            'status' => [
                'sometimes',
                Rule::enum(VoucherStatus::class),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $value = (float) $this->input(
                'value',
                0
            );

            if (
                $type === VoucherType::Percent->value
                && $value > 100
            ) {
                $validator->errors()->add(
                    'value',
                    'Voucher phần trăm không được vượt quá 100%.'
                );
            }

            if (
                $type === VoucherType::Fixed->value
                && $this->filled(
                    'max_discount_amount'
                )
            ) {
                $validator->errors()->add(
                    'max_discount_amount',
                    'Voucher giảm cố định không cần giới hạn giảm tối đa.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'code.required'
                => 'Mã voucher không được để trống.',

            'code.unique'
                => 'Mã voucher đã tồn tại.',

            'value.required'
                => 'Giá trị voucher không được để trống.',

            'value.gt'
                => 'Giá trị voucher phải lớn hơn 0.',

            'expires_at.after'
                => 'Thời gian hết hạn phải sau thời gian bắt đầu.',
        ];
    }
}
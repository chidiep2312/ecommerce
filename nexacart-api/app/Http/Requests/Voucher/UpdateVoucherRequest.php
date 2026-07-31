<?php

namespace App\Http\Requests\Voucher;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voucher = $this->route('voucher');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')
                    ->ignore($voucher),
            ],

            'type' => [
                'sometimes',
                Rule::enum(VoucherType::class),
            ],

            'value' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'numeric',
                'gt:0',
            ],

            'usage_limit' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],

            'usage_limit_per_user' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],

            'starts_at' => [
                'sometimes',
                'required',
                'date',
            ],

            'expires_at' => [
                'sometimes',
                'required',
                'date',
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
            $voucher = $this->route('voucher');

            $type = $this->input(
                'type',
                $voucher->type->value
            );

            $value = (float) $this->input(
                'value',
                $voucher->value
            );

            $startsAt = $this->date(
                'starts_at'
            ) ?? $voucher->starts_at;

            $expiresAt = $this->date(
                'expires_at'
            ) ?? $voucher->expires_at;

            if ($expiresAt->lessThanOrEqualTo($startsAt)) {
                $validator->errors()->add(
                    'expires_at',
                    'Thời gian hết hạn phải sau thời gian bắt đầu.'
                );
            }

            if (
                $type === VoucherType::Percent->value
                && $value > 100
            ) {
                $validator->errors()->add(
                    'value',
                    'Voucher phần trăm không được vượt quá 100%.'
                );
            }
        });
    }
}
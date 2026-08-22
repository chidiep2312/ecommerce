<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethod;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seller_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'cart_item_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'cart_item_ids.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],

            /*
             * Frontend chỉ gửi ID Address.
             *
             * Backend sẽ tự lấy:
             * - recipient_name
             * - phone
             * - address
             */
            'address_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'shipping_provider' => [
                'required',
                'string',

                Rule::in([
                    'ghn',
                ]),
            ],

            'shipping_service_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'payment_method' => [
                'required',
                'string',

                Rule::in([
                    'cod',
                    PaymentMethod::Vnpay,
                ]),
            ],

            'voucher_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'customer_note' => [
                'nullable',
                'string',
                'max:500',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.required' =>
                'Người bán không được để trống.',

            'cart_item_ids.required' =>
                'Danh sách sản phẩm không được để trống.',

            'cart_item_ids.min' =>
                'Phải chọn ít nhất một sản phẩm.',

            'address_id.required' =>
                'Vui lòng chọn địa chỉ nhận hàng.',

            'shipping_provider.required' =>
                'Vui lòng chọn đơn vị vận chuyển.',

            'shipping_service_id.required' =>
                'Vui lòng chọn phương thức vận chuyển.',

            'payment_method.required' =>
                'Vui lòng chọn phương thức thanh toán.',

            'idempotency_key.required' =>
                'Thiếu khóa xác nhận checkout.',
        ];
    }
}
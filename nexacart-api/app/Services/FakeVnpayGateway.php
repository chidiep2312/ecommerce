<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Contracts\PaymentGateway;
use App\Models\Payment;

class FakeVnpayGateway implements PaymentGateway
{

    public function __construct(
        private VnpaySignature $signature
    ) {}
    public function createPayment(
        Payment $payment,
        string $ipAddress
    ): PaymentInitResult {

        $params = [
            'vnp_Version' => '2.1.0',

            'vnp_Command' => 'pay',

            'vnp_TmnCode' => config('services.vnpay.tmn_code'),

            'vnp_Amount' => $payment->amount * 100,

            'vnp_TxnRef' => $payment->provider_order_id,

            'vnp_OrderInfo' => $payment->order_id,

            'vnp_ReturnUrl' => config('services.vnpay.return_url'),

            'vnp_CreateDate' => now()->format('YmdHis'),

            'vnp_IpAddr' => $ipAddress,
        ];

        $secureHash = $this->signature->sign($params);
        ksort($params);
        $query = http_build_query($params);
        $paymentUrl =
            config('services.vnpay.fake_payment_url') . '?' . $query . '&vnp_SecureHash=' . urlencode($secureHash);
        return new PaymentInitResult(
            payment: $payment,
            paymentUrl: $paymentUrl
        );
    }

    public function verifyCallback(
        array $data
    ): bool {
        return $this->signature->verify($data,config('services.vnpay.hash_secret'));
    }
}

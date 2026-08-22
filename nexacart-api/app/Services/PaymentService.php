<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private FakeVnpayGateway $gateway) {}

    public function createVnpayPayment(
        Order $order,
        string $ipAddress
    ): PaymentInitResult {

        // race condition - lock order vì payment chưa có k lock được
        $payment = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_method !== PaymentMethod::Vnpay) {
                throw ValidationException::withMessages([
                    'message' => 'Order không thanh toán Vnpay.',
                ]);
            }
            if ($lockedOrder->isPaid()) {
                throw ValidationException::withMessages([
                    'message' => 'Order đã thanh toán Vnpay.',
                ]);
            }


            if ($lockedOrder->status == OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'message' => 'Order đã bị hủy.',
                ]);
            }

            $payment = Payment::query()->where('order_id', $lockedOrder->id)
                ->where('provider', PaymentMethod::Vnpay)
                ->where('status', PaymentStatus::Pending)
                ->first();

            if (!$payment) {
                $paymentMethod = PaymentMethod::Vnpay;
                $payment = $this->createPendingPayment($lockedOrder,  $paymentMethod);
            }
            return $payment;
        });

        $result = $this->gateway
            ->createPayment(
                $payment,
                $ipAddress
            );
        return $result;
    }
    private function createPendingPayment(Order $order, PaymentMethod $paymentMethod): Payment
    {

        return Payment::create([
            'order_id' => $order->id,
            'request_id' => Str::uuid()->toString(),
            'provider' => $paymentMethod,
            'provider_order_id' => $this->generateTransactionRef(),
            'amount' => $order->total,
            'status' =>  PaymentStatus::Pending,
        ]);
    }


    /**
     * trả về mã giao dịch do Nexacart tạo
     * 
     */
    private function  generateTransactionRef(): string
    {

        return 'NX-' . Str::upper(Str::random(16));
    }

    public function handleVnpayCallback(
        array $data
    ): void {
        DB::transaction(
            function () use ($data) {

                $payment = Payment::query()->where('provider_order_id', $data['vnp_TxnRef'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $expectedAmount =  (int) $payment->amount * 100;

                $receivedAmount =   (int) ($data['vnp_Amount'] ?? 0);

                if (
                    $expectedAmount !==  $receivedAmount
                ) {
                    throw ValidationException::withMessages([
                        'amount' =>
                        'Payment amount does not match.',
                    ]);
                }
                if (
                    $payment->status ===
                    PaymentStatus::Paid
                ) {
                    return;
                }
                $isSuccess =
                    ($data['vnp_ResponseCode'] ?? null)  === '00'
                    &&
                    ($data['vnp_TransactionStatus'] ?? null)    === '00';
                if ($isSuccess) {
                    $payment->update([
                        'status' =>
                        PaymentStatus::Paid,

                        'transaction_id' =>
                        $data['vnp_TransactionNo'],

                        'provider_response_code' =>
                        $data['vnp_ResponseCode'],

                        'provider_message' =>
                        'Payment successful.',

                        'paid_at' =>
                        now(),
                    ]);

                    return;
                }
                $payment->update([
                    'status' =>
                    PaymentStatus::Failed,

                    'transaction_id' =>
                    $data['vnp_TransactionNo']
                        ?? null,

                    'provider_response_code' =>
                    $data['vnp_ResponseCode']
                        ?? null,

                    'provider_message' =>
                    'Payment failed.',
                ]);
            }

        );
    }
}

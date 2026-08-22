<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\VnpaySignature;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\FakeVnpayGateway;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $service,
        protected FakeVnpayGateway $gateway
    ) {}

    public function store(
        Request $request,
        Order $order,

    ) {
        $this->authorize('view', $order);
        $result = $this->service->createVnpayPayment($order, $request->ip());
        return response()->json([
            'data' => [
                'payment_id' =>
                $result->payment->id,
                'status' =>
                $result->payment->status,
                'payment_url' =>
                $result->paymentUrl,
            ],
        ]);
    }
    public function vnpayIpn(
        Request $request
    ) {
        $data = $request->query();

        if (!$this->gateway->verifyCallback($data)) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ]);
        }
        $this->service->handleVnpayCallback($data);
        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm Success',
        ]);
    }



    public function vnpayReturn(
        Request $request
    ) {
        $data = $request->query();

        if (!$this->gateway->verifyCallback($data)) {
            return redirect()->away(
                config('services.vnpay.frontend_result_url')    . '?result=invalid'
            );
        }

        $txnRef =  $data['vnp_TxnRef']  ?? '';

        return redirect()->away(
            config('services.vnpay.frontend_result_url')    . '?txn_ref=' . urlencode($txnRef)
        );
    }
    public function status(
        string $providerOrderId
    ) {
        $payment =   Payment::query()->with('order')
            ->where(
                'provider_order_id',
                $providerOrderId
            )
            ->firstOrFail();

        if ($payment->order->user_id  !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'data' => [
                'status' =>
                $payment->status,
                'amount' =>$payment->amount,

                'order_id' =>
                $payment->order_id,

                'transaction_id' =>
                $payment->transaction_id,

                'provider_order_id' =>
                $payment->provider_order_id,
            ],
        ]);
    }
}

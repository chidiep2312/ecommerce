<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\VnpaySignature;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class FakeVnpayController extends Controller
{
    public function __construct(
        private VnpaySignature $signature
    ) {}

    public function show(Request $request)
    {
        $params = $request->query();

        $validSignature = $this->signature->verify($params, config('services.vnpay.hash_secret'));

        if (!$validSignature) {
            abort(400,  'Invalid VNPay signature.');
        }

        $amount = ((int) ($params['vnp_Amount'] ?? 0))   / 100;

        return view(
            'fake-vnpay.pay',
            [
                'params' => $params,
                'amount' => $amount,
            ]
        );
    }
    public function process(
        Request $request
    ) {
        $params = $request->input(
            'params',
            []
        );

        $result = $request->input('result');

        if (
            !in_array(
                $result,
                [
                    'success',
                    'failed',
                    'cancelled',
                ],
                true
            )
        ) {
            abort(400,  'Invalid payment result.');
        }

        $validSignature = $this->signature->verify($params, config('services.vnpay.hash_secret'));

        if (!$validSignature) {
            abort(
                400,
                'Invalid VNPay signature.'
            );
        }
        if ($result === 'success') {
            $responseCode = '00';
            $transactionStatus = '00';
        } elseif ($result === 'cancelled') {
            $responseCode = '24';
            $transactionStatus = '02';
        } else {
            $responseCode = '99';
            $transactionStatus = '02';
        }

        $callbackData = [
            'vnp_TxnRef' => $params['vnp_TxnRef'],

            'vnp_Amount' => $params['vnp_Amount'],

            'vnp_ResponseCode' => $responseCode,

            'vnp_TransactionStatus' => $transactionStatus,

            'vnp_TransactionNo' =>  $this->generateTransactionId(),

            'vnp_PayDate' =>  now()->format('YmdHis'),
        ];
        $callbackData['vnp_SecureHash'] = $this->signature->sign($callbackData, config('services.vnpay.hash_secret'));
        Http::get(config('services.vnpay.ipn_url'), $callbackData);
        $returnUrl = $params['vnp_ReturnUrl'];
        return redirect()->away(
            $returnUrl   . '?'   . http_build_query($callbackData)
        );
    }

    private function  generateTransactionId(): string
    {

        return 'FAKE-VNP' . Str::upper(Str::random(16));
    }
}

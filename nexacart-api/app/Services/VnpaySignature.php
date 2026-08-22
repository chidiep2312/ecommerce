<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Contracts\PaymentGateway;
use Illuminate\Validation\ValidationException;

class VnpaySignature
{

    public function sign(
        array $params,
    ): string {
        $hashSecret = config('services.vnpay.hash_secret');
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = urlencode($key) . '=' . urlencode($value);
        }
        $hashData = implode('&', $parts);

        $vnp_SecureHash = hash_hmac(
            'sha512',
            $hashData,
            $hashSecret
        );
        return  $vnp_SecureHash;
    }

    public function verify(
        array $params,
      
    ): bool {
 
        $receiveSign = $params['vnp_SecureHash'] ?? null;
        if ($receiveSign === null || $receiveSign === '') {
            return false;
        }
        unset($params['vnp_SecureHash']);
        $expectedSign = $this->sign($params);
        return  hash_equals($expectedSign, $receiveSign);
    }
    
}

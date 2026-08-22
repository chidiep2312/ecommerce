<?php
namespace App\Contracts;
use App\Services\PaymentInitResult;
use App\Models\Payment;

interface PaymentGateway
{
    public function createPayment(
          Payment $payment,
        string $ipAddress
    ): PaymentInitResult;

    public function verifyCallback(
        array $data
    ): bool;
}
?>
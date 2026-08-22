<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentInitResult
{
     public function __construct(
        public Payment $payment,
        public string $paymentUrl,

    ) {
    }
}
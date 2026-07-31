<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\ValidateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;

class VoucherValidationController extends Controller
{
    public function __construct(
        private readonly VoucherService $voucherService
    ) {
    }

    public function __invoke(
        ValidateVoucherRequest $request
    ): JsonResponse {
        $result = $this->voucherService
            ->validateForUser(
                $request->user(),
                $request->string('code')->toString(),
                $request->float('subtotal')
            );

        return response()->json([
            'success' => true,
            'message' => 'Voucher hợp lệ.',
            'data' => [
                'voucher' => new VoucherResource(
                    $result['voucher']
                ),

                'discount_amount'
                    => $result['discount_amount'],

                'total_after_discount'
                    => $result[
                        'total_after_discount'
                    ],
            ],
            'errors' => null,
        ]);
    }
}
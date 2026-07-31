<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
    }

    public function store(
        CheckoutRequest $request
    ): JsonResponse {
        $order = $this->checkoutService
            ->checkout(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' => 'Đặt hàng thành công.',
            'data' => new OrderResource($order),
            'errors' => null,
        ], 201);
    }
}
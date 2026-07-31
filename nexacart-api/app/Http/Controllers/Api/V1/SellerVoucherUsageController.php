<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherUsageResource;
use App\Services\SellerVoucherUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SellerVoucherUsageController extends Controller
{
    public function __construct(
        private readonly SellerVoucherUsageService
            $sellerVoucherUsageService
    ) {
    }

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'order_status' => [
                'nullable',
                Rule::enum(
                    OrderStatus::class
                ),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $usages = $this
            ->sellerVoucherUsageService
            ->paginate(
                $request->user(),
                $filters
            );

        return VoucherUsageResource::collection(
            $usages
        );
    }

    public function summary(
        Request $request
    ): JsonResponse {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'order_status' => [
                'nullable',
                Rule::enum(
                    OrderStatus::class
                ),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Lấy thống kê sử dụng voucher thành công.',

            'data' =>
                $this
                    ->sellerVoucherUsageService
                    ->getSummary(
                        $request->user(),
                        $filters
                    ),

            'errors' => null,
        ]);
    }
}
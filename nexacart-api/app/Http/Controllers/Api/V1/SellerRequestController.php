<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SellerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerRequestController extends Controller
{
    public function __construct(
        private readonly SellerRequestService $sellerRequestService
    ) {
    }

    /**
     * Customer gửi yêu cầu làm seller.
     */
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $sellerRequest =
            $this->sellerRequestService->submit(
                user: $request->user(),
                reason: $validated['reason'] ?? null,
            );

        return response()->json([
            'message' =>
                'Gửi yêu cầu đăng ký người bán thành công.',
            'data' => $sellerRequest,
        ], 201);
    }

    /**
     * Lấy yêu cầu seller mới nhất của user hiện tại.
     */
    public function current(
        Request $request
    ): JsonResponse {
        $sellerRequest = $request
            ->user()
            ->latestSellerRequest()
            ->with('reviewer:id,name')
            ->first();

        return response()->json([
            'data' => $sellerRequest,
        ]);
    }

    /**
     * Lấy toàn bộ lịch sử đăng ký seller.
     */
    public function history(
        Request $request
    ): JsonResponse {
        $sellerRequests = $request
            ->user()
            ->sellerRequests()
            ->with('reviewer:id,name')
            ->latest()
            ->paginate(10);

        return response()->json(
            $sellerRequests
        );
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService
            $reviewService
    ) {
    }

    /**
     * Public:
     * xem review của một product.
     */
    public function index(
        Product $product
    ): AnonymousResourceCollection {
        $reviews = $this
            ->reviewService
            ->paginateForProduct(
                $product
            );

        return ReviewResource::collection(
            $reviews
        );
    }

    /**
     * Customer:
     * lấy thông tin OrderItem trước khi review.
     */
    public function createContext(
        Request $request,
        Order $order,
        OrderItem $orderItem
    ): OrderItemResource {
        $orderItem = $this
            ->reviewService
            ->getReviewContext(
                $request->user(),
                $order,
                $orderItem
            );

        return new OrderItemResource(
            $orderItem
        );
    }

    /**
     * Customer:
     * tạo review cho một lần mua.
     */
    public function store(
        StoreReviewRequest $request,
        Order $order,
        OrderItem $orderItem
    ): JsonResponse {
        $review = $this
            ->reviewService
            ->create(
                $request->user(),
                $order,
                $orderItem,
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Đánh giá sản phẩm thành công.',

            'data' =>
                new ReviewResource(
                    $review
                ),

            'errors' => null,
        ], 201);
    }

    /**
     * Customer chỉnh review của chính mình.
     */
    public function update(
        UpdateReviewRequest $request,
        Review $review
    ): JsonResponse {
        $this->authorize(
            'update',
            $review
        );

        $review = $this
            ->reviewService
            ->update(
                $review,
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Cập nhật đánh giá thành công.',

            'data' =>
                new ReviewResource(
                    $review
                ),

            'errors' => null,
        ]);
    }

    public function destroy(
        Review $review
    ): JsonResponse {
        $this->authorize(
            'delete',
            $review
        );

        $this
            ->reviewService
            ->delete(
                $review
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Xóa đánh giá thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
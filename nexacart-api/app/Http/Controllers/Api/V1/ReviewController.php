<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {
    }

    public function index(
        Product $product
    ): AnonymousResourceCollection {
        $reviews = $this->reviewService
            ->paginateForProduct($product);

        return ReviewResource::collection(
            $reviews
        );
    }

    public function store(
        StoreReviewRequest $request,
        Product $product
    ): JsonResponse {
        $review = $this->reviewService->create(
            $request->user(),
            $product,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá sản phẩm thành công.',
            'data' => new ReviewResource($review),
            'errors' => null,
        ], 201);
    }

    public function update(
        UpdateReviewRequest $request,
        Review $review
    ): JsonResponse {
        $this->authorize('update', $review);

        $review = $this->reviewService->update(
            $review,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đánh giá thành công.',
            'data' => new ReviewResource($review),
            'errors' => null,
        ]);
    }

    public function destroy(
        Review $review
    ): JsonResponse {
        $this->authorize('delete', $review);

        $this->reviewService->delete($review);

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
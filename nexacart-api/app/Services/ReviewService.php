<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\ProductAlreadyReviewedException;
use App\Exceptions\ProductNotPurchasedException;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function paginateForProduct(
        Product $product
    ): LengthAwarePaginator {
        return $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);
    }

    public function create(
        User $user,
        Product $product,
        array $data
    ): Review {
        return DB::transaction(function () use (
            $user,
            $product,
            $data
        ) {
            $hasPurchased = $user
                ->customerOrders()
                ->where(
                    'status',
                    OrderStatus::Completed->value
                )
                ->whereHas(
                    'items',
                    fn ($query) => $query->where(
                        'product_id',
                        $product->id
                    )
                )
                ->exists();

            if (! $hasPurchased) {
                throw new ProductNotPurchasedException(
                    'Bạn chỉ được đánh giá sản phẩm đã mua và nhận hàng thành công.'
                );
            }

            $alreadyReviewed = Review::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->withTrashed()
                ->exists();

            if ($alreadyReviewed) {
                throw new ProductAlreadyReviewedException(
                    'Bạn đã đánh giá sản phẩm này.'
                );
            }

            $review = Review::query()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            return $review->load(
                'user:id,name'
            );
        });
    }

    public function update(
        Review $review,
        array $data
    ): Review {
        $review->update($data);

        return $review
            ->refresh()
            ->load('user:id,name');
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }
}
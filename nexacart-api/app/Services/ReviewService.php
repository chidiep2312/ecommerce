<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\ProductAlreadyReviewedException;
use App\Exceptions\ProductNotPurchasedException;
use App\Models\Order;
use App\Models\OrderItem;
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
        return $product
            ->reviews()
            ->with([
                'user:id,name',
            ])
            ->latest()
            ->paginate(10);
    }

    /**
     * Lấy OrderItem trước khi customer đánh giá.
     */
    public function getReviewContext(
        User $user,
        Order $order,
        OrderItem $orderItem
    ): OrderItem {
        /*
         * Order phải thuộc customer hiện tại.
         */
        if (
            $order->user_id !==
            $user->id
        ) {
            throw new ProductNotPurchasedException(
                'Đơn hàng không thuộc tài khoản của bạn.'
            );
        }

        /*
         * OrderItem phải thực sự thuộc Order
         * đang nằm trong URL.
         */
        if (
            $orderItem->order_id !==
            $order->id
        ) {
            throw new ProductNotPurchasedException(
                'Sản phẩm không thuộc đơn hàng này.'
            );
        }

        /*
         * Chỉ order completed mới được review.
         */
        if (
            $order->status !==
            OrderStatus::Completed
        ) {
            throw new ProductNotPurchasedException(
                'Bạn chỉ được đánh giá sản phẩm sau khi đơn hàng hoàn thành.'
            );
        }

        /*
         * Nếu OrderItem đã có review thì
         * frontend không được tạo tiếp.
         */
        $alreadyReviewed = Review::query()
            ->where(
                'order_item_id',
                $orderItem->id
            )
            ->withTrashed()
            ->exists();

        if ($alreadyReviewed) {
            throw new ProductAlreadyReviewedException(
                'Sản phẩm trong lần mua này đã được đánh giá.'
            );
        }

        return $orderItem->load([
            'product:id,name,slug,sku',
            'product.mainImage:id,product_id,path,is_main,sort_order',
        ]);
    }

    public function create(
        User $user,
        Order $order,
        OrderItem $orderItem,
        array $data
    ): Review {
        return DB::transaction(
            function () use (
                $user,
                $order,
                $orderItem,
                $data
            ) {
                /*
                 * Lock Order trước để tránh trạng thái
                 * thay đổi trong lúc review.
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $order->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedOrder->user_id !==
                    $user->id
                ) {
                    throw new ProductNotPurchasedException(
                        'Đơn hàng không thuộc tài khoản của bạn.'
                    );
                }

                if (
                    $lockedOrder->status !==
                    OrderStatus::Completed
                ) {
                    throw new ProductNotPurchasedException(
                        'Bạn chỉ được đánh giá sản phẩm sau khi đơn hàng hoàn thành.'
                    );
                }

                /*
                 * Lấy lại OrderItem từ DB.
                 *
                 * Không tin hoàn toàn object
                 * được route binding truyền vào.
                 */
                $lockedOrderItem =
                    OrderItem::query()
                        ->whereKey(
                            $orderItem->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedOrderItem->order_id !==
                    $lockedOrder->id
                ) {
                    throw new ProductNotPurchasedException(
                        'Sản phẩm không thuộc đơn hàng này.'
                    );
                }

                if (
                    $lockedOrderItem->product_id ===
                    null
                ) {
                    throw new ProductNotPurchasedException(
                        'Sản phẩm không còn tồn tại để đánh giá.'
                    );
                }

                /*
                 * Một OrderItem chỉ được
                 * đánh giá một lần.
                 */
                $alreadyReviewed =
                    Review::query()
                        ->where(
                            'order_item_id',
                            $lockedOrderItem->id
                        )
                        ->withTrashed()
                        ->exists();

                if ($alreadyReviewed) {
                    throw new ProductAlreadyReviewedException(
                        'Sản phẩm trong lần mua này đã được đánh giá.'
                    );
                }

                $review = Review::query()
                    ->create([
                        'user_id' =>
                            $user->id,

                        'product_id' =>
                            $lockedOrderItem
                                ->product_id,

                        'order_item_id' =>
                            $lockedOrderItem->id,

                        'rating' =>
                            $data['rating'],

                        'comment' =>
                            $data[
                                'comment'
                            ] ?? null,
                    ]);

                return $review
                    ->load([
                        'user:id,name',
                        'product:id,name,slug',
                        'orderItem',
                    ]);
            },
            3
        );
    }

    public function update(
        Review $review,
        array $data
    ): Review {
        $review->update(
            $data
        );

        return $review
            ->refresh()
            ->load([
                'user:id,name',
                'product:id,name,slug',
                'orderItem',
            ]);
    }

    public function delete(
        Review $review
    ): void {
        $review->delete();
    }
}
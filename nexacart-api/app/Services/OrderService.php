<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function getCustomerOrders(
        User $customer
    ): LengthAwarePaginator {
        return Order::query()
            ->where(
                'user_id',
                $customer->id
            )
            ->with([
                'seller:id,name,email',
            ])
            ->withCount('items')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getSellerOrders(
        User $seller
    ): LengthAwarePaginator {
        return Order::query()
            ->where(
                'seller_id',
                $seller->id
            )
            ->with([
                'customer:id,name,email',
                'voucher',
            ])
            ->withCount('items')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getAdminOrders():
        LengthAwarePaginator {
        return Order::query()
            ->with([
                'customer:id,name,email',
                'seller:id,name,email',
            ])
            ->withCount('items')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function loadDetail(
        Order $order
    ): Order {
        return $order->load([
            'customer:id,name,email',
            'seller:id,name,email',
            'voucher',

            'items.product:id,name,slug,sku,stock',

            'items.product.mainImage:id,product_id,path,is_main',
        ]);
    }

    /**
     * Seller/Admin cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(
        Order $order,
        OrderStatus $newStatus,
        ?User $actor = null
    ): Order {
        return DB::transaction(function () use (
            $order,
            $newStatus,
            $actor
        ) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus =
                $lockedOrder->status;

            if (
                !$currentStatus->canTransitionTo(
                    $newStatus
                )
            ) {
                throw new InvalidOrderStatusTransitionException(
                    "Không thể chuyển đơn hàng từ "
                    . "{$currentStatus->value} "
                    . "sang {$newStatus->value}."
                );
            }

            /*
             * Khi chuyển sang cancelled:
             * - hoàn tồn kho;
             * - hoàn lượt sử dụng voucher.
             */
            if (
                $newStatus ===
                OrderStatus::Cancelled
            ) {
                $this->restoreOrderResources(
                    $lockedOrder
                );
            }

            $updateData =
                $this->buildStatusUpdateData(
                    $newStatus
                );

            if (
                $newStatus ===
                    OrderStatus::Cancelled &&
                $actor !== null
            ) {
                $updateData['cancelled_by'] =
                    $actor->id;
            }

            $lockedOrder->update(
                $updateData
            );

            return $this->loadDetail(
                $lockedOrder->refresh()
            );
        }, 3);
    }

    /**
     * Khách hàng tự hủy đơn.
     */
    public function cancelByCustomer(
        Order $order,
        User $customer
    ): Order {
        return DB::transaction(function () use (
            $order,
            $customer
        ) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Kiểm tra lại quyền sở hữu ở Service
             * để tăng an toàn ngoài Policy.
             */
            if (
                $lockedOrder->user_id !==
                $customer->id
            ) {
                throw ValidationException::withMessages([
                    'order' =>
                        'Bạn không có quyền hủy đơn hàng này.',
                ]);
            }

            if (
                $lockedOrder->status !==
                OrderStatus::Pending
            ) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Chỉ có thể hủy đơn hàng đang chờ xác nhận.',
                ]);
            }

            $this->restoreOrderResources(
                $lockedOrder
            );

            $lockedOrder->update([
                'status' =>
                    OrderStatus::Cancelled,

                'cancelled_at' =>
                    now(),

                'cancelled_by' =>
                    $customer->id,
            ]);

            return $this->loadDetail(
                $lockedOrder->refresh()
            );
        }, 3);
    }

    private function buildStatusUpdateData(
        OrderStatus $status
    ): array {
        $data = [
            'status' => $status,
        ];

        match ($status) {
            OrderStatus::Confirmed =>
                $data['confirmed_at'] =
                    now(),

            OrderStatus::Shipping =>
                $data['shipping_at'] =
                    now(),

            OrderStatus::Completed =>
                $data['completed_at'] =
                    now(),

            OrderStatus::Cancelled =>
                $data['cancelled_at'] =
                    now(),

            default => null,
        };

        return $data;
    }

    /**
     * Hoàn lại tài nguyên khi đơn bị hủy.
     *
     * Bao gồm:
     * - hoàn tồn kho;
     * - giảm used_count của voucher;
     * - xóa voucher usage của đơn.
     */
    private function restoreOrderResources(
        Order $order
    ): void {
        $orderItems = $order
            ->items()
            ->orderBy('product_id')
            ->get();

        $productIds = $orderItems
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        /*
         * Lock sản phẩm theo cùng thứ tự ID
         * để hạn chế deadlock.
         */
        $products = Product::query()
            ->whereIn(
                'id',
                $productIds
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach (
            $orderItems as $item
        ) {
            if (
                $item->product_id === null
            ) {
                continue;
            }

            $product = $products->get(
                $item->product_id
            );

            if ($product !== null) {
                $product->increment(
                    'stock',
                    (int) $item->quantity
                );
            }
        }

        if ($order->voucher_id === null) {
            return;
        }

        $voucher = Voucher::query()
            ->whereKey(
                $order->voucher_id
            )
            ->lockForUpdate()
            ->first();

        if ($voucher === null) {
            return;
        }

        /*
         * Chỉ giảm used_count nếu thực sự
         * tồn tại usage thuộc đơn hàng này.
         */
        $deletedUsageCount = $voucher
            ->usages()
            ->where(
                'order_id',
                $order->id
            )
            ->delete();

        if ($deletedUsageCount > 0) {
            $voucher->update([
                'used_count' => max(
                    0,
                    (int) $voucher->used_count
                    - 1
                ),
            ]);
        }
    }
}
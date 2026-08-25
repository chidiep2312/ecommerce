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
    public function __construct(
        private readonly ShipmentService $shipmentService,
    ) {}

    public function getCustomerOrders(
        User $customer,
        array $filters = [],
    ): LengthAwarePaginator {
        return Order::query()
            ->where('user_id', $customer->id)
            ->with([
                'seller:id,name,email',
            ])
            ->withCount('items')
            ->when(
                $filters['search'] ?? null,
                function ($query, string $search) {
                    $query->where(
                        'order_code',
                        'like',
                        '%' . trim($search) . '%',
                    );
                },
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where(
                    'status',
                    $status,
                ),
            )
            ->latest('id')
            ->paginate(
                min(
                    max((int) ($filters['per_page'] ?? 10), 5),
                    50,
                ),
            )
            ->withQueryString();
    }

    public function getSellerOrders(
        User $seller,
    ): LengthAwarePaginator {
        return Order::query()
            ->where('seller_id', $seller->id)
            ->with([
                'customer:id,name,email',
                'voucher',
            ])
            ->withCount('items')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getAdminOrders(): LengthAwarePaginator
    {
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

    public function loadDetail(Order $order): Order
    {
        return $order->load([
            'customer:id,name,email',
            'seller:id,name,email',
            'voucher',
            'items.review',
            'items.product:id,name,slug,sku',
            'items.product.mainImage:id,product_id,path,is_main,sort_order',
        ]);
    }

    public function updateStatus(
        Order $order,
        OrderStatus $newStatus,
    ): Order {
        $result = DB::transaction(
            function () use ($order, $newStatus) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldStatus = $lockedOrder->status;
                
                if (!$oldStatus->canTransitionTo($newStatus)) {
                    throw ValidationException::withMessages([
                        'status' => [
                            "Không thể chuyển trạng thái "
                            . "từ {$oldStatus->value} "
                            . "sang {$newStatus->value}.",
                        ],
                    ]);
                }

                $lockedOrder->status = $newStatus;

                match ($newStatus) {
                    OrderStatus::Confirmed => $lockedOrder->confirmed_at = now(),
                    OrderStatus::Shipping => $lockedOrder->shipping_at = now(),
                    OrderStatus::Completed => $lockedOrder->completed_at = now(),
                    OrderStatus::Cancelled => $lockedOrder->cancelled_at = now(),
                    default => null,
                };

                $lockedOrder->save();

                return [
                    'order' => $lockedOrder,
                    'should_create_shipment' =>
                        $oldStatus === OrderStatus::Pending
                        && $newStatus === OrderStatus::Confirmed,
                ];
            },
            3,
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $result['order'];

        if ($result['should_create_shipment']) {
            $this->shipmentService->createForConfirmedOrder(
                $updatedOrder,
            );
        }

        return $this->loadDetail($updatedOrder->fresh());
    }

    public function cancelByCustomer(
        Order $order,
        User $customer,
    ): Order {
        return DB::transaction(function () use ($order, $customer) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->user_id !== $customer->id) {
                throw ValidationException::withMessages([
                    'order' => 'Bạn không có quyền hủy đơn hàng này.',
                ]);
            }

            if ($lockedOrder->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ có thể hủy đơn hàng đang chờ xác nhận.',
                ]);
            }

            $this->restoreOrderResources($lockedOrder);

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $customer->id,
            ]);

            return $this->loadDetail($lockedOrder->refresh());
        }, 3);
    }

    private function buildStatusUpdateData(
        OrderStatus $status,
    ): array {
        $data = [
            'status' => $status,
        ];

        match ($status) {
            OrderStatus::Confirmed => $data['confirmed_at'] = now(),
            OrderStatus::Shipping => $data['shipping_at'] = now(),
            OrderStatus::Completed => $data['completed_at'] = now(),
            OrderStatus::Cancelled => $data['cancelled_at'] = now(),
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
    private function restoreOrderResources(Order $order): void
    {
        $orderItems = $order->items()
            ->orderBy('product_id')
            ->get();

        $productIds = $orderItems
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($orderItems as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = $products->get($item->product_id);

            if ($product !== null) {
                $product->increment(
                    'stock',
                    (int) $item->quantity,
                );
            }
        }

        if ($order->voucher_id === null) {
            return;
        }

        $voucher = Voucher::query()
            ->whereKey($order->voucher_id)
            ->lockForUpdate()
            ->first();

        if ($voucher === null) {
            return;
        }

        $deletedUsageCount = $voucher->usages()
            ->where('order_id', $order->id)
            ->delete();

        if ($deletedUsageCount > 0) {
            $voucher->update([
                'used_count' => max(
                    0,
                    (int) $voucher->used_count - 1,
                ),
            ]);
        }
    }
}
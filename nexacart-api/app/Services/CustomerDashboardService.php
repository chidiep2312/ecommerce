<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class CustomerDashboardService
{
    public function getDashboard(
        User $customer
    ): array {
        /*
         * Query gốc:
         * chỉ lấy order thuộc customer
         * đang đăng nhập.
         */
        $orderQuery = Order::query()
            ->where(
                'user_id',
                $customer->id
            );

        /*
         * Thống kê toàn bộ đơn hàng.
         *
         * Dùng clone để mỗi câu query
         * hoạt động độc lập.
         */
        $totalOrders =
            (clone $orderQuery)->count();

        $pendingOrders =
            (clone $orderQuery)
                ->where(
                    'status',
                    OrderStatus::Pending
                )
                ->count();

        $confirmedOrders =
            (clone $orderQuery)
                ->where(
                    'status',
                    OrderStatus::Confirmed
                )
                ->count();

        $shippingOrders =
            (clone $orderQuery)
                ->where(
                    'status',
                    OrderStatus::Shipping
                )
                ->count();

        $completedOrders =
            (clone $orderQuery)
                ->where(
                    'status',
                    OrderStatus::Completed
                )
                ->count();

        $cancelledOrders =
            (clone $orderQuery)
                ->where(
                    'status',
                    OrderStatus::Cancelled
                )
                ->count();

        /*
         * 5 đơn hàng mới nhất.
         */
        $recentOrders = Order::query()
            ->where(
                'user_id',
                $customer->id
            )
            ->with([
                'seller:id,name,email',
                'voucher:id,code,type,value',
            ])
            ->withCount('items')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'customer' =>
                $customer,

            'statistics' => [
                'total_orders' =>
                    $totalOrders,

                'pending_orders' =>
                    $pendingOrders,

                'confirmed_orders' =>
                    $confirmedOrders,

                'shipping_orders' =>
                    $shippingOrders,

                'completed_orders' =>
                    $completedOrders,

                'cancelled_orders' =>
                    $cancelledOrders,
            ],

            'recent_orders' =>
                $recentOrders,
        ];
    }
}
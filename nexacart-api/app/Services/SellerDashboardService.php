<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SellerDashboardService
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function getDashboard(
        User $seller
    ): array {
   
    return Cache::remember(
            "seller-dashboard:{$seller->id}",
            now()->addMinutes(5),
            fn() => [
                'summary' =>
                    $this->getSummary($seller),

                'order_statuses' =>
                    $this->getOrderStatuses($seller),

                'top_products' =>
                    $this->getTopProducts($seller),

                'daily_revenue' =>
                    $this->getDailyRevenue($seller),
            ]
        );
    }

    private function getSummary(
        User $seller
    ): array {
        $productQuery = Product::query()
            ->where(
                'seller_id',
                $seller->id
            );

        $orderQuery = Order::query()
            ->where(
                'seller_id',
                $seller->id
            );

        $completedOrderQuery =
            Order::query()
                ->where(
                    'seller_id',
                    $seller->id
                )
                ->where(
                    'status',
                    OrderStatus::Completed->value
                );

        return [
            'total_products' =>
                (clone $productQuery)
                    ->count(),

            'active_products' =>
                (clone $productQuery)
                    ->where(
                        'status',
                        ProductStatus::Active->value
                    )
                    ->count(),

            'low_stock_products' =>
                (clone $productQuery)
                    ->where(
                        'stock',
                        '>',
                        0
                    )
                    ->where(
                        'stock',
                        '<=',
                        self::LOW_STOCK_THRESHOLD
                    )
                    ->count(),

            'out_of_stock_products' =>
                (clone $productQuery)
                    ->where(
                        'stock',
                        0
                    )
                    ->count(),

            'total_orders' =>
                (clone $orderQuery)
                    ->count(),

            'completed_revenue' =>
                (float) (
                    (clone $completedOrderQuery)
                        ->sum('total')
                ),

            'current_month_revenue' =>
                (float) (
                    (clone $completedOrderQuery)
                        ->whereYear(
                            'completed_at',
                            now()->year
                        )
                        ->whereMonth(
                            'completed_at',
                            now()->month
                        )
                        ->sum('total')
                ),
        ];
    }

    private function getOrderStatuses(
        User $seller
    ): array {
        $counts = Order::query()
            ->where(
                'seller_id',
                $seller->id
            )
            ->selectRaw(
                'status, COUNT(*) as total'
            )
            ->groupBy('status')
            ->pluck(
                'total',
                'status'
            );

        return collect(
            OrderStatus::cases()
        )
            ->mapWithKeys(
                fn(
                    OrderStatus $status
                ) => [
                    $status->value =>
                        (int) (
                            $counts[
                                $status->value
                            ] ?? 0
                        ),
                ]
            )
            ->all();
    }

    private function getTopProducts(
        User $seller
    ): Collection {
        return OrderItem::query()
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->where(
                'orders.seller_id',
                $seller->id
            )
            ->where(
                'orders.status',
                OrderStatus::Completed->value
            )
            ->whereNull(
                'orders.deleted_at'
            )
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.product_sku',
            ])
            ->selectRaw(
                'SUM(order_items.quantity)
                 as total_quantity'
            )
            ->selectRaw(
                'SUM(order_items.line_total)
                 as total_revenue'
            )
            ->groupBy(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.product_sku'
            )
            ->orderByDesc(
                'total_quantity'
            )
            ->limit(5)
            ->get()
            ->map(
                fn($item) => [
                    'product_id' =>
                        $item->product_id,

                    'product_name' =>
                        $item->product_name,

                    'product_sku' =>
                        $item->product_sku,

                    'total_quantity' =>
                        (int)
                        $item->total_quantity,

                    'total_revenue' =>
                        (float)
                        $item->total_revenue,
                ]
            );
    }

    private function getDailyRevenue(
        User $seller
    ): Collection {
        $startDate = now()
            ->subDays(29)
            ->startOfDay();

        $endDate = now()
            ->endOfDay();

        $revenues = Order::query()
            ->where(
                'seller_id',
                $seller->id
            )
            ->where(
                'status',
                OrderStatus::Completed->value
            )
            ->whereNotNull(
                'completed_at'
            )
            ->whereBetween(
                'completed_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->selectRaw(
                'DATE(completed_at)
                 as revenue_date'
            )
            ->selectRaw(
                'SUM(total)
                 as revenue'
            )
            ->selectRaw(
                'COUNT(*)
                 as orders_count'
            )
            ->groupByRaw(
                'DATE(completed_at)'
            )
            ->orderBy(
                'revenue_date'
            )
            ->get()
            ->keyBy(
                'revenue_date'
            );

        return collect(
            CarbonPeriod::create(
                $startDate->toDateString(),
                $endDate->toDateString()
            )
        )
            ->map(
                function ($date)
                use ($revenues) {
                    $dateString =
                        $date->format(
                            'Y-m-d'
                        );

                    $row =
                        $revenues->get(
                            $dateString
                        );

                    return [
                        'date' =>
                            $dateString,

                        'revenue' =>
                            $row
                                ? (float)
                                    $row->revenue
                                : 0,

                        'orders_count' =>
                            $row
                                ? (int)
                                    $row->orders_count
                                : 0,
                    ];
                }
            )
            ->values();
    }
}
<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    private const PLATFORM_FEE_RATE = 0.05;

    private const MIN_FEE_ORDER_TOTAL = 300000;

    public function getDashboard(): array
    {
        return [
            'summary' => $this->getSummary(),
            'order_statuses' => $this->getOrderStatuses(),
            'top_sellers' => $this->getTopSellers(),
            'top_products' => $this->getTopProducts(),
            'platform_fee' => [
                'total' => $this->getTotalPlatformFee(),
                'current_month' => $this->getCurrentMonthPlatformFee(),
            ],
            'daily_platform_fee' => $this->fillMissingFeeDates(
                $this->getDailyPlatformFee()
            ),
        ];
    }

    private function getSummary(): array
    {
        return [
            'total_users' => User::query()->count(),

            'total_customers' => User::query()
                ->where('role', UserRole::Customer->value)
                ->count(),

            'total_sellers' => User::query()
                ->where('role', UserRole::Seller->value)
                ->count(),

            'total_products' => Product::query()->count(),

            'active_products' => Product::query()
                ->where('status', ProductStatus::Active->value)
                ->count(),

            'total_orders' => Order::query()->count(),

            'completed_orders' => Order::query()
                ->where('status', OrderStatus::Completed->value)
                ->count(),
        ];
    }

    private function getOrderStatuses(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status) => [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    private function getTopSellers(): Collection
    {
        return Order::query()
            ->join('users', 'users.id', '=', 'orders.seller_id')
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereNull('orders.deleted_at')
            ->whereNull('users.deleted_at')
            ->select([
                'orders.seller_id',
                'users.name as seller_name',
                'users.email as seller_email',
            ])
            ->selectRaw('COUNT(orders.id) as completed_orders')
            ->selectRaw('SUM(orders.total) as total_sales')
            ->groupBy(
                'orders.seller_id',
                'users.name',
                'users.email'
            )
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn ($seller) => [
                'seller_id' => $seller->seller_id,
                'seller_name' => $seller->seller_name,
                'seller_email' => $seller->seller_email,
                'completed_orders' => (int) $seller->completed_orders,
                'total_sales' => (float) $seller->total_sales,
            ]);
    }

    private function getTopProducts(): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereNull('orders.deleted_at')
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.product_sku',
            ])
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.line_total) as total_sales')
            ->groupBy(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.product_sku'
            )
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->map(fn ($product) => [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_sku' => $product->product_sku,
                'total_quantity' => (int) $product->total_quantity,
                'total_sales' => (float) $product->total_sales,
            ]);
    }

    private function feeEligibleOrders(): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->where('total', '>=', self::MIN_FEE_ORDER_TOTAL);
    }

    private function getTotalPlatformFee(): float
    {
        $sales = $this->feeEligibleOrders()->sum('total');

        return (float) $sales * self::PLATFORM_FEE_RATE;
    }

    private function getCurrentMonthPlatformFee(): float
    {
        $sales = $this->feeEligibleOrders()
            ->whereYear('completed_at', now()->year)
            ->whereMonth('completed_at', now()->month)
            ->sum('total');

        return (float) $sales * self::PLATFORM_FEE_RATE;
    }

    private function getDailyPlatformFee(): Collection
    {
        return $this->feeEligibleOrders()
            ->where('completed_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(completed_at) as fee_date')
            ->selectRaw('SUM(total) as eligible_sales')
            ->selectRaw('COUNT(*) as orders_count')
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('fee_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->fee_date,
                'fee' => (float) $row->eligible_sales * self::PLATFORM_FEE_RATE,
                'orders_count' => (int) $row->orders_count,
            ]);
    }

    private function fillMissingFeeDates(
        Collection $feeRows,
        int $days = 30
    ): Collection {
        $feesByDate = $feeRows->keyBy('date');

        return collect(range($days - 1, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString())
            ->map(fn (string $date) => $feesByDate->get($date, [
                'date' => $date,
                'fee' => 0,
                'orders_count' => 0,
            ]))
            ->values();
    }
}
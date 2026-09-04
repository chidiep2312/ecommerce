<?php

namespace App\Services;

use App\Enums\OrderStatus;
use Illuminate\Support\Collection;
use App\Enums\ShopStatus;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Order;

class ShopService
{
    private const PLATFORM_FEE_RATE = 0.05;

    private const MIN_FEE_ORDER_TOTAL = 300000;

    public function getSellerShop(User $seller): Shop
    {
        $shop = $seller->shop()->with(['owner:id,name,email',])->first();

        if ($shop === null) {
            throw ValidationException::withMessages([
                'shop' => 'Tài khoản chưa có cửa hàng.',
            ]);
        }

        return $shop;
    }

    public function updateProfile(User $seller, array $data): Shop
    {
        return DB::transaction(function () use ($seller, $data) {
            $shop = Shop::query()
                ->where('user_id', $seller->id)
                ->lockForUpdate()
                ->first();

            if ($shop === null) {
                throw ValidationException::withMessages([
                    'shop' => 'Tài khoản chưa có cửa hàng.',
                ]);
            }

            if (array_key_exists('name', $data)) {
                $name = trim($data['name']);

                if ($name !== $shop->name) {
                    $data['name'] = $name;
                    $data['slug'] = $this->generateUniqueSlug($name, $shop->id);
                }
            }

            if (array_key_exists('phone', $data)) {
                $data['phone'] = $data['phone'] !== null
                    ? trim($data['phone'])
                    : null;
            }

            if (array_key_exists('description', $data)) {
                $description = $data['description'];

                $data['description'] = $description !== null
                    ? trim($description)
                    : null;
            }

            $shop->update($data);

            return $shop->refresh()
                ->load([
                    'owner:id,name,email',
                ]);
        });
    }

    private function generateUniqueSlug(string $name, ?int $ignoreShopId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'shop';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Shop::query()
            ->where('slug', $slug)
            ->when(
                $ignoreShopId !== null,
                fn($query) => $query->where('id', '!=', $ignoreShopId)
            )
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getPublicShop(string $slug): Shop
    {
        return Shop::query()
            ->where('slug', $slug)
            ->where('status', ShopStatus::Active)
            ->with([
                'owner:id,name',
            ])
            ->withCount([
                'products',
            ])
            ->firstOrFail();
    }

    public function getPublicShopProducts(
        string $slug,
        array $filters = []
    ): LengthAwarePaginator {
        $shop = Shop::query()
            ->where('slug', $slug)
            ->where('status', ShopStatus::Active)
            ->firstOrFail();

        $query = Product::query()
            ->where('seller_id', $shop->user_id)
            ->active()
            ->where('stock', '>', 0)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name',
                'mainImage:id,product_id,path,is_main,sort_order',
            ]);

        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);

            $query->where(function ($query) use ($keyword) {
                $query
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            });
        }

        match ($filters['sort'] ?? 'latest') {
            'oldest' => $query->oldest(),

            'price_asc' => $query->orderByRaw(
                'COALESCE(sale_price, price) ASC'
            ),

            'price_desc' => $query->orderByRaw(
                'COALESCE(sale_price, price) DESC'
            ),

            'name_asc' => $query->orderBy('name'),

            'name_desc' => $query->orderByDesc('name'),

            default => $query->latest(),
        };

        return $query
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();
    }

    public function getSystemFee(User $seller): array
    {
        return [
            'current_fee' => $this->getCurrentMonthPlatformFee($seller),
            'total_fee' => $this->getTotalPlatformFee($seller),
            'fee_rate' => self::PLATFORM_FEE_RATE * 100,
            'min_order_total' => self::MIN_FEE_ORDER_TOTAL,
        ];
    }

    private function feeEligibleOrders(User $seller): Builder
    {
        return Order::query()
            ->where('seller_id', $seller->id)
            ->where('status', OrderStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->where('total', '>=', self::MIN_FEE_ORDER_TOTAL);
    }

    private function getTotalPlatformFee(User $seller): float
    {
        $sales = $this->feeEligibleOrders($seller)->sum('total');

        return (float) $sales * self::PLATFORM_FEE_RATE;
    }

    private function getCurrentMonthPlatformFee(User $seller): float
    {
        $sales = $this->feeEligibleOrders($seller)
            ->whereYear('completed_at', now()->year)
            ->whereMonth('completed_at', now()->month)
            ->sum('total');

        return (float) $sales * self::PLATFORM_FEE_RATE;
    }
   

   

    // private function getDailyPlatformFee(): Collection
    // {
    //     return $this->feeEligibleOrders()
    //         ->where('completed_at', '>=', now()->subDays(29)->startOfDay())
    //         ->selectRaw('DATE(completed_at) as fee_date')
    //         ->selectRaw('SUM(total) as eligible_sales')
    //         ->selectRaw('COUNT(*) as orders_count')
    //         ->groupByRaw('DATE(completed_at)')
    //         ->orderBy('fee_date')
    //         ->get()
    //         ->map(fn ($row) => [
    //             'date' => $row->fee_date,
    //             'fee' => (float) $row->eligible_sales * self::PLATFORM_FEE_RATE,
    //             'orders_count' => (int) $row->orders_count,
    //         ]);
    // }

}
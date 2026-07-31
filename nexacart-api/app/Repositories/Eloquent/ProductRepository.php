<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator {
        $query = Product::query()
            ->active()
            ->where('stock', '>', 0)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name',
                  'mainImage:id,product_id,path,is_main,sort_order',
            ])->withAvg('reviews', 'rating')->withCount('reviews');

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate(
            $filters['per_page'] ?? 15
        )->withQueryString();
    }

    public function paginateBySeller(
        int $sellerId,
        array $filters
    ): LengthAwarePaginator {
        $query = Product::query()
            ->ownedBy($sellerId)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate(
            $filters['per_page'] ?? 15
        )->withQueryString();
    }

    public function create(
        array $data
    ): Product {
        return Product::query()->create($data);
    }

    public function update(
        Product $product,
        array $data
    ): Product {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(
        Product $product
    ): void {
        $product->delete();
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        $query
            ->when(
                $filters['keyword'] ?? null,
                function (
                    Builder $query,
                    string $keyword
                ) {
                    $query->where(function (
                        Builder $query
                    ) use ($keyword) {
                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $keyword . '%'
                            )
                            ->orWhere(
                                'sku',
                                'like',
                                '%' . $keyword . '%'
                            );
                    });
                }
            )
            ->when(
                $filters['category'] ?? null,
                function (
                    Builder $query,
                    string $categorySlug
                ) {
                    $query->whereHas(
                        'category',
                        fn (Builder $categoryQuery)
                            => $categoryQuery->where(
                                'slug',
                                $categorySlug
                            )
                    );
                }
            )
            ->when(
                $filters['brand'] ?? null,
                function (
                    Builder $query,
                    string $brandSlug
                ) {
                    $query->whereHas(
                        'brand',
                        fn (Builder $brandQuery)
                            => $brandQuery->where(
                                'slug',
                                $brandSlug
                            )
                    );
                }
            )
            ->when(
                isset($filters['min_price']),
                fn (Builder $query)
                    => $query->whereRaw(
                        'COALESCE(sale_price, price) >= ?',
                        [$filters['min_price']]
                    )
            )
            ->when(
                isset($filters['max_price']),
                fn (Builder $query)
                    => $query->whereRaw(
                        'COALESCE(sale_price, price) <= ?',
                        [$filters['max_price']]
                    )
            );
    }

    private function applySort(
        Builder $query,
        ?string $sort
    ): void {
        match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query
                ->orderByRaw(
                    'COALESCE(sale_price, price) ASC'
                ),
            'price_desc' => $query
                ->orderByRaw(
                    'COALESCE(sale_price, price) DESC'
                ),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query
                ->orderByDesc('name'),
            default => $query->latest(),
        };
    }
}
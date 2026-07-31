<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function getSellerInventory(
        User $seller,
        array $filters = []
    ): LengthAwarePaginator {
        return Product::query()
            ->where(
                'seller_id',
                $seller->id
            )
            ->with([
                'category:id,name',
                'brand:id,name',
                'mainImage:id,product_id,path,is_main',
            ])
            ->when(
                !empty($filters['search']),
                function ($query) use ($filters) {
                    $search = trim(
                        $filters['search']
                    );

                    $query->where(
                        function ($subQuery) use (
                            $search
                        ) {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                !empty($filters['stock_status']),
                function ($query) use ($filters) {
                    match (
                        $filters['stock_status']
                    ) {
                        'in_stock' =>
                            $query->where(
                                'stock',
                                '>',
                                5
                            ),

                        'low_stock' =>
                            $query
                                ->where(
                                    'stock',
                                    '>',
                                    0
                                )
                                ->where(
                                    'stock',
                                    '<=',
                                    5
                                ),

                        'out_of_stock' =>
                            $query->where(
                                'stock',
                                0
                            ),

                        default => null,
                    };
                }
            )
            ->latest('id')
            ->paginate(
                min(
                    max(
                        (int) (
                            $filters['per_page']
                            ?? 15
                        ),
                        5
                    ),
                    100
                )
            )
            ->withQueryString();
    }

    public function updateStock(
        User $seller,
        Product $product,
        array $data
    ): Product {
        return DB::transaction(function () use (
            $seller,
            $product,
            $data
        ) {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedProduct->seller_id !==
                $seller->id
            ) {
                throw ValidationException::withMessages([
                    'product' =>
                        'Bạn không có quyền cập nhật sản phẩm này.',
                ]);
            }

            $type = $data['type'];

            $quantity = (int)
                $data['quantity'];

            if ($type === 'import') {
                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' =>
                            'Số lượng nhập thêm phải lớn hơn 0.',
                    ]);
                }

                $lockedProduct->increment(
                    'stock',
                    $quantity
                );
            } else {
                $lockedProduct->update([
                    'stock' => $quantity,
                ]);
            }

            return $lockedProduct
                ->refresh()
                ->load([
                    'category:id,name',
                    'brand:id,name',
                    'mainImage:id,product_id,path,is_main',
                ]);
        }, 3);
    }
}
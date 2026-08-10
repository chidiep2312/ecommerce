<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WishlistService
{
    public function getWishlist(
        User $user
    ): LengthAwarePaginator {
        return $user
            ->wishlistProducts()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name',
                'mainImage:id,product_id,path,is_main',
            ])
            ->latest('wishlists.created_at')
            ->paginate(12);
    }

    public function add(
        User $user,
        Product $product
    ): void {
        $user
            ->wishlistProducts()
            ->syncWithoutDetaching([
                $product->id,
            ]);
    }

    public function remove(
        User $user,
        Product $product
    ): void {
        $user
            ->wishlistProducts()
            ->detach(
                $product->id
            );
    }

    public function isWishlisted(
        User $user,
        Product $product
    ): bool {
        return $user
            ->wishlistProducts()
            ->where(
                'products.id',
                $product->id
            )
            ->exists();
    }
}
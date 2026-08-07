<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getCart(User $user): Cart
    {
        $cart = $this->getOrCreateCart($user);

        return $cart->load([
            'items.product.category:id,name,slug',
            'items.product.brand:id,name,slug',
            'items.product.seller:id,name',
            'items.product.mainImage:id,product_id,path,is_main,sort_order',
        ]);
    }

    public function addItem(
        User $user,
        array $data
    ): Cart {
        return DB::transaction(function () use (
            $user,
            $data
        ) {
            $cart = $this->getOrCreateCart($user);

            $product = Product::query()
                ->whereKey($data['product_id'])
                ->firstOrFail();

            $this->ensureProductCanBePurchased(
                $product
            );

            $cartItem = $cart->items()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = $data['quantity'];

            if ($cartItem !== null) {
                $newQuantity += $cartItem->quantity;
            }

            $this->ensureStockIsAvailable(
                $product,
                $newQuantity
            );

            if ($cartItem !== null) {
                $cartItem->update([
                    'quantity' => $newQuantity,
                ]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $newQuantity,
                ]);
            }

            return $this->getCart($user);
        });
    }

    public function updateItem(
        User $user,
        CartItem $cartItem,
        int $quantity
    ): Cart {
        $product = $cartItem
            ->product()
            ->firstOrFail();

        $this->ensureProductCanBePurchased(
            $product
        );

        $this->ensureStockIsAvailable(
            $product,
            $quantity
        );

        $cartItem->update([
            'quantity' => $quantity,
        ]);

        return $this->getCart($user);
    }

    public function removeItem(
        User $user,
        CartItem $cartItem
    ): Cart {
        $cartItem->delete();

        return $this->getCart($user);
    }

    public function clear(User $user): void
    {
        $cart = $this->getOrCreateCart($user);

        $cart->items()->delete();
    }

    private function getOrCreateCart(
        User $user
    ): Cart {
        return $user->cart()->firstOrCreate();
    }

    private function ensureProductCanBePurchased(
        Product $product
    ): void {
        if (
            $product->status !== ProductStatus::Active
        ) {
            throw new ProductUnavailableException(
                'Sản phẩm hiện không được phép bán.'
            );
        }

        if ($product->stock < 1) {
            throw new ProductUnavailableException(
                'Sản phẩm hiện đã hết hàng.'
            );
        }
    }

    private function ensureStockIsAvailable(
        Product $product,
        int $quantity
    ): void {
        if ($quantity > $product->stock) {
            throw new InsufficientStockException(
                "Sản phẩm chỉ còn {$product->stock} sản phẩm trong kho."
            );
        }
    }
}

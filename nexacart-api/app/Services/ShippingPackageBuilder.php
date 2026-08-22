<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShippingPackageBuilder
{
    public function build(
        Collection $cartItems,
    ): array {
        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [
                    'Không có sản phẩm để tính phí vận chuyển.',
                ],
            ]);
        }

        foreach ($cartItems as $item) {
            $product = $item->product;

            if (
                !$product->weight ||
                !$product->length ||
                !$product->width ||
                !$product->height
            ) {
                throw ValidationException::withMessages([
                    'shipping' => [
                        "Sản phẩm {$product->name} chưa có thông tin vận chuyển.",
                    ],
                ]);
            }
        }
        
        $weight =
            $cartItems->sum(
                function ($item) {
                    return (
                        $item->product->weight * $item->quantity
                    );
                },
            );

        $length =
            $cartItems->max(
                fn ($item) => $item->product->length
            );

        $width =
            $cartItems->max(
                fn ($item) => $item->product->width
            );

        $height =
            $cartItems->sum(
                fn ($item) =>
                    $item->product->height * $item->quantity
            );

        return [
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
        ];
    }
}
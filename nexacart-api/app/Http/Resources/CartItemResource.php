<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartItemResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $product = $this->product;

        return [
            /*
             * cart_items.id
             */
            'id' => $this->id,

            'product_id' =>
                $this->product_id,

            'quantity' =>
                $this->quantity,

            'product' => [
                'id' => $product->id,

                /*
                 * Bắt buộc có seller_id.
                 */
                'seller_id' =>
                    $product->seller_id,

                'name' =>
                    $product->name,

                'slug' =>
                    $product->slug,

                'price' =>
                    $product->price,

                'sale_price' =>
                    $product->sale_price,

                'effective_price' =>
                    $product->effective_price,

                'stock' =>
                    $product->stock,

                'image' =>
                    $product->mainImage
                        ? Storage::url(
                            $product
                                ->mainImage
                                ->path
                        )
                        : null,

                /*
                 * Trả thêm thông tin seller.
                 */
                'seller' => $product->seller
                    ? [
                        'id' =>
                            $product->seller->id,

                        'name' =>
                            $product->seller->name,
                    ]
                    : null,
            ],
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'sku' => $this->sku,

            'price' =>  $this->price,

            'sale_price' => $this->sale_price !== null
                ? $this->sale_price
                : null,

            'effective_price'
            => $this->effective_price,

            'stock' => $this->stock,

            'in_stock' => $this->isInStock(),

            'description' => $this->description,

            'status' => $this->status?->value,

            'category' => new CategoryResource(
                $this->whenLoaded('category')
            ),

            'brand' => new BrandResource(
                $this->whenLoaded('brand')
            ),

            'seller' => $this->whenLoaded(
                'seller',
                function () {
                    return [
                        'id' =>
                        $this->seller->id,

                        'name' =>
                        $this->seller->name,

                        'shop' =>
                        $this->seller->relationLoaded(
                            'shop'
                        )
                            ? (
                                $this->seller->shop
                                ? [
                                    'id' =>
                                    $this->seller
                                        ->shop
                                        ->id,

                                    'name' =>
                                    $this->seller
                                        ->shop
                                        ->name,

                                    'slug' =>
                                    $this->seller
                                        ->shop
                                        ->slug,
                                ]
                                : null
                            )
                            : null,
                    ];
                }
            ),
            'is_wishlisted' =>
            (bool)
            $this->is_wishlisted,
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 1)
                : null,

            'reviews_count' => $this->whenCounted(
                'reviews'
            ),
            'reviews' =>
            ReviewResource::collection(
                $this->whenLoaded(
                    'reviews'
                )
            ),

            'created_at'
            => $this->created_at?->toISOString(),

            'updated_at'
            => $this->updated_at?->toISOString(),
            'main_image' => new ProductImageResource(
                $this->whenLoaded('mainImage')
            ),

            'images' => ProductImageResource::collection(
                $this->whenLoaded('images')
            ),
            'is_suspended' => $this->is_suspended,
            'suspended_at' => $this->suspended_at?->toISOString(),
            'suspended_by' => new UserResource(
                $this->whenLoaded('suspendedBy')
            ),
        ];
    }
}

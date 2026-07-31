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

            'price' => (float) $this->price,

            'sale_price' => $this->sale_price !== null
                ? (float) $this->sale_price
                : null,

            'effective_price'
            => (float) $this->effective_price,

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

            'seller' => new UserResource(
                $this->whenLoaded('seller')
            ),
            'average_rating' => $this->reviews_avg_rating !== null
                ? round((float) $this->reviews_avg_rating, 1)
                : null,

            'reviews_count' => $this->whenCounted(
                'reviews'
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

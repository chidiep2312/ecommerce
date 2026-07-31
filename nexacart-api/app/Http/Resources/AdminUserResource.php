<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'role' => $this->role?->value,

            'status' => $this->status?->value,

            'email_verified_at'
                => $this->email_verified_at?->toISOString(),

            'products_count'
                => $this->whenCounted('products'),

            'customer_orders_count'
                => $this->whenCounted('customerOrders'),

            'seller_orders_count'
                => $this->whenCounted('sellerOrders'),

            'reviews_count'
                => $this->whenCounted('reviews'),

            'created_at'
                => $this->created_at?->toISOString(),

            'updated_at'
                => $this->updated_at?->toISOString(),
        ];
    }
}
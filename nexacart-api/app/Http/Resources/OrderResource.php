<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'order_code' => $this->order_code,

            'subtotal' => (float) $this->subtotal,

            'discount_amount'
            => (float) $this->discount_amount,

            'total' => (float) $this->total,

            'status' => $this->status?->value,

            'shipping' => [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
            ],

            'customer_note' => $this->customer_note,

            'customer' => new UserResource(
                $this->whenLoaded('customer')
            ),

            'seller' => new UserResource(
                $this->whenLoaded('seller')
            ),

            'voucher' => new VoucherResource(
                $this->whenLoaded('voucher')
            ),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'items_count' => $this->whenCounted(
                'items'
            ),

            'confirmed_at'
            => $this->confirmed_at?->toISOString(),

            'shipping_at'
            => $this->shipping_at?->toISOString(),

            'completed_at'
            => $this->completed_at?->toISOString(),

            'cancelled_at'
            => $this->cancelled_at?->toISOString(),

            'created_at'
            => $this->created_at?->toISOString(),

            'updated_at'
            => $this->updated_at?->toISOString(),
        ];
    }
}

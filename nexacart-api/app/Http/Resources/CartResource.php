<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        $subtotal = $this->items->sum(
            function ($item) {
                return (float) $item
                    ->product
                    ->effective_price
                    * $item->quantity;
            }
        );

        return [
            'id' => $this->id,

            'items' => CartItemResource::collection(
                $items
            ),

            'total_items' => $this->items->sum(
                'quantity'
            ),

            'subtotal' => $subtotal,

            'created_at'
                => $this->created_at?->toISOString(),

            'updated_at'
                => $this->updated_at?->toISOString(),
        ];
    }
}
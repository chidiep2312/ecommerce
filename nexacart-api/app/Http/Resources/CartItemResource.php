<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
  public function toArray(Request $request): array
{
    $product = $this->resource
        ->relationLoaded('product')
            ? $this->product
            : null;

    $unitPrice = $product !== null
        ? (float) $product->effective_price
        : 0;

    return [
        'id' => $this->id,
        'quantity' => $this->quantity,
        'unit_price' => $unitPrice,
        'line_total' => $unitPrice * $this->quantity,
        'product' => $product !== null
            ? new ProductResource($product)
            : null,
    ];
}
}
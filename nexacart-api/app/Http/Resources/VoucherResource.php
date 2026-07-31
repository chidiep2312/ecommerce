<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type?->value,

            'value' => (float) $this->value,

            'min_order_amount'
                => (float) $this->min_order_amount,

            'max_discount_amount'
                => $this->max_discount_amount !== null
                    ? (float) $this
                        ->max_discount_amount
                    : null,

            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,

            'usage_limit_per_user'
                => $this->usage_limit_per_user,

            'usages_count'
                => $this->whenCounted('usages'),

            'starts_at'
                => $this->starts_at?->toISOString(),

            'expires_at'
                => $this->expires_at?->toISOString(),

            'status' => $this->status?->value,

            'created_at'
                => $this->created_at?->toISOString(),
        ];
    }
}
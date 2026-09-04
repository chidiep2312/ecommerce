<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherUsageResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,

            'discount_amount' =>
                (float)
                $this->discount_amount,

            'voucher' => [
                'id' =>
                    $this->voucher?->id,

                'code' =>
                    $this->voucher?->code,

                'type' =>
                    $this->voucher
                        ?->type?->value,

                'value' =>
                    (float) (
                        $this->voucher
                            ?->value ?? 0
                    ),

                'min_order_amount' =>
                    (float) (
                        $this->voucher
                            ?->min_order_amount
                        ?? 0
                    ),

                'max_discount_amount' =>
                    $this->voucher
                        ?->max_discount_amount
                        !== null
                            ? (float)
                                $this->voucher
                                    ->max_discount_amount
                            : null,
            ],

            'customer' => [
                'id' =>
                    $this->user?->id,

                'name' =>
                    $this->user?->name,

                'email' =>
                    $this->user?->email,
            ],

            'order' => [
                'id' =>
                    $this->order?->id,

                'order_code' =>
                    $this->order
                        ?->order_code,

                'status' =>
                    $this->order
                        ?->status?->value,

                'subtotal' =>
                    (float) (
                        $this->order
                            ?->subtotal ?? 0
                    ),

                'discount_amount' =>
                    (float) (
                        $this->order
                            ?->discount_amount
                        ?? 0
                    ),

                'total_amount' =>
                    (float) (
                        $this->order
                            ?->total ?? 0
                    ),
            ],

            'used_at' =>
                $this->created_at
                    ?->toISOString(),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            'role' => $this->role,
            'status' => $this->status,

            'products_count' =>
                $this->whenCounted('products'),

            'seller_orders_count' =>
                $this->whenCounted('sellerOrders'),

            'seller_request' =>
                $this->whenLoaded(
                    'latestSellerRequest',
                    function () {
                        return [
                            'id' =>
                                $this->latestSellerRequest?->id,

                            'approved_at' =>
                                $this->latestSellerRequest
                                    ?->reviewed_at,

                            'approved_at_formatted' =>
                                $this->latestSellerRequest
                                    ?->reviewed_at
                                    ?->format('d/m/Y H:i'),
                        ];
                    }
                ),

            'created_at' => $this->created_at,

            'created_at_formatted' =>
                $this->created_at?->format(
                    'd/m/Y H:i'
                ),

            'updated_at' => $this->updated_at,
        ];
    }
}
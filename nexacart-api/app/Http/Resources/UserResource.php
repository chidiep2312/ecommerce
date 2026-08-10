<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'status' => $this->status?->value,
            'latest_seller_request' =>
            $this->whenLoaded(
                'latestSellerRequest',
                function () {
                    return [
                        'id' =>
                        $this
                            ->latestSellerRequest
                            ?->id,

                        'status' =>
                        $this
                            ->latestSellerRequest
                            ?->status,

                        'reason' =>
                        $this
                            ->latestSellerRequest
                            ?->reason,

                        'rejection_reason' =>
                        $this
                            ->latestSellerRequest
                            ?->rejection_reason,

                        'reviewed_at' =>
                        $this
                            ->latestSellerRequest
                            ?->reviewed_at
                            ?->toISOString(),

                        'created_at' =>
                        $this
                            ->latestSellerRequest
                            ?->created_at
                            ?->toISOString(),
                    ];
                }
            ),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

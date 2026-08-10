<?php

namespace App\Http\Resources;

use App\Enums\ShopStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
            $this->id,

            'name' =>
            $this->name,

            'slug' =>
            $this->slug,

            'description' =>
            $this->description,

            'phone' =>
            $this->phone,

            'logo' =>
            $this->logo,

            'banner' =>
            $this->banner,

            'status' => $this->status->value,



            'owner' =>
            new UserResource(
                $this->whenLoaded(
                    'owner'
                )
            ),

            'created_at' =>
            $this->created_at
                ?->toISOString(),

            'updated_at' =>
            $this->updated_at
                ?->toISOString(),
        ];
    }
}

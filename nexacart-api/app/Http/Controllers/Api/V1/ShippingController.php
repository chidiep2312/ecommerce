<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GhnService;
use App\Services\ShippingService;
use App\Http\Requests\ShippingQuoteRequest;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    public function __construct(
        private GhnService $ghnService,

         private ShippingService $shippingService

    ) {}

    public function provinces(): JsonResponse
    {
        return response()->json([
            'data' =>
            $this->ghnService->getProvinces(),
        ]);
    }

    public function districts(
        int $provinceId
    ): JsonResponse {
        return response()->json([
            'data' =>
            $this->ghnService
                ->getDistricts($provinceId),
        ]);
    }

    public function wards(
        int $districtId
    ): JsonResponse {
        return response()->json([
            'data' =>
            $this->ghnService
                ->getWards($districtId),
        ]);
    }
    public function options(
        ShippingQuoteRequest $request,
    ): JsonResponse {
        $options =
            $this
            ->shippingService
            ->getOptions(
                customer: $request->user(),

                sellerId: $request->integer(
                    'seller_id',
                ),

                addressId: $request->integer(
                    'address_id',
                ),

                cartItemIds: $request->input(
                    'cart_item_ids',
                    [],
                ),
            );

        return response()->json([
            'data' => $options,
        ]);
    }
}

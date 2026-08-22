<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerPickupAddressRequest;
use App\Models\SellerPickupAddress;
use App\Services\SellerPickupAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerPickupAddressController extends Controller
{
    public function __construct(
        private SellerPickupAddressService $service,
    ) {
    }

    public function index(
        Request $request,
    ): JsonResponse {
        $addresses =
            $request
                ->user()
                ->pickupAddresses()
                ->orderByDesc(
                    'is_default',
                )
                ->latest()
                ->get();

        return response()->json([
            'data' =>
                $addresses,
        ]);
    }

    public function store(
        StoreSellerPickupAddressRequest $request,
    ): JsonResponse {
        $address =
            $this
                ->service
                ->create(
                    seller:
                        $request->user(),

                    data:
                        $request
                            ->validated(),
                );

        $message =
            $address
                ->ghn_sync_status ===
            'synced'
                ? 'Đã tạo địa chỉ lấy hàng và đồng bộ GHN.'
                : 'Đã lưu địa chỉ lấy hàng nhưng chưa đồng bộ được GHN.';

        return response()->json([
            'message' =>
                $message,

            'data' =>
                $address,
        ], 201);
    }

    public function sync(
        Request $request,
        SellerPickupAddress $pickupAddress,
    ): JsonResponse {
        abort_unless(
            $pickupAddress->seller_id ===
                $request->user()->id,
            403,
        );

        $address =
            $this
                ->service
                ->syncWithGhn(
                    $pickupAddress,
                );

        return response()->json([
            'message' =>
                $address
                    ->ghn_sync_status ===
                'synced'
                    ? 'Đồng bộ GHN thành công.'
                    : 'Chưa thể đồng bộ GHN.',

            'data' =>
                $address,
        ]);
    }

    public function setDefault(
        Request $request,
        SellerPickupAddress $pickupAddress,
    ): JsonResponse {
        $address =
            $this
                ->service
                ->setDefault(
                    seller:
                        $request->user(),

                    address:
                        $pickupAddress,
                );

        return response()->json([
            'message' =>
                'Đã đặt địa chỉ lấy hàng mặc định.',

            'data' =>
                $address,
        ]);
    }
}
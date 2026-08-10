<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends Controller
{
    public function __construct(
        private readonly AddressService
            $addressService
    ) {
    }

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $addresses = $this
            ->addressService
            ->getUserAddresses(
                $request->user()
            );

        return AddressResource::collection(
            $addresses
        );
    }

    public function store(
        StoreAddressRequest $request
    ): JsonResponse {
        $address = $this
            ->addressService
            ->create(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Thêm địa chỉ thành công.',

            'data' =>
                new AddressResource(
                    $address
                ),

            'errors' => null,
        ], 201);
    }

    public function update(
        UpdateAddressRequest $request,
        Address $address
    ): JsonResponse {
        $address = $this
            ->addressService
            ->update(
                $request->user(),
                $address,
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Cập nhật địa chỉ thành công.',

            'data' =>
                new AddressResource(
                    $address
                ),

            'errors' => null,
        ]);
    }

    public function setDefault(
        Request $request,
        Address $address
    ): JsonResponse {
        $address = $this
            ->addressService
            ->setDefault(
                $request->user(),
                $address
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Đã đặt làm địa chỉ mặc định.',

            'data' =>
                new AddressResource(
                    $address
                ),

            'errors' => null,
        ]);
    }

    public function destroy(
        Request $request,
        Address $address
    ): JsonResponse {
        $this
            ->addressService
            ->delete(
                $request->user(),
                $address
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Xóa địa chỉ thành công.',

            'data' => null,

            'errors' => null,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopProfileRequest;
use App\Http\Resources\ShopResource;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
class ShopController extends Controller
{
    public function __construct(
        private readonly ShopService
        $shopService
    ) {}

    public function profile(
        Request $request
    ): JsonResponse {
        $shop = $this
            ->shopService
            ->getSellerShop(
                $request->user()
            );

        return response()->json([
            'success' => true,

            'message' =>
            'Lấy hồ sơ cửa hàng thành công.',

            'data' =>
            new ShopResource(
                $shop
            ),

            'errors' => null,
        ]);
    }

    public function updateProfile(
        UpdateShopProfileRequest $request
    ): JsonResponse {
        $shop = $this
            ->shopService
            ->updateProfile(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
            'Cập nhật hồ sơ cửa hàng thành công.',

            'data' =>
            new ShopResource(
                $shop
            ),

            'errors' => null,
        ]);
    }

    public function show(
        string $slug
    ): ShopResource {
        $shop = $this
            ->shopService
            ->getPublicShop(
                $slug
            );

        return new ShopResource(
            $shop
        );
    }
    public function products(
        Request $request,
        string $slug
    ): AnonymousResourceCollection {
        $filters = $request->validate([
            'keyword' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'latest',
                    'oldest',
                    'price_asc',
                    'price_desc',
                    'name_asc',
                    'name_desc',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ]);

        $products = $this
            ->shopService
            ->getPublicShopProducts(
                $slug,
                $filters
            );

        return ProductResource::collection(
            $products
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService
            $wishlistService
    ) {
    }

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $products =
            $this->wishlistService
                ->getWishlist(
                    $request->user()
                );

        return ProductResource::collection(
            $products
        );
    }

    public function store(
        Request $request,
        Product $product
    ): JsonResponse {
        $this->wishlistService
            ->add(
                $request->user(),
                $product
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Đã thêm sản phẩm vào danh sách yêu thích.',
            'data' => null,
            'errors' => null,
        ]);
    }

    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse {
        $this->wishlistService
            ->remove(
                $request->user(),
                $product
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Đã xóa sản phẩm khỏi danh sách yêu thích.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
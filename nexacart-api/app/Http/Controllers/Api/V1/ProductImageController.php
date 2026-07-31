<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductImage\StoreProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $productImageService
    ) {
    }

    public function store(
        StoreProductImageRequest $request,
        Product $product
    ): JsonResponse {
        $this->authorize('update', $product);

        $images = $this->productImageService
            ->uploadMany(
                $product,
                $request->file('images')
            );

        return response()->json([
            'success' => true,
            'message' => 'Tải ảnh sản phẩm thành công.',
            'data' => ProductImageResource::collection(
                $images
            ),
            'errors' => null,
        ], 201);
    }

    public function setMain(
        Product $product,
        ProductImage $image
    ): JsonResponse {
        $this->authorize('update', $product);

        $this->ensureImageBelongsToProduct(
            $product,
            $image
        );

        $image = $this->productImageService
            ->setMain($product, $image);

        return response()->json([
            'success' => true,
            'message' => 'Đặt ảnh chính thành công.',
            'data' => new ProductImageResource($image),
            'errors' => null,
        ]);
    }

    public function destroy(
        Product $product,
        ProductImage $image
    ): JsonResponse {
        $this->authorize('update', $product);

        $this->ensureImageBelongsToProduct(
            $product,
            $image
        );

        $this->productImageService->delete(
            $product,
            $image
        );

        return response()->json([
            'success' => true,
            'message' => 'Xóa ảnh sản phẩm thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }

    private function ensureImageBelongsToProduct(
        Product $product,
        ProductImage $image
    ): void {
        abort_unless(
            $image->product_id === $product->id,
            404
        );
    }
}
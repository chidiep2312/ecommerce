<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Product\SuspendProductRequest;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(
        ProductFilterRequest $request
    ): AnonymousResourceCollection {
        $products = $this->productService
            ->getPublicProducts(
                $request->validated()
            );

        return ProductResource::collection(
            $products
        );
    }

    public function sellerIndex(
        ProductFilterRequest $request
    ): AnonymousResourceCollection {
        $products = $this->productService
            ->getSellerProducts(
                $request->user(),
                $request->validated()
            );

        return ProductResource::collection(
            $products
        );
    }

    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $product = $this->productService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm thành công.',
            'data' => new ProductResource($product),
            'errors' => null,
        ], 201);
    }

    public function show(
        Product $product
    ): ProductResource {
        abort_unless(
            $product->status->value === 'active',
            404
        );

        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'seller:id,name',
        ]);

        return new ProductResource($product);
    }

    public function sellerShow(
        Product $product
    ): ProductResource {
        $this->authorize('view', $product);

        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'seller:id,name',
            'mainImage:id,product_id,path,is_main,sort_order',
            'images:id,product_id,path,is_main,sort_order',
        ]);

        return new ProductResource($product);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): JsonResponse {
        $this->authorize('update', $product);

        $product = $this->productService->update(
            $product,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => new ProductResource($product),
            'errors' => null,
        ]);
    }

    public function destroy(
        Product $product
    ): JsonResponse {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return ApiResponse::success(
            message: 'Xóa sản phẩm thành công.'
        );
    }
    public function adminIndex(
        ProductFilterRequest $request
    ): AnonymousResourceCollection {
        $products = $this->productService
            ->getAdminProducts(
                $request->validated()
            );

        return ProductResource::collection(
            $products
        );
    }

    public function adminShow(
        Product $product
    ): AdminProductResource {
        $this->authorize('view', $product);

        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'seller:id,name',
            'mainImage:id,product_id,path,is_main,sort_order',
            'images:id,product_id,path,is_main,sort_order',
        ]);

        return new AdminProductResource($product);
    }

    public function suspend(
        SuspendProductRequest $request,
        Product $product
    ): JsonResponse {
        $product = $this->productService
            ->suspend(
                $product,
                $request->user(),
                $request->validated('reason')
            );

        return ApiResponse::success(
            data: new AdminProductResource($product),
            message: 'Khóa sản phẩm thành công.'
        );
    }
    public function restore(
        Product $product
    ): JsonResponse {
        abort_unless(
            request()->user()?->role->value === 'admin',
            403
        );

        $product = $this->productService
            ->restore($product);

        return ApiResponse::success(
            data: new ProductResource($product),
            message: 'Mở khóa sản phẩm thành công.'
        );
    }
    public function getProduct(
        string $slug
    ): ProductResource {
        $product = $this->productService
            ->getProduct($slug);
  
        return new ProductResource(
            $product
        );
    }
}

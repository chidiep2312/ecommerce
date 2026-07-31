<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $brands = $this->brandService
            ->getPublicBrands();

        return BrandResource::collection($brands);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $brands = $this->brandService
            ->getAdminBrands();

        return BrandResource::collection($brands);
    }

    public function store(
        StoreBrandRequest $request
    ): JsonResponse {
        $brand = $this->brandService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo thương hiệu thành công.',
            'data' => new BrandResource($brand),
            'errors' => null,
        ], 201);
    }

    public function show(
        Brand $brand
    ): BrandResource {
        return new BrandResource($brand);
    }

    public function update(
        UpdateBrandRequest $request,
        Brand $brand
    ): JsonResponse {
        $brand = $this->brandService->update(
            $brand,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thương hiệu thành công.',
            'data' => new BrandResource($brand),
            'errors' => null,
        ]);
    }

    public function destroy(
        Brand $brand
    ): JsonResponse {
        $this->brandService->delete($brand);

        return response()->json([
            'success' => true,
            'message' => 'Xóa thương hiệu thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
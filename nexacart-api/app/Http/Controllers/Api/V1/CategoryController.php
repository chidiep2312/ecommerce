<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{


    public function __construct(
        private readonly CategoryService $categoryService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService
            ->getPublicCategories();

        return CategoryResource::collection($categories);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $categories = $this->categoryService
            ->getAdminCategories();

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->create(
            $request->validated()
        );
        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục thành công.',
            'data' => new CategoryResource($category),
            'errors' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ) {
        $category = $this->categoryService->update(
            $category,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công.',
            'data' => new CategoryResource($category),
            'errors' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Category $category
    ): JsonResponse {
        $this->categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}

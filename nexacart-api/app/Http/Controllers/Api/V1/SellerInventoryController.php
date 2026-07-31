<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerInventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'stock_status' => [
                'nullable',
                'in:in_stock,low_stock,out_of_stock',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $products = $this
            ->inventoryService
            ->getSellerInventory(
                $request->user(),
                $filters
            );

        return ProductResource::collection(
            $products
        );
    }

    public function update(
        UpdateInventoryRequest $request,
        Product $product
    ): JsonResponse {
        $product = $this
            ->inventoryService
            ->updateStock(
                $request->user(),
                $product,
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Cập nhật tồn kho thành công.',

            'data' =>
                new ProductResource($product),

            'errors' => null,
        ]);
    }
}
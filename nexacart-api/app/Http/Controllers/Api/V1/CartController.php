<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {
    }

    public function show(
        Request $request
    ): CartResource {
        $cart = $this->cartService->getCart(
            $request->user()
        );

        return new CartResource($cart);
    }

    public function addItem(
        AddCartItemRequest $request
    ): JsonResponse {
        $cart = $this->cartService->addItem(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
            'data' => new CartResource($cart),
            'errors' => null,
        ], 201);
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        CartItem $cartItem
    ): JsonResponse {
        $this->authorize('update', $cartItem);

        $cart = $this->cartService->updateItem(
            $request->user(),
            $cartItem,
            $request->integer('quantity')
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật giỏ hàng thành công.',
            'data' => new CartResource($cart),
            'errors' => null,
        ]);
    }

    public function removeItem(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        $this->authorize('delete', $cartItem);

        $cart = $this->cartService->removeItem(
            $request->user(),
            $cartItem
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
            'data' => new CartResource($cart),
            'errors' => null,
        ]);
    }

    public function clear(
        Request $request
    ): JsonResponse {
        $this->cartService->clear(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
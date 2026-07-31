<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function customerIndex(
        Request $request
    ): AnonymousResourceCollection {
        $orders = $this->orderService
            ->getCustomerOrders(
                $request->user()
            );

        return OrderResource::collection($orders);
    }

    public function sellerIndex(
        Request $request
    ): AnonymousResourceCollection {
        $orders = $this->orderService
            ->getSellerOrders(
                $request->user()
            );

        return OrderResource::collection($orders);
    }

    public function adminIndex(): AnonymousResourceCollection
    {
        $orders = $this->orderService
            ->getAdminOrders();

        return OrderResource::collection($orders);
    }

    public function show(
        Order $order
    ): OrderResource {
        $this->authorize('view', $order);

        $order = $this->orderService
            ->loadDetail($order);

        return new OrderResource($order);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    ): JsonResponse {
        $this->authorize(
            'updateStatus',
            $order
        );

        $data = $request->validated();

        $newStatus = OrderStatus::from(
            $data['status']
        );

        $order = $this->orderService
            ->updateStatus(
                $order,
                $newStatus,
                $request->user()
            );

        return response()->json([
            'success' => true,

            'message' =>
            'Cập nhật trạng thái đơn hàng thành công.',

            'data' =>
            new OrderResource($order),

            'errors' => null,
        ]);
    }
    public function cancel(
        Request $request,
        Order $order
    ): JsonResponse {
        $this->authorize(
            'cancel',
            $order
        );

        $order = $this->orderService
            ->cancelByCustomer(
                $order,
                $request->user()
            );

        return response()->json([
            'success' => true,

            'message' =>
            'Hủy đơn hàng thành công.',

            'data' =>
            new OrderResource($order),

            'errors' => null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Services\CustomerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly CustomerDashboardService
            $customerDashboardService
    ) {
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        $dashboard =
            $this->customerDashboardService
                ->getDashboard(
                    $request->user()
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Lấy thông tin tài khoản thành công.',

            'data' => [
                'customer' =>
                    new UserResource(
                        $dashboard[
                            'customer'
                        ]
                    ),

                'statistics' =>
                    $dashboard[
                        'statistics'
                    ],

                'recent_orders' =>
                    OrderResource::collection(
                        $dashboard[
                            'recent_orders'
                        ]
                    ),
            ],

            'errors' => null,
        ]);
    }
}
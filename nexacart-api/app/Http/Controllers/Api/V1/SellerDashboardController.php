<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SellerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    public function __construct(
        private readonly SellerDashboardService $dashboardService
    ) {
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Lấy dashboard người bán thành công.',
            'data' => $this->dashboardService
                ->getDashboard(
                    $request->user()
                ),
            'errors' => null,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy dashboard quản trị thành công.',
            'data' => $this->dashboardService
                ->getDashboard(),
            'errors' => null,
        ]);
    }
}
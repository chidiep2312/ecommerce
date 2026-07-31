<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SellerRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SellerRequest;
use App\Services\SellerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSellerRequestController extends Controller
{
    public function __construct(
        private readonly SellerRequestService $sellerRequestService
    ) {
    }

    /**
     * Danh sách yêu cầu đăng ký seller.
     */
    public function index(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    SellerRequestStatus::class
                ),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $sellerRequests = SellerRequest::query()
            ->with([
                'user:id,name,email,role,status',
                'reviewer:id,name,email',
            ])
            ->when(
                $validated['search'] ?? null,
                function ($query, $search) {
                    $query->whereHas(
                        'user',
                        function ($userQuery) use ($search) {
                            $userQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $validated['status'] ?? null,
                function ($query, $status) {
                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->latest()
            ->paginate(
                $validated['per_page'] ?? 10
            )
            ->withQueryString();

        return response()->json(
            $sellerRequests
        );
    }

    /**
     * Xem chi tiết một yêu cầu.
     */
    public function show(
        SellerRequest $sellerRequest
    ): JsonResponse {
        $sellerRequest->load([
            'user:id,name,email,role,status,created_at',
            'reviewer:id,name,email',
        ]);

        return response()->json([
            'data' => $sellerRequest,
        ]);
    }

    /**
     * Duyệt yêu cầu.
     */
    public function approve(
        Request $request,
        SellerRequest $sellerRequest
    ): JsonResponse {
        $approvedRequest =
            $this->sellerRequestService->approve(
                sellerRequest: $sellerRequest,
                reviewer: $request->user(),
            );

        return response()->json([
            'message' =>
                'Duyệt yêu cầu đăng ký người bán thành công.',
            'data' => $approvedRequest,
        ]);
    }

    /**
     * Từ chối yêu cầu.
     */
    public function reject(
        Request $request,
        SellerRequest $sellerRequest
    ): JsonResponse {
        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $rejectedRequest =
            $this->sellerRequestService->reject(
                sellerRequest: $sellerRequest,
                reviewer: $request->user(),
                rejectionReason:
                    $validated['rejection_reason'],
            );

        return response()->json([
            'message' =>
                'Đã từ chối yêu cầu đăng ký người bán.',
            'data' => $rejectedRequest,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Seller\UpdateSellerStatusRequest;
use App\Http\Resources\AdminSellerResource;
use App\Models\User;
use App\Services\AdminSellerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminSellerController extends Controller
{
    public function __construct(
        private readonly AdminSellerService $adminSellerService
    ) {
    }

    /**
     * Danh sách seller.
     */
    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                Rule::enum(UserStatus::class),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $sellers =
            $this->adminSellerService
                ->getSellers($validated);

        return AdminSellerResource::collection(
            $sellers
        );
    }

    /**
     * Chi tiết seller.
     */
    public function show(
        User $seller
    ): AdminSellerResource {
        $seller =
            $this->adminSellerService
                ->getSeller($seller);

        return new AdminSellerResource(
            $seller
        );
    }

    /**
     * Khóa hoặc mở khóa seller.
     */
    public function updateStatus(
        UpdateSellerStatusRequest $request,
        User $seller
    ): AdminSellerResource {
        $validated = $request->validated();

        $seller =
            $this->adminSellerService
                ->updateStatus(
                    seller: $seller,

                    status: UserStatus::from(
                        $validated['status']
                    ),
                );

        return (new AdminSellerResource(
            $seller
        ))->additional([
            'message' =>
                'Cập nhật trạng thái người bán thành công.',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreVoucherRequest;
use App\Http\Requests\Voucher\UpdateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $voucherService
    ) {
    }

    public function index(
    Request $request
): AnonymousResourceCollection {
    $validated = $request->validate([
        'search' => [
            'nullable',
            'string',
            'max:50',
        ],

        'type' => [
            'nullable',
            Rule::enum(
                VoucherType::class
            ),
        ],

        'status' => [
            'nullable',
            Rule::enum(
                VoucherStatus::class
            ),
        ],

        'effective_status' => [
            'nullable',
            Rule::in([
                'active',
                'inactive',
                'upcoming',
                'expired',
                'exhausted',
            ]),
        ],

        'per_page' => [
            'nullable',
            'integer',
            'min:5',
            'max:100',
        ],
    ]);

    return VoucherResource::collection(
        $this->voucherService->paginate(
            $validated
        )
    );
}

    public function store(
        StoreVoucherRequest $request
    ): JsonResponse {
        $voucher = $this->voucherService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo voucher thành công.',
            'data' => new VoucherResource($voucher),
            'errors' => null,
        ], 201);
    }

    public function show(
        Voucher $voucher
    ): VoucherResource {
        $voucher->loadCount('usages');

        return new VoucherResource($voucher);
    }

    public function update(
        UpdateVoucherRequest $request,
        Voucher $voucher
    ): JsonResponse {
        $voucher = $this->voucherService->update(
            $voucher,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật voucher thành công.',
            'data' => new VoucherResource($voucher),
            'errors' => null,
        ]);
    }

    public function destroy(
        Voucher $voucher
    ): JsonResponse {
        $this->voucherService->delete($voucher);

        return response()->json([
            'success' => true,
            'message' => 'Xóa voucher thành công.',
            'data' => null,
            'errors' => null,
        ]);
    }
}
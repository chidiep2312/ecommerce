<?php

namespace App\Services;

use App\Enums\SellerRequestStatus;
use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Models\SellerRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellerRequestService
{
    public function submit(
        User $user,
        ?string $reason = null
    ): SellerRequest {
       
        if ($user->isSeller()) {
            throw ValidationException::withMessages([
                'seller_request' =>
                    'Tài khoản của bạn đã là người bán.',
            ]);
        }

        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'seller_request' =>
                    'Tài khoản quản trị không thể đăng ký người bán.',
            ]);
        }

        
        $hasPendingRequest = $user
            ->sellerRequests()
            ->where(
                'status',
                SellerRequestStatus::Pending->value
            )
            ->exists();

        if ($hasPendingRequest) {
            throw ValidationException::withMessages([
                'seller_request' =>
                    'Bạn đã có yêu cầu đang chờ quản trị viên duyệt.',
            ]);
        }

        return $user
            ->sellerRequests()
            ->create([
                'status' =>
                    SellerRequestStatus::Pending,

                'reason' =>
                    $reason,
            ]);
    }

    public function approve(
        SellerRequest $sellerRequest,
        User $reviewer
    ): SellerRequest {
        return DB::transaction(
            function () use (
                $sellerRequest,
                $reviewer
            ) {
                
                $lockedRequest =
                    SellerRequest::query()
                        ->whereKey(
                            $sellerRequest->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

               
                if (
                    $lockedRequest->status !==
                    SellerRequestStatus::Pending
                ) {
                    throw ValidationException::withMessages([
                        'seller_request' =>
                            'Yêu cầu này đã được xử lý.',
                    ]);
                }

               
                $user = User::query()
                    ->whereKey(
                        $lockedRequest->user_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($user->isSeller()) {
                    throw ValidationException::withMessages([
                        'seller_request' =>
                            'Người dùng này đã là người bán.',
                    ]);
                }

             
                $lockedRequest->update([
                    'status' =>
                        SellerRequestStatus::Approved,

                    'reviewed_by' =>
                        $reviewer->id,

                    'reviewed_at' =>
                        now(),

                    'rejection_reason' =>
                        null,
                ]);

             
                $user->update([
                    'role' =>
                        UserRole::Seller,
                ]);

              
                $user->shop()
                    ->firstOrCreate(
                        [],
                        [
                            'name' =>
                                $user->name,

                            'slug' =>
                                $this
                                    ->generateShopSlug(
                                        $user->name
                                    ),

                            'status' =>
                                ShopStatus::Active,
                        ]
                    );

                return $lockedRequest
                    ->fresh([
                        'user',
                        'reviewer',
                    ]);
            },
            3
        );
    }

    public function reject(
        SellerRequest $sellerRequest,
        User $reviewer,
        string $rejectionReason
    ): SellerRequest {
        return DB::transaction(
            function () use (
                $sellerRequest,
                $reviewer,
                $rejectionReason
            ) {
                $lockedRequest =
                    SellerRequest::query()
                        ->whereKey(
                            $sellerRequest->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedRequest->status !==
                    SellerRequestStatus::Pending
                ) {
                    throw ValidationException::withMessages([
                        'seller_request' =>
                            'Yêu cầu này đã được xử lý.',
                    ]);
                }

                $lockedRequest->update([
                    'status' =>
                        SellerRequestStatus::Rejected,

                    'rejection_reason' =>
                        $rejectionReason,

                    'reviewed_by' =>
                        $reviewer->id,

                    'reviewed_at' =>
                        now(),
                ]);

                return $lockedRequest
                    ->fresh([
                        'user',
                        'reviewer',
                    ]);
            },
            3
        );
    }

    private function generateShopSlug(
        string $name
    ): string {
        $baseSlug = Str::slug(
            $name
        );

        /*
         * Trường hợp tên toàn ký tự đặc biệt
         * khiến Str::slug() trả chuỗi rỗng.
         */
        if ($baseSlug === '') {
            $baseSlug = 'shop';
        }

        $slug = $baseSlug;

        $counter = 1;

        while (
            Shop::query()
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $baseSlug .
                '-' .
                $counter;

            $counter++;
        }

        return $slug;
    }
}
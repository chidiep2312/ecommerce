<?php

namespace App\Services;

use App\Enums\SellerRequestStatus;
use App\Enums\UserRole;
use App\Models\SellerRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerRequestService
{
    /**
     * Customer gửi yêu cầu trở thành seller.
     */
    public function submit(
        User $user,
        ?string $reason = null
    ): SellerRequest {
        /*
         * Người đã là seller thì không được gửi yêu cầu mới.
         */
        if ($user->isSeller()) {
            throw ValidationException::withMessages([
                'seller_request' =>
                    'Tài khoản của bạn đã là người bán.',
            ]);
        }

        /*
         * Admin cũng không cần đăng ký seller.
         */
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'seller_request' =>
                    'Tài khoản quản trị không thể đăng ký người bán.',
            ]);
        }

        /*
         * Không cho phép có hai yêu cầu pending cùng lúc.
         */
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

        /*
         * Tạo request thông qua relationship.
         * Laravel tự gán user_id bằng id của $user.
         */
        return $user
            ->sellerRequests()
            ->create([
                'status' =>
                    SellerRequestStatus::Pending,
                'reason' => $reason,
            ]);
    }

    /**
     * Admin duyệt yêu cầu.
     */
    public function approve(
        SellerRequest $sellerRequest,
        User $reviewer
    ): SellerRequest {
        return DB::transaction(
            function () use (
                $sellerRequest,
                $reviewer
            ) {
                /*
                 * Khóa dòng dữ liệu để tránh hai admin
                 * cùng xử lý một request tại một thời điểm.
                 */
                $lockedRequest = SellerRequest::query()
                    ->with('user')
                    ->lockForUpdate()
                    ->findOrFail($sellerRequest->id);

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
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedRequest->user_id
                    );

                /*
                 * Nếu tài khoản đã là seller thì không xử lý lại.
                 */
                if ($user->isSeller()) {
                    throw ValidationException::withMessages([
                        'seller_request' =>
                            'Người dùng này đã là người bán.',
                    ]);
                }

                /*
                 * Cập nhật trạng thái request.
                 */
                $lockedRequest->update([
                    'status' =>
                        SellerRequestStatus::Approved,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

                /*
                 * Nâng role customer thành seller.
                 */
                $user->update([
                    'role' => UserRole::Seller,
                ]);

                return $lockedRequest->fresh([
                    'user',
                    'reviewer',
                ]);
            }
        );
    }

    /**
     * Admin từ chối yêu cầu.
     */
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
                $lockedRequest = SellerRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($sellerRequest->id);

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
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);

                /*
                 * Không thay đổi role.
                 * User vẫn giữ role Customer.
                 */
                return $lockedRequest->fresh([
                    'user',
                    'reviewer',
                ]);
            }
        );
    }
}
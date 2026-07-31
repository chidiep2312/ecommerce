<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\CannotModifyOwnAccountException;
use App\Exceptions\InvalidUserRoleTransitionException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminUserService
{
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        $query = User::query()
            ->withCount([
                'products',
                'customerOrders',
                'sellerOrders',
                'reviews',
            ]);

        $this->applyFilters(
            $query,
            $filters
        );

        $this->applySort(
            $query,
            $filters['sort'] ?? null
        );

        return $query
            ->paginate(
                $filters['per_page'] ?? 15
            )
            ->withQueryString();
    }

    public function loadDetail(
        User $user
    ): User {
        return $user->loadCount([
            'products',
            'customerOrders',
            'sellerOrders',
            'reviews',
        ]);
    }

    public function updateStatus(
        User $admin,
        User $targetUser,
        UserStatus $newStatus
    ): User {
        if ($admin->id === $targetUser->id) {
            throw new CannotModifyOwnAccountException(
                'Bạn không thể thay đổi trạng thái tài khoản của chính mình.'
            );
        }

        return DB::transaction(function () use (
            $targetUser,
            $newStatus
        ) {
            $lockedUser = User::query()
                ->whereKey($targetUser->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->update([
                'status' => $newStatus,
            ]);

            if ($newStatus === UserStatus::Locked) {
                $lockedUser->tokens()->delete();
            }

            return $this->loadDetail(
                $lockedUser->refresh()
            );
        });
    }

    public function updateRole(
        User $admin,
        User $targetUser,
        UserRole $newRole
    ): User {
        if ($admin->id === $targetUser->id) {
            throw new CannotModifyOwnAccountException(
                'Bạn không thể thay đổi vai trò của chính mình.'
            );
        }

        if ($targetUser->role === UserRole::Admin) {
            throw new InvalidUserRoleTransitionException(
                'Không thể thay đổi vai trò của tài khoản Admin bằng chức năng này.'
            );
        }

        if (
            $targetUser->role === UserRole::Seller
            && $newRole === UserRole::Customer
        ) {
            $this->ensureSellerCanBecomeCustomer(
                $targetUser
            );
        }

        return DB::transaction(function () use (
            $targetUser,
            $newRole
        ) {
            $lockedUser = User::query()
                ->whereKey($targetUser->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->update([
                'role' => $newRole,
            ]);

            /*
             * Quyền tài khoản đã thay đổi nên thu hồi
             * toàn bộ token và yêu cầu đăng nhập lại.
             */
            $lockedUser->tokens()->delete();

            return $this->loadDetail(
                $lockedUser->refresh()
            );
        });
    }

    private function ensureSellerCanBecomeCustomer(
        User $seller
    ): void {
        $hasActiveProducts = $seller
            ->products()
            ->whereIn('status', [
                ProductStatus::Draft->value,
                ProductStatus::Active->value,
            ])
            ->exists();

        if ($hasActiveProducts) {
            throw new InvalidUserRoleTransitionException(
                'Không thể chuyển Seller thành Customer khi vẫn còn sản phẩm đang hoạt động hoặc ở trạng thái nháp.'
            );
        }

        $hasOpenOrders = $seller
            ->sellerOrders()
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Confirmed->value,
                OrderStatus::Shipping->value,
            ])
            ->exists();

        if ($hasOpenOrders) {
            throw new InvalidUserRoleTransitionException(
                'Không thể chuyển Seller thành Customer khi vẫn còn đơn hàng chưa hoàn tất.'
            );
        }
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        $query
            ->when(
                $filters['keyword'] ?? null,
                function (
                    Builder $query,
                    string $keyword
                ) {
                    $keyword = trim($keyword);

                    $query->where(function (
                        Builder $query
                    ) use ($keyword) {
                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $keyword . '%'
                            )
                            ->orWhere(
                                'email',
                                'like',
                                '%' . $keyword . '%'
                            );
                    });
                }
            )
            ->when(
                $filters['role'] ?? null,
                fn (
                    Builder $query,
                    string $role
                ) => $query->where(
                    'role',
                    $role
                )
            )
            ->when(
                $filters['status'] ?? null,
                fn (
                    Builder $query,
                    string $status
                ) => $query->where(
                    'status',
                    $status
                )
            );
    }

    private function applySort(
        Builder $query,
        ?string $sort
    ): void {
        match ($sort) {
            'oldest'
                => $query->oldest(),

            'name_asc'
                => $query->orderBy('name'),

            'name_desc'
                => $query->orderByDesc('name'),

            default
                => $query->latest(),
        };
    }
}
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AdminSellerService
{
    
    public function getSellers(
        array $filters
    ): LengthAwarePaginator {
        return User::query()
            ->where(
                'role',
                UserRole::Seller
            )
            ->with([
                'latestSellerRequest' => function ($query) {
                    $query->select([
                        'seller_requests.id',
                        'seller_requests.user_id',
                        'seller_requests.status',
                        'seller_requests.reviewed_at',
                    ]);
                },
            ])
            ->withCount([
                'products',
                'sellerOrders',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search
                ) {
                    $query->where(
                        function (
                            Builder $subQuery
                        ) use ($search) {
                            $subQuery
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
                $filters['status'] ?? null,
                function (
                    Builder $query,
                    string $status
                ) {
                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->latest('id')
            ->paginate(
                $filters['per_page'] ?? 10
            )
            ->withQueryString();
    }

   
    public function getSeller(
        User $seller
    ): User {
        $this->ensureSeller($seller);

        return $seller->load([
            'latestSellerRequest.reviewer:id,name,email',
        ])->loadCount([
            'products',
            'sellerOrders',
        ]);
    }

  
    public function updateStatus(
        User $seller,
        UserStatus $status
    ): User {
        $this->ensureSeller($seller);

        $seller->update([
            'status' => $status,
        ]);

        return $seller->fresh()->load([
            'latestSellerRequest:seller_requests.id,seller_requests.user_id,seller_requests.status,seller_requests.reviewed_at',
        ])->loadCount([
            'products',
            'sellerOrders',
        ]);
    }


    private function ensureSeller(
        User $user
    ): void {
        if ($user->role !== UserRole::Seller) {
            throw ValidationException::withMessages([
                'seller' =>
                'Người dùng này không phải là người bán.',
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SellerVoucherUsageService
{
    public function paginate(
        User $seller,
        array $filters = []
    ): LengthAwarePaginator {
        return $this->buildQuery(
            $seller,
            $filters
        )
            ->with([
                'voucher:id,code,type,value,min_order_amount,max_discount_amount',
                'user:id,name,email',

                'order:id,user_id,seller_id,order_code,status,subtotal,discount_amount,total_amount,created_at',
            ])
            ->latest(
                'voucher_usages.id'
            )
            ->paginate(
                min(
                    max(
                        (int) (
                            $filters['per_page']
                            ?? 15
                        ),
                        5
                    ),
                    100
                )
            )
            ->withQueryString();
    }

    public function getSummary(
        User $seller,
        array $filters = []
    ): array {
        $query = $this->buildQuery(
            $seller,
            $filters
        );

        return [
            'usage_count' =>
                (clone $query)->count(),

            'total_discount' =>
                (float) (
                    (clone $query)->sum(
                        'voucher_usages.discount_amount'
                    )
                ),

            'unique_vouchers' =>
                (clone $query)
                    ->distinct()
                    ->count(
                        'voucher_usages.voucher_id'
                    ),

            'unique_customers' =>
                (clone $query)
                    ->distinct()
                    ->count(
                        'voucher_usages.user_id'
                    ),
        ];
    }

    private function buildQuery(
        User $seller,
        array $filters
    ): Builder {
        return VoucherUsage::query()
            ->whereHas(
                'order',
                fn (Builder $query) =>
                    $query->where(
                        'seller_id',
                        $seller->id
                    )
            )
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search
                ) {
                    $search = trim(
                        $search
                    );

                    $query->where(
                        function (
                            Builder $subQuery
                        ) use ($search) {
                            $subQuery
                                ->whereHas(
                                    'voucher',
                                    fn (
                                        Builder $voucherQuery
                                    ) =>
                                        $voucherQuery
                                            ->where(
                                                'code',
                                                'like',
                                                '%' .
                                                strtoupper(
                                                    $search
                                                ) .
                                                '%'
                                            )
                                )
                                ->orWhereHas(
                                    'order',
                                    fn (
                                        Builder $orderQuery
                                    ) =>
                                        $orderQuery
                                            ->where(
                                                'order_code',
                                                'like',
                                                "%{$search}%"
                                            )
                                )
                                ->orWhereHas(
                                    'user',
                                    function (
                                        Builder $userQuery
                                    ) use (
                                        $search
                                    ) {
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
                    );
                }
            )
            ->when(
                $filters['order_status']
                    ?? null,
                fn (
                    Builder $query,
                    string $status
                ) =>
                    $query->whereHas(
                        'order',
                        fn (
                            Builder $orderQuery
                        ) =>
                            $orderQuery->where(
                                'status',
                                $status
                            )
                    )
            )
            ->when(
                $filters['date_from']
                    ?? null,
                fn (
                    Builder $query,
                    string $date
                ) =>
                    $query->whereDate(
                        'voucher_usages.created_at',
                        '>=',
                        $date
                    )
            )
            ->when(
                $filters['date_to']
                    ?? null,
                fn (
                    Builder $query,
                    string $date
                ) =>
                    $query->whereDate(
                        'voucher_usages.created_at',
                        '<=',
                        $date
                    )
            );
    }
}
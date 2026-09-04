<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Exceptions\InvalidVoucherException;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VoucherService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Voucher::query()
            ->withCount('usages')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(
                    'code',
                    'like',
                    '%' . strtoupper(trim($search)) . '%'
                );
            })
            ->when(
                $filters['type'] ?? null,
                fn ($query, $type) => $query->where('type', $type)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['effective_status'] ?? null,
                function ($query, $effectiveStatus) {
                    $this->applyEffectiveStatusFilter(
                        $query,
                        $effectiveStatus
                    );
                }
            )
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function create(array $data): Voucher
    {
        $data['code'] = strtoupper(trim($data['code']));

        $data['status'] ??= VoucherStatus::Active;
        $data['min_order_amount'] ??= 0;

        return Voucher::query()->create($data);
    }

    public function update(Voucher $voucher, array $data): Voucher
    {
        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $voucher->update($data);

        return $voucher->refresh();
    }

    public function delete(Voucher $voucher): void
    {
        if ($voucher->usages()->exists()) {
            $voucher->update([
                'status' => VoucherStatus::Inactive,
            ]);

            return;
        }

        $voucher->delete();
    }

    public function validateForUser(
        User $user,
        string $code,
        string $subtotal,
        bool $lockForUpdate = false
    ): array {
        $query = Voucher::query()
            ->where('code', strtoupper(trim($code)));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if ($voucher === null) {
            throw new InvalidVoucherException(
                'Mã voucher không tồn tại.'
            );
        }

        $this->ensureVoucherIsUsable(
            $voucher,
            $user,
            $subtotal
        );

        $discount = $this->calculateDiscount(
            $voucher,
            $subtotal
        );

        return [
            'voucher' => $voucher,
            'discount_amount' => $discount,
            'total_after_discount' => max(
                0,
                $subtotal - $discount
            ),
        ];
    }

    public function ensureVoucherIsUsable(
        Voucher $voucher,
        User $user,
        string $subtotal
    ): void {
        if ($voucher->status !== VoucherStatus::Active) {
            throw new InvalidVoucherException(
                'Voucher hiện không hoạt động.'
            );
        }

        if (! $voucher->isWithinValidPeriod()) {
            throw new InvalidVoucherException(
                'Voucher chưa bắt đầu hoặc đã hết hạn.'
            );
        }

        if (! $voucher->hasAvailableUsage()) {
            throw new InvalidVoucherException(
                'Voucher đã hết lượt sử dụng.'
            );
        }

        if (
            Money::compare(
                $subtotal,
                (string) $voucher->min_order_amount
            ) < 0
        ) {
            throw new InvalidVoucherException(
                'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng voucher.'
            );
        }

        if ($voucher->usage_limit_per_user !== null) {
            $usedByUser = $voucher->usages()
                ->where('user_id', $user->id)
                ->count();

            if ($usedByUser >= (int) $voucher->usage_limit_per_user) {
                throw new InvalidVoucherException(
                    'Bạn đã sử dụng hết lượt của voucher này.'
                );
            }
        }
    }

    public function calculateDiscount(
        Voucher $voucher,
        string $subtotal
    ): string {
        $scale = 2;

        if ($voucher->type === VoucherType::Fixed) {
            $voucherValue = (string) $voucher->value;

            return bccomp(
                $voucherValue,
                $subtotal,
                $scale
            ) <= 0
                ? $voucherValue
                : $subtotal;
        }

        /*
         * subtotal × phần trăm / 100
         *
         * Dùng scale trung gian cao hơn để hạn chế mất
         * phần thập phân trước khi chuẩn hóa về 2 số lẻ.
         */
        $discount = bcdiv(
            bcmul(
                $subtotal,
                (string) $voucher->value,
                4
            ),
            '100',
            $scale
        );

        if ($voucher->max_discount_amount !== null) {
            $maxDiscount = (string) $voucher->max_discount_amount;

            if (
                bccomp(
                    $discount,
                    $maxDiscount,
                    $scale
                ) > 0
            ) {
                $discount = $maxDiscount;
            }
        }

        /*
         * Tiền giảm không được lớn hơn subtotal.
         */
        if (
            bccomp(
                $discount,
                $subtotal,
                $scale
            ) > 0
        ) {
            return $subtotal;
        }

        return $discount;
    }

    private function applyEffectiveStatusFilter(
        $query,
        string $status
    ): void {
        match ($status) {
            'active' => $query
                ->where(
                    'status',
                    VoucherStatus::Active->value
                )
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->where(function ($query) {
                    $query
                        ->whereNull('usage_limit')
                        ->orWhereColumn(
                            'used_count',
                            '<',
                            'usage_limit'
                        );
                }),

            'upcoming' => $query
                ->where(
                    'status',
                    VoucherStatus::Active->value
                )
                ->where('starts_at', '>', now()),

            'expired' => $query
                ->where('expires_at', '<=', now()),

            'exhausted' => $query
                ->whereNotNull('usage_limit')
                ->whereColumn(
                    'used_count',
                    '>=',
                    'usage_limit'
                ),

            'inactive' => $query
                ->where(
                    'status',
                    VoucherStatus::Inactive->value
                ),

            default => null,
        };
    }
}
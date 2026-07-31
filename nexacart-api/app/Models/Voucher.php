<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'usage_limit_per_user',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'type' => VoucherType::class,
        'status' => VoucherStatus::class,

        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',

        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'usage_limit_per_user' => 'integer',

        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            VoucherStatus::Active->value
        );
    }

    public function isWithinValidPeriod(): bool
    {
        return now()->between(
            $this->starts_at,
            $this->expires_at
        );
    }

    public function hasAvailableUsage(): bool
    {
        return $this->usage_limit === null
            || $this->used_count < $this->usage_limit;
    }
    public function orders()
{
    return $this->hasMany(Order::class);
}
}
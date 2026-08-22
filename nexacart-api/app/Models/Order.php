<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'seller_id',
        'voucher_id',
        'order_code',
        'idempotency_key',

        'subtotal',
        'discount_amount',
        'shipping_fee',
        'total',

        'status',
        'payment_method',

        'shipping_provider',
        'shipping_service_id',
        'shipping_service_type_id',
        'shipping_service_name',

        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_district_id',
        'shipping_ward_code',

        'pickup_address_id',
        'pickup_ghn_shop_id',
        'pickup_name',
        'pickup_phone',
        'pickup_address',
        'pickup_district_id',
        'pickup_ward_code',

        'package_weight',
        'package_length',
        'package_width',
        'package_height',

        'customer_note',

        'confirmed_at',
        'shipping_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
        'shipping_fee' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipping_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function customer()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function seller()
    {
        return $this->belongsTo(
            User::class,
            'seller_id'
        );
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(
            Shipment::class,
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
        );
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status',PaymentStatus::Paid)->exists();
    }
}

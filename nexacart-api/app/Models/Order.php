<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'voucher_id',
        'order_code',
        'idempotency_key',
        'seller_id',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'total',
        'status',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
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
}

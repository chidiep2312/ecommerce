<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'provider',
        'client_order_code',
        'provider_order_code',
        'shop_id',
        'service_id',
        'status',
        'cod_amount',
        'provider_total_fee',
        'expected_delivery_at',
        'failure_message',
        'create_response',
        'synced_at',
    ];

    protected $casts = [
        'shop_id' =>
            'integer',

        'service_id' =>
            'integer',

        'cod_amount' =>
            'decimal:2',

        'provider_total_fee' =>
            'decimal:2',

        'expected_delivery_at' =>
            'datetime',

        'synced_at' =>
            'datetime',

        'create_response' =>
            'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
        );
    }
}
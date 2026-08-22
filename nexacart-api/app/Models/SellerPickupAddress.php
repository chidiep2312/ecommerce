<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerPickupAddress extends Model
{
    protected $fillable = [
        'contact_name',
        'phone',

        'province',
        'province_id',

        'district',
        'district_id',

        'ward',
        'ward_code',

        'address_line',

        'is_default',

        'ghn_shop_id',
        'ghn_synced_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',

        'ghn_synced_at' =>
            'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'seller_id',
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ShopStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'phone',
        'logo',
        'banner',
        'status',
    ];

    protected $casts = [
        'status' => ShopStatus::class,
    ];
    public function products(): HasMany
    {
        return $this->hasMany(
            Product::class,
            'seller_id',
            'user_id'
        );
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}

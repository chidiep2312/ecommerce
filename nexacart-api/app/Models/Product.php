<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'name',
        'weight',
        'length',
        'width',
        'height',
        'slug',
        'sku',
        'price',
        'sale_price',
        'stock',
        'description',
        'status',
        'is_suspended',
        'suspended_reason',
        'suspended_by',
        'suspended_at',
        'images',
        'main_image'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'status' => ProductStatus::class,
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(
            User::class,
            'seller_id'
        );
    }

    public function category()
    {
        return $this->belongsTo(
            Category::class
        );
    }

    public function brand()
    {
        return $this->belongsTo(
            Brand::class
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            ProductStatus::Active->value
        );
    }

    public function scopeOwnedBy(
        Builder $query,
        int $sellerId
    ): Builder {
        return $query->where(
            'seller_id',
            $sellerId
        );
    }

    public function getEffectivePriceAttribute(): string
    {
        return $this->sale_price
            ?? $this->price;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_main', true);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'suspended_by'
        );
    }

    public function wishlistedByUsers()
    {
        return $this->belongsToMany(
            User::class,
            'wishlists',
            'product_id',
            'user_id'
        )->withTimestamps();
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class
        );
    }
}

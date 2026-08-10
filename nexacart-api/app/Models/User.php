<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'status' => UserStatus::class
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSeller(): bool
    {
        return $this->role === UserRole::Seller;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function products()
    {
        return $this->hasMany(
            Product::class,
            'seller_id'
        );
    }
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function voucherUsages()
    {
        return $this->hasMany(
            VoucherUsage::class
        );
    }
    // public function orders()
    // {
    //     return $this->hasMany(Order::class);
    // }
    public function customerOrders()
    {
        return $this->hasMany(
            Order::class,
            'user_id'
        );
    }

    public function sellerOrders()
    {
        return $this->hasMany(
            Order::class,
            'seller_id'
        );
    }


    public function sellerRequests(): HasMany
    {
        return $this->hasMany(
            SellerRequest::class,
        );
    }

    public function latestSellerRequest(): HasOne
    {
        return $this->hasOne(
            SellerRequest::class,
        )->latestOfMany();
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(
            Address::class
        );
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(
            Address::class
        )->where(
            'is_default',
            true
        );
    }
    public function wishlistProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'wishlists',
            'user_id',
            'product_id'
        )->withTimestamps();
    }
    public function shop(): HasOne
    {
        return $this->hasOne(
            Shop::class
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class
        );
    }
}

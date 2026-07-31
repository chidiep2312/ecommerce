<?php

namespace App\Providers;

use App\Models\Review;
use App\Policies\ReviewPolicy;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\CartItem;
use App\Policies\CartItemPolicy;
use App\Models\Order;
use App\Policies\OrderPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        CartItem::class => CartItemPolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}

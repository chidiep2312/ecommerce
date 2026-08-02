<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(
            100_000,
            5_000_000
        );

        return [
            // Khách hàng đặt đơn
            'user_id' => User::factory()->state([
                'role' => UserRole::Customer,
            ]),

            // Người bán nhận đơn
            'seller_id' => User::factory()->state([
                'role' => UserRole::Seller,
            ]),

            'voucher_id' => null,

            'order_code' => 'ORD-' . Str::upper(
                Str::random(12)
            ),

            'subtotal' => $subtotal,

            'discount_amount' => 0,

            // Vì chưa giảm giá nên total bằng subtotal
            'total' => $subtotal,

            'status' => OrderStatus::Pending->value,

            'shipping_name' => fake()->name(),

            'shipping_phone' => '09'
                . fake()->numerify('########'),

            'shipping_address' => fake()->address(),

            'customer_note' => null,

            'confirmed_at' => null,
            'shipping_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
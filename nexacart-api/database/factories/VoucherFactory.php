<?php

namespace Database\Factories;

use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([
            VoucherType::Percent,
            VoucherType::Fixed,
        ]);

        return [
            'code' => strtoupper(
                Str::random(8)
            ),

            'type' => $type,

            'value' => $type === VoucherType::Percent
                ? fake()->numberBetween(5, 30)
                : fake()->numberBetween(
                    50000,
                    500000
                ),

            'min_order_amount'
                => fake()->numberBetween(
                    0,
                    1000000
                ),

            'max_discount_amount'
                => $type === VoucherType::Percent
                    ? fake()->numberBetween(
                        100000,
                        500000
                    )
                    : null,

            'usage_limit'
                => fake()->numberBetween(
                    10,
                    1000
                ),

            'used_count' => 0,

            'usage_limit_per_user' => 1,

            'starts_at' => now()
                ->subDay(),

            'expires_at' => now()
                ->addMonth(),

            'status' => VoucherStatus::Active,
        ];
    }
}
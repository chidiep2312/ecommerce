<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(4, true);

        $price = fake()->numberBetween(
            100000,
            30000000
        );

        $hasSalePrice = fake()->boolean(40);

        return [
            'seller_id' =>  User::factory()->seller(),

            'category_id' => Category::factory(),

            'brand_id' => fake()->boolean(80)
                ? Brand::factory()
                : null,

            'name' => ucfirst($name),

            'slug' => Str::slug($name)
                . '-'
                . fake()->unique()->numberBetween(
                    1,
                    999999
                ),

            'sku' => strtoupper(
                fake()->unique()->bothify(
                    'PRD-####-????'
                )
            ),

            'price' => $price,

            'sale_price' => $hasSalePrice
                ? fake()->numberBetween(
                    (int) ($price * 0.7),
                    $price
                )
                : null,

            'stock' => fake()->numberBetween(
                0,
                100
            ),

            'description' => fake()->paragraph(),

            'status' => ProductStatus::Active,
        ];
    }
}
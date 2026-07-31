<?php

namespace Database\Factories;

use App\Enums\BrandStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name)
                . '-' . fake()->unique()->numberBetween(
                    1,
                    999999
                ),
            'status' => BrandStatus::Active,
        ];
    }
}
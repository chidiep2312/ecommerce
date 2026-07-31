<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name)
                . '-' . fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'status' => CategoryStatus::Active,
        ];
    }
}
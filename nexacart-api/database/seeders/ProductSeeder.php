<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()
            ->where('role', UserRole::Seller->value)
            ->firstOrFail();

        $categories = Category::query()->get();
        $brands = Brand::query()->get();

        Product::factory()
            ->count(30)
            ->make()
            ->each(function (
                Product $product
            ) use (
                $seller,
                $categories,
                $brands
            ) {
                $product->seller_id = $seller->id;

                $product->category_id = $categories
                    ->random()
                    ->id;

                $product->brand_id = $brands
                    ->random()
                    ->id;

                $product->save();
            });
    }
}
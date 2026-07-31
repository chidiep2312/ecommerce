<?php

namespace Database\Seeders;

use App\Enums\BrandStatus;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Apple',
            'Samsung',
            'Xiaomi',
            'Dell',
            'HP',
            'Lenovo',
            'Asus',
            'Nike',
            'Adidas',
        ];

        foreach ($brands as $name) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'status' => BrandStatus::Active,
                ]
            );
        }
    }
}
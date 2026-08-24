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
            'Asus',
            'Lenovo',
            'Logitech',
            'Sony',
        ];

        foreach ($brands as $name) {
            Brand::updateOrCreate(
                [
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,

                    'status' =>
                        BrandStatus::Active->value,
                ]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Điện thoại',
            'Laptop',
            'Máy tính bảng',
            'Thời trang nam',
            'Thời trang nữ',
            'Gia dụng',
        ];

        foreach ($categories as $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => null,
                    'status' => CategoryStatus::Active,
                ]
            );
        }
    }
}
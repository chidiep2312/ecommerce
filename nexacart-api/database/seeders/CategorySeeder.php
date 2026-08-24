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
            [
                'name' => 'Điện thoại',
                'description' => 'Điện thoại thông minh và điện thoại di động.',
            ],
            [
                'name' => 'Laptop',
                'description' => 'Laptop phục vụ học tập, làm việc và giải trí.',
            ],
            [
                'name' => 'Máy tính bảng',
                'description' => 'Các sản phẩm máy tính bảng.',
            ],
            [
                'name' => 'Tai nghe',
                'description' => 'Tai nghe có dây và không dây.',
            ],
            [
                'name' => 'Bàn phím',
                'description' => 'Bàn phím văn phòng và gaming.',
            ],
            [
                'name' => 'Chuột',
                'description' => 'Chuột máy tính có dây và không dây.',
            ],
            [
                'name' => 'Phụ kiện',
                'description' => 'Phụ kiện công nghệ.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'slug' => Str::slug(
                        $category['name']
                    ),
                ],
                [
                    'name' => $category['name'],
                    'description' =>
                        $category['description'],

                    'status' =>
                        CategoryStatus::Active->value,
                ]
            );
        }
    }
}
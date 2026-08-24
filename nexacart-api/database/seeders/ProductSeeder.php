<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()
            ->where('role', 'seller')
            ->first();

        if ($seller === null) {
            $this->command?->warn(
                'Không có seller. Bỏ qua ProductSeeder.'
            );

            return;
        }

        $products = [
            [
                'name' => 'iPhone 15 128GB',
                'sku' => 'IP15-128',
                'category' => 'dien-thoai',
                'brand' => 'apple',

                'price' => 19990000,
                'sale_price' => 18490000,

                'stock' => 20,

                'description' =>
                'iPhone 15 128GB chính hãng.',
                'weight' => 500,
                'length' => 20,
                'width' => 12,
                'height' => 8,
            ],

            [
                'name' => 'Samsung Galaxy S24 256GB',
                'sku' => 'SS-S24-256',
                'category' => 'dien-thoai',
                'brand' => 'samsung',

                'price' => 22990000,
                'sale_price' => 20990000,

                'stock' => 15,

                'description' =>
                'Samsung Galaxy S24 256GB.',
                'weight' => 500,
                'length' => 20,
                'width' => 12,
                'height' => 8,
            ],

            [
                'name' => 'Dell Inspiron 15',
                'sku' => 'DELL-INS15',
                'category' => 'laptop',
                'brand' => 'dell',

                'price' => 18990000,
                'sale_price' => 17490000,

                'stock' => 10,

                'description' =>
                'Dell Inspiron 15 phục vụ học tập và làm việc.',
                'weight' => 500,
                'length' => 20,
                'width' => 12,
                'height' => 8,
            ],

            [
                'name' => 'Logitech MX Master 3S',
                'sku' => 'LOGI-MX3S',
                'category' => 'chuot',
                'brand' => 'logitech',

                'price' => 2490000,
                'sale_price' => 2190000,

                'stock' => 30,

                'description' =>
                'Chuột không dây Logitech MX Master 3S.',
                'weight' => 500,
                'length' => 20,
                'width' => 12,
                'height' => 8,
            ],

            [
                'name' => 'Sony WH-1000XM5',
                'sku' => 'SONY-XM5',
                'category' => 'tai-nghe',
                'brand' => 'sony',

                'price' => 8490000,
                'sale_price' => 7990000,

                'stock' => 12,

                'description' =>
                'Tai nghe chống ồn Sony WH-1000XM5.',
                'weight' => 500,
                'length' => 20,
                'width' => 12,
                'height' => 8,
            ],
        ];

        foreach ($products as $item) {
            $category = Category::query()
                ->where(
                    'slug',
                    $item['category']
                )
                ->firstOrFail();

            $brand = Brand::query()
                ->where(
                    'slug',
                    $item['brand']
                )
                ->firstOrFail();

            Product::updateOrCreate(
                [
                    'sku' => $item['sku'],
                ],
                [
                    'seller_id' =>
                    $seller->id,

                    'category_id' =>
                    $category->id,

                    'brand_id' =>
                    $brand->id,

                    'name' =>
                    $item['name'],

                    'slug' =>
                    Str::slug(
                        $item['name']
                    ),

                    'price' =>
                    $item['price'],

                    'sale_price' =>
                    $item['sale_price'],

                    'stock' =>
                    $item['stock'],

                    'description' =>
                    $item['description'],

                    'status' =>
                    ProductStatus::Active->value,
                ]
            );
        }
    }
}

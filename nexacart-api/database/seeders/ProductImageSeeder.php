<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $productImages = [
            'IP15-128' => [
                'iphone.jpg',
               
            ],

            'SS-S24-256' => [
                'samsung.jpg',
            ],

            'DELL-INS15' => [
                'dell.jpg',
            ],

            'LOGI-MX3S' => [
                'logitech.jpg',
            ],

            'SONY-XM5' => [
                'sony.jpg',
            ],
        ];

        foreach (
            $productImages as $sku => $fileNames
        ) {
            $product = Product::query()
                ->where('sku', $sku)
                ->first();

            if ($product === null) {
                continue;
            }

            foreach (
                $fileNames as $index => $fileName
            ) {
                $sourcePath =
                    database_path(
                        "seeders/assets/products/{$fileName}"
                    );

                if (!file_exists($sourcePath)) {
                    $this->command?->warn(
                        "Không tìm thấy {$fileName}"
                    );

                    continue;
                }

                $extension =
                    pathinfo(
                        $fileName,
                        PATHINFO_EXTENSION
                    );

                $destination =
                    "products/{$product->id}/"
                    . "{$sku}-{$index}.{$extension}";

                Storage::disk('public')->put(
                    $destination,
                    file_get_contents(
                        $sourcePath
                    )
                );

                $product
                    ->images()
                    ->updateOrCreate(
                        [
                            'path' =>
                                $destination,
                        ],
                        [
                            'is_main' =>
                                $index === 0,

                            'sort_order' =>
                                $index + 1,
                        ]
                    );
            }
        }
    }
}
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageService
{
    /**
     * @param array<int, UploadedFile> $images
     */
    public function uploadMany(
        Product $product,
        array $images
    ): Collection {
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $product,
                $images,
                &$storedPaths
            ) {
                $hasMainImage = $product
                    ->images()
                    ->where('is_main', true)
                    ->exists();

                $currentMaxOrder = (int) $product
                    ->images()
                    ->max('sort_order');

                $createdImages = collect();

                foreach ($images as $index => $image) {
                    $path = $image->store(
                        "products/{$product->id}",
                        'public'
                    );

                    $storedPaths[] = $path;

                    $productImage = $product
                        ->images()
                        ->create([
                            'path' => $path,

                            'is_main' => ! $hasMainImage
                                && $index === 0,

                            'sort_order'
                                => $currentMaxOrder
                                    + $index
                                    + 1,
                        ]);

                    $createdImages->push(
                        $productImage
                    );
                }

                return $createdImages;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')
                ->delete($storedPaths);

            throw $exception;
        }
    }

   public function setMain(
    Product $product,
    ProductImage $image
): ProductImage {
    if (
        $image->product_id !==
        $product->id
    ) {
        abort(
            422,
            'Ảnh không thuộc sản phẩm này.'
        );
    }

    return DB::transaction(function () use (
        $product,
        $image
    ) {
        $product
            ->images()
            ->where('is_main', true)
            ->update([
                'is_main' => false,
            ]);

        $image->update([
            'is_main' => true,
        ]);

        return $image->refresh();
    });
}
public function delete(
    Product $product,
    ProductImage $image
): void {
    if (
        $image->product_id !==
        $product->id
    ) {
        abort(
            422,
            'Ảnh không thuộc sản phẩm này.'
        );
    }

    DB::transaction(function () use (
        $product,
        $image
    ) {
        $wasMainImage =
            (bool) $image->is_main;

        $path = $image->path;

        $image->delete();

        if ($wasMainImage) {
            $newMainImage = $product
                ->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            $newMainImage?->update([
                'is_main' => true,
            ]);
        }

        Storage::disk('public')
            ->delete($path);
    });
}
}
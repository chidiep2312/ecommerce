<?php

namespace App\Services;

use App\Enums\BrandStatus;
use App\Exceptions\BrandHasProductsException;
use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BrandService
{
    public function getPublicBrands(): LengthAwarePaginator
    {
        return Brand::query()
            ->active()
            ->orderBy('name')
            ->paginate(15);
    }

    public function getAdminBrands(): LengthAwarePaginator
    {
        return Brand::query()
            ->withCount('products')
            ->latest()
            ->paginate(15);
    }

    public function create(array $data): Brand
    {
        $name = trim($data['name']);

        return Brand::query()->create([
            'name' => $name,
            'slug' => $this->generateUniqueSlug($name),
            'status' => $data['status']
                ?? BrandStatus::Active,
        ]);
    }

    public function update(
        Brand $brand,
        array $data
    ): Brand {
        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);

            if ($name !== $brand->name) {
                $data['name'] = $name;

                $data['slug'] = $this->generateUniqueSlug(
                    $name,
                    $brand->id
                );
            }
        }

        $brand->update($data);

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw new BrandHasProductsException(
                'Không thể xóa thương hiệu đang có sản phẩm.'
            );
        }

        $brand->delete();
    }

    private function generateUniqueSlug(
        string $name,
        ?int $ignoredBrandId = null
    ): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (
            Brand::query()
                ->withTrashed()
                ->when(
                    $ignoredBrandId !== null,
                    fn ($query) => $query->where(
                        'id',
                        '!=',
                        $ignoredBrandId
                    )
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
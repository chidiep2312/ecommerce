<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use RuntimeException;

class CategoryService
{
    public function getPublicCategories(): LengthAwarePaginator
    {
        return Category::query()
            ->active()
            ->latest()
            ->paginate(15);
    }

    public function getAdminCategories(): LengthAwarePaginator
    {
        return Category::query()
            ->latest()
            ->paginate(15);
    }

    public function create(array $data): Category
    {
        $data['name'] = trim($data['name']);

        $data['slug'] = $this->generateUniqueSlug(
            $data['name']
        );

        $data['status'] ??= CategoryStatus::Active;

        return Category::query()->create($data);
    }

    public function update(
        Category $category,
        array $data
    ): Category {
        if (
            array_key_exists('name', $data) &&
            $data['name'] !== $category->name
        ) {
            $data['name'] = trim($data['name']);

            $data['slug'] = $this->generateUniqueSlug(
                $data['name'],
                $category->id
            );
        }

        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new RuntimeException(
                'Không thể xóa danh mục đang chứa sản phẩm.'
            );
        }

        $category->delete();
    }

    private function generateUniqueSlug(
        string $name,
        ?int $ignoredCategoryId = null
    ): string {
        $baseSlug = Str::slug($name);

        $slug = $baseSlug;
        $number = 1;

        while (
            Category::query()
                ->when(
                    $ignoredCategoryId !== null,
                    fn ($query) => $query->whereKeyNot(
                        $ignoredCategoryId
                    )
                )
                ->where('slug', $slug)
                ->withTrashed()
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $number;
            $number++;
        }

        return $slug;
    }
}
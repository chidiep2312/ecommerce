<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface 
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator;

    public function paginateBySeller(
        int $sellerId,
        array $filters
    ): LengthAwarePaginator;

    public function create(
        array $data
    ): Product;

    public function update(
        Product $product,
        array $data
    ): Product;

    public function delete(
        Product $product
    ): void;
    public function getProductBySlug(string $slug): Product;
}
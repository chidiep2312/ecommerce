<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
           private readonly ProductImageService $productImageService,
    ) {}

    public function getAdminProducts(
        array $filters
    ): LengthAwarePaginator {
        return Product::query()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name,email',
                'mainImage:id,product_id,path,is_main',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search
                ) {
                    $query->where(
                        function (
                            Builder $subQuery
                        ) use ($search) {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $filters['seller_id'] ?? null,
                fn(
                    Builder $query,
                    int|string $sellerId
                ) => $query->where(
                    'seller_id',
                    $sellerId
                )
            )
            ->when(
                $filters['brand_id'] ?? null,
                fn(
                    Builder $query,
                    int|string $brandId
                ) => $query->where(
                    'brand_id',
                    $brandId
                )
            )
            ->when(
                $filters['category_id'] ?? null,
                fn(
                    Builder $query,
                    int|string $categoryId
                ) => $query->where(
                    'category_id',
                    $categoryId
                )
            )
            ->when(
                $filters['is_suspended'] ?? null,
                function (
                    Builder $query,
                    string $status
                ) {
                    if ($status == 1) {
                        $query->where(
                            'is_suspended',
                            true
                        );

                        return;
                    }

                    $query
                        ->where('status', $status)
                        ->where(
                            'is_suspended',
                            false
                        );
                }
            )
            ->when(
                $filters['stock_status'] ?? null,
                function (
                    Builder $query,
                    string $stockStatus
                ) {
                    match ($stockStatus) {
                        'in_stock' =>
                        $query->where(
                            'stock',
                            '>',
                            5
                        ),

                        'low_stock' =>
                        $query
                            ->where(
                                'stock',
                                '>',
                                0
                            )
                            ->where(
                                'stock',
                                '<=',
                                5
                            ),

                        'out_of_stock' =>
                        $query->where(
                            'stock',
                            '<=',
                            0
                        ),

                        default => null,
                    };
                }
            )
            ->latest('id')
            ->paginate(
                $filters['per_page'] ?? 15
            )
            ->withQueryString();
    }
    public function getAdminProduct(
        Product $product
    ): Product {
        return $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'seller:id,name,email,status',
            'images:id,product_id,path,is_main,sort_order',
            'suspendedBy:id,name,email',
        ]);
    }
    public function restore(
        Product $product
    ): Product {
        return DB::transaction(
            function () use ($product) {
                $product->update([
                    'is_suspended' => false,
                    'suspended_reason' => null,
                    'suspended_by' => null,
                    'suspended_at' => null,
                ]);

                return $product
                    ->refresh()
                    ->load([
                        'category:id,name,slug',
                        'brand:id,name,slug',
                        'seller:id,name,email',
                    ]);
            }
        );
    }

     public function suspend(
    Product $product,
    User $admin,
   string $data,
): Product {
    return DB::transaction(
        function () use (
            $product,
            $admin,
            $data,
        ) {
            $product->update([
                'is_suspended' => true,
                'suspended_reason' => $data,
                'suspended_by' => $admin->id,
                'suspended_at' => now(),
            ]);

            return $product
                ->refresh()
                ->load([
                    'category:id,name,slug',
                    'brand:id,name,slug',
                    'seller:id,name,email',
                    'images:id,product_id,path,is_main,sort_order',
                    'suspendedBy:id,name,email',
                ]);
        }
    );
}
    public function getPublicProducts(
        array $filters
    ): LengthAwarePaginator {
        return $this->productRepository
            ->paginatePublic($filters);
    }

    public function getSellerProducts(
        User $seller,
        array $filters
    ): LengthAwarePaginator {
        return $this->productRepository
            ->paginateBySeller(
                $seller->id,
                $filters
            );
    }

    public function create(
    User $seller,
    array $data
): Product {
    return DB::transaction(function () use (
        $seller,
        $data
    ) {
        $images = $data['images'] ?? [];

        unset($data['images']);

        $data['seller_id'] = $seller->id;

        $data['name'] = trim(
            $data['name']
        );

        $data['slug'] =
            $this->generateUniqueSlug(
                $data['name']
            );

        $data['sku'] = strtoupper(
            trim($data['sku'])
        );

        $data['status'] ??=
            ProductStatus::Draft;

        $product = $this
            ->productRepository
            ->create($data);

        if (!empty($images)) {
            $this->productImageService
                ->uploadMany(
                    $product,
                    $images
                );
        }

        return $product
            ->fresh()
            ->load([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name',

                'mainImage:id,product_id,path,is_main,sort_order',

                'images:id,product_id,path,is_main,sort_order',
            ]);
    });
}

   public function update(
    Product $product,
    array $data
): Product {
    return DB::transaction(function () use (
        $product,
        $data
    ) {
        $newImages =
            $data['images'] ?? [];

        $removedImageIds =
            $data['removed_image_ids'] ?? [];

        $mainExistingImageId =
            $data['main_existing_image_id']
            ?? null;

        $mainNewImageIndex =
            $data['main_new_image_index']
            ?? null;

        /*
         * Không truyền dữ liệu ảnh vào
         * repository cập nhật bảng products.
         */
        unset(
            $data['images'],
            $data['removed_image_ids'],
            $data['main_existing_image_id'],
            $data['main_new_image_index'],
        );

        if (
            array_key_exists(
                'name',
                $data
            )
        ) {
            $name = trim(
                $data['name']
            );

            $data['name'] = $name;

            if (
                $name !== $product->name
            ) {
                $data['slug'] =
                    $this->generateUniqueSlug(
                        $name,
                        $product->id
                    );
            }
        }

        if (
            array_key_exists(
                'sku',
                $data
            ) &&
            $data['sku'] !== null
        ) {
            $data['sku'] = strtoupper(
                trim($data['sku'])
            );
        }

        $product = $this
            ->productRepository
            ->update(
                $product,
                $data
            );

        /*
         * Xóa các ảnh cũ.
         */
        if (!empty($removedImageIds)) {
            $imagesToDelete = $product
                ->images()
                ->whereIn(
                    'id',
                    $removedImageIds
                )
                ->get();

            foreach (
                $imagesToDelete as $image
            ) {
                $this->productImageService
                    ->delete(
                        $product,
                        $image
                    );
            }
        }

        /*
         * Upload ảnh mới.
         */
        $createdImages = collect();

        if (!empty($newImages)) {
            $createdImages =
                $this->productImageService
                    ->uploadMany(
                        $product,
                        $newImages
                    );
        }

        /*
         * Chọn một ảnh cũ làm ảnh chính.
         */
        if (
            $mainExistingImageId !== null
        ) {
            $mainImage = $product
                ->images()
                ->whereKey(
                    $mainExistingImageId
                )
                ->first();

            if ($mainImage !== null) {
                $this->productImageService
                    ->setMain(
                        $product,
                        $mainImage
                    );
            }
        }

        /*
         * Chọn một ảnh mới làm ảnh chính.
         *
         * Chỉ thực hiện khi frontend không
         * gửi main_existing_image_id.
         */
        if (
            $mainExistingImageId === null &&
            $mainNewImageIndex !== null
        ) {
            $mainImage =
                $createdImages->get(
                    (int) $mainNewImageIndex
                );

            if ($mainImage !== null) {
                $this->productImageService
                    ->setMain(
                        $product,
                        $mainImage
                    );
            }
        }

        return $product
            ->fresh()
            ->load([
                'category:id,name,slug',
                'brand:id,name,slug',
                'seller:id,name',

                'mainImage:id,product_id,path,is_main,sort_order',

                'images:id,product_id,path,is_main,sort_order',
            ]);
    });
}

    public function delete(
        Product $product
    ): void {
        $this->productRepository->delete($product);
    }
    
    public function getProduct($data){
       return $this->productRepository
            ->getProductBySlug($data);
    }


    private function generateUniqueSlug(
        string $name,
        ?int $ignoredProductId = null
    ): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (
            Product::query()
            ->withTrashed()
            ->when(
                $ignoredProductId !== null,
                fn($query) => $query->where(
                    'id',
                    '!=',
                    $ignoredProductId
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

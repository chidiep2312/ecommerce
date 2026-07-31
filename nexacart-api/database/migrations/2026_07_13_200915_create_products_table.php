<?php

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->string('name');

            $table->string('slug')->unique();

            $table->string('sku', 100)->unique();

            $table->decimal('price', 15, 2);

            $table->decimal('sale_price', 15, 2)
                ->nullable();

            $table->unsignedInteger('stock')
                ->default(0);

            $table->text('description')
                ->nullable();

            $table->string('status')
                ->default(ProductStatus::Draft->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'status',
                'created_at',
            ]);

            $table->index([
                'category_id',
                'status',
            ]);

            $table->index([
                'brand_id',
                'status',
            ]);

            $table->index([
                'seller_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
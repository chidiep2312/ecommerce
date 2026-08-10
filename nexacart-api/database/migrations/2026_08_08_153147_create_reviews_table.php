<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'reviews',
            function (Blueprint $table) {
                $table->id();

                
                $table->foreignId(
                    'user_id'
                )
                    ->constrained(
                        'users'
                    )
                    ->restrictOnDelete();

        
                $table->foreignId(
                    'product_id'
                )
                    ->constrained(
                        'products'
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'order_item_id'
                )
                    ->unique()
                    ->constrained(
                        'order_items'
                    )
                    ->restrictOnDelete();

               
                $table->unsignedTinyInteger(
                    'rating'
                );

               
                $table->text(
                    'comment'
                )->nullable();

                $table->timestamps();

              
                $table->softDeletes();

               
                $table->index([
                    'product_id',
                    'created_at',
                ]);

              
                $table->index([
                    'user_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'reviews'
        );
    }
};
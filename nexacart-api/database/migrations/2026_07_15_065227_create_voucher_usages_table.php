<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'voucher_usages',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('voucher_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->foreignId('user_id')
                    ->constrained()
                    ->restrictOnDelete();

           
                $table->unsignedBigInteger('order_id')
                    ->nullable();

                $table->decimal(
                    'discount_amount',
                    15,
                    2
                );

                $table->timestamps();

                $table->index([
                    'voucher_id',
                    'user_id',
                ]);

                $table->index('order_id');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
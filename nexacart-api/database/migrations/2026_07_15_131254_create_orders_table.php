<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('voucher_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('order_code', 50)
                ->unique();

            $table->decimal('subtotal', 15, 2);

            $table->decimal(
                'discount_amount',
                15,
                2
            )->default(0);

            $table->decimal('total', 15, 2);

            $table->string('status')
                ->default(OrderStatus::Pending->value);

            $table->string('shipping_name');

            $table->string('shipping_phone', 20);

            $table->string(
                'shipping_address',
                500
            );

            $table->text('customer_note')
                ->nullable();

            $table->timestamp('confirmed_at')
                ->nullable();

            $table->timestamp('shipping_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'user_id',
                'status',
            ]);

            $table->index([
                'status',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
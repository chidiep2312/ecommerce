<?php

use App\Enums\VoucherStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)
                ->unique();

            $table->string('type');

            $table->decimal('value', 15, 2);

            $table->decimal(
                'min_order_amount',
                15,
                2
            )->default(0);

            $table->decimal(
                'max_discount_amount',
                15,
                2
            )->nullable();

            $table->unsignedInteger('usage_limit')
                ->nullable();

            $table->unsignedInteger('used_count')
                ->default(0);

            $table->unsignedInteger(
                'usage_limit_per_user'
            )->nullable();

            $table->dateTime('starts_at');

            $table->dateTime('expires_at');

            $table->string('status')
                ->default(VoucherStatus::Active->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'status',
                'starts_at',
                'expires_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create(
    'seller_pickup_addresses',
    function (Blueprint $table) {
        $table->id();

        $table
            ->foreignId('seller_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->string('contact_name');

        $table->string(
            'phone',
            20,
        );

        /*
         * Tên dùng hiển thị.
         */
        $table->string('province');
        $table->string('district');
        $table->string('ward');

        /*
         * Mapping GHN.
         */
        $table
            ->unsignedInteger(
                'province_id',
            );

        $table
            ->unsignedInteger(
                'district_id',
            );

        $table
            ->string(
                'ward_code',
                20,
            );

        $table->string(
            'address_line',
        );

        $table
            ->boolean('is_default')
            ->default(false);

        /*
         * Store tương ứng phía GHN.
         */
        $table
            ->unsignedBigInteger(
                'ghn_shop_id',
            )
            ->nullable();

        /*
         * Biết lần cuối sync GHN.
         */
        $table
            ->timestamp(
                'ghn_synced_at',
            )
            ->nullable();

        $table->timestamps();

        $table->index([
            'seller_id',
            'is_default',
        ]);
    },
);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_pickup_addresses');
    }
};

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
       Schema::table(
    'orders',
    function (Blueprint $table) {
        /*
         * Destination snapshot.
         */
        $table
            ->unsignedInteger(
                'shipping_district_id',
            )
            ->nullable();

        $table
            ->string(
                'shipping_ward_code',
                30,
            )
            ->nullable();

        /*
         * Pickup snapshot.
         */
        $table
            ->foreignId(
                'pickup_address_id',
            )
            ->nullable()
            ->constrained(
                'seller_pickup_addresses',
            )
            ->nullOnDelete();

        $table
            ->unsignedBigInteger(
                'pickup_ghn_shop_id',
            )
            ->nullable();

        $table
            ->string(
                'pickup_name',
                100,
            )
            ->nullable();

        $table
            ->string(
                'pickup_phone',
                20,
            )
            ->nullable();

        $table
            ->string(
                'pickup_address',
                500,
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'pickup_district_id',
            )
            ->nullable();

        $table
            ->string(
                'pickup_ward_code',
                30,
            )
            ->nullable();

        /*
         * Package snapshot.
         */
        $table
            ->unsignedInteger(
                'package_weight',
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'package_length',
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'package_width',
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'package_height',
            )
            ->nullable();
    },
);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};

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
        $table
            ->string(
                'shipping_provider',
                30,
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'shipping_service_id',
            )
            ->nullable();

        $table
            ->unsignedInteger(
                'shipping_service_type_id',
            )
            ->nullable();

        $table
            ->string(
                'shipping_service_name',
                100,
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

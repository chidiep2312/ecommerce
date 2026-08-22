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
    'products',
    function (Blueprint $table) {
        $table
            ->unsignedInteger('weight')
            ->default(500);

        $table
            ->unsignedInteger('length')
            ->default(10);

        $table
            ->unsignedInteger('width')
            ->default(10);

        $table
            ->unsignedInteger('height')
            ->default(10);
    },
);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};

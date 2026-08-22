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
    'addresses',
    function (Blueprint $table) {
        $table
            ->unsignedInteger('province_id')
            ->nullable();

        $table
            ->unsignedInteger('district_id')
            ->nullable();

        $table
            ->string('ward_code')
            ->nullable();
    }
);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            //
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ShopStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shops',
            function (Blueprint $table) {
                $table->id();


                $table->foreignId('user_id')
                    ->unique()
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string(
                    'name',
                    255
                );


                $table->string(
                    'slug',
                    255
                )->unique();


                $table->text(
                    'description'
                )->nullable();


                $table->string(
                    'phone',
                    20
                )->nullable();


                $table->string(
                    'logo'
                )->nullable();

                $table->string(
                    'banner'
                )->nullable();


                $table->string(
                    'status',
                    30
                )
                    ->default(
                        ShopStatus::Active->value
                    )
                    ->index();

                $table->timestamps();

                $table->softDeletes();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shops'
        );
    }
};

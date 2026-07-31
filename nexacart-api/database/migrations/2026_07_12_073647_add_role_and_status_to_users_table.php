<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserRole;
use App\Enums\UserStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')
                    ->default(UserRole::Customer->value)
                    ->after('password');

                $table->string('status')
                    ->default(UserStatus::Active->value)
                    ->after('role');

                $table->softDeletes();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn(['role', 'status']);
            });
        });
    }
};

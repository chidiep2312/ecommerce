<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_suspended')
                ->default(false)
                ->index()
                ->after('status');

            $table->text('suspended_reason')
                ->nullable()
                ->after('is_suspended');

            $table->foreignId('suspended_by')
                ->nullable()
                ->after('suspended_reason')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('suspended_at')
                ->nullable()
                ->after('suspended_by');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign([
                'suspended_by',
            ]);

            $table->dropColumn([
                'is_suspended',
                'suspended_reason',
                'suspended_by',
                'suspended_at',
            ]);
        });
    }
};
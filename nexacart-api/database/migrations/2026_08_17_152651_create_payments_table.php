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
       Schema::create('payments', function (Blueprint $table) {
    $table->id();

    $table
        ->foreignId('order_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('provider');

    $table->string('method');

    $table->unsignedBigInteger('amount');

    $table
        ->string('status')
        ->default('pending');

    $table
        ->uuid('request_id')
        ->unique();

    $table
        ->string('provider_order_id')
        ->unique();

    $table
        ->string('transaction_id')
        ->nullable()
        ->unique();

    $table
        ->string('provider_response_code')
        ->nullable();

    $table
        ->string('provider_message')
        ->nullable();

    $table
        ->timestamp('paid_at')
        ->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

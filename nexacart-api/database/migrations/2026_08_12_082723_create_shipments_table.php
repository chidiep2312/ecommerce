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
    'shipments',
    function (Blueprint $table) {
        $table->id();

        $table
            ->foreignId('order_id')
            ->constrained()
            ->cascadeOnDelete();

        /*
         * ghn
         */
        $table
            ->string(
                'provider',
                30,
            );

        /*
         * NexaCart gửi mã này cho GHN.
         *
         * Ví dụ:
         * NC202608120001
         */
        $table
            ->string(
                'client_order_code',
                50,
            )
            ->unique();

        /*
         * GHN trả về.
         *
         * Ví dụ:
         * FFFNL9HH
         */
        $table
            ->string(
                'provider_order_code',
                100,
            )
            ->nullable()
            ->unique();

        $table
            ->unsignedBigInteger(
                'shop_id',
            );

        $table
            ->unsignedInteger(
                'service_id',
            )
            ->nullable();

        /*
         * pending_creation
         * ready_to_pick
         * picking
         * picked
         * transporting
         * delivering
         * delivered
         * ...
         */
        $table
            ->string(
                'status',
                50,
            )
            ->default(
                'pending_creation',
            );

        $table
            ->decimal(
                'cod_amount',
                15,
                2,
            )
            ->default(0);

        /*
         * Phí thực GHN trả
         * khi Create Order.
         */
        $table
            ->decimal(
                'provider_total_fee',
                15,
                2,
            )
            ->nullable();

        $table
            ->timestamp(
                'expected_delivery_at',
            )
            ->nullable();

        $table
            ->string(
                'failure_message',
                1000,
            )
            ->nullable();

        /*
         * Debug / audit.
         */
        $table
            ->json(
                'create_response',
            )
            ->nullable();

        $table
            ->timestamp(
                'synced_at',
            )
            ->nullable();

        $table->timestamps();

        $table->index([
            'order_id',
            'provider',
        ]);

        $table->index('status');
    },
);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

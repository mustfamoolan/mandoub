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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->foreignId('merchant_address_id')->nullable()->constrained('merchant_addresses')->onDelete('set null');
            $table->string('merchant_address')->nullable();
            $table->double('merchant_latitude')->nullable();
            $table->double('merchant_longitude')->nullable();
            $table->string('customer_address');
            $table->double('customer_latitude')->nullable();
            $table->double('customer_longitude')->nullable();
            $table->string('customer_phone');
            $table->text('order_description')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('delivery_fee', 12, 2)->default(5000.00);
            $table->decimal('net_payout', 12, 2)->default(0.00);
            $table->string('status')->default('pending'); // pending, driver_assigned, in_delivery, delivered, returned, cancelled
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

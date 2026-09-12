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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable()->change();
            $table->string('sender_name')->nullable()->after('merchant_id');
            $table->string('sender_phone')->nullable()->after('sender_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable(false)->change();
            $table->dropColumn(['sender_name', 'sender_phone']);
        });
    }
};

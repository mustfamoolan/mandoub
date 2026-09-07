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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('delivery_fee', 10, 2)->default(5000.00);
            $table->text('about_us')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

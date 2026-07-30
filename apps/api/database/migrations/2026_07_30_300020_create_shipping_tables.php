<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->decimal('max_order_amount', 15, 2)->nullable();
            $table->string('region_code', 32)->nullable();
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });

        Schema::create('shipping_provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->json('credentials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_provider_accounts');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
    }
};

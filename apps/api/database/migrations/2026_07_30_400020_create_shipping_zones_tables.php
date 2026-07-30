<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipping_zone_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('level', 16);
            $table->string('geo_code', 32);
            $table->timestamps();
            $table->unique(['shipping_zone_id', 'level', 'geo_code']);
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->after('shipping_method_id')
                ->constrained('shipping_zones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_zone_id');
        });

        Schema::dropIfExists('shipping_zone_regions');
        Schema::dropIfExists('shipping_zones');
    }
};

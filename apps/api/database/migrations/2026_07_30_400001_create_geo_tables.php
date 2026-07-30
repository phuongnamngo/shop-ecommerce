<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('geo_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_province_id')->constrained('geo_provinces')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['geo_province_id', 'code']);
        });

        Schema::create('geo_wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_district_id')->constrained('geo_districts')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['geo_district_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_wards');
        Schema::dropIfExists('geo_districts');
        Schema::dropIfExists('geo_provinces');
    }
};

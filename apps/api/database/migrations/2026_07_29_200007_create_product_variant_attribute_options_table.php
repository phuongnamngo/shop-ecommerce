<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->restrictOnDelete();
            $table->foreignId('attribute_option_id')->constrained('attribute_options')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_variant_id', 'attribute_id'], 'pvao_variant_attribute_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_options');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('compare_at_price', 15, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'is_default']);
        });

        DB::statement('CREATE UNIQUE INDEX product_variants_sku_unique ON product_variants (sku) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_sale_items', function (Blueprint $table) {
            $table->unsignedInteger('qty_sold')->default(0)->after('qty_cap');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('flash_sale_item_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('flash_sale_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flash_sale_item_id');
        });

        Schema::table('flash_sale_items', function (Blueprint $table) {
            $table->dropColumn('qty_sold');
        });
    }
};

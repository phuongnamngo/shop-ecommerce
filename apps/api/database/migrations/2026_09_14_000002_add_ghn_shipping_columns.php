<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('ghn_service_id')->nullable()->after('shipping_method_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('weight_grams')->nullable()->after('compare_at_price');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('ghn_service_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('weight_grams');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
};

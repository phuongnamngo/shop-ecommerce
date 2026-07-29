<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX product_variants_one_default_per_product ON product_variants (product_id) WHERE is_default = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_variants_one_default_per_product');
    }
};

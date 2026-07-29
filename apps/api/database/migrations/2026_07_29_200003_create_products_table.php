<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

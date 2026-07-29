<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX categories_slug_unique ON categories (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

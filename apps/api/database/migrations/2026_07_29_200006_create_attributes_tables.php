<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX attributes_slug_unique ON attributes (slug) WHERE deleted_at IS NULL');

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->ulid('code')->unique();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');
    }
};

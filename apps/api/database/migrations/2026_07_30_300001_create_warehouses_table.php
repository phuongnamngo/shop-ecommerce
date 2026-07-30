<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->ulid('code')->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX warehouses_one_default ON warehouses (is_default) WHERE is_default = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_banners', function (Blueprint $table) {
            $table->string('image_url')->nullable()->change();
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        DB::statement('CREATE UNIQUE INDEX cms_pages_slug_unique ON cms_pages (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropUnique('cms_pages_slug_unique');
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('cms_banners', function (Blueprint $table) {
            $table->string('image_url')->nullable(false)->change();
        });
    }
};

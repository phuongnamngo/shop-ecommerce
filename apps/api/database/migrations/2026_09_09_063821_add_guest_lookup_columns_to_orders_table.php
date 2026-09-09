<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_lookup_token_hash')->nullable()->unique();
            $table->text('guest_lookup_token_cipher')->nullable();
            $table->timestamp('guest_lookup_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['guest_lookup_token_hash']);
            $table->dropColumn([
                'guest_lookup_token_hash',
                'guest_lookup_token_cipher',
                'guest_lookup_token_expires_at',
            ]);
        });
    }
};

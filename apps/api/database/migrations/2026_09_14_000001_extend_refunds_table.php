<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->text('reason')->nullable();
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('provider_refund_id')->nullable()->index();
            $table->uuid('idempotency_key')->unique();
            $table->json('payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_admin_id');
            $table->dropConstrainedForeignId('reviewed_by_admin_id');
            $table->dropColumn(['reviewed_at', 'provider_refund_id', 'idempotency_key', 'payload', 'reason']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->string('reason')->nullable();
        });
    }
};

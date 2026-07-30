<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->integer('points_balance')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->integer('delta');
            $table->string('reason')->nullable();
            $table->nullableMorphs('reference');
            $table->integer('balance_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_ledger_entries');
        Schema::dropIfExists('loyalty_accounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_ledger_entries', function (Blueprint $table) {
            $table->unique(
                ['reference_type', 'reference_id', 'reason'],
                'loyalty_ledger_entries_reference_reason_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_ledger_entries', function (Blueprint $table) {
            $table->dropUnique('loyalty_ledger_entries_reference_reason_unique');
        });
    }
};

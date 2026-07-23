<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contributors', function (Blueprint $table) {
            $table->foreignId('transaction_type_id')
                ->nullable()
                ->after('amount')
                ->constrained('transaction_types')
                ->nullOnDelete();
        });

        // Set default transaction_type_id = 1 (Iuran Warga) for existing contributors
        DB::table('contributors')->whereNull('transaction_type_id')->update(['transaction_type_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contributors', function (Blueprint $table) {
            $table->dropForeign(['transaction_type_id']);
            $table->dropColumn('transaction_type_id');
        });
    }
};

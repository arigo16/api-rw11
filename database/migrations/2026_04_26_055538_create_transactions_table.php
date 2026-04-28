<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('id_settlement', 50)->unique();
            $table->enum('mutation', ['IN', 'OUT']);
            $table->foreignId('type_id')->constrained('transaction_types')->onDelete('restrict');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('mutation');
            $table->index('transaction_date');
            $table->index('id_settlement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

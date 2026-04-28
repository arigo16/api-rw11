<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contributor_id')->constrained('contributors')->onDelete('cascade');
            $table->unsignedSmallInteger('year_bill');
            $table->unsignedTinyInteger('month_bill');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contributor_id', 'year_bill', 'month_bill']);
            $table->index('status');
            $table->index(['year_bill', 'month_bill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributor_bills');
    }
};

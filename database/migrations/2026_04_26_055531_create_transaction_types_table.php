<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default data
        DB::table('transaction_types')->insert([
            ['name' => 'Iuran Warga', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Donasi/Sumbangan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bunga Bank', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bayar Listrik', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bayar Air', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Biaya Kebersihan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Biaya Keamanan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Biaya Kegiatan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pembelian Barang', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pemeliharaan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Santunan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_types');
    }
};

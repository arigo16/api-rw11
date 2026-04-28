<?php

namespace Database\Seeders;

use App\Models\Contributor;
use Illuminate\Database\Seeder;

class ContributorSeeder extends Seeder
{
    public function run(): void
    {
        $contributors = [];

        // RT 1 - RT 12
        for ($i = 1; $i <= 12; $i++) {
            $contributors[] = [
                'code' => 'RT-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'name' => 'RT ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'type' => 'RT',
                'amount' => 150000,
                'is_active' => true,
                'start_month' => 1,
                'start_year' => 2026,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($contributors as $contributor) {
            Contributor::updateOrCreate(
                ['code' => $contributor['code']],
                $contributor
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Arigo',
            'email' => 'arigo1602@gmail.com',
            'password' => bcrypt('12345'),
        ]);

        $this->call([
            PengurusSeeder::class,
            RwInfoSeeder::class,
        ]);
    }
}

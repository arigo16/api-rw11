<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'arigo1602@gmail.com'],
            [
                'name' => 'Arigo',
                'email' => 'arigo1602@gmail.com',
                'password' => Hash::make('qwerty123'),
            ]
        );
    }
}

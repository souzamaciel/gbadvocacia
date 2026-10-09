<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gbadvocacia.com.br'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );
    }
}

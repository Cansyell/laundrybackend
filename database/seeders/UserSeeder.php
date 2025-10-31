<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        // Tambahkan akun admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Admin123'),
            'role' => 'owner', // Enkripsi password
        ]);

        // Jika ingin menambah user lain, bisa seperti ini:
        // User::create([
        //     'name' => 'User Biasa',
        //     'email' => 'user@gmail.com',
        //     'password' => Hash::make('User123'),
        // ]);
    }
}

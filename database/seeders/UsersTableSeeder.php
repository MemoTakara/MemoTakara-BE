<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'username' => 'admin', 
                'password' => Hash::make('1234'), // Mã hóa mật khẩu bằng Hash
                'email' => 'admin@gmail.com', 
                'role' => 'admin',
            ],
            [
                'username' => 'user1', 
                'password' => Hash::make('1234'), // Mã hóa mật khẩu bằng Hash
                'email' => 'user1@example.com', 
                'role' => 'user', // Ví dụ vai trò là user
            ],
            [
                'username' => 'user2', 
                'password' => Hash::make('5678'), 
                'email' => 'user2@example.com', 
                'role' => 'admin', // Ví dụ vai trò là admin
            ],
            [
                'username' => 'user3', 
                'password' => Hash::make('abcd'), 
                'email' => 'user3@example.com', 
                'role' => 'guest', // Ví dụ vai trò là guest
            ],
        ]);
    }
}

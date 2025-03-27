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
                'username' => 'memo-admin',
                'password' => Hash::make('admin1234'), // Mã hóa mật khẩu bằng Hash
                'email' => 'admin@gmail.com',
                'role' => 'admin',
            ],
        ]);
    }
}

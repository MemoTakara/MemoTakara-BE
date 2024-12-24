<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); // Tạo cột user_id (serial, PK)
            $table->string('username'); // Tên người dùng (character)
            $table->string('password'); // Mật khẩu (integer)
            $table->string('email')->unique(); // Email của người dùng (character)
            $table->string('role')->default('guest'); // Vai trò của người dùng (character, mặc định là 'guest')
            $table->timestamps(); // created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

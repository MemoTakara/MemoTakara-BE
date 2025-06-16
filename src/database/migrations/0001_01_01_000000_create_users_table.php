<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động tăng, đặt tên là user_id, tự động tạo khóa chính
            $table->string('name')->nullable(); // Cột lưu tên người dùng, có thể là null
            $table->string('username'); // Cột lưu tên đăng nhập
            $table->string('email')->unique(); // Cột lưu email, phải là duy nhất
            $table->string('google_id')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable(); // Cột để lưu thời gian xác minh email, có thể là null
            $table->string('password')->nullable(); // nullable cho Google login
            $table->string('role')->default('user'); // Cột lưu vai trò người dùng, mặc định là 'guest'
            $table->boolean('is_active')->default(true); // Mặc định tài khoản là hoạt động
            $table->string('timezone')->default('Asia/Ho_Chi_Minh'); // Thêm mới
            $table->json('study_preferences')->nullable(); // Thêm mới - Cài đặt học tập cá nhân
            $table->integer('daily_study_goal')->default(20); // Thêm mới - Mục tiêu học hàng ngày
            $table->rememberToken(); // Cột cho tính năng "remember me" khi người dùng đăng nhập
            $table->timestamps(); // Tạo cột created_at và updated_at

            // Indexes
            $table->index(['is_active', 'created_at']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Cột email là khóa chính
            $table->string('token'); // Cột lưu token để reset mật khẩu
            $table->timestamp('created_at')->nullable(); // Cột lưu thời gian tạo token, có thể là null
            $table->timestamp('expires_at')->nullable(); // Thêm mới - Thời gian hết hạn
            $table->boolean('used')->default(false); // Thêm mới - Đánh dấu đã sử dụng

            // Indexes
            $table->index(['email', 'expires_at']);
            $table->index('expires_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Cột id là khóa chính
            $table->foreignId('user_id')->nullable()->index(); // Cột user_id là khóa ngoại, có thể là null và sẽ được index
            $table->string('ip_address', 45)->nullable(); // Cột lưu địa chỉ IP, có thể là null
            $table->text('user_agent')->nullable(); // Cột lưu thông tin về trình duyệt, có thể là null
            $table->longText('payload'); // Cột lưu dữ liệu phiên
            $table->integer('last_activity')->index(); // Cột lưu thời gian hoạt động cuối cùng và được index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

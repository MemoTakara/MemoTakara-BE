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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Người nhận thông báo
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('cascade'); // Người gửi thông báo (có thể là admin hoặc user khác)
            $table->string('type'); // Loại thông báo (rating, duplicate, new_collection, system_update,...)
            $table->text('message'); // Nội dung thông báo
            $table->boolean('is_read')->default(false); // Trạng thái đã đọc hay chưa
            $table->json('data')->nullable(); // Lưu thông tin bổ sung (VD: ID collection, điểm rating,...)
            $table->timestamps(); // created_at và updated_at

            // Indexes để tối ưu hiệu suất
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

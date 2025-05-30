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
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id(); // ID của flashcard (serial, PK)
            $table->text('front'); // Mặt trước của thẻ (text)
            $table->text('back'); // Mặt sau của thẻ (text)
            $table->text('pronunciation')->nullable();
            $table->text('kanji')->nullable(); // Kanji, Hán Việt
            $table->string('language_front', 10)->default('vi'); // Thêm mới - Ngôn ngữ mặt trước
            $table->string('language_back', 10)->default('en'); // Thêm mới - Ngôn ngữ mặt sau
            $table->string('image')->nullable(); // Hình ảnh miêu tả từ vựng (character, cho phép null)
            $table->json('extra_data')->nullable(); // Thêm mới - Dữ liệu bổ sung
            $table->timestamps(); // created_at và updated_at

            // Khóa ngoại liên kết với collections
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcards'); // Xóa bảng
    }
};

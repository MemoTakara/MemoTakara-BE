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
            $table->string('audio_file')->nullable(); // File phát âm của từ vựng (character, cho phép null)
            $table->string('image')->nullable(); // Hình ảnh miêu tả từ vựng (character, cho phép null)
            $table->enum('status', ['new', 'learning', 're-learning', 'young', 'mastered'])->default('new'); // Trạng thái của từ vựng
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

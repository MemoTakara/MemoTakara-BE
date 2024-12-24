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
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id('flashcard_id'); // ID của flashcard (serial, PK)
            $table->text('front'); // Mặt trước của thẻ (text)
            $table->text('back'); // Mặt sau của thẻ (text)
            $table->string('audio_file')->nullable(); // File phát âm của từ vựng (character, cho phép null)
            $table->string('vocabulary_meaning')->nullable(); // Nghĩa của từ vựng (character, cho phép null)
            $table->string('image')->nullable(); // Hình ảnh miêu tả từ vựng (character, cho phép null)
            $table->string('status'); // Trạng thái của từ vựng (character)
            $table->unsignedBigInteger('collection_id'); // Khóa ngoại liên kết đến bảng collections
            $table->timestamps(); // created_at và updated_at

            // Thiết lập khóa ngoại
            $table->foreign('collection_id')->references('collection_id')->on('collections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            $table->dropForeign(['collection_id']); // Xóa ràng buộc khóa ngoại
        });
        Schema::dropIfExists('flashcards'); // Xóa bảng
    }
};

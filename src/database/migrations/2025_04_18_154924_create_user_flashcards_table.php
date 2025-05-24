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
        Schema::create('user_flashcards', function (Blueprint $table) {
            $table->id();

            $table->enum('quality', ['again', 'hard', 'good', 'easy']); // chất lượng đánh giá sau mỗi lần ôn
            $table->integer('interval')->nullable(); // khoảng cách lần sau
            $table->float('ease_factor')->nullable(); // EF sau mỗi lần học
            $table->integer('repetition')->nullable(); // lần lặp hiện tại

            $table->timestamp('reviewed_at'); // thời điểm ôn
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('flashcard_id')->constrained('flashcards')->onDelete('cascade');

            $table->unique(['user_id', 'flashcard_id']); // mỗi user chỉ có một bản ghi cho mỗi flashcard
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_flashcards');
    }
};

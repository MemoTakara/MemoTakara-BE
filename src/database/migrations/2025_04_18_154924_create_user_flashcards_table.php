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
            $table->foreignId('status_id')->constrained('flashcard_statuses'); // trạng thái học
            $table->timestamp('next_review_at')->nullable(); // thời điểm ôn tiếp theo (cho spaced repetition)
            $table->integer('interval')->default(1); // số ngày cách nhau giữa các lần ôn (thuật toán SR)
            $table->integer('repetition')->default(0); // số lần ôn lại
            $table->float('ease_factor')->default(2.5); // dùng trong thuật toán SM2
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

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
        Schema::create('flashcard_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['new', 'learning', 're-learning', 'young', 'mastered'])->default('new');

            // SM-2
            $table->integer('interval')->default(1); // Khoảng thời gian giữa các lần ôn
            $table->float('ease_factor')->default(2.5); // Độ dễ, dùng cho thuật toán SM-2
            $table->integer('repetitions')->default(0); // Số lần đã ôn
            
            $table->timestamp('last_reviewed_at')->nullable(); // Lần ôn gần nhất
            $table->timestamp('next_review_at')->nullable();   // Lần ôn tiếp theo
            $table->timestamps();


            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('flashcard_id')->constrained('flashcards')->onDelete('cascade');

            $table->unique(['user_id', 'flashcard_id']); // Mỗi người dùng chỉ có 1 trạng thái cho mỗi flashcard
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_statuses');
    }
};

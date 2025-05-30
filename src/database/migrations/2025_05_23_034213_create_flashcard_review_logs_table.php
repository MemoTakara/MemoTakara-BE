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
        Schema::create('flashcard_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('flashcard_id')->constrained()->onDelete('cascade');

            // Study context - Thêm mới
            $table->enum('study_type', ['flashcard', 'game_match', 'typing', 'handwriting', 'test']);
            $table->enum('study_mode', ['front_to_back', 'back_to_front']);
            $table->integer('response_time_ms')->nullable(); // Thêm mới - Thời gian phản hồi

            // SM-2 data
            $table->tinyInteger('quality'); // 0–5 theo SM-2
            $table->unsignedSmallInteger('prev_interval');
            $table->unsignedSmallInteger('new_interval');
            $table->float('prev_ease_factor', 3, 2);
            $table->float('new_ease_factor', 3, 2);
            $table->unsignedSmallInteger('prev_repetitions');
            $table->unsignedSmallInteger('new_repetitions');

            $table->timestamp('reviewed_at');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'reviewed_at']);
            $table->index(['flashcard_id', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_review_logs');
    }
};

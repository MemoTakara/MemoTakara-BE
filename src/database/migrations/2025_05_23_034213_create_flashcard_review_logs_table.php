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

            $table->tinyInteger('quality'); // 0–5 theo SM-2
            $table->unsignedSmallInteger('prev_interval');
            $table->unsignedSmallInteger('new_interval');
            $table->float('prev_ease_factor', 3, 2);
            $table->float('new_ease_factor', 3, 2);
            $table->unsignedSmallInteger('prev_repetitions');
            $table->unsignedSmallInteger('new_repetitions');

            $table->timestamp('reviewed_at');
            $table->timestamps();
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

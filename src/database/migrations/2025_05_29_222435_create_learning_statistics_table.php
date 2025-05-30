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
        Schema::create('learning_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('study_date');
            $table->integer('new_cards')->default(0);
            $table->integer('learning_cards')->default(0);
            $table->integer('review_cards')->default(0);
            $table->integer('mastered_cards')->default(0);
            $table->integer('total_study_time')->default(0); // phút
            $table->integer('total_sessions')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'study_date']);
            $table->index(['user_id', 'study_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_statistics');
    }
};

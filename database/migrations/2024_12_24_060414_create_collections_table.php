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
        Schema::create('collections', function (Blueprint $table) {
            $table->id('collection_id'); // ID của collection (serial, PK)
            $table->string('name'); // Tên collection (character)
            $table->text('description')->nullable(); // Mô tả collection (text, cho phép null)
            $table->boolean('privacy')->default(true); // Collection công khai (true) hoặc riêng tư (false)
            $table->text('tag')->nullable(); // Tag của collection (text, cho phép null)
            $table->decimal('star_count')->default(0); // Số sao collection được đánh giá (int, mặc định 0)
            $table->unsignedBigInteger('user_id'); // Khóa ngoại liên kết đến bảng users
            $table->timestamps(); // created_at và updated_at

            // Thiết lập khóa ngoại
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Xóa ràng buộc khóa ngoại
        });
        Schema::dropIfExists('collections'); // Xóa bảng
    }
};

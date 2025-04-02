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
        Schema::create('collections', function (Blueprint $table) {
            $table->id(); // ID của collection (serial, PK)
            $table->string('collection_name'); // Tên collection (character)
            $table->text('description')->nullable(); // Mô tả collection (text, cho phép null)
            $table->tinyInteger('privacy')->default(0); // Công khai (1) hoặc riêng tư (0), thay vì boolean
            $table->timestamps(); // created_at và updated_at

            // Thiết lập khóa ngoại với bảng users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Mỗi tag là duy nhất
            $table->timestamps();
        });

        Schema::create('collection_tag', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');

            $table->primary(['collection_id', 'tag_id']); // Đảm bảo không có tag trùng trong cùng 1 collection
        });

        Schema::create('collection_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Người đánh giá
            $table->decimal('rating', 2, 1); // Điểm đánh giá (0.0 - 5.0)
            $table->text('review')->nullable(); // Đánh giá bằng chữ (tùy chọn)
            $table->timestamps();

            $table->unique(['collection_id', 'user_id']); // Một người chỉ đánh giá một lần
        });

        Schema::create('collection_duplicates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_collection_id')->constrained('collections')->onDelete('cascade'); // Collection gốc
            $table->foreignId('duplicated_collection_id')->constrained('collections')->onDelete('cascade'); // Collection mới được duplicate
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Ai duplicate?
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_duplicates');
        Schema::dropIfExists('collection_ratings');
        Schema::dropIfExists('collection_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('collections'); // Xóa bảng
    }
};

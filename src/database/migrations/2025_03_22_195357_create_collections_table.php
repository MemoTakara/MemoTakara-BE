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
            $table->id(); // ID của collection (serial, PK)
            $table->string('collection_name'); // Tên collection (character)
            $table->text('description')->nullable(); // Mô tả collection (text, cho phép null)
            $table->tinyInteger('privacy')->default(0); // Công khai (1) hoặc riêng tư (0), thay vì boolean
            $table->text('tag')->nullable(); // Tag của collection (text, cho phép null)
            $table->decimal('star_count', 3, 1)->default(0); // Số sao (VD: 4.5, tối đa 5.0)
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

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('collections'); // Xóa bảng
    }
};

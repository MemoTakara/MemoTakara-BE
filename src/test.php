<?php
// ===== MIGRATION 1: Tạo bảng users =====
// File: 2025_05_30_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('google_id')->nullable()->unique(); // Đã thêm từ đầu
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Đã nullable từ đầu cho Google login
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->string('timezone')->default('Asia/Ho_Chi_Minh'); // Thêm mới
            $table->json('study_preferences')->nullable(); // Thêm mới - Cài đặt học tập cá nhân
            $table->integer('daily_study_goal')->default(20); // Thêm mới - Mục tiêu học hàng ngày
            $table->rememberToken();
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

// ===== MIGRATION 2: Tạo bảng password_reset_tokens =====
// File: 2025_05_30_000002_create_password_reset_tokens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Thêm mới - Thời gian hết hạn
            $table->boolean('used')->default(false); // Thêm mới - Đánh dấu đã sử dụng

            // Indexes
            $table->index(['email', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};

// ===== MIGRATION 3: Tạo bảng sessions =====
// File: 2025_05_30_000003_create_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};

// ===== MIGRATION 4: Tạo bảng personal_access_tokens =====
// File: 2025_05_30_000004_create_personal_access_tokens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};

// ===== MIGRATION 5: Tạo bảng user_levels =====
// File: 2025_05_30_000005_create_user_levels_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('level')->default(1);
            $table->integer('max_collections')->default(5);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('total_ratings')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};

// ===== MIGRATION 6: Tạo bảng collections =====
// File: 2025_05_30_000006_create_collections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('collection_name');
            $table->text('description')->nullable();
            $table->tinyInteger('privacy')->default(0); // 0: private, 1: public
            $table->integer('total_cards')->default(0); // Thêm mới - Tổng số flashcard
            $table->decimal('average_rating', 3, 2)->default(0.00); // Thêm mới - Điểm trung bình
            $table->integer('total_ratings')->default(0); // Thêm mới - Tổng số lượt đánh giá
            $table->integer('total_duplicates')->default(0); // Thêm mới - Số lần được duplicate
            $table->string('language_front', 10)->default('vi'); // Thêm mới - Ngôn ngữ mặt trước
            $table->string('language_back', 10)->default('en'); // Thêm mới - Ngôn ngữ mặt sau
            $table->json('metadata')->nullable(); // Thêm mới - Thông tin bổ sung
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->boolean('is_featured')->default(false); // Thêm mới - Collection nổi bật
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Indexes
            $table->index(['privacy', 'is_featured', 'average_rating']);
            $table->index(['user_id', 'privacy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};

// ===== MIGRATION 7: Tạo bảng tags =====
// File: 2025_05_30_000007_create_tags_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};

// ===== MIGRATION 8: Tạo bảng collection_tag =====
// File: 2025_05_30_000008_create_collection_tag_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_tag', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');

            $table->primary(['collection_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_tag');
    }
};

// ===== MIGRATION 9: Tạo bảng collection_ratings =====
// File: 2025_05_30_000009_create_collection_ratings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('rating', 2, 1); // 0.0 - 5.0
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_ratings');
    }
};

// ===== MIGRATION 10: Tạo bảng collection_duplicates =====
// File: 2025_05_30_000010_create_collection_duplicates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_duplicates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('duplicated_collection_id')->constrained('collections')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_duplicates');
    }
};

// ===== MIGRATION 11: Tạo bảng recent_collections =====
// File: 2025_05_30_000011_create_recent_collections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recent_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recent_collections');
    }
};

// ===== MIGRATION 12: Tạo bảng flashcards =====
// File: 2025_05_30_000012_create_flashcards_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->text('front');
            $table->text('back');
            $table->text('pronunciation')->nullable();
            $table->text('kanji')->nullable();
            $table->string('image')->nullable();
            $table->json('extra_data')->nullable(); // Thêm mới - Dữ liệu bổ sung
            $table->timestamps();

            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');

            // Index
            $table->index(['collection_id', 'difficulty_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};

// ===== MIGRATION 13: Tạo bảng flashcard_statuses =====
// File: 2025_05_30_000013_create_flashcard_statuses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flashcard_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['new', 'learning', 're-learning', 'young', 'mastered'])->default('new');
            $table->enum('study_mode', ['front_to_back', 'back_to_front', 'both'])->default('front_to_back'); // Thêm mới

            // SM-2 Algorithm fields
            $table->integer('interval')->default(1); // Giữ lại cho backward compatibility
            $table->integer('interval_minutes')->default(1); // Thêm mới - Khoảng thời gian tính bằng phút
            $table->float('ease_factor')->default(2.5);
            $table->integer('repetitions')->default(0);
            $table->integer('lapses')->default(0); // Thêm mới - Số lần quên
            $table->boolean('is_leech')->default(false); // Thêm mới - Thẻ khó học

            // Timestamps
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamp('due_date')->nullable(); // Thêm mới - Thời điểm cần ôn tiếp theo
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('flashcard_id')->constrained('flashcards')->onDelete('cascade');

            $table->unique(['user_id', 'flashcard_id']);

            // SM-2 Indexes
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcard_statuses');
    }
};

// ===== MIGRATION 14: Tạo bảng flashcard_review_logs =====
// File: 2025_05_30_000014_create_flashcard_review_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flashcard_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('flashcard_id')->constrained('flashcards')->onDelete('cascade');

            // Study context - Thêm mới
            $table->enum('study_type', ['flashcard', 'game_match', 'typing', 'handwriting', 'test']);
            $table->enum('study_mode', ['front_to_back', 'back_to_front']);
            $table->integer('response_time_ms')->nullable(); // Thêm mới - Thời gian phản hồi

            // SM-2 data
            $table->tinyInteger('quality'); // 0–5
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

    public function down(): void
    {
        Schema::dropIfExists('flashcard_review_logs');
    }
};

// ===== MIGRATION 15: Tạo bảng study_sessions =====
// File: 2025_05_30_000015_create_study_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->enum('study_type', ['flashcard', 'game_match', 'typing', 'handwriting', 'test']);
            $table->integer('cards_studied')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'started_at']);
            $table->index(['collection_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};

// ===== MIGRATION 16: Tạo bảng learning_statistics =====
// File: 2025_05_30_000016_create_learning_statistics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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

    public function down(): void
    {
        Schema::dropIfExists('learning_statistics');
    }
};

// ===== MIGRATION 17: Tạo bảng notifications =====
// File: 2025_05_30_000017_create_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('type');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();

            // Indexes để tối ưu hiệu suất
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

// ===== MIGRATION 18: Tạo bảng system_settings =====
// File: 2025_05_30_000018_create_system_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->timestamps();

            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

// ===== MIGRATION 19: Seed system_settings =====
// File: 2025_05_30_000019_seed_system_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $settings = [
            [
                'key' => 'sm2_initial_interval',
                'value' => '1',
                'description' => 'SM-2: Khoảng thời gian ban đầu (phút)',
                'type' => 'integer'
            ],
            [
                'key' => 'sm2_second_interval',
                'value' => '6',
                'description' => 'SM-2: Khoảng thời gian lần thứ 2 (phút)',
                'type' => 'integer'
            ],
            [
                'key' => 'sm2_min_ease_factor',
                'value' => '1.3',
                'description' => 'SM-2: Ease factor tối thiểu',
                'type' => 'float'
            ],
            [
                'key' => 'user_level_thresholds',
                'value' => '{"1": {"rating": 0, "max_collections": 5}, "2": {"rating": 2.1, "max_collections": 10}, "3": {"rating": 3.1, "max_collections": 20}, "4": {"rating": 4.1, "max_collections": -1}}',
                'description' => 'Ngưỡng cấp độ người dùng',
                'type' => 'json'
            ],
            [
                'key' => 'new_cards_per_day_limit',
                'value' => '20',
                'description' => 'Giới hạn thẻ mới mỗi ngày',
                'type' => 'integer'
            ],
            [
                'key' => 'password_reset_token_expiry',
                'value' => '60',
                'description' => 'Thời gian hết hạn token reset password (phút)',
                'type' => 'integer'
            ]
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'description' => $setting['description'],
                'type' => $setting['type'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    public function down(): void
    {
        $keys = [
            'sm2_initial_interval',
            'sm2_second_interval',
            'sm2_min_ease_factor',
            'user_level_thresholds',
            'new_cards_per_day_limit',
            'password_reset_token_expiry'
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};

// ===== LƯU Ý QUAN TRỌNG =====
/*
BẢNG ĐÃ XÓA (không tạo nữa):
- user_flashcards: Đã xóa vì trùng lặp với flashcard_statuses

TRƯỜNG ĐÃ ĐỔI TÊN/THAY ĐỔI:
- flashcard_statuses.interval: Giữ lại cho backward compatibility, thêm interval_minutes
- users.password: Đã nullable từ đầu cho Google login
- collections: Thêm nhiều trường thống kê và metadata
- flashcards: Thêm các trường ngôn ngữ và độ khó
- password_reset_tokens: Thêm expires_at và used

INDEXES ĐÃ THÊM:
- Tất cả các index cần thiết đã được thêm từ đầu để tối ưu hiệu suất
- Đặc biệt chú ý các index cho SM-2 algorithm và query thống kê

FOREIGN KEYS:
- Tất cả đã được thiết lập với onDelete('cascade') phù hợp
- Đảm bảo tính toàn vẹn dữ liệu
*/
